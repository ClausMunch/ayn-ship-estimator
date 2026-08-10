<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MigrateSqliteToMariaDb extends Command
{
    protected $signature = 'db:copy-sqlite-to-mariadb
        {--source= : Absolute path to the source SQLite database}
        {--target=mariadb : Configured MariaDB/MySQL connection name}
        {--chunk=1000 : Rows inserted per batch}';

    protected $description = 'Copy durable application data from SQLite into an empty MariaDB/MySQL schema';

    /**
     * Tables are ordered so referenced rows are copied before rows with foreign keys.
     * Cache, sessions, queued jobs, and migration metadata are intentionally excluded.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'model_variants',
        'shipping_batches',
        'subscribers',
        'scrape_logs',
        'page_views',
        'estimation_logs',
        'failed_jobs',
    ];

    public function handle(): int
    {
        try {
            $sourcePath = $this->validatedSourcePath();
            $target = (string) $this->option('target');
            $chunkSize = filter_var($this->option('chunk'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 10000],
            ]);

            if ($chunkSize === false) {
                throw new RuntimeException('--chunk must be an integer between 1 and 10000.');
            }

            if (! array_key_exists($target, config('database.connections', []))) {
                throw new RuntimeException("Database connection [{$target}] is not configured.");
            }

            config([
                'database.connections.migration_sqlite' => array_merge(
                    config('database.connections.sqlite'),
                    ['database' => $sourcePath],
                ),
            ]);

            DB::purge('migration_sqlite');
            DB::purge($target);
            DB::connection('migration_sqlite')->getPdo();
            DB::connection($target)->getPdo();

            $this->ensureTargetIsEmpty($target);

            $this->info("Copying durable data from {$sourcePath} to [{$target}]...");

            foreach (self::TABLES as $table) {
                $this->copyTable('migration_sqlite', $target, $table, $chunkSize);
            }

            $this->newLine();
            $this->info('Copy complete. Run the validation commands from docs/mariadb-migration.md before cutover.');

            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }

    private function validatedSourcePath(): string
    {
        $source = (string) $this->option('source');

        if ($source === '') {
            throw new RuntimeException('--source is required.');
        }

        $realPath = realpath($source);

        if ($realPath === false || ! is_file($realPath) || ! is_readable($realPath)) {
            throw new RuntimeException('The SQLite source must be an existing, readable file.');
        }

        return $realPath;
    }

    private function ensureTargetIsEmpty(string $target): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::connection($target)->hasTable($table)
                && DB::connection($target)->table($table)->exists()) {
                throw new RuntimeException(
                    "Target table [{$table}] is not empty. Refusing to mix or overwrite data.",
                );
            }
        }
    }

    private function copyTable(string $source, string $target, string $table, int $chunkSize): void
    {
        if (! Schema::connection($source)->hasTable($table)) {
            $this->components->twoColumnDetail($table, '<fg=yellow>absent in source; skipped</>');

            return;
        }

        if (! Schema::connection($target)->hasTable($table)) {
            throw new RuntimeException("Target table [{$table}] does not exist. Run migrations first.");
        }

        $sourceColumns = Schema::connection($source)->getColumnListing($table);
        $targetColumns = Schema::connection($target)->getColumnListing($table);
        $columns = array_values(array_intersect($sourceColumns, $targetColumns));
        $sourceCount = DB::connection($source)->table($table)->count();
        $copyCount = $sourceCount;
        $skippedCount = 0;

        if ($sourceCount === 0) {
            $this->components->twoColumnDetail($table, '0 rows');

            return;
        }

        if (! in_array('id', $columns, true)) {
            throw new RuntimeException("Table [{$table}] has data but no shared [id] column.");
        }

        $query = DB::connection($source)
            ->table($table)
            ->select($columns);

        if ($table === 'shipping_batches') {
            // Older SQLite rows may store the same logical DATE as either Y-m-d or
            // Y-m-d 00:00:00. SQLite considers those distinct for the unique index,
            // while MariaDB normalizes both values to Y-m-d. Keep the newest row for
            // each normalized key so the copy is deterministic.
            $newestIds = DB::connection($source)
                ->table($table)
                ->selectRaw('MAX(id)')
                ->groupBy('model_variant_id', 'order_range_start')
                ->groupByRaw('DATE(ship_date)');

            $query->whereIn('id', $newestIds);
            $copyCount = (clone $query)->count();
            $skippedCount = $sourceCount - $copyCount;
        } elseif ($table === 'subscribers') {
            // The MariaDB utf8mb4 collation compares email addresses without case
            // sensitivity and ignores trailing spaces. SQLite's default unique
            // comparison does neither, so consolidate legacy variants first.
            $newestIds = DB::connection($source)
                ->table($table)
                ->selectRaw('MAX(id)')
                ->groupBy('model_variant_id', 'order_prefix')
                ->groupByRaw('LOWER(TRIM(email))');

            $query->whereIn('id', $newestIds);
            $copyCount = (clone $query)->count();
            $skippedCount = $sourceCount - $copyCount;
        }

        DB::connection($target)->transaction(function () use (
            $target,
            $table,
            $query,
            $chunkSize,
        ): void {
            $query
                ->orderBy('id')
                ->chunkById($chunkSize, function ($rows) use ($target, $table): void {
                    $records = $rows->map(function ($row) use ($table): array {
                        $record = (array) $row;

                        if ($table === 'shipping_batches') {
                            $record['ship_date'] = substr((string) $record['ship_date'], 0, 10);
                        } elseif ($table === 'subscribers') {
                            $record['email'] = strtolower(trim((string) $record['email']));
                        }

                        return $record;
                    })->all();
                    DB::connection($target)->table($table)->insert($records);
                });
        });

        $targetCount = DB::connection($target)->table($table)->count();

        if ($targetCount !== $copyCount) {
            throw new RuntimeException(
                "Row-count mismatch for [{$table}]: expected={$copyCount}, target={$targetCount}.",
            );
        }

        $detail = "{$targetCount} rows";

        if ($skippedCount > 0) {
            $detail .= "; {$skippedCount} normalized duplicate(s) skipped";
        }

        $this->components->twoColumnDetail($table, $detail);
    }
}
