# Production Deployment — AVSB-ERP API

## Prerequisites

- PHP 8.4+ with extensions: mbstring, pdo_mysql, bcmath, gd, xml, curl
- MySQL 8.0+ / MariaDB 10.5+
- Composer 2.x
- Nginx
- Supervisor (for queue worker, optional)

## Setup

```bash
# 1. Clone
cd /home/deployer/workspace
git clone git@github.com:adbrsln/avsb-erp-api.git
cd avsb-erp-api

# 2. Environment
cp .env.example .env
# Edit .env with production credentials (DB, R2, Mail, etc.)
php artisan key:generate

# 3. Dependencies (no dev)
composer install --no-dev --no-interaction --optimize-autoloader

# 4. Migrations
php artisan migrate --force

# 5. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 6. Nginx
sudo cp deploy/nginx.conf /etc/nginx/sites-available/api.azamventures.com
sudo ln -s /etc/nginx/sites-available/api.azamventures.com /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# 7. Cron (Laravel scheduler)
crontab -e
# Add: * * * * * cd /home/deployer/workspace/avsb-erp-api && php artisan schedule:run >> /dev/null 2>&1

# 8. Queue worker (optional, for email/push notifications)
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/avsb-erp-api-worker.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start avsb-erp-api-worker:*
```

## Directory Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Certs

Place SSL certs at:
- `/etc/ssl/certs/api.azamventures.com.pem`
- `/etc/ssl/private/api.azamventures.com.key`

## Logs

- App: `storage/logs/laravel.log`
- Nginx: `/var/log/nginx/avsb-erp-api-*.log`
