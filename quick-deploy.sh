#!/bin/bash
# Quick Deploy Script for Hostinger VPS
# Domain: potunakos.my.id | IP: 148.230.96.228

echo "🚀 Starting Kost Management System Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    print_error "Please run as root (use sudo)"
    exit 1
fi

print_status "Updating system packages..."
apt update && apt upgrade -y

print_status "Installing Node.js 18..."
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
apt-get install -y nodejs

print_status "Installing Nginx..."
apt install nginx -y

print_status "Installing MySQL..."
apt install mysql-server -y

print_status "Adding PHP repository and installing PHP..."
# Add PHP repository for Ubuntu 22.04
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2 and extensions
apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring php8.2-cli -y

print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

print_status "Installing Git..."
apt install git -y

# Create and navigate to web directory
mkdir -p /var/www
cd /var/www/

print_status "Setting up project directory..."
if [ -d "kost-10" ]; then
    print_warning "Directory exists, updating..."
    cd kost-10
    git pull
else
    print_error "Project directory not found!"
    echo ""
    echo "Please upload your project to /var/www/kost-10 first:"
    echo ""
    echo "Method 1 - GitHub (Recommended):"
    echo "1. Upload project to GitHub repository"
    echo "2. Run: git clone https://github.com/USERNAME/REPO-NAME.git kost-10"
    echo ""
    echo "Method 2 - FileZilla:"
    echo "1. Use FileZilla to upload project folder to /var/www/"
    echo ""
    echo "Then run this script again: sudo ./quick-deploy.sh"
    exit 1
fi

# Set permissions
print_status "Setting permissions..."
chown -R www-data:www-data /var/www/kost-10
chmod -R 755 /var/www/kost-10

# Setup Backend
print_status "Setting up Laravel backend..."
cd /var/www/kost-10/kost-backend

# Install composer dependencies
composer install --optimize-autoloader --no-dev

# Copy env file
if [ ! -f ".env" ]; then
    cp ../.env.production.example .env
    print_status "Environment file created"
else
    print_warning ".env file already exists"
fi

# Generate app key
php artisan key:generate

print_warning "Please setup MySQL database manually:"
echo "1. Run: mysql -u root -p"
echo "2. Execute these SQL commands:"
echo "   CREATE DATABASE potunakos_kost;"
echo "   CREATE USER 'potunakos_user'@'localhost' IDENTIFIED BY 'YourSecurePassword';"
echo "   GRANT ALL PRIVILEGES ON potunakos_kost.* TO 'potunakos_user'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo "   EXIT;"
echo "3. Update .env file with your database password"
echo ""
read -p "Press Enter after completing database setup..."

# Run migrations
print_status "Running database migrations..."
php artisan migrate --seed

# Link storage
print_status "Linking storage..."
php artisan storage:link

# Cache config
print_status "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Setup Frontend
print_status "Setting up React frontend..."
cd /var/www/kost-10/kost-frontend

# Install npm dependencies
npm install

# Build for production
print_status "Building frontend..."
npm run build:prod

# Setup Nginx
print_status "Configuring Nginx..."
cat > /etc/nginx/sites-available/potunakos.my.id << 'EOF'
server {
    listen 80;
    server_name potunakos.my.id www.potunakos.my.id;
    root /var/www/kost-10/kost-frontend/dist;
    index index.html;

    # Frontend (React) - SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API (Laravel)
    location /api/ {
        proxy_pass http://127.0.0.1:8000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/potunakos.my.id /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t

if [ $? -eq 0 ]; then
    print_status "Nginx configuration valid"
    systemctl restart nginx
else
    print_error "Nginx configuration error"
    exit 1
fi

# Setup systemd service for Laravel
print_status "Setting up Laravel systemd service..."
cat > /etc/systemd/system/kost-backend.service << 'EOF'
[Unit]
Description=Kost Backend Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/kost-10/kost-backend
ExecStart=/usr/bin/php artisan serve --host=127.0.0.1 --port=8000
Restart=on-failure

[Install]
WantedBy=multi-user.target
EOF

systemctl enable kost-backend.service
systemctl start kost-backend.service

# Check service status
sleep 3
if systemctl is-active --quiet kost-backend.service; then
    print_status "Laravel backend service started successfully"
else
    print_error "Laravel backend service failed to start"
    systemctl status kost-backend.service
fi

# Setup SSL with Let's Encrypt
print_status "Setting up SSL certificate..."
apt install certbot python3-certbot-nginx -y

print_warning "Setting up SSL certificate..."
echo "Run this command to get SSL certificate:"
echo "certbot --nginx -d potunakos.my.id -d www.potunakos.my.id"
echo ""

# Setup auto-renewal
print_status "Setting up SSL auto-renewal..."
(crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | crontab -

print_status "🎉 Deployment completed!"
echo ""
echo "======================================"
echo "🌐 Frontend: http://potunakos.my.id"
echo "🔌 API: http://potunakos.my.id/api"
echo "📊 Admin Login: Use seeded admin account"
echo "======================================"
echo ""
echo "📝 Next Steps:"
echo "1. Run SSL setup: certbot --nginx -d potunakos.my.id -d www.potunakos.my.id"
echo "2. Test all functionality"
echo "3. Check logs: systemctl status kost-backend.service"
echo ""
echo "🔧 Quick Update Command:"
echo "cd /var/www/kost-10 && git pull && cd kost-frontend && npm run build:prod && systemctl restart kost-backend.service && systemctl reload nginx"