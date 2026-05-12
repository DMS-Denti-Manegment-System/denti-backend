# VPS Deployment Model

Denti now targets a single-company deployment model: one application instance, one database and one internal company record per customer/clinic group.

## Recommended VPS Stack

- Ubuntu 24.04 LTS
- HestiaCP for practical panel management, SSL, database and mail setup
- Nginx + PHP-FPM
- PHP 8.4 or the closest supported PHP 8.3/8.4 package available on the VPS
- SQLite database stored outside the public document root
- Supervisor for `queue:work`
- Cron for Laravel scheduler

Hestia is acceptable for this project because the app is a conventional Laravel app and does not need AWS-specific infrastructure. If the VPS will host only this app and the team is comfortable with terminal operations, a plain Nginx/PHP-FPM/Supervisor setup is leaner. If the VPS will be managed by non-dev operators, Hestia is the more practical choice.

## Data Model Rule

Do not remove `company_id` columns. They are internal ownership keys used by:

- Spatie Permission teams
- query indexes
- data ownership checks
- future migration path if a customer outgrows a single VPS

The product UI must not expose company creation or company switching.

## Install Flow

1. Create a Hestia web domain.
2. Point the domain document root to Laravel `public`.
3. Copy `.env.production` to `.env` and set real values.
4. Set `DB_DATABASE` to an absolute path outside the public document root.
5. Create the SQLite file and make it writable by the PHP-FPM user.
6. Run:

```bash
mkdir -p database
touch database/database.sqlite
chmod 664 database/database.sqlite
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize
```

7. Add scheduler cron:

```bash
* * * * * php /home/USER/web/DOMAIN/private/artisan schedule:run >> /dev/null 2>&1
```

8. Run queue worker with Supervisor:

```bash
php /home/USER/web/DOMAIN/private/artisan queue:work --sleep=3 --tries=3 --timeout=90
```

## Required Env Values

```dotenv
DENTI_COMPANY_NAME="Clinic Name"
DENTI_COMPANY_CODE=default
DENTI_COMPANY_DOMAIN=clinic.example.com
DENTI_COMPANY_EMAIL=admin@clinic.example.com
DENTI_OWNER_NAME="Clinic Owner"
DENTI_OWNER_USERNAME=admin
DENTI_OWNER_EMAIL=admin@clinic.example.com
DENTI_OWNER_PASSWORD=change-this-before-seed
```

`DENTI_COMPANY_CODE` is internal. It is not entered on the login screen.

## SQLite Production Notes

Use these settings in production:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/home/USER/web/DOMAIN/private/database/database.sqlite
DB_BUSY_TIMEOUT=5000
DB_JOURNAL_MODE=wal
DB_SYNCHRONOUS=normal
```

The SQLite database, `database.sqlite-wal` and `database.sqlite-shm` files must be included in backups. Keep the database under the private Laravel application path, never under `public`.
