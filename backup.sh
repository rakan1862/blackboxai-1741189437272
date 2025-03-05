#!/bin/bash

# UAE Compliance Platform Backup Script

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

# Create backup directory if not exists
BACKUP_DIR="storage/backups"
BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_DATE}"

mkdir -p "$BACKUP_PATH"

echo -e "${GREEN}Starting backup process...${NC}"

# Database backup
echo "Backing up database..."
if mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "${BACKUP_PATH}/database.sql"; then
    echo -e "${GREEN}✓${NC} Database backup completed"
    
    # Compress database backup
    gzip "${BACKUP_PATH}/database.sql"
    echo -e "${GREEN}✓${NC} Database backup compressed"
else
    echo -e "${RED}Error: Database backup failed${NC}"
    exit 1
fi

# Document files backup
echo "Backing up documents..."
if [ -d "storage/documents" ]; then
    tar -czf "${BACKUP_PATH}/documents.tar.gz" -C storage documents
    echo -e "${GREEN}✓${NC} Documents backup completed"
else
    echo -e "${YELLOW}Warning: No documents directory found${NC}"
fi

# Environment file backup
echo "Backing up environment file..."
cp .env "${BACKUP_PATH}/.env.backup"
echo -e "${GREEN}✓${NC} Environment file backup completed"

# Create backup manifest
echo "Creating backup manifest..."
cat > "${BACKUP_PATH}/manifest.txt" << EOF
UAE Compliance Platform Backup
============================
Date: $(date)
Version: $(grep "APP_VERSION" .env | cut -d '=' -f2)
Environment: $(grep "APP_ENV" .env | cut -d '=' -f2)

Contents:
- database.sql.gz (Database backup)
- documents.tar.gz (Document files)
- .env.backup (Environment configuration)

Database Information:
- Host: $DB_HOST
- Database: $DB_DATABASE
- Username: $DB_USERNAME

System Information:
- PHP Version: $(php -v | head -n 1)
- MySQL Version: $(mysql --version)
EOF

echo -e "${GREEN}✓${NC} Backup manifest created"

# Create final archive
echo "Creating final backup archive..."
cd storage/backups
tar -czf "${BACKUP_DATE}.tar.gz" "$BACKUP_DATE"
rm -rf "$BACKUP_DATE"
cd - > /dev/null

echo -e "${GREEN}✓${NC} Final backup archive created"

# Cleanup old backups
echo "Cleaning up old backups..."
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete
echo -e "${GREEN}✓${NC} Old backups cleaned up"

# Calculate backup size
BACKUP_SIZE=$(du -h "${BACKUP_DIR}/${BACKUP_DATE}.tar.gz" | cut -f1)

echo -e "\n${GREEN}Backup completed successfully!${NC}"
echo "Backup location: ${BACKUP_DIR}/${BACKUP_DATE}.tar.gz"
echo "Backup size: $BACKUP_SIZE"

# Optional: Upload to remote storage
if [ ! -z "$BACKUP_REMOTE_PATH" ]; then
    echo -e "\nUploading backup to remote storage..."
    
    case "$BACKUP_STORAGE" in
        "s3")
            if command -v aws &> /dev/null; then
                aws s3 cp "${BACKUP_DIR}/${BACKUP_DATE}.tar.gz" "s3://${BACKUP_REMOTE_PATH}/"
                if [ $? -eq 0 ]; then
                    echo -e "${GREEN}✓${NC} Backup uploaded to S3"
                else
                    echo -e "${RED}Error: Failed to upload backup to S3${NC}"
                fi
            else
                echo -e "${YELLOW}Warning: AWS CLI not installed, skipping S3 upload${NC}"
            fi
            ;;
        "ftp")
            if [ ! -z "$FTP_HOST" ] && [ ! -z "$FTP_USER" ] && [ ! -z "$FTP_PASS" ]; then
                ftp -n "$FTP_HOST" << EOF
user "$FTP_USER" "$FTP_PASS"
binary
cd "$BACKUP_REMOTE_PATH"
put "${BACKUP_DIR}/${BACKUP_DATE}.tar.gz"
quit
EOF
                if [ $? -eq 0 ]; then
                    echo -e "${GREEN}✓${NC} Backup uploaded to FTP"
                else
                    echo -e "${RED}Error: Failed to upload backup to FTP${NC}"
                fi
            else
                echo -e "${YELLOW}Warning: FTP credentials not configured, skipping FTP upload${NC}"
            fi
            ;;
    esac
fi

# Send notification
if [ ! -z "$NOTIFICATION_EMAIL" ]; then
    echo -e "\nSending backup notification..."
    
    EMAIL_BODY="UAE Compliance Platform Backup Completed\n\n"
    EMAIL_BODY+="Date: $(date)\n"
    EMAIL_BODY+="Backup File: ${BACKUP_DATE}.tar.gz\n"
    EMAIL_BODY+="Size: $BACKUP_SIZE\n\n"
    EMAIL_BODY+="Backup contents:\n"
    EMAIL_BODY+="- Database backup\n"
    EMAIL_BODY+="- Document files\n"
    EMAIL_BODY+="- Environment configuration\n"
    
    echo -e "$EMAIL_BODY" | mail -s "Backup Completed - UAE Compliance Platform" "$NOTIFICATION_EMAIL"
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} Backup notification sent"
    else
        echo -e "${RED}Error: Failed to send backup notification${NC}"
    fi
fi

echo -e "\n${GREEN}Backup process completed!${NC}"
