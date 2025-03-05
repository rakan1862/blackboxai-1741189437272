#!/bin/bash

# UAE Compliance Platform Migration Script

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
else
    echo -e "${RED}Error: .env file not found${NC}"
    exit 1
fi

# Migration directory
MIGRATION_DIR="database/migrations"
MIGRATION_LOG="logs/migrations.log"

# Create migrations table if not exists
echo "Checking migrations table..."
mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" << EOF
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Migrations table ready"
else
    echo -e "${RED}Error: Failed to create migrations table${NC}"
    exit 1
fi

# Function to log migration
log_migration() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$MIGRATION_LOG"
}

# Get last batch number
LAST_BATCH=$(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -se "SELECT COALESCE(MAX(batch), 0) FROM migrations;")
CURRENT_BATCH=$((LAST_BATCH + 1))

# Get list of executed migrations
EXECUTED_MIGRATIONS=$(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -se "SELECT migration FROM migrations;")

# Process each migration file
echo "Checking for new migrations..."
for migration in "$MIGRATION_DIR"/*.sql; do
    if [ -f "$migration" ]; then
        MIGRATION_NAME=$(basename "$migration")
        
        # Check if migration was already executed
        if echo "$EXECUTED_MIGRATIONS" | grep -q "$MIGRATION_NAME"; then
            echo -e "${YELLOW}Skipping${NC} $MIGRATION_NAME (already executed)"
            continue
        }
        
        echo "Executing $MIGRATION_NAME..."
        
        # Begin transaction
        mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" << EOF
START TRANSACTION;

SOURCE $migration;

INSERT INTO migrations (migration, batch) VALUES ('$MIGRATION_NAME', $CURRENT_BATCH);

COMMIT;
EOF
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✓${NC} Successfully executed $MIGRATION_NAME"
            log_migration "Successfully executed $MIGRATION_NAME"
        else
            echo -e "${RED}Error: Failed to execute $MIGRATION_NAME${NC}"
            log_migration "Failed to execute $MIGRATION_NAME"
            
            # Rollback transaction
            mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "ROLLBACK;"
            exit 1
        fi
    fi
done

# Function to create new migration
create_migration() {
    local name=$1
    local timestamp=$(date +%Y%m%d%H%M%S)
    local filename="${timestamp}_${name}.sql"
    local filepath="$MIGRATION_DIR/$filename"
    
    # Create migration file
    cat > "$filepath" << EOF
-- Migration: $name
-- Created at: $(date '+%Y-%m-%d %H:%M:%S')

-- Up
-- Add your SQL statements here

-- Down
-- Add your rollback statements here
EOF
    
    echo -e "${GREEN}Created migration:${NC} $filename"
}

# Handle command line arguments
case "$1" in
    "create")
        if [ -z "$2" ]; then
            echo -e "${RED}Error: Migration name required${NC}"
            echo "Usage: ./migrate.sh create <migration_name>"
            exit 1
        fi
        create_migration "$2"
        ;;
    "rollback")
        echo "Rolling back last batch..."
        
        # Get migrations from last batch
        ROLLBACK_MIGRATIONS=$(mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -se "SELECT migration FROM migrations WHERE batch = $LAST_BATCH;")
        
        for migration in $ROLLBACK_MIGRATIONS; do
            echo "Rolling back $migration..."
            
            # Extract rollback SQL between "-- Down" and next comment or EOF
            sed -n '/-- Down/,/-- /p' "$MIGRATION_DIR/$migration" | grep -v "^--" | \
            mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
            
            if [ $? -eq 0 ]; then
                mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "DELETE FROM migrations WHERE migration = '$migration';"
                echo -e "${GREEN}✓${NC} Successfully rolled back $migration"
                log_migration "Successfully rolled back $migration"
            else
                echo -e "${RED}Error: Failed to rollback $migration${NC}"
                log_migration "Failed to rollback $migration"
                exit 1
            fi
        done
        ;;
    "status")
        echo "Migration Status:"
        mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
            SELECT 
                migration,
                batch,
                executed_at
            FROM migrations
            ORDER BY id DESC;
        "
        ;;
    "reset")
        echo -e "${RED}Warning: This will reset all migrations. Are you sure? (y/n)${NC}"
        read -r confirm
        if [ "$confirm" = "y" ]; then
            echo "Resetting all migrations..."
            mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "DROP TABLE IF EXISTS migrations;"
            echo -e "${GREEN}✓${NC} All migrations reset"
        fi
        ;;
    *)
        echo "UAE Compliance Platform Migration Tool"
        echo ""
        echo "Usage:"
        echo "  ./migrate.sh              Run pending migrations"
        echo "  ./migrate.sh create NAME  Create a new migration"
        echo "  ./migrate.sh rollback     Rollback last batch of migrations"
        echo "  ./migrate.sh status       Show migration status"
        echo "  ./migrate.sh reset        Reset all migrations"
        ;;
esac

echo -e "\n${GREEN}Migration process completed!${NC}"
