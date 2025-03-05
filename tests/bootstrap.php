<?php
/**
 * UAE Compliance Platform - Test Bootstrap File
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH, '.env.testing');
$dotenv->load();

// Set up test database
function setupTestDatabase() {
    try {
        // Create test database connection
        $db = new PDO(
            "mysql:host=" . getenv('DB_HOST'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        
        // Create test database if it doesn't exist
        $db->exec("CREATE DATABASE IF NOT EXISTS " . getenv('DB_DATABASE') . "_test");
        
        // Select test database
        $db->exec("USE " . getenv('DB_DATABASE') . "_test");
        
        // Import schema
        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        $db->exec($schema);
        
        return true;
    } catch (PDOException $e) {
        echo "Error setting up test database: " . $e->getMessage() . "\n";
        return false;
    }
}

// Create test storage directories
function setupTestStorage() {
    $directories = [
        BASE_PATH . '/storage/test/documents',
        BASE_PATH . '/storage/test/backups',
        BASE_PATH . '/logs/test'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Clean up test files
function cleanupTestFiles() {
    $directories = [
        BASE_PATH . '/storage/test/documents',
        BASE_PATH . '/storage/test/backups',
        BASE_PATH . '/logs/test'
    ];
    
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }
}

// Mock functions
function mockMailer() {
    if (!function_exists('mail')) {
        function mail($to, $subject, $message, $headers = '', $parameters = '') {
            return true;
        }
    }
}

// Test helper functions
class TestHelper {
    public static function createTestUser($db, $data = []) {
        $defaults = [
            'company_id' => 1,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO users (
            company_id, first_name, last_name,
            email, password, role, status
        ) VALUES (
            :company_id, :first_name, :last_name,
            :email, :password, :role, :status
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        
        return $db->lastInsertId();
    }
    
    public static function createTestCompany($db, $data = []) {
        $defaults = [
            'name' => 'Test Company',
            'trade_license_no' => 'TEST-' . uniqid(),
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO companies (
            name, trade_license_no, status
        ) VALUES (
            :name, :trade_license_no, :status
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        
        return $db->lastInsertId();
    }
    
    public static function createTestDocument($db, $data = []) {
        $defaults = [
            'company_id' => 1,
            'type' => 'license',
            'title' => 'Test Document',
            'file_path' => 'test/document.pdf',
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO documents (
            company_id, type, title,
            file_path, status
        ) VALUES (
            :company_id, :type, :title,
            :file_path, :status
        )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        
        return $db->lastInsertId();
    }
}

// Set up test environment
if (!setupTestDatabase()) {
    die("Failed to set up test database\n");
}

setupTestStorage();
mockMailer();

// Clean up when tests are done
register_shutdown_function(function() {
    cleanupTestFiles();
});
