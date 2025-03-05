<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\BackupController;

class BackupTest extends TestCase
{
    protected $backupController;
    protected $backupPath;
    protected $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->backupPath = dirname(dirname(__DIR__)) . '/storage/test/backups';
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
        
        $this->db = new \PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . "_test",
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        
        $this->backupController = new BackupController($this->db);
        $this->backupController->setBackupPath($this->backupPath);
    }
    
    public function testCreateBackup()
    {
        $result = $this->backupController->createBackup();
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['backup_file']);
        $this->assertFileExists($result['backup_file']);
        
        // Verify backup file contains required components
        $backupContents = file_get_contents($result['backup_file']);
        $this->assertStringContainsString('CREATE TABLE', $backupContents);
        $this->assertStringContainsString('INSERT INTO', $backupContents);
    }
    
    public function testCreateBackupWithData()
    {
        // Insert test data
        $this->db->exec("
            INSERT INTO companies (name, trade_license_no, status)
            VALUES ('Test Company', 'TEST-123', 'active')
        ");
        
        $result = $this->backupController->createBackup();
        
        $this->assertTrue($result['success']);
        
        // Verify backup contains test data
        $backupContents = file_get_contents($result['backup_file']);
        $this->assertStringContainsString('Test Company', $backupContents);
        $this->assertStringContainsString('TEST-123', $backupContents);
    }
    
    public function testRestoreBackup()
    {
        // Create backup with test data
        $this->db->exec("
            INSERT INTO companies (name, trade_license_no, status)
            VALUES ('Original Company', 'TEST-123', 'active')
        ");
        
        $backup = $this->backupController->createBackup();
        
        // Clear database
        $this->db->exec("DELETE FROM companies");
        
        // Restore backup
        $result = $this->backupController->restoreBackup($backup['backup_file']);
        
        $this->assertTrue($result['success']);
        
        // Verify data was restored
        $stmt = $this->db->query("SELECT * FROM companies");
        $company = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals('Original Company', $company['name']);
        $this->assertEquals('TEST-123', $company['trade_license_no']);
    }
    
    public function testListBackups()
    {
        // Create multiple backups
        $this->backupController->createBackup();
        sleep(1); // Ensure different timestamps
        $this->backupController->createBackup();
        
        $backups = $this->backupController->listBackups();
        
        $this->assertIsArray($backups);
        $this->assertCount(2, $backups);
        $this->assertArrayHasKey('filename', $backups[0]);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('created_at', $backups[0]);
    }
    
    public function testDeleteBackup()
    {
        $backup = $this->backupController->createBackup();
        $backupFile = $backup['backup_file'];
        
        $result = $this->backupController->deleteBackup($backupFile);
        
        $this->assertTrue($result['success']);
        $this->assertFileDoesNotExist($backupFile);
    }
    
    public function testCleanOldBackups()
    {
        // Create multiple backups with different dates
        $this->createBackupWithDate('-31 days');
        $this->createBackupWithDate('-15 days');
        $this->createBackupWithDate('now');
        
        $result = $this->backupController->cleanOldBackups(30); // 30 days retention
        
        $this->assertTrue($result['success']);
        
        $backups = $this->backupController->listBackups();
        $this->assertCount(2, $backups); // Only recent backups should remain
    }
    
    public function testBackupCompression()
    {
        $result = $this->backupController->createBackup(['compress' => true]);
        
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.gz', $result['backup_file']);
        
        // Verify file is actually compressed
        $this->assertLessThan(
            filesize(str_replace('.gz', '', $result['backup_file'])),
            filesize($result['backup_file'])
        );
    }
    
    public function testBackupEncryption()
    {
        $password = 'test_password';
        $result = $this->backupController->createBackup([
            'encrypt' => true,
            'password' => $password
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertStringEndsWith('.enc', $result['backup_file']);
        
        // Verify file is encrypted
        $contents = file_get_contents($result['backup_file']);
        $this->assertStringNotContainsString('CREATE TABLE', $contents);
        
        // Test decryption
        $decrypted = $this->backupController->decryptBackup(
            $result['backup_file'],
            $password
        );
        
        $this->assertStringContainsString('CREATE TABLE', $decrypted);
    }
    
    protected function createBackupWithDate($date)
    {
        $backup = $this->backupController->createBackup();
        $timestamp = strtotime($date);
        touch($backup['backup_file'], $timestamp);
    }
    
    protected function tearDown(): void
    {
        // Clean up test backups
        array_map('unlink', glob($this->backupPath . '/*'));
        rmdir($this->backupPath);
        
        // Clean up test data
        $this->db->exec("DELETE FROM companies");
        
        parent::tearDown();
    }
}
