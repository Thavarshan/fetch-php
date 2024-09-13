#!/usr/bin/env bash

# Exit on error
set -e

# Function to check if a composer package is installed
is_composer_package_installed() {
    composer show "$1" >/dev/null 2>&1
}

# Constants
PHPCS_PACKAGE="squizlabs/php_codesniffer"
PHPCS_PATH="vendor/bin/phpcs"
DIRECTORIES_TO_ANALYSE="src"
STANDARD="PSR12"

# Check if PHPCS is installed
if ! is_composer_package_installed $PHPCS_PACKAGE; then
    echo "Installing $PHPCS_PACKAGE..."
    composer require --dev $PHPCS_PACKAGE
fi

# Run the PHPCS analysis
echo "Running PHPCS analysis on $DIRECTORIES_TO_ANALYSE using standard $STANDARD..."
$PHPCS_PATH --standard=$STANDARD $DIRECTORIES_TO_ANALYSE
