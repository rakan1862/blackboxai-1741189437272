<?php
namespace Tests;

class TestHelper
{
    protected static $db;
    
    /**
     * Initialize database connection
     */
    public static function initDatabase()
    {
        if (!self::$db) {
            self::$db = new \PDO(
                "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . "_test",
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$db;
    }
    
    /**
     * Create test file
     */
    public static function createTestFile($content = 'Test content', $extension = 'pdf')
    {
        $filePath = sys_get_temp_dir() . '/test_' . uniqid() . '.' . $extension;
        file_put_contents($filePath, $content);
        return $filePath;
    }
    
    /**
     * Create test user
     */
    public static function createTestUser($data = [])
    {
        $db = self::initDatabase();
        
        $defaults = [
            'company_id' => 1,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $stmt = $db->prepare("
            INSERT INTO users (
                company_id, first_name, last_name,
                email, password, role, status
            ) VALUES (
                :company_id, :first_name, :last_name,
                :email, :password, :role, :status
            )
        ");
        
        $stmt->execute($data);
        return $db->lastInsertId();
    }
    
    /**
     * Create test company
     */
    public static function createTestCompany($data = [])
    {
        $db = self::initDatabase();
        
        $defaults = [
            'name' => 'Test Company ' . uniqid(),
            'trade_license_no' => 'TEST-' . uniqid(),
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $stmt = $db->prepare("
            INSERT INTO companies (
                name, trade_license_no, status
            ) VALUES (
                :name, :trade_license_no, :status
            )
        ");
        
        $stmt->execute($data);
        return $db->lastInsertId();
    }
    
    /**
     * Create test document
     */
    public static function createTestDocument($data = [])
    {
        $db = self::initDatabase();
        
        $defaults = [
            'company_id' => 1,
            'type' => 'license',
            'title' => 'Test Document ' . uniqid(),
            'file_path' => 'test/document_' . uniqid() . '.pdf',
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $stmt = $db->prepare("
            INSERT INTO documents (
                company_id, type, title,
                file_path, status
            ) VALUES (
                :company_id, :type, :title,
                :file_path, :status
            )
        ");
        
        $stmt->execute($data);
        return $db->lastInsertId();
    }
    
    /**
     * Mock HTTP request
     */
    public static function mockRequest($method, $data = [], $files = [])
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_POST = $data;
        $_FILES = $files;
        $_GET = [];
        
        if ($method === 'GET') {
            $_GET = $data;
        }
    }
    
    /**
     * Mock authenticated user
     */
    public static function mockAuthenticatedUser($userId = null)
    {
        if (!$userId) {
            $userId = self::createTestUser();
        }
        $_SESSION['user_id'] = $userId;
    }
    
    /**
     * Clean up test data
     */
    public static function cleanup()
    {
        $db = self::initDatabase();
        
        $tables = [
            'notifications',
            'documents',
            'company_compliance',
            'compliance_rules',
            'users',
            'companies'
        ];
        
        foreach ($tables as $table) {
            $db->exec("DELETE FROM $table");
        }
    }
    
    /**
     * Clean up test files
     */
    public static function cleanupFiles()
    {
        $testFiles = glob(sys_get_temp_dir() . '/test_*');
        foreach ($testFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Assert JSON response
     */
    public static function assertJsonResponse($expected, $actual)
    {
        $expected = json_encode($expected);
        $actual = json_encode($actual);
        
        if ($expected !== $actual) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that two JSON strings are equal.\n" .
                "Expected: $expected\n" .
                "Actual: $actual"
            );
        }
    }
    
    /**
     * Assert database has record
     */
    public static function assertDatabaseHas($table, $data)
    {
        $db = self::initDatabase();
        
        $conditions = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        $count = $stmt->fetchColumn();
        
        if ($count === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that table '$table' has record matching criteria."
            );
        }
    }
    
    /**
     * Assert database missing record
     */
    public static function assertDatabaseMissing($table, $data)
    {
        $db = self::initDatabase();
        
        $conditions = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "$key = :$key";
            $values[":$key"] = $value;
        }
        
        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $conditions);
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        $count = $stmt->fetchColumn();
        
        if ($count !== 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that table '$table' does not have record matching criteria."
            );
        }
    }
}
