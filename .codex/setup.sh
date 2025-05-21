#!/usr/bin/env bash
set -euo pipefail

echo "Setting up environment for GPSTools..."

# ——— language runtimes ———
# Codex-universal image will auto-install the versions you request
export CODEX_ENV_PHP_VERSION=8.3   # see supported list in codex-universal README

# ——— Check existing tools ———
MISSING_PACKAGES=""

# Check PHP and required extensions
if ! command -v php &> /dev/null || [[ "$(php -r 'echo PHP_VERSION;' | cut -c1-3)" < "8.3" ]]; then
    # Include all needed extensions when installing PHP
    MISSING_PACKAGES="$MISSING_PACKAGES php8.3-cli php8.3-mbstring php8.3-xml php8.3-ctype php8.3-iconv"
else
    # If PHP is installed, check individual extensions
    PHP_EXTENSIONS="mbstring xml ctype iconv"
    for ext in $PHP_EXTENSIONS; do
        if ! php -m | grep -q -i "$ext" && ! php -r "echo extension_loaded('$ext') ? 'yes' : '';" | grep -q "yes"; then
            echo "⚠️ PHP extension $ext is missing, adding to installation list"
            MISSING_PACKAGES="$MISSING_PACKAGES php8.3-$ext"
        else
            echo "✓ PHP extension $ext is available"
        fi
    done
fi

# Check for unzip if needed
if ! command -v unzip &> /dev/null; then
    MISSING_PACKAGES="$MISSING_PACKAGES unzip"
fi

# Verify Java is installed for FitCSVTool.jar
if ! command -v java &> /dev/null; then
    echo "⚠️ Java is not installed. Installing default-jre for FitCSVTool.jar"
    MISSING_PACKAGES="$MISSING_PACKAGES default-jre"
else
    echo "✓ Java is already installed: $(java -version 2>&1 | head -n 1)"
fi

# ——— Install missing packages if any ———
if [ -n "$MISSING_PACKAGES" ]; then
    echo "Installing missing packages: $MISSING_PACKAGES"
    apt-get update -qq
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends $MISSING_PACKAGES
else
    echo "✓ All system dependencies are already installed"
fi

# ——— Composer ———
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
else
    echo "✓ Composer is already installed: $(composer --version)"
fi

# ——— Project deps ———
echo "Installing project dependencies..."

# Check if composer.json exists in the current directory
if [ -f "./composer.json" ]; then
    # We're already in the project root, install dependencies
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "⚠️ Could not find composer.json in the current directory."
    echo "The setup script should be run from the root of the project."
fi

echo "✅ Environment ready for offline execution"
