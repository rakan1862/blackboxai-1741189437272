<?php
namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Controllers\DocumentController;

class DocumentManagementTest extends TestCase
{
    protected $db;
    protected $documentController;
    protected $testFilePath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test database connection
        $this->db = new \PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . "_test",
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        
        // Initialize controller
        $this->documentController = new DocumentController($this->db);
        
        // Create test file
        $this->testFilePath = sys_get_temp_dir() . '/test_document.pdf';
        file_put_contents($this->testFilePath, 'Test PDF content');
        
        // Set up test environment
        $_SESSION['user_id'] = 1;
        $_SESSION['company_id'] = 1;
    }
    
    public function testDocumentUpload()
    {
        $_FILES['document'] = [
            'name' => 'test_document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $this->testFilePath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($this->testFilePath)
        ];
        
        $_POST = [
            'title' => 'Test Document',
            'type' => 'license',
            'expiry_date' => date('Y-m-d', strtotime('+1 year'))
        ];
        
        $response = $this->documentController->upload();
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertNotEmpty($response['document_id']);
        
        // Verify document was saved in database
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$response['document_id']]);
        $document = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($document);
        $this->assertEquals($_POST['title'], $document['title']);
        $this->assertEquals($_POST['type'], $document['type']);
    }
    
    public function testDocumentRetrieval()
    {
        // Insert test document
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title, file_path,
                expiry_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            1,
            'license',
            'Test Document',
            'documents/1/test.pdf',
            date('Y-m-d', strtotime('+1 year')),
            'active'
        ]);
        
        $documentId = $this->db->lastInsertId();
        
        // Test document listing
        $response = $this->documentController->index();
        $this->assertIsArray($response);
        $this->assertNotEmpty($response['documents']);
        
        // Test single document retrieval
        $response = $this->documentController->show($documentId);
        $this->assertIsArray($response);
        $this->assertEquals('Test Document', $response['title']);
    }
    
    public function testDocumentUpdate()
    {
        // Insert test document
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title, file_path,
                expiry_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            1,
            'license',
            'Original Title',
            'documents/1/test.pdf',
            date('Y-m-d', strtotime('+1 year')),
            'active'
        ]);
        
        $documentId = $this->db->lastInsertId();
        
        // Update document
        $_POST = [
            'document_id' => $documentId,
            'title' => 'Updated Title',
            'type' => 'permit',
            'expiry_date' => date('Y-m-d', strtotime('+2 years'))
        ];
        
        $response = $this->documentController->update();
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Verify update in database
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals('Updated Title', $document['title']);
        $this->assertEquals('permit', $document['type']);
    }
    
    public function testDocumentDeletion()
    {
        // Insert test document
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title, file_path,
                expiry_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            1,
            'license',
            'Test Document',
            'documents/1/test.pdf',
            date('Y-m-d', strtotime('+1 year')),
            'active'
        ]);
        
        $documentId = $this->db->lastInsertId();
        
        // Delete document
        $_POST['document_id'] = $documentId;
        $response = $this->documentController->delete();
        
        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        
        // Verify deletion
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM documents WHERE id = ?");
        $stmt->execute([$documentId]);
        $count = $stmt->fetchColumn();
        
        $this->assertEquals(0, $count);
    }
    
    public function testExpiryNotifications()
    {
        // Insert expiring document
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title, file_path,
                expiry_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            1,
            'license',
            'Expiring Document',
            'documents/1/test.pdf',
            date('Y-m-d', strtotime('+29 days')), // Within 30-day notification window
            'active'
        ]);
        
        // Check for notifications
        $response = $this->documentController->checkExpiringDocuments();
        $this->assertIsArray($response);
        $this->assertNotEmpty($response['expiring_documents']);
    }
    
    public function testDocumentSearch()
    {
        // Insert test documents
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title, file_path,
                expiry_date, status, created_at
            ) VALUES 
            (?, ?, ?, ?, ?, ?, NOW()),
            (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            1, 'license', 'Trade License', 'documents/1/trade.pdf',
            date('Y-m-d', strtotime('+1 year')), 'active',
            1, 'permit', 'Building Permit', 'documents/1/permit.pdf',
            date('Y-m-d', strtotime('+1 year')), 'active'
        ]);
        
        // Test search functionality
        $_GET['search'] = 'Trade';
        $response = $this->documentController->search();
        
        $this->assertIsArray($response);
        $this->assertCount(1, $response['documents']);
        $this->assertEquals('Trade License', $response['documents'][0]['title']);
    }
    
    protected function tearDown(): void
    {
        // Clean up test file
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        
        // Clean up test data
        $this->db->exec("DELETE FROM documents WHERE company_id = 1");
        
        // Clear session data
        $_SESSION = [];
        
        parent::tearDown();
    }
}
