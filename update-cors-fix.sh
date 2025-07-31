#!/bin/bash
# Quick CORS Fix Update Script

echo "🔧 Updating CORS configuration on VPS..."

# You need to run these commands on your VPS:
echo "Run these commands on your VPS (148.230.96.228):"
echo ""
echo "cd /var/www/kost-10"
echo "git pull"
echo "cd kost-backend"
echo "php artisan config:clear"
echo "php artisan config:cache"
echo "systemctl restart kost-backend.service"
echo ""
echo "Then test the website: https://www.potunakos.my.id"