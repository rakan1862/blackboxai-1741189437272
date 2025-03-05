<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\EncryptionService;

class EncryptionTest extends TestCase
{
    protected $encryption;
    protected $key;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->key = base64_encode(random_bytes(32)); // 256-bit key
        $this->encryption = new EncryptionService([
            'key' => $this->key,
            'cipher' => 'AES-256-CBC'
        ]);
    }
    
    public function testEncryptDecrypt()
    {
        $plaintext = 'Test message to encrypt';
        
        // Encrypt
        $encrypted = $this->encryption->encrypt($plaintext);
        $this->assertNotEquals($plaintext, $encrypted);
        
        // Decrypt
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }
    
    public function testEncryptArray()
    {
        $data = [
            'key1' => 'value1',
            'key2' => [
                'nested' => 'value2'
            ]
        ];
        
        // Encrypt array
        $encrypted = $this->encryption->encrypt($data);
        $this->assertNotEquals(json_encode($data), $encrypted);
        
        // Decrypt array
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertEquals($data, $decrypted);
    }
    
    public function testEncryptWithDifferentKeys()
    {
        $plaintext = 'Test message';
        
        // Encrypt with current key
        $encrypted = $this->encryption->encrypt($plaintext);
        
        // Create new instance with different key
        $newKey = base64_encode(random_bytes(32));
        $newEncryption = new EncryptionService([
            'key' => $newKey,
            'cipher' => 'AES-256-CBC'
        ]);
        
        // Attempt to decrypt with different key should fail
        $this->expectException(\Exception::class);
        $newEncryption->decrypt($encrypted);
    }
    
    public function testEncryptEmptyValue()
    {
        // Test empty string
        $encrypted = $this->encryption->encrypt('');
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertEquals('', $decrypted);
        
        // Test null
        $encrypted = $this->encryption->encrypt(null);
        $decrypted = $this->encryption->decrypt($encrypted);
        $this->assertNull($decrypted);
    }
    
    public function testEncryptLargeData()
    {
        $largeData = str_repeat('A', 1024 * 1024); // 1MB of data
        
        $encrypted = $this->encryption->encrypt($largeData);
        $decrypted = $this->encryption->decrypt($encrypted);
        
        $this->assertEquals($largeData, $decrypted);
    }
    
    public function testInvalidEncryptedData()
    {
        // Test corrupted data
        $this->expectException(\Exception::class);
        $this->encryption->decrypt('invalid-encrypted-data');
    }
    
    public function testKeyRotation()
    {
        $plaintext = 'Test message';
        
        // Encrypt with old key
        $encrypted = $this->encryption->encrypt($plaintext);
        
        // Generate new key
        $newKey = base64_encode(random_bytes(32));
        
        // Re-encrypt with new key
        $reencrypted = $this->encryption->rotateKey($encrypted, $this->key, $newKey);
        
        // Create new instance with new key
        $newEncryption = new EncryptionService([
            'key' => $newKey,
            'cipher' => 'AES-256-CBC'
        ]);
        
        // Decrypt with new key
        $decrypted = $newEncryption->decrypt($reencrypted);
        $this->assertEquals($plaintext, $decrypted);
    }
    
    public function testEncryptWithCustomCipher()
    {
        // Create instance with different cipher
        $encryption = new EncryptionService([
            'key' => $this->key,
            'cipher' => 'AES-128-CBC'
        ]);
        
        $plaintext = 'Test message';
        
        $encrypted = $encryption->encrypt($plaintext);
        $decrypted = $encryption->decrypt($encrypted);
        
        $this->assertEquals($plaintext, $decrypted);
    }
    
    public function testGenerateKey()
    {
        $key = $this->encryption->generateKey();
        
        // Test key length (256 bits = 32 bytes)
        $this->assertEquals(32, strlen(base64_decode($key)));
    }
    
    public function testHashComparison()
    {
        $plaintext = 'Test message';
        
        // Same plaintext should produce different ciphertexts
        $encrypted1 = $this->encryption->encrypt($plaintext);
        $encrypted2 = $this->encryption->encrypt($plaintext);
        
        $this->assertNotEquals($encrypted1, $encrypted2);
        
        // But should decrypt to same plaintext
        $this->assertEquals(
            $this->encryption->decrypt($encrypted1),
            $this->encryption->decrypt($encrypted2)
        );
    }
    
    protected function tearDown(): void
    {
        unset($this->encryption);
        parent::tearDown();
    }
}
