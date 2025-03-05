#!/bin/bash

# UAE Compliance Platform Setup Script

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting UAE Compliance Platform setup...${NC}\n"

# Check PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
REQUIRED_PHP="8.0"

if (( $(echo "$PHP_VERSION < $REQUIRED_PHP" | bc -l) )); then
    echo -e "${RED}Error: PHP version $REQUIRED_PHP or higher is required. Current version: $PHP_VERSION${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} PHP version check passed ($PHP_VERSION)"

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo -e "${RED}Error: Composer is not installed${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Composer check passed"

# Create necessary directories
echo "Creating necessary directories..."
mkdir -p storage/documents
mkdir -p storage/backups
mkdir -p logs

# Set directory permissions
echo "Setting directory permissions..."
chmod -R 755 storage
chmod -R 755 logs
chmod 755 public

# Create environment file if not exists
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    
    # Generate random key for APP_KEY
    APP_KEY=$(openssl rand -base64 32)
    sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" .env
    
    echo -e "${GREEN}✓${NC} Environment file created"
fi

# Install composer dependencies
echo "Installing composer dependencies..."
composer install --no-interaction --optimize-autoloader

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Composer dependencies installed"
else
    echo -e "${RED}Error: Failed to install composer dependencies${NC}"
    exit 1
fi

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}Warning: MySQL is not installed${NC}"
else
    # Database setup
    echo -e "\nWould you like to set up the database now? (y/n)"
    read -r setup_db

    if [ "$setup_db" = "y" ]; then
        echo "Enter database credentials:"
        read -p "Host (default: localhost): " db_host
        db_host=${db_host:-localhost}
        
        read -p "Database name (default: uae_compliance): " db_name
        db_name=${db_name:-uae_compliance}
        
        read -p "Username: " db_user
        read -s -p "Password: " db_pass
        echo ""

        # Update .env file with database credentials
        sed -i "s/DB_HOST=.*/DB_HOST=$db_host/" .env
        sed -i "s/DB_DATABASE=.*/DB_DATABASE=$db_name/" .env
        sed -i "s/DB_USERNAME=.*/DB_USERNAME=$db_user/" .env
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$db_pass/" .env

        # Create database and import schema
        echo "Creating database..."
        mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        
        if [ $? -eq 0 ]; then
            echo "Importing database schema..."
            mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name" < database/schema.sql
            
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✓${NC} Database setup completed"
            else
                echo -e "${RED}Error: Failed to import database schema${NC}"
            fi
        else
            echo -e "${RED}Error: Failed to create database${NC}"
        fi
    fi
fi

# Create Apache .htaccess if not exists
if [ ! -f public/.htaccess ]; then
    echo "Creating .htaccess file..."
    cp .htaccess public/.htaccess
    echo -e "${GREEN}✓${NC} .htaccess file created"
fi

# Final checks
echo -e "\nPerforming final checks..."

# Check storage directory permissions
if [ -w "storage" ]; then
    echo -e "${GREEN}✓${NC} Storage directory is writable"
else
    echo -e "${RED}Warning: Storage directory is not writable${NC}"
fi

# Check logs directory permissions
if [ -w "logs" ]; then
    echo -e "${GREEN}✓${NC} Logs directory is writable"
else
    echo -e "${RED}Warning: Logs directory is not writable${NC}"
fi

# Check environment file
if [ -f .env ]; then
    echo -e "${GREEN}✓${NC} Environment file exists"
else
    echo -e "${RED}Warning: Environment file is missing${NC}"
fi

echo -e "\n${GREEN}Setup completed!${NC}"
echo -e "\nDefault admin credentials:"
echo -e "Email: ${YELLOW}admin@uaecompliance.com${NC}"
echo -e "Password: ${YELLOW}change_me_immediately${NC}"
echo -e "\n${RED}Important: Please change the default admin password immediately after first login!${NC}"

# Development server option
echo -e "\nWould you like to start the development server? (y/n)"
read -r start_server

if [ "$start_server" = "y" ]; then
    echo "Starting PHP development server..."
    php -S localhost:8000 -t public/
fi
