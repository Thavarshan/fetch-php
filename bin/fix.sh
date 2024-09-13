#!/usr/bin/env bash

# Exit on error
set -e

# Function to check if a composer package is installed
is_composer_package_installed() {
    composer show "$1" >/dev/null 2>&1
}

# Constants
PHP_CS_FIXER_PACKAGE="friendsofphp/php-cs-fixer"
PHP_CS_FIXER_PATH="vendor/bin/php-cs-fixer"
SRC_DIR="./src"
CONFIG_FILE="./.php-cs-fixer.dist.php"

# Check if PHP-CS-Fixer is installed
if ! is_composer_package_installed $PHP_CS_FIXER_PACKAGE; then
    echo "Installing $PHP_CS_FIXER_PACKAGE..."
    composer require --dev $PHP_CS_FIXER_PACKAGE
fi

# Run the PHP-CS-Fixer analysis
echo "Running PHP-CS-Fixer on $SRC_DIR..."
$PHP_CS_FIXER_PATH fix $SRC_DIR --config=$CONFIG_FILE --using-cache=no --allow-risky=yes --verbose
