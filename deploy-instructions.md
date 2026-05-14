# AMIS Enrollment - Bluehost Deployment Guide

## Step 1: Prepare Files for Upload

### Files to Upload:
- Upload ALL files EXCEPT:
  - `.env` (create new one on server)
  - `node_modules/` (not needed)
  - `storage/logs/*` (will be recreated)
  - `.git/` (not needed for production)

### Upload Structure:
```
public_html/
├── enrollment/          # Create this folder
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   ├── composer.json
│   └── ...
└── public/             # Laravel's public folder contents go here
    ├── index.php       # From Laravel's public folder
    ├── css/
    ├── js/
    └── ...
```

## Step 2: Bluehost Setup

### 1. Create Database
- Login to Bluehost cPanel
- Go to "MySQL Databases"
- Create new database: `yourusername_amis`
- Create database user with full privileges
- Note down: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

### 2. Upload Files
- Use File Manager or FTP
- Upload Laravel files to `public_html/enrollment/`
- Upload `public` folder contents to `public_html/`

### 3. Update index.php
Edit `public_html/index.php`:
```php
<?php
require __DIR__.'/enrollment/vendor/autoload.php';
$app = require_once __DIR__.'/enrollment/bootstrap/app.php';
```

## Step 3: Configuration

### 1. Create .env file
- Copy `.env.production` to `.env` in `public_html/enrollment/`
- Update database credentials from Step 2.1

### 2. Set Permissions
```bash
chmod -R 755 public_html/enrollment/
chmod -R 775 public_html/enrollment/storage/
chmod -R 775 public_html/enrollment/bootstrap/cache/
```

### 3. Run Commands (via SSH or cPanel Terminal)
```bash
cd public_html/enrollment
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Step 4: Domain Setup

### Option A: Subdomain (enrollment.yourdomain.com)
- Create subdomain in cPanel
- Point document root to `public_html/enrollment/public`

### Option B: Main Domain
- Use the structure above with public contents in public_html root

## Step 5: SSL Certificate
- Enable SSL in Bluehost cPanel
- Update APP_URL to https://

## Troubleshooting

### Common Issues:
1. **500 Error**: Check storage permissions
2. **Database Error**: Verify .env database credentials
3. **Missing Assets**: Run `php artisan storage:link`
4. **Route Issues**: Clear cache with `php artisan config:clear`

### File Permissions:
```bash
find public_html/enrollment -type f -exec chmod 644 {} \;
find public_html/enrollment -type d -exec chmod 755 {} \;
chmod -R 775 public_html/enrollment/storage
chmod -R 775 public_html/enrollment/bootstrap/cache
```