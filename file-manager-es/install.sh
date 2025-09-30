#!/bin/bash

# --- Configuration ---
APP_DIR="/var/www/file-manager-es"
APACHE_CONF_DIR="/etc/apache2/sites-available"
SYSTEMD_DIR="/etc/systemd/system"
APACHE_USER="www-data"

# Make the script self-aware of its location to resolve paths correctly
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# --- Helper Functions ---
function print_info {
    echo -e "\e[34m[INFO]\e[0m $1"
}

function print_success {
    echo -e "\e[32m[SUCCESS]\e[0m $1"
}

function print_error {
    echo -e "\e[31m[ERROR]\e[0m $1"
    exit 1
}

# --- Main Script ---

# Check for root privileges
if [ "$EUID" -ne 0 ]; then
  print_error "This script must be run as root."
fi

# --- Package Installation ---
print_info "Updating package lists..."
apt-get update

print_info "Installing dependencies: Apache, PHP, Java, Composer..."
apt-get install -y apache2 php libapache2-mod-php default-jdk composer || print_error "Failed to install base packages."

# --- Elasticsearch Installation ---
if ! command -v elasticsearch &> /dev/null; then
    print_info "Installing Elasticsearch..."
    wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg
    apt-get install -y apt-transport-https
    echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-8.x.list
    apt-get update
    apt-get install -y elasticsearch || print_error "Failed to install Elasticsearch."
else
    print_info "Elasticsearch is already installed."
fi

print_info "Enabling and starting Elasticsearch service..."
systemctl daemon-reload
systemctl enable elasticsearch.service
systemctl start elasticsearch.service

# --- Apache Tika Installation & Service Setup ---
print_info "Installing Apache Tika..."
mkdir -p /opt/tika
wget -q -O /opt/tika/tika-server-standard.jar https://dlcdn.apache.org/tika/2.9.1/tika-server-standard-2.9.1.jar || print_error "Failed to download Tika."

print_info "Setting up Tika systemd service..."
cp "$SCRIPT_DIR/config/tika.service" "$SYSTEMD_DIR/tika.service"
systemctl daemon-reload
systemctl enable tika.service
systemctl start tika.service

# --- Application Setup ---
print_info "Setting up application directory structure at $APP_DIR..."
mkdir -p "$APP_DIR"

print_info "Copying application files..."
# Use SCRIPT_DIR to ensure paths are absolute and correct
cp -r "$SCRIPT_DIR"/public/* "$APP_DIR/public/"
cp -r "$SCRIPT_DIR"/scripts/* "$APP_DIR/scripts/"
cp -r "$SCRIPT_DIR"/config/* "$APP_DIR/config/"
cp "$SCRIPT_DIR/composer.json" "$APP_DIR/"

print_info "Installing PHP dependencies with Composer..."
cd "$APP_DIR"
composer install --no-dev --optimize-autoloader || print_error "Composer install failed."

print_info "Creating uploads directory and setting permissions..."
mkdir -p "$APP_DIR/uploads"
chown -R $APACHE_USER:$APACHE_USER "$APP_DIR"
chmod -R 755 "$APP_DIR"
# Give write permissions to the web server for config and uploads
chmod -R 775 "$APP_DIR/config"
chmod -R 775 "$APP_DIR/uploads"

# --- Apache Configuration ---
print_info "Configuring Apache..."
cp "$APP_DIR/config/file-manager.conf" "$APACHE_CONF_DIR/file-manager.conf"
a2dissite 000-default.conf
a2ensite file-manager.conf
a2enmod rewrite
systemctl restart apache2

# --- Finalization ---
print_success "Installation complete!"
print_info "Your File Manager is now available."
print_info "Please navigate to your server's IP address in a web browser."
print_info "Default admin credentials: admin / password"
print_info "IMPORTANT: For security, please change the admin password by editing the public/admin/login.php file."