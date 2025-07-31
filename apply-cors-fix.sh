#!/bin/bash
# Apply Complete CORS Fix

echo "🔧 Applying complete CORS fix..."

# Commands to run on VPS:
cat << 'EOF'
=== Run these commands on your VPS (148.230.96.228) ===

# 1. Update code from git
cd /var/www/kost-10
git pull

# 2. Update .env file with new CORS configuration
cd kost-backend
cp ../.env.production.example .env

# 3. Update .env with your actual database password
# Edit the DB_PASSWORD line in .env file:
nano .env
# Change: DB_PASSWORD=your_secure_database_password
# To your actual password

# 4. Clear and rebuild all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart Laravel service
sudo systemctl restart kost-backend.service

# 7. Check service status
sudo systemctl status kost-backend.service

# 8. Test API endpoint
curl -H "Origin: https://www.potunakos.my.id" \
     -X OPTIONS \
     https://potunakos.my.id/api/health

EOF

echo ""
echo "🎯 What was fixed:"
echo "✅ Custom CORS middleware with both domains"
echo "✅ Updated .env.production.example with both origins"
echo "✅ Simplified OPTIONS route handler"
echo "✅ Production-ready CORS configuration"