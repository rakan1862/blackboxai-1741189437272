#!/bin/bash

# UAE Compliance Platform Test Script

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test directory
TEST_DIR="tests"
COVERAGE_DIR="coverage"

echo -e "${BLUE}UAE Compliance Platform Test Runner${NC}\n"

# Check if PHPUnit is installed
if ! command -v ./vendor/bin/phpunit &> /dev/null; then
    echo -e "${RED}Error: PHPUnit not found. Please run 'composer install' first.${NC}"
    exit 1
fi

# Create test database if testing environment
if [ "$APP_ENV" = "testing" ]; then
    echo "Setting up test database..."
    mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" << EOF
CREATE DATABASE IF NOT EXISTS ${DB_DATABASE}_test;
EOF
fi

# Function to run specific test suite
run_suite() {
    local suite=$1
    echo -e "\n${BLUE}Running $suite tests...${NC}"
    ./vendor/bin/phpunit --testsuite $suite
}

# Function to generate coverage report
generate_coverage() {
    echo -e "\n${BLUE}Generating code coverage report...${NC}"
    
    # Check if Xdebug is enabled
    if ! php -m | grep -q xdebug; then
        echo -e "${RED}Error: Xdebug is not enabled. Code coverage requires Xdebug.${NC}"
        return 1
    }
    
    mkdir -p $COVERAGE_DIR
    ./vendor/bin/phpunit --coverage-html $COVERAGE_DIR
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Coverage report generated in $COVERAGE_DIR directory${NC}"
    else
        echo -e "${RED}Failed to generate coverage report${NC}"
        return 1
    fi
}

# Parse command line arguments
case "$1" in
    "unit")
        run_suite "Unit"
        ;;
    "feature")
        run_suite "Feature"
        ;;
    "integration")
        run_suite "Integration"
        ;;
    "coverage")
        generate_coverage
        ;;
    "all")
        echo -e "${BLUE}Running all tests...${NC}\n"
        ./vendor/bin/phpunit
        ;;
    "clean")
        echo "Cleaning up test artifacts..."
        rm -rf $COVERAGE_DIR
        echo -e "${GREEN}Clean up completed${NC}"
        ;;
    *)
        echo "UAE Compliance Platform Test Runner"
        echo ""
        echo "Usage:"
        echo "  ./test.sh unit        Run unit tests"
        echo "  ./test.sh feature     Run feature tests"
        echo "  ./test.sh integration Run integration tests"
        echo "  ./test.sh coverage    Generate code coverage report"
        echo "  ./test.sh all         Run all tests"
        echo "  ./test.sh clean       Clean up test artifacts"
        ;;
esac

# Clean up test database if in testing environment
if [ "$APP_ENV" = "testing" ]; then
    echo "Cleaning up test database..."
    mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS ${DB_DATABASE}_test;"
fi

# Check for PHP syntax errors
echo -e "\n${BLUE}Checking for PHP syntax errors...${NC}"
find . -name "*.php" -not -path "./vendor/*" -print0 | while IFS= read -r -d '' file; do
    php -l "$file" > /dev/null
    if [ $? -ne 0 ]; then
        echo -e "${RED}Syntax error found in $file${NC}"
        exit 1
    fi
done

# Run PHP Code Sniffer if available
if command -v ./vendor/bin/phpcs &> /dev/null; then
    echo -e "\n${BLUE}Running PHP Code Sniffer...${NC}"
    ./vendor/bin/phpcs --standard=PSR12 app/
fi

# Run PHPStan if available
if command -v ./vendor/bin/phpstan &> /dev/null; then
    echo -e "\n${BLUE}Running PHPStan...${NC}"
    ./vendor/bin/phpstan analyse -l 5 app/
fi

# Run PHPMD if available
if command -v ./vendor/bin/phpmd &> /dev/null; then
    echo -e "\n${BLUE}Running PHP Mess Detector...${NC}"
    ./vendor/bin/phpmd app/ text cleancode,codesize,controversial,design,naming,unusedcode
fi

echo -e "\n${GREEN}Test process completed!${NC}"

# Display test summary if available
if [ -f "junit.xml" ]; then
    echo -e "\n${BLUE}Test Summary:${NC}"
    echo "----------------------------------------"
    grep -A 1 "testsuites" junit.xml | tail -n 1 | \
    awk '{printf "Tests: %d, Assertions: %d, Failures: %d, Errors: %d, Time: %.2fs\n", 
        $3, $4, $5, $6, $7}'
    echo "----------------------------------------"
fi

# Clean up temporary files
rm -f junit.xml
