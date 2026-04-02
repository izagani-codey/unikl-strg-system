# 🔧 Debugging Guide for UniKL STRG System

## 🚨 **Common Issues & Solutions**

### **1. Redirect Loop (ERR_TOO_MANY_REDIRECTS)**
**Symptoms**: Browser shows redirect loop error when accessing dashboard

**Causes**:
- Missing controllers or services
- Route conflicts
- Middleware issues
- Cache problems

**Solutions**:
```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Check routes
php artisan route:list

# Optimize system
php artisan optimize
```

### **2. White Screen / 500 Error**
**Symptoms**: Blank page or server error

**Causes**:
- Syntax errors in PHP files
- Missing dependencies
- Database connection issues
- Permission problems

**Solutions**:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check file permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Check composer dependencies
composer install --no-dev --optimize-autoloader
```

### **3. Database Connection Issues**
**Symptoms**: Database connection errors

**Solutions**:
```bash
# Check .env file
cat .env

# Test database connection
php artisan tinker
>>> DB::select('SELECT 1');

# Run migrations
php artisan migrate:status
php artisan migrate
```

### **4. Asset Loading Issues**
**Symptoms**: CSS/JS not loading, broken UI

**Solutions**:
```bash
# Build assets
npm install
npm run build

# Clear asset cache
php artisan assets:clean

# Check public directory
ls -la public/
```

## 🛠 **Debugging Tools**

### **1. Laravel Debug Bar**
```bash
# Install debug bar
composer require barryvdh/laravel-debugbar --dev

# Add to config/app.php
'providers' => [
    Barryvdh\Debugbar\ServiceProvider::class,
],
```

### **2. Laravel Telescope**
```bash
# Install telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### **3. Log Monitoring**
```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Filter logs by level
grep "ERROR" storage/logs/laravel.log
```

## 🔍 **Debugging Steps**

### **Step 1: Identify the Issue**
1. **Check Browser Console**: F12 → Console tab
2. **Check Network Tab**: Look for failed requests
3. **Check Laravel Logs**: `storage/logs/laravel.log`
4. **Check Environment**: `php artisan env`

### **Step 2: Isolate the Problem**
1. **Test Basic Routes**: Access `/` and `/login`
2. **Check Authentication**: Test login flow
3. **Check Database**: Run basic queries
4. **Check Permissions**: Verify file permissions

### **Step 3: Fix the Issue**
1. **Clear Caches**: All cache clear commands
2. **Check Dependencies**: Composer and npm
3. **Verify Configuration**: `.env` file settings
4. **Test Incrementally**: Add features back one by one

## 📋 **Preventive Measures**

### **1. Regular Maintenance**
```bash
# Weekly maintenance script
#!/bin/bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize
composer dump-autoload
```

### **2. Environment Checks**
```bash
# Check system status
php artisan about
php artisan migrate:status
php artisan route:list
```

### **3. Backup Strategy**
```bash
# Database backup
php artisan db:backup

# File backup
tar -czf backup-$(date +%Y%m%d).tar.gz storage/
```

## 🚀 **Herd-Specific Issues**

### **1. URL Not Loading**
**Symptoms**: `http://my-app.test/dashboard` not working

**Solutions**:
```bash
# Check Herd configuration
herd list
herd status

# Restart Herd services
herd restart

# Check site configuration
herd sites
```

### **2. SSL Certificate Issues**
```bash
# Regenerate SSL certificate
herd secure my-app.test

# Check certificate status
herd secure --list
```

### **3. Port Conflicts**
```bash
# Check what's running on port 80
netstat -ano | findstr :80

# Kill conflicting processes
taskkill /PID <PID> /F
```

## 📊 **Performance Debugging**

### **1. Slow Page Loads**
```bash
# Check query performance
php artisan tinker
>>> DB::enableQueryLog();
>>> // Run your query
>>> DB::getQueryLog();

# Check memory usage
php artisan tinker
>>> memory_get_usage(true);
>>> memory_get_peak_usage(true);
```

### **2. Asset Optimization**
```bash
# Optimize assets
npm run build --production

# Check asset sizes
du -sh public/build/
```

## 🔒 **Security Debugging**

### **1. Authentication Issues**
```bash
# Check user authentication
php artisan tinker
>>> Auth::user();
>>> Auth::check();

# Reset admin password
php artisan tinker
>>> $user = User::find(1);
>>> $user->password = Hash::make('newpassword');
>>> $user->save();
```

### **2. Permission Issues**
```bash
# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Fix permissions
chmod -R 755 storage bootstrap/cache
```

## 📞 **Getting Help**

### **1. Laravel Resources**
- [Laravel Documentation](https://laravel.com/docs)
- [Laravel News](https://laravel-news.com)
- [Laracasts](https://laracasts.com)

### **2. Community Support**
- [Laravel Discord](https://discord.gg/laravel)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/laravel)
- [Reddit r/laravel](https://reddit.com/r/laravel)

### **3. Debug Commands Cheat Sheet**
```bash
# Essential debugging commands
php artisan about                    # System overview
php artisan route:list               # All routes
php artisan migrate:status           # Migration status
php artisan config:cache             # Cache config
php artisan optimize                  # Optimize system
php artisan tinker                    # Interactive shell
tail -f storage/logs/laravel.log    # Real-time logs
```

## 🎯 **Quick Fix Checklist**

When something goes wrong, run these in order:

1. ✅ **Clear Caches**: `php artisan optimize:clear`
2. ✅ **Check Logs**: `tail -f storage/logs/laravel.log`
3. ✅ **Test Basic Routes**: Access `/` and `/login`
4. ✅ **Check Database**: `php artisan tinker` → `DB::select('SELECT 1')`
5. ✅ **Verify Environment**: `php artisan env`
6. ✅ **Restart Services**: `herd restart`
7. ✅ **Check Permissions**: `chmod -R 755 storage bootstrap/cache`

**If still broken**: Check specific error message in logs and search Laravel documentation.
