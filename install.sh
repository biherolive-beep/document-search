#!/bin/bash

# Installation script for WordPress File Manager with Elasticsearch
# Supports: Debian 12/13, Ubuntu 22.04+

set -e # Exit on any error

# --- Colors for output ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# --- Helper Functions ---
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use sudo)."
        exit 1
    fi
}

detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$ID
        OS_VERSION=$VERSION_ID
        print_status "Detected OS: $OS $OS_VERSION"

        if [[ "$OS" != "debian" && "$OS" != "ubuntu" ]]; then
            print_error "This script is designed for Debian or Ubuntu only."
            exit 1
        fi
    else
        print_error "Cannot detect operating system. /etc/os-release not found."
        exit 1
    fi
}

# --- Installation Steps ---
install_base_packages() {
    print_status "Updating package lists..."
    apt-get update

    print_status "Installing base packages (Apache, PHP, and extensions)..."
    apt-get install -y \
        apache2 \
        php libapache2-mod-php php-mysql php-curl php-gd php-intl php-mbstring \
        php-soap php-xml php-xmlrpc php-zip \
        wget gnupg apt-transport-https \
        unzip
    print_success "Base packages installed."
}

install_elasticsearch() {
    print_status "Installing Elasticsearch..."

    if dpkg -l | grep -q "elasticsearch"; then
        print_warning "Elasticsearch appears to be already installed. Skipping installation."
        return
    fi

    # Add Elasticsearch PGP Key
    print_status "Adding Elasticsearch PGP key..."
    wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | gpg --dearmor -o /usr/share/keyrings/elasticsearch-keyring.gpg

    # Add the Elasticsearch repository
    print_status "Adding Elasticsearch repository..."
    echo "deb [signed-by=/usr/share/keyrings/elasticsearch-keyring.gpg] https://artifacts.elastic.co/packages/8.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-8.x.list

    # Install Elasticsearch
    print_status "Updating package lists and installing Elasticsearch..."
    apt-get update
    apt-get install -y elasticsearch

    print_success "Elasticsearch installed."
}

install_es_ingest_plugin() {
    print_status "Installing the Elasticsearch 'ingest-attachment' plugin for file content extraction..."
    /usr/share/elasticsearch/bin/elasticsearch-plugin install ingest-attachment
    print_success "'ingest-attachment' plugin installed."
}

configure_services() {
    print_status "Configuring and enabling services..."

    # Configure PHP for larger uploads
    print_status "Configuring PHP (upload_max_filesize, post_max_size)..."
    PHP_INI=$(php -i | grep "Loaded Configuration File" | sed 's/.*=> //')
    if [ -f "$PHP_INI" ]; then
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' "$PHP_INI"
        sed -i 's/post_max_size = .*/post_max_size = 64M/' "$PHP_INI"
    else
        print_warning "Could not find PHP ini file automatically. You may need to set it manually."
    fi

    # Enable and start Apache
    systemctl enable apache2
    systemctl start apache2

    # Enable and start Elasticsearch
    systemctl daemon-reload
    systemctl enable elasticsearch
    systemctl start elasticsearch

    print_success "Apache2 and Elasticsearch have been enabled and started."
}

final_instructions() {
    IP_ADDRESS=$(hostname -I | awk '{print $1}')
    echo -e "\n\n"
    echo "========================================================================="
    echo -e "🎉 ${GREEN}Server Setup Complete!${NC} 🎉"
    echo "========================================================================="
    echo ""
    echo "Your server has been configured with Apache2, PHP, and Elasticsearch."
    echo ""
    print_status "Next Steps:"
    echo "1.  **Set up a MySQL/MariaDB database for WordPress.**"
    echo "    Example commands:"
    echo "    > sudo apt-get install mariadb-server"
    echo "    > sudo mysql -u root"
    echo "    > CREATE DATABASE wordpress;"
    echo "    > CREATE USER 'wpuser'@'localhost' IDENTIFIED BY 'your-password';"
    echo "    > GRANT ALL PRIVILEGES ON wordpress.* TO 'wpuser'@'localhost';"
    echo "    > FLUSH PRIVILEGES;"
    echo "    > EXIT;"
    echo ""
    echo "2.  **Download and configure WordPress.**"
    echo "    > cd /tmp && wget https://wordpress.org/latest.zip"
    echo "    > unzip latest.zip"
    echo "    > sudo mv wordpress/* /var/www/html/"
    echo "    > sudo chown -R www-data:www-data /var/www/html/"
    echo "    > Follow the on-screen WordPress installation at http://${IP_ADDRESS}"
    echo ""
    echo "3.  **Install the Plugin.**"
    echo "    - Copy the 'wp-file-manager-elasticsearch' directory to '/var/www/html/wp-content/plugins/'"
    echo "    - Activate the 'File Manager with Elasticsearch' plugin from the WordPress admin dashboard."
    echo ""
    echo "4.  **Configure Firewall (if needed).**"
    echo "    If you use ufw:"
    echo "    > sudo ufw allow 'Apache Full'"
    echo "    > sudo ufw allow ssh"
    echo "    > sudo ufw enable"
    echo ""
    print_warning "It may take a minute for Elasticsearch to start fully."
    echo "You can check its status with: ${YELLOW}systemctl status elasticsearch${NC}"
    echo ""
}


# --- Main Execution ---
main() {
    check_root
    detect_os
    install_base_packages
    install_elasticsearch
    install_es_ingest_plugin
    configure_services
    final_instructions
}

main "$@"