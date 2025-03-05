<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Controllers\ComplianceController;
use App\Controllers\DocumentController;
use App\Controllers\NotificationController;

class ComplianceWorkflowTest extends TestCase
{
    protected $db;
    protected $complianceController;
    protected $documentController;
    protected $notificationController;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test database connection
        $this->db = new \PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . "_test",
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        
        // Initialize controllers
        $this->complianceController = new ComplianceController($this->db);
        $this->documentController = new DocumentController($this->db);
        $this->notificationController = new NotificationController($this->db);
        
        // Set up test environment
        $_SESSION['user_id'] = 1;
        $_SESSION['company_id'] = 1;
        
        // Clean up any existing test data
        $this->cleanupTestData();
        
        // Set up initial test data
        $this->setupTestData();
    }
    
    protected function setupTestData()
    {
        // Insert test company
        $this->db->exec("
            INSERT INTO companies (
                id, name, trade_license_no, status
            ) VALUES (
                1, 'Test Company', 'TEST-123', 'active'
            )
        ");
        
        // Insert test user
        $this->db->exec("
            INSERT INTO users (
                id, company_id, first_name, last_name,
                email, password, role, status
            ) VALUES (
                1, 1, 'Test', 'User',
                'test@example.com',
                '" . password_hash('password123', PASSWORD_DEFAULT) . "',
                'manager', 'active'
            )
        ");
        
        // Insert compliance rules
        $this->db->exec("
            INSERT INTO compliance_rules (
                id, title, category, priority, frequency
            ) VALUES 
            (1, 'Trade License Renewal', 'trade_license', 'critical', 365),
            (2, 'VAT Return Filing', 'tax_registration', 'high', 90)
        ");
    }
    
    public function testCompleteComplianceWorkflow()
    {
        // Step 1: Create compliance check
        $complianceData = [
            'rule_id' => 1,
            'status' => 'in_progress',
            'notes' => 'Initial compliance check'
        ];
        
        $response = $this->complianceController->create($complianceData);
        $this->assertTrue($response['success']);
        $complianceId = $response['compliance_id'];
        
        // Step 2: Upload supporting document
        $_FILES['document'] = [
            'name' => 'trade_license.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $this->createTestFile(),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $_POST = [
            'title' => 'Trade License 2024',
            'type' => 'license',
            'compliance_id' => $complianceId,
            'expiry_date' => date('Y-m-d', strtotime('+1 year'))
        ];
        
        $response = $this->documentController->upload();
        $this->assertTrue($response['success']);
        $documentId = $response['document_id'];
        
        // Step 3: Update compliance status
        $updateData = [
            'compliance_id' => $complianceId,
            'status' => 'compliant',
            'notes' => 'Document verified and approved',
            'document_id' => $documentId
        ];
        
        $response = $this->complianceController->update($updateData);
        $this->assertTrue($response['success']);
        
        // Step 4: Verify notification was created
        $notifications = $this->notificationController->getNotifications(1);
        $this->assertNotEmpty($notifications);
        $this->assertEquals('compliance_update', $notifications[0]['type']);
        
        // Step 5: Verify compliance status
        $compliance = $this->complianceController->get($complianceId);
        $this->assertEquals('compliant', $compliance['status']);
        
        // Step 6: Test document association
        $document = $this->documentController->show($documentId);
        $this->assertEquals($complianceId, $document['compliance_id']);
    }
    
    public function testComplianceExpiryWorkflow()
    {
        // Step 1: Create compliance with expiring document
        $complianceData = [
            'rule_id' => 1,
            'status' => 'compliant',
            'notes' => 'Compliance with expiring document'
        ];
        
        $response = $this->complianceController->create($complianceData);
        $complianceId = $response['compliance_id'];
        
        // Step 2: Upload expiring document
        $_FILES['document'] = [
            'name' => 'expiring_license.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $this->createTestFile(),
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $_POST = [
            'title' => 'Expiring License',
            'type' => 'license',
            'compliance_id' => $complianceId,
            'expiry_date' => date('Y-m-d', strtotime('+29 days'))
        ];
        
        $this->documentController->upload();
        
        // Step 3: Run expiry check
        $this->complianceController->checkExpiringItems();
        
        // Step 4: Verify expiry notifications
        $notifications = $this->notificationController->getNotifications(1);
        $this->assertNotEmpty($notifications);
        $this->assertEquals('document_expiring', $notifications[0]['type']);
    }
    
    public function testComplianceReportingWorkflow()
    {
        // Step 1: Create multiple compliance records
        $complianceData = [
            ['rule_id' => 1, 'status' => 'compliant'],
            ['rule_id' => 2, 'status' => 'non_compliant']
        ];
        
        foreach ($complianceData as $data) {
            $this->complianceController->create($data);
        }
        
        // Step 2: Generate compliance report
        $report = $this->complianceController->generateReport([
            'company_id' => 1,
            'from_date' => date('Y-m-d', strtotime('-1 month')),
            'to_date' => date('Y-m-d')
        ]);
        
        // Step 3: Verify report contents
        $this->assertIsArray($report);
        $this->assertEquals(2, $report['total_items']);
        $this->assertEquals(50, $report['compliance_rate']);
    }
    
    protected function createTestFile()
    {
        $filePath = sys_get_temp_dir() . '/test_document.pdf';
        file_put_contents($filePath, 'Test PDF content');
        return $filePath;
    }
    
    protected function cleanupTestData()
    {
        $this->db->exec("DELETE FROM company_compliance WHERE company_id = 1");
        $this->db->exec("DELETE FROM documents WHERE company_id = 1");
        $this->db->exec("DELETE FROM notifications WHERE company_id = 1");
        $this->db->exec("DELETE FROM users WHERE company_id = 1");
        $this->db->exec("DELETE FROM companies WHERE id = 1");
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        $this->cleanupTestData();
        
        // Clean up test files
        $testFile = sys_get_temp_dir() . '/test_document.pdf';
        if (file_exists($testFile)) {
            unlink($testFile);
        }
        
        // Clear session data
        $_SESSION = [];
        
        parent::tearDown();
    }
}
