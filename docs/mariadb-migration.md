# Production migration from SQLite to MariaDB/MySQL

This runbook migrates AYNGonnaShip from SQLite to MariaDB (or MySQL) with a short maintenance window and a straightforward rollback. It creates a fresh target schema with Laravel migrations and then copies durable data using `php artisan db:copy-sqlite-to-mariadb`.

Do not import the output of `sqlite3 .dump` into MariaDB. SQLite and MariaDB differ in schema syntax, quoting, JSON handling, auto-increment behavior, and foreign-key handling.

## What will be copied

The copy command preserves IDs, null values, timestamps, password hashes, subscriber tokens, and foreign-key relationships for:

- `users`
- `model_variants`
- `shipping_batches`
- `subscribers`
- `scrape_logs`
- `page_views`
- `estimation_logs`
- `failed_jobs`, when present

Laravel creates a new `migrations` history on MariaDB. Volatile `cache`, `cache_locks`, `sessions`, `jobs`, `job_batches`, and `password_reset_tokens` data is intentionally not copied. Drain the queue before the final copy; users will need to sign in again after cutover.

## Assumptions and placeholders

Run application commands from:

```bash
cd /home/ayngonnaship-vgzcb/ayngonnaship.com
```

This guide uses these placeholders:

```text
Database: ayn_ship_estimator
Application user: ayn_ship_app
SQLite source: /home/ayngonnaship-vgzcb/ayngonnaship.com/database/database.sqlite
Target connection: mariadb
```

Use the database name and credentials created in Ploi if they differ. Never paste database passwords into chat, logs, tickets, or shell commands that will be saved in shell history.

## 1. Record the current state

Confirm that production is healthy and locate the actual SQLite file without printing secrets:

```bash
php artisan about --only=environment
php artisan migrate:status
php artisan tinker --execute="dump(DB::connection()->getDriverName(), DB::connection()->getDatabaseName());"
php artisan queue:monitor database:mail,database:default --max=100
```

Record source counts:

```bash
php artisan tinker --execute="
foreach (['users','model_variants','shipping_batches','subscribers','scrape_logs','page_views','estimation_logs','failed_jobs'] as \$table) {
    dump(\$table, Schema::hasTable(\$table) ? DB::table(\$table)->count() : 'absent');
}
"
```

Check SQLite integrity before trusting it as a source:

```bash
sqlite3 database/database.sqlite 'PRAGMA quick_check;'
sqlite3 database/database.sqlite 'PRAGMA foreign_key_check;'
```

Expected output from `quick_check` is `ok`; `foreign_key_check` should produce no rows. Stop and investigate if either check fails.

## 2. Install and secure MariaDB

If Ploi already provisioned MariaDB/MySQL, use that service. Otherwise install a supported MariaDB release and run its secure-installation process using the server's normal administration procedure.

Confirm PHP and the database service are ready:

```bash
php -m | grep -E '^pdo_mysql$'
mariadb --version
sudo systemctl is-active mariadb
```

If `pdo_mysql` is absent, install the MySQL extension matching the active PHP version before continuing.

Create the database and a dedicated local application user from an administrative MariaDB session:

```bash
sudo mariadb
```

Run the following SQL, replacing the password with a unique generated value:

```sql
CREATE DATABASE ayn_ship_estimator
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'ayn_ship_app'@'127.0.0.1'
    IDENTIFIED BY 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD';

GRANT ALL PRIVILEGES ON ayn_ship_estimator.*
    TO 'ayn_ship_app'@'127.0.0.1';

SHOW GRANTS FOR 'ayn_ship_app'@'127.0.0.1';
```

Use `localhost` instead of `127.0.0.1` consistently if the Ploi database user was created for `localhost`. MariaDB treats these as distinct user hosts.

Verify the server is bound locally or protected by a firewall. The application database port should not be publicly reachable.

## 3. Configure the target connection without cutting over

The repository already contains `mysql` and `mariadb` connections in `config/database.php`. Keep the production default connection on SQLite during rehearsal. Add temporary, migration-only variables to `.env`:

```env
MIGRATION_DB_HOST=127.0.0.1
MIGRATION_DB_PORT=3306
MIGRATION_DB_DATABASE=ayn_ship_estimator
MIGRATION_DB_USERNAME=ayn_ship_app
MIGRATION_DB_PASSWORD=REPLACE_WITH_THE_REAL_PASSWORD
```

Temporarily add this connection to `config/database.php` under `connections`:

```php
'migration_mariadb' => [
    'driver' => 'mariadb',
    'host' => env('MIGRATION_DB_HOST', '127.0.0.1'),
    'port' => env('MIGRATION_DB_PORT', '3306'),
    'database' => env('MIGRATION_DB_DATABASE'),
    'username' => env('MIGRATION_DB_USERNAME'),
    'password' => env('MIGRATION_DB_PASSWORD'),
    'unix_socket' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
],
```

Clear cached configuration and verify only the driver/database name:

```bash
php artisan config:clear
php artisan tinker --execute="dump(
    DB::connection('migration_mariadb')->getDriverName(),
    DB::connection('migration_mariadb')->getDatabaseName(),
);"
```

Do not dump the complete connection configuration because it contains the password.

## 4. Create the MariaDB schema

Run the existing Laravel migrations against the target connection:

```bash
php artisan migrate --database=migration_mariadb --force
php artisan migrate:status --database=migration_mariadb
```

Do not seed the target: the source database already contains model variants, users, and shipping data.

## 5. Rehearse the copy while the site remains online

The rehearsal proves compatibility but is not the final copy because production can still receive writes.

```bash
php artisan db:copy-sqlite-to-mariadb \
    --source=/home/ayngonnaship-vgzcb/ayngonnaship.com/database/database.sqlite \
    --target=migration_mariadb
```

The command refuses to run when a durable target table already contains data. It also validates each copied row count.

Exercise the target through read-only checks:

```bash
php artisan tinker --execute="
\$db = DB::connection('migration_mariadb');
foreach (['users','model_variants','shipping_batches','subscribers','scrape_logs','page_views','estimation_logs','failed_jobs'] as \$table) {
    dump(\$table, Schema::connection('migration_mariadb')->hasTable(\$table) ? \$db->table(\$table)->count() : 'absent');
}
"
```

Compare those counts with step 1. Also verify:

- The newest `shipping_batches.scraped_at` matches the source.
- Verified and unverified subscriber counts match.
- Admin password hashes were copied unchanged.
- The longest `referrer`, `user_agent`, error, and runtime-context values were not truncated.
- MariaDB's server timezone and the Laravel application timezone produce the expected dates.

Before the final migration, reset only the target database. Triple-check the target connection name first:

```bash
php artisan tinker --execute="dump(DB::connection('migration_mariadb')->getDatabaseName());"
php artisan migrate:fresh --database=migration_mariadb --force
```

`migrate:fresh` is destructive. Never run it without `--database=migration_mariadb`, and never run it after MariaDB becomes production.

## 6. Prepare the maintenance window

Schedule a short maintenance window. Confirm a current filesystem backup or server snapshot exists. Keep the original SQLite database unchanged until the MariaDB deployment has been stable for several days.

Drain queued work before stopping workers:

```bash
php artisan queue:failed
```

Wait until the `jobs` table is empty:

```bash
php artisan tinker --execute="dump(DB::table('jobs')->count());"
```

Prevent the twice-daily scrape or notification schedule from overlapping the cutover. In Ploi, temporarily disable this site's scheduled task, or comment only this exact line in `/etc/crontab`:

```text
* * * * * ayngonnaship-vgzcb php /home/ayngonnaship-vgzcb/ayngonnaship.com/artisan schedule:run ...
```

Do not disable other sites' scheduler entries.

## 7. Enter maintenance mode and take the final SQLite backup

```bash
php artisan down --refresh=15
```

Stop only this site's two Supervisor workers through Ploi, or use the specific Supervisor program names after confirming them with `sudo supervisorctl status`. Do not stop all workers on a shared server.

Create a backup directory and use SQLite's online backup command:

```bash
mkdir -p /home/ayngonnaship-vgzcb/backups
sqlite3 database/database.sqlite ".backup '/home/ayngonnaship-vgzcb/backups/database-before-mariadb.sqlite'"
sqlite3 /home/ayngonnaship-vgzcb/backups/database-before-mariadb.sqlite 'PRAGMA quick_check;'
```

Record its checksum:

```bash
sha256sum /home/ayngonnaship-vgzcb/backups/database-before-mariadb.sqlite
```

Use this immutable backup as the final copy source, not the live SQLite file.

## 8. Perform the final copy

Ensure the rehearsed target was reset and is empty, then run:

```bash
php artisan db:copy-sqlite-to-mariadb \
    --source=/home/ayngonnaship-vgzcb/backups/database-before-mariadb.sqlite \
    --target=migration_mariadb
```

Repeat the target count checks from step 5. Do not proceed if any count differs or the command reports an error.

Create a post-import MariaDB backup before cutover:

```bash
mariadb-dump \
    --host=127.0.0.1 \
    --user=ayn_ship_app \
    --password \
    --single-transaction \
    --routines --triggers \
    ayn_ship_estimator \
    > /home/ayngonnaship-vgzcb/backups/mariadb-before-cutover.sql
```

The client prompts for the password so it does not appear in shell history.

## 9. Cut the application over

Back up `.env` somewhere outside the public document root, then replace the SQLite database settings:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ayn_ship_estimator
DB_USERNAME=ayn_ship_app
DB_PASSWORD=REPLACE_WITH_THE_REAL_PASSWORD
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

Remove the temporary `MIGRATION_DB_*` variables and, in a normal code deployment, remove the temporary `migration_mariadb` connection from `config/database.php`.

Apply configuration and verify the active database before allowing traffic:

```bash
php artisan config:clear
php artisan config:cache
php artisan tinker --execute="dump(DB::connection()->getDriverName(), DB::connection()->getDatabaseName());"
php artisan migrate:status
```

Expected driver is `mariadb`; expected database is `ayn_ship_estimator`.

Restart only this site's queue workers through Ploi/Supervisor. Re-enable its scheduler entry.

## 10. Smoke-test before reopening

While maintenance mode remains active, verify locally from the server using the maintenance bypass secret if one was configured, or briefly use `php artisan up` and be ready to return to maintenance mode.

Check:

1. Homepage returns successfully and model variants appear.
2. Several known order prefixes produce the same estimates as before.
3. Admin login works.
4. Subscriber, analytics, scrape-log, and queue pages load.
5. A tracking request creates a MariaDB row.
6. A controlled `php artisan scrape` succeeds through HTTP fallback.
7. A test email can be queued and processed.
8. `storage/logs/laravel.log` contains no SQL, collation, truncation, or connection errors.

Confirm auto-increment IDs are advancing by comparing `MAX(id)` with table status:

```bash
mariadb --host=127.0.0.1 --user=ayn_ship_app --password ayn_ship_estimator
```

```sql
SELECT 'users', MAX(id) FROM users
UNION ALL SELECT 'model_variants', MAX(id) FROM model_variants
UNION ALL SELECT 'shipping_batches', MAX(id) FROM shipping_batches
UNION ALL SELECT 'subscribers', MAX(id) FROM subscribers
UNION ALL SELECT 'scrape_logs', MAX(id) FROM scrape_logs
UNION ALL SELECT 'page_views', MAX(id) FROM page_views
UNION ALL SELECT 'estimation_logs', MAX(id) FROM estimation_logs;
```

Exit the client and reopen the site:

```bash
php artisan up
```

## 11. Monitor after cutover

For at least the first day, monitor:

```bash
tail -n 200 storage/logs/laravel.log
php artisan queue:failed
php artisan schedule:list
```

Check that the next scheduled scrape and notification jobs complete, new analytics rows appear in MariaDB, and no new rows are written to the old SQLite file.

Keep both backups and the old SQLite database for an agreed retention period. Remove database credentials from shell history or temporary files and restrict `.env` permissions appropriately for the deployment user/runtime group.

## Rollback

Rollback is simplest before accepting substantial MariaDB writes:

1. Run `php artisan down --refresh=15`.
2. Disable this site's scheduler and stop its queue workers.
3. Restore the original SQLite `DB_CONNECTION` and absolute `DB_DATABASE` values in `.env`.
4. Run `php artisan config:clear && php artisan config:cache`.
5. Verify the active driver is `sqlite` and the path is the expected production file.
6. Restart this site's workers and scheduler.
7. Run the homepage/admin smoke tests.
8. Run `php artisan up`.

If MariaDB accepted production writes after cutover, preserve a `mariadb-dump` before rollback. Those writes will not exist in SQLite and must be reconciled before attempting the migration again.

## Notes specific to this application

- The existing migrations, `DATE(created_at)` analytics queries, foreign keys, and `upsert()` operations are compatible with MariaDB.
- The database queue uses the default database connection, so it moves to MariaDB automatically after cutover.
- Changing databases does not address the malformed zero-byte filename incident. That was caused by the removed Puppeteer browser fallback executing an x86-64 binary on ARM.
- MariaDB migration does not require rotating `APP_KEY`. Rotate the previously exposed key as a separate planned operation because doing so invalidates encrypted sessions and cookies.

## References

- Laravel database support and configuration: https://laravel.com/docs/12.x/database
- Laravel configuration and maintenance mode: https://laravel.com/docs/12.x/configuration#maintenance-mode
- MariaDB character sets and collations: https://mariadb.com/docs/server/reference/data-types/string-data-types/character-sets/setting-character-sets-and-collations
- MariaDB logical backups: https://mariadb.com/docs/server/mariadb-quickstart-guides/mariadb-backup-guide
