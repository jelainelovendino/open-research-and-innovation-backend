# Deployment Checklist

## Pre-Deployment (Local)

### 1. Environment & Config
```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate-if-not-set>

# Database (update for your production DB)
DB_CONNECTION=mysql
DB_HOST=your-production-host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Frontend URL (for CORS)
FRONTEND_URL=https://your-frontend-domain.com
```

### 2. Migrations
```bash
# Run migrations locally to confirm no errors
php artisan migrate

# Check migration status
php artisan migrate:status
```

### 3. Dependencies
```bash
# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# Cache config for faster startup
php artisan config:cache

# Cache routes (optional but recommended)
php artisan route:cache
```

### 4. Testing
```bash
# Test key endpoints (update token/IDs)
TOKEN="your-test-token"

# Register user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"pass","password_confirmation":"pass","course":"CS","school":"Uni","department":"IT","bio":"Test"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"pass"}'

# Create project (multipart)
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer $TOKEN" \
  -F "title=Test" \
  -F "description=test" \
  -F "category_id=1" \
  -F "file=@test.pdf"

# Get my projects
curl -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/my-projects

# Search projects
curl "http://localhost:8000/api/projects/search?title=test&category_id=1"
```

## Production Deployment

### 1. Server Setup (Linux/Ubuntu)
```bash
# SSH into production server
ssh user@your-server

# Clone repository
git clone https://github.com/jelainelovendino/open-research-and-innovation-backend.git
cd open-research-and-innovation-backend

# Create .env from .env.example and configure
cp .env.example .env
nano .env  # Update DB, FRONTEND_URL, APP_KEY, etc.

# Install dependencies
composer install --no-dev --optimize-autoloader

# Generate app key (if not set)
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed

# Create storage symlink
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Cache config
php artisan config:cache
php artisan route:cache
```

### 2. Web Server Config (Nginx)
```nginx
server {
    listen 80;
    server_name your-api-domain.com;

    root /path/to/backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 3. SSL (Let's Encrypt)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-nginx

# Generate certificate
sudo certbot certonly --nginx -d your-api-domain.com

# Auto-renew (cron job)
0 0 1 * * certbot renew
```

### 4. Database Backup (Cron)
```bash
# Add to crontab: backup daily at 2 AM
0 2 * * * mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > /backups/db_$(date +\%Y\%m\%d).sql
```

### 5. Monitoring & Logs
```bash
# Watch application logs
tail -f storage/logs/laravel.log

# Check PHP-FPM
systemctl status php8.1-fpm

# Check Nginx
systemctl status nginx

# Monitor disk/memory
df -h
free -h
```

## Post-Deployment

### 1. Verify API
```bash
# Test from your frontend domain
curl -H "Origin: https://your-frontend.com" \
  -H "Access-Control-Request-Method: POST" \
  https://your-api-domain.com/api/projects

# Should include CORS headers in response
```

### 2. Test Auth Flow
```bash
# Register → Login → Create Project → Get Projects
# Verify tokens work and user owns their projects
```

### 3. File Storage
```bash
# Verify uploaded files are accessible
# Check public/storage symlink exists
# Confirm file permissions are correct
```

## Troubleshooting

### 500 Internal Server Error
```bash
# Check error logs
tail -f storage/logs/laravel.log

# Verify .env is configured
php artisan tinker
>>> config('app.env');
```

### CORS Issues
```bash
# Verify CORS config matches frontend URL
cat config/cors.php

# Check FRONTEND_URL in .env
grep FRONTEND_URL .env
```

### File Upload Fails
```bash
# Verify storage permissions
ls -la storage/app/public/

# Check symlink
ls -la public/storage

# Recreate if needed
php artisan storage:link
```

### Database Connection Error
```bash
# Verify DB credentials
php artisan tinker
>>> DB::connection()->getPDO();
```

## Rollback (if needed)
```bash
# Revert last migration
php artisan migrate:rollback

# Or specific migration
php artisan migrate:rollback --target=<migration_name>

# Check current migration status
php artisan migrate:status
```
