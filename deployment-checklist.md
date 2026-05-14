# 🚀 AMIS Enrollment - Deployment Checklist

## ✅ Pre-Deployment (Do this first)

- [ ] Test application locally (fix XAMPP MySQL first)
- [ ] Update `.env.production` with your domain name
- [ ] Remove any test/debug code
- [ ] Ensure all migrations are working
- [ ] Test enrollment form functionality

## ✅ Bluehost Setup

- [ ] Login to Bluehost cPanel
- [ ] Create MySQL database
- [ ] Note database credentials (host, name, user, password)
- [ ] Create subdomain (optional): enrollment.yourdomain.com

## ✅ File Upload

- [ ] Compress your `amis_enrollment` folder (exclude: .env, node_modules, .git)
- [ ] Upload via File Manager or FTP to `public_html/enrollment/`
- [ ] Move contents of `public` folder to `public_html/` (or subdomain root)
- [ ] Update `public_html/index.php` to point to enrollment folder

## ✅ Configuration

- [ ] Create `.env` file from `.env.production` template
- [ ] Update database credentials in `.env`
- [ ] Set file permissions (755 for folders, 644 for files)
- [ ] Set 775 for storage/ and bootstrap/cache/

## ✅ Laravel Setup

- [ ] Run `php deploy.php` (or manual commands)
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan storage:link`
- [ ] Test database connection

## ✅ Final Testing

- [ ] Visit your domain/subdomain
- [ ] Test enrollment form
- [ ] Check email functionality
- [ ] Test file uploads
- [ ] Enable SSL certificate
- [ ] Update APP_URL to https://

## 🆘 If Something Goes Wrong

1. **500 Error**: Check storage permissions and error logs
2. **Database Error**: Verify .env database settings
3. **Missing CSS/JS**: Check if public files uploaded correctly
4. **Email Issues**: Test SMTP settings in cPanel

## 📞 Need Help?

- Check Bluehost error logs in cPanel
- Use cPanel Terminal for Laravel commands
- Contact Bluehost support for server issues