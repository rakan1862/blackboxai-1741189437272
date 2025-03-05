<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\FileUploadService;

class FileUploadTest extends TestCase
{
    protected $uploadService;
    protected $uploadPath;
    protected $testFiles = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->uploadPath = dirname(dirname(__DIR__)) . '/storage/test/uploads';
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        
        $this->uploadService = new FileUploadService([
            'upload_path' => $this->uploadPath,
            'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'encrypt_files' => true
        ]);
    }
    
    public function testUploadPdfFile()
    {
        $testFile = $this->createTestFile('test.pdf', 'application/pdf');
        
        $result = $this->uploadService->upload($testFile);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['file_path']);
        $this->assertFileExists($this->uploadPath . '/' . $result['file_path']);
    }
    
    public function testUploadImageFile()
    {
        $testFile = $this->createTestFile('test.jpg', 'image/jpeg');
        
        $result = $this->uploadService->upload($testFile);
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['file_path']);
        $this->assertFileExists($this->uploadPath . '/' . $result['file_path']);
    }
    
    public function testRejectInvalidFileType()
    {
        $testFile = $this->createTestFile('test.exe', 'application/x-msdownload');
        
        $result = $this->uploadService->upload($testFile);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid file type', $result['error']);
    }
    
    public function testRejectOversizedFile()
    {
        $testFile = $this->createTestFile('large.pdf', 'application/pdf', 11 * 1024 * 1024);
        
        $result = $this->uploadService->upload($testFile);
        
        $this->assertFalse($result['success']);
        $this->assertEquals('File size exceeds maximum limit', $result['error']);
    }
    
    public function testFileEncryption()
    {
        $testFile = $this->createTestFile('sensitive.pdf', 'application/pdf');
        
        $result = $this->uploadService->upload($testFile, ['encrypt' => true]);
        
        $this->assertTrue($result['success']);
        
        // Verify file is encrypted
        $uploadedContent = file_get_contents($this->uploadPath . '/' . $result['file_path']);
        $this->assertNotEquals(file_get_contents($testFile['tmp_name']), $uploadedContent);
        
        // Test decryption
        $decryptedContent = $this->uploadService->decryptFile($result['file_path']);
        $this->assertEquals(file_get_contents($testFile['tmp_name']), $decryptedContent);
    }
    
    public function testBulkUpload()
    {
        $files = [
            $this->createTestFile('doc1.pdf', 'application/pdf'),
            $this->createTestFile('doc2.pdf', 'application/pdf'),
            $this->createTestFile('doc3.jpg', 'image/jpeg')
        ];
        
        $result = $this->uploadService->bulkUpload($files);
        
        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['uploaded_files']);
        
        foreach ($result['uploaded_files'] as $file) {
            $this->assertFileExists($this->uploadPath . '/' . $file['path']);
        }
    }
    
    public function testUploadWithCustomName()
    {
        $testFile = $this->createTestFile('original.pdf', 'application/pdf');
        $customName = 'custom_name.pdf';
        
        $result = $this->uploadService->upload($testFile, ['name' => $customName]);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString($customName, $result['file_path']);
    }
    
    public function testUploadWithSubdirectory()
    {
        $testFile = $this->createTestFile('test.pdf', 'application/pdf');
        $subdir = 'company_1/documents';
        
        $result = $this->uploadService->upload($testFile, ['directory' => $subdir]);
        
        $this->assertTrue($result['success']);
        $this->assertStringContainsString($subdir, $result['file_path']);
        $this->assertFileExists($this->uploadPath . '/' . $subdir . '/' . basename($result['file_path']));
    }
    
    public function testFileValidation()
    {
        // Test empty file
        $result = $this->uploadService->validate([]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('No file uploaded', $result['error']);
        
        // Test upload error
        $result = $this->uploadService->validate([
            'error' => UPLOAD_ERR_INI_SIZE
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('File upload failed', $result['error']);
        
        // Test valid file
        $testFile = $this->createTestFile('test.pdf', 'application/pdf');
        $result = $this->uploadService->validate($testFile);
        $this->assertTrue($result['valid']);
    }
    
    protected function createTestFile($name, $type, $size = 1024)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, str_repeat('0', $size));
        
        $this->testFiles[] = $tmpFile;
        
        return [
            'name' => $name,
            'type' => $type,
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => $size
        ];
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        foreach ($this->testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Clean up upload directory
        array_map('unlink', glob($this->uploadPath . '/*'));
        rmdir($this->uploadPath);
        
        parent::tearDown();
    }
}
