<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;

class AuthTest extends TestCase
{
    protected $db;
    protected $authController;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock database connection
        $this->db = $this->createMock(\PDO::class);
        
        // Initialize controller with mock database
        $this->authController = new AuthController($this->db);
    }
    
    public function testLoginValidation()
    {
        // Test empty credentials
        $result = $this->authController->validateLoginInput([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('password', $result);
        
        // Test invalid email
        $result = $this->authController->validateLoginInput([
            'email' => 'invalid-email',
            'password' => 'password123'
        ]);
        $this->assertArrayHasKey('email', $result);
        
        // Test valid credentials
        $result = $this->authController->validateLoginInput([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->assertEmpty($result);
    }
    
    public function testPasswordHashing()
    {
        $password = 'test123';
        $hash = $this->authController->hashPassword($password);
        
        // Test hash is not empty
        $this->assertNotEmpty($hash);
        
        // Test hash is different from original password
        $this->assertNotEquals($password, $hash);
        
        // Test password verification
        $this->assertTrue(password_verify($password, $hash));
    }
    
    public function testUserAuthentication()
    {
        // Mock successful login
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
            ->willReturn([
                'id' => 1,
                'email' => 'test@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'status' => 'active'
            ]);
        
        $this->db->method('prepare')
            ->willReturn($stmt);
        
        $result = $this->authController->authenticate('test@example.com', 'password123');
        $this->assertTrue($result);
        
        // Mock failed login
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('fetch')
            ->willReturn(false);
        
        $this->db->method('prepare')
            ->willReturn($stmt);
        
        $result = $this->authController->authenticate('wrong@example.com', 'wrongpass');
        $this->assertFalse($result);
    }
    
    public function testPasswordResetTokenGeneration()
    {
        $token = $this->authController->generateResetToken();
        
        // Test token length
        $this->assertEquals(32, strlen($token));
        
        // Test token is hexadecimal
        $this->assertTrue(ctype_xdigit($token));
    }
    
    public function testPasswordStrengthValidation()
    {
        // Test short password
        $result = $this->authController->validatePassword('short');
        $this->assertFalse($result);
        
        // Test password without numbers
        $result = $this->authController->validatePassword('NoNumbers');
        $this->assertFalse($result);
        
        // Test password without uppercase
        $result = $this->authController->validatePassword('nouppercase123');
        $this->assertFalse($result);
        
        // Test valid password
        $result = $this->authController->validatePassword('ValidPass123');
        $this->assertTrue($result);
    }
    
    public function testUserSessionManagement()
    {
        $userId = 1;
        
        // Test session creation
        $this->authController->createUserSession($userId);
        $this->assertEquals($userId, $_SESSION['user_id']);
        
        // Test session destruction
        $this->authController->destroyUserSession();
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
    
    public function testLoginAttemptTracking()
    {
        $email = 'test@example.com';
        
        // Test failed attempt tracking
        $this->authController->recordFailedLoginAttempt($email);
        $attempts = $this->authController->getLoginAttempts($email);
        $this->assertEquals(1, $attempts);
        
        // Test attempt reset
        $this->authController->resetLoginAttempts($email);
        $attempts = $this->authController->getLoginAttempts($email);
        $this->assertEquals(0, $attempts);
    }
    
    public function testAccountLockout()
    {
        $email = 'test@example.com';
        
        // Simulate multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->authController->recordFailedLoginAttempt($email);
        }
        
        // Test account is locked
        $this->assertTrue($this->authController->isAccountLocked($email));
        
        // Test lockout reset
        $this->authController->resetLoginAttempts($email);
        $this->assertFalse($this->authController->isAccountLocked($email));
    }
    
    protected function tearDown(): void
    {
        // Clean up after each test
        unset($this->db);
        unset($this->authController);
        
        // Clear session data
        $_SESSION = [];
        
        parent::tearDown();
    }
}
