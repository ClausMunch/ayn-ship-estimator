<?php

namespace App\Console\Commands;

use App\Models\ModelVariant;
use App\Models\ShippingBatch;
use Illuminate\Console\Command;

class SeedData extends Command
{
    protected $signature = 'seed:data';
    protected $description = 'Seed model variants and historical shipping batch data';

    public function handle(): int
    {
        $this->seedModelVariants();
        $this->seedShippingBatches();

        return self::SUCCESS;
    }

    private function seedModelVariants(): void
    {
        $variants = [
            ['display_order' => 1, 'name' => 'Rainbow Max', 'slug' => 'rainbow-max', 'color_config' => ['border' => '#e8567d', 'accent' => '#e8567d', 'tagBg' => '#fff0f3']],
            ['display_order' => 2, 'name' => 'Rainbow Pro', 'slug' => 'rainbow-pro', 'color_config' => ['border' => '#d4943a', 'accent' => '#d4943a', 'tagBg' => '#fff8ed']],
            ['display_order' => 3, 'name' => 'Black Max', 'slug' => 'black-max', 'color_config' => ['border' => '#111', 'accent' => '#000', 'tagBg' => '#e8e8e8']],
            ['display_order' => 4, 'name' => 'Black Pro', 'slug' => 'black-pro', 'color_config' => ['border' => '#333', 'accent' => '#1a1a1a', 'tagBg' => '#f0f0f0']],
            ['display_order' => 5, 'name' => 'Black Base', 'slug' => 'black-base', 'color_config' => ['border' => '#555', 'accent' => '#444', 'tagBg' => '#f5f5f5']],
            ['display_order' => 6, 'name' => 'Black Lite', 'slug' => 'black-lite', 'color_config' => ['border' => '#777', 'accent' => '#666', 'tagBg' => '#f8f8f8']],
            ['display_order' => 7, 'name' => 'White Max', 'slug' => 'white-max', 'color_config' => ['border' => '#9a9ab0', 'accent' => '#55557a', 'tagBg' => '#e8e8f5']],
            ['display_order' => 8, 'name' => 'White Pro', 'slug' => 'white-pro', 'color_config' => ['border' => '#c0c0d0', 'accent' => '#6a6a8a', 'tagBg' => '#eeeef8']],
            ['display_order' => 9, 'name' => 'Clear Purple Max', 'slug' => 'clear-purple-max', 'color_config' => ['border' => '#9b59b6', 'accent' => '#8e44ad', 'tagBg' => '#f5eef8']],
            ['display_order' => 10, 'name' => 'Clear Purple Pro', 'slug' => 'clear-purple-pro', 'color_config' => ['border' => '#b07cc5', 'accent' => '#a368b8', 'tagBg' => '#f8f2fb']],
        ];

        foreach ($variants as $variant) {
            ModelVariant::updateOrCreate(
                ['slug' => $variant['slug']],
                $variant
            );
        }

        $this->info('Seeded ' . count($variants) . ' model variants.');
    }

    private function seedShippingBatches(): void
    {
        // slug => [[date, start, end], ...]
        $batches = [
            'rainbow-max' => [
                ['2026-01-15', 1062, 1114],
                ['2026-01-19', 1114, 1175],
                ['2026-01-23', 1175, 1210],
                ['2026-01-25', 1210, 1258],
                ['2026-01-27', 1258, 1286],
                ['2026-01-28', 1286, 1308],
                ['2026-01-30', 1300, 1319],
                ['2026-02-05', 1319, 1343],
                ['2026-02-09', 1343, 1417],
                ['2026-02-12', 1417, 1426],
            ],
            'rainbow-pro' => [
                ['2026-01-15', 1050, 1109],
                ['2026-01-20', 1109, 1147],
                ['2026-01-24', 1147, 1186],
                ['2026-01-26', 1186, 1219],
                ['2026-01-29', 1219, 1291],
                ['2026-01-30', 1291, 1295],
                ['2026-02-02', 1295, 1364],
                ['2026-02-03', 1364, 1386],
                ['2026-02-04', 1386, 1395],
                ['2026-02-05', 1395, 1438],
                ['2026-02-12', 1438, 1440],
            ],
            'black-max' => [
                ['2026-01-20', 1067, 1135],
                ['2026-01-21', 1067, 1147],
                ['2026-01-24', 1148, 1190],
                ['2026-01-27', 1190, 1267],
                ['2026-01-29', 1267, 1300],
                ['2026-02-01', 1300, 1352],
                ['2026-02-09', 1352, 1402],
                ['2026-02-12', 1402, 1437],
            ],
            'black-pro' => [
                ['2026-01-20', 1067, 1094],
                ['2026-01-21', 1094, 1139],
                ['2026-01-24', 1139, 1195],
                ['2026-01-29', 1195, 1232],
                ['2026-01-30', 1232, 1285],
                ['2026-02-01', 1285, 1311],
                ['2026-02-03', 1311, 1404],
                ['2026-02-12', 1311, 1437],
            ],
            'black-base' => [
                ['2026-01-21', 1058, 1088],
                ['2026-01-30', 1088, 1269],
                ['2026-02-01', 1269, 1344],
                ['2026-02-03', 1344, 1420],
                ['2026-02-04', 1420, 1500],
                ['2026-02-12', 1344, 1510],
            ],
            'black-lite' => [
                ['2026-02-04', 1254, 1492],
            ],
            'white-max' => [
                ['2026-01-22', 1064, 1160],
                ['2026-02-09', 1160, 1335],
                ['2026-02-12', 1335, 1438],
            ],
            'white-pro' => [
                ['2026-01-21', 1072, 1097],
                ['2026-01-26', 1097, 1279],
                ['2026-02-02', 1279, 1376],
                ['2026-02-05', 1376, 1465],
                ['2026-02-12', 1376, 1465],
            ],
            'clear-purple-max' => [
                ['2026-01-22', 1058, 1090],
                ['2026-01-23', 1090, 1102],
                ['2026-01-26', 1102, 1139],
                ['2026-01-27', 1139, 1153],
                ['2026-02-01', 1153, 1172],
                ['2026-02-12', 1172, 1439],
            ],
            'clear-purple-pro' => [
                ['2026-01-22', 1074, 1117],
                ['2026-01-27', 1117, 1156],
                ['2026-02-01', 1156, 1317],
                ['2026-02-12', 1317, 1440],
            ],
        ];

        $count = 0;
        $slugToId = ModelVariant::pluck('id', 'slug');
        $now = now();

        $rows = [];
        foreach ($batches as $slug => $batchRows) {
            $variantId = $slugToId[$slug];

            foreach ($batchRows as [$date, $start, $end]) {
                $rows[] = [
                    'model_variant_id' => $variantId,
                    'ship_date' => $date,
                    'order_range_start' => $start,
                    'order_range_end' => $end,
                    'scraped_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $count++;
            }
        }

        ShippingBatch::upsert(
            $rows,
            ['model_variant_id', 'ship_date', 'order_range_start'],
            ['order_range_end', 'scraped_at', 'updated_at'],
        );

        $this->info("Seeded {$count} shipping batches.");
    }
}
