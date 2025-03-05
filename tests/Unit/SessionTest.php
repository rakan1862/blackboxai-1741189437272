<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\SessionService;

class SessionTest extends TestCase
{
    protected $sessionService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize session service with test configuration
        $this->sessionService = new SessionService([
            'lifetime' => 3600,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Start with clean session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    public function testSessionStart()
    {
        $result = $this->sessionService->start();
        
        $this->assertTrue($result);
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertNotEmpty(session_id());
    }
    
    public function testSessionDataManagement()
    {
        $this->sessionService->start();
        
        // Test setting data
        $this->sessionService->set('test_key', 'test_value');
        $this->assertEquals('test_value', $_SESSION['test_key']);
        
        // Test getting data
        $value = $this->sessionService->get('test_key');
        $this->assertEquals('test_value', $value);
        
        // Test default value for non-existent key
        $default = $this->sessionService->get('non_existent', 'default');
        $this->assertEquals('default', $default);
    }
    
    public function testSessionArrayData()
    {
        $this->sessionService->start();
        
        $data = [
            'key1' => 'value1',
            'key2' => [
                'nested' => 'value2'
            ]
        ];
        
        // Test setting array data
        $this->sessionService->set('array_data', $data);
        $this->assertEquals($data, $_SESSION['array_data']);
        
        // Test getting array data
        $retrieved = $this->sessionService->get('array_data');
        $this->assertEquals($data, $retrieved);
    }
    
    public function testSessionDestroy()
    {
        $this->sessionService->start();
        $this->sessionService->set('test_key', 'test_value');
        
        $result = $this->sessionService->destroy();
        
        $this->assertTrue($result);
        $this->assertEmpty(session_id());
        $this->assertEmpty($_SESSION);
    }
    
    public function testSessionRegenerateId()
    {
        $this->sessionService->start();
        $oldId = session_id();
        
        $result = $this->sessionService->regenerateId();
        
        $this->assertTrue($result);
        $this->assertNotEquals($oldId, session_id());
    }
    
    public function testSessionExpiry()
    {
        $this->sessionService->start();
        
        // Set session data
        $this->sessionService->set('test_key', 'test_value');
        
        // Simulate session expiry
        $_SESSION['LAST_ACTIVITY'] = time() - 7200; // 2 hours ago
        
        $isExpired = $this->sessionService->isExpired();
        $this->assertTrue($isExpired);
    }
    
    public function testSessionGarbageCollection()
    {
        $this->sessionService->start();
        
        // Create some expired session data
        $_SESSION['expired_data'] = 'old_value';
        $_SESSION['LAST_ACTIVITY'] = time() - 7200;
        
        $result = $this->sessionService->garbageCollect();
        
        $this->assertTrue($result);
        $this->assertEmpty($_SESSION);
    }
    
    public function testSessionFingerprint()
    {
        $this->sessionService->start();
        
        // Set user agent
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Browser';
        
        // Generate fingerprint
        $this->sessionService->setFingerprint();
        
        // Verify fingerprint
        $isValid = $this->sessionService->validateFingerprint();
        $this->assertTrue($isValid);
        
        // Change user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Different Browser';
        
        // Verify fingerprint fails
        $isValid = $this->sessionService->validateFingerprint();
        $this->assertFalse($isValid);
    }
    
    public function testSessionFlashData()
    {
        $this->sessionService->start();
        
        // Set flash data
        $this->sessionService->setFlash('success', 'Operation completed');
        
        // Verify flash data exists
        $this->assertTrue($this->sessionService->hasFlash('success'));
        
        // Get flash data
        $message = $this->sessionService->getFlash('success');
        $this->assertEquals('Operation completed', $message);
        
        // Verify flash data is removed after reading
        $this->assertFalse($this->sessionService->hasFlash('success'));
    }
    
    public function testSessionSecurity()
    {
        $this->sessionService->start();
        
        // Test CSRF token generation
        $token = $this->sessionService->generateCsrfToken();
        $this->assertNotEmpty($token);
        
        // Test CSRF token validation
        $isValid = $this->sessionService->validateCsrfToken($token);
        $this->assertTrue($isValid);
        
        // Test invalid token
        $isValid = $this->sessionService->validateCsrfToken('invalid_token');
        $this->assertFalse($isValid);
    }
    
    protected function tearDown(): void
    {
        // Clean up session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        parent::tearDown();
    }
}
