# UAE Compliance Platform - Installation Guide

## System Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer
- The following PHP extensions:
  - PDO PHP Extension
  - OpenSSL PHP Extension
  - Mbstring PHP Extension
  - JSON PHP Extension
  - Fileinfo PHP Extension
  - GD PHP Extension
  - ZIP PHP Extension

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/uae-compliance-platform.git
cd uae-compliance-platform
```

### 2. Automated Setup (Recommended)

Run the setup script:

```bash
chmod +x setup.sh
./setup.sh
```

The setup script will:
- Check system requirements
- Create necessary directories
- Set proper permissions
- Create environment file
- Install dependencies
- Set up the database
- Perform final checks

### 3. Manual Setup

If you prefer to set up manually, follow these steps:

#### 3.1. Create Required Directories

```bash
mkdir -p storage/documents
mkdir -p storage/backups
mkdir -p logs
```

#### 3.2. Set Directory Permissions

```bash
chmod -R 755 storage
chmod -R 755 logs
chmod 755 public
```

#### 3.3. Create Environment File

```bash
cp .env.example .env
```

Edit `.env` file with your configuration:
```env
APP_NAME="UAE Compliance Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=localhost
DB_DATABASE=uae_compliance
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 3.4. Install Dependencies

```bash
composer install --no-interaction --optimize-autoloader
```

#### 3.5. Set Up Database

```bash
# Create database
mysql -u root -p
CREATE DATABASE uae_compliance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Import schema
mysql -u root -p uae_compliance < database/schema.sql
```

## Web Server Configuration

### Apache

Ensure mod_rewrite is enabled:
```bash
a2enmod rewrite
```

The `.htaccess` file is already configured in the public directory.

### Nginx

Add this to your server configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/uae-compliance-platform/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Post-Installation

1. Access your application at `http://your-domain.com`

2. Log in with default admin credentials:
   - Email: `admin@uaecompliance.com`
   - Password: `change_me_immediately`

3. **IMPORTANT**: Change the default admin password immediately!

## Security Recommendations

1. Ensure proper file permissions:
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   ```

2. Secure the `.env` file:
   ```bash
   chmod 600 .env
   ```

3. Enable HTTPS in production

4. Set up proper backup procedures

5. Configure server firewalls

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   chmod -R 755 storage logs
   chown -R www-data:www-data storage logs
   ```

2. **Database Connection Error**
   - Verify database credentials in `.env`
   - Ensure MySQL service is running
   - Check MySQL user permissions

3. **500 Server Error**
   - Check PHP error logs
   - Verify `.env` configuration
   - Ensure all required PHP extensions are installed

### Debug Mode

To enable debug mode, set in `.env`:
```env
APP_DEBUG=true
```

**Note**: Never enable debug mode in production!

## Maintenance

### Regular Tasks

1. Update dependencies:
   ```bash
   composer update
   ```

2. Clear cache:
   ```bash
   rm -rf storage/cache/*
   ```

3. Database backup:
   ```bash
   mysqldump -u root -p uae_compliance > backup.sql
   ```

### Monitoring

1. Check error logs:
   ```bash
   tail -f logs/error.log
   ```

2. Monitor storage space:
   ```bash
   du -sh storage/*
   ```

## Support

For technical support:
- Email: support@uaecompliance.com
- Phone: +971 XX XXX XXXX

## Updates

To update the platform:

1. Backup your data:
   ```bash
   ./backup.sh
   ```

2. Pull latest changes:
   ```bash
   git pull origin main
   ```

3. Update dependencies:
   ```bash
   composer update
   ```

4. Check for database updates:
   ```bash
   mysql -u root -p uae_compliance < database/updates.sql
   ```

## License

This software is proprietary. All rights reserved.
