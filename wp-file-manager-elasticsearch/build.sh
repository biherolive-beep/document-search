#!/bin/bash

# This script prepares the plugin for distribution.
# It installs the Composer dependencies into a self-contained 'vendor' directory.

# Check for Composer
if ! [ -f "composer.phar" ]; then
  echo 'Error: composer.phar not found.' >&2
  echo "Please run the Composer installer first: php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php && php -r \"unlink('composer-setup.php');\"" >&2
  exit 1
fi

echo "Installing Composer dependencies..."
php composer.phar install --no-dev --optimize-autoloader

echo "Build complete. The 'vendor' directory is now populated."
echo "You can now package the plugin for distribution."