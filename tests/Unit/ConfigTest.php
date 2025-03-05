<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ConfigService;

class ConfigTest extends TestCase
{
    protected $config;
    protected $testConfigPath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testConfigPath = dirname(dirname(__DIR__)) . '/config/test';
        if (!is_dir($this->testConfigPath)) {
            mkdir($this->testConfigPath, 0755, true);
        }
        
        // Create test config files
        $this->createTestConfigs();
        
        $this->config = new ConfigService([
            'path' => $this->testConfigPath,
            'cache_enabled' => false
        ]);
    }
    
    protected function createTestConfigs()
    {
        // App config
        file_put_contents($this->testConfigPath . '/app.php', '<?php
            return [
                "name" => "Test App",
                "env" => "testing",
                "debug" => true,
                "url" => "http://localhost",
                "timezone" => "UTC"
            ];
        ');
        
        // Database config
        file_put_contents($this->testConfigPath . '/database.php', '<?php
            return [
                "default" => "mysql",
                "connections" => [
                    "mysql" => [
                        "driver" => "mysql",
                        "host" => "localhost",
                        "database" => "testdb",
                        "username" => "root",
                        "password" => ""
                    ]
                ]
            ];
        ');
        
        // Nested config
        file_put_contents($this->testConfigPath . '/services.php', '<?php
            return [
                "mail" => [
                    "driver" => "smtp",
                    "host" => "smtp.test.com",
                    "port" => 587,
                    "encryption" => "tls",
                    "from" => [
                        "address" => "test@example.com",
                        "name" => "Test System"
                    ]
                ]
            ];
        ');
    }
    
    public function testGetConfig()
    {
        // Test simple key
        $value = $this->config->get('app.name');
        $this->assertEquals('Test App', $value);
        
        // Test nested key
        $value = $this->config->get('database.connections.mysql.host');
        $this->assertEquals('localhost', $value);
        
        // Test default value
        $value = $this->config->get('non.existent.key', 'default');
        $this->assertEquals('default', $value);
    }
    
    public function testSetConfig()
    {
        // Set simple value
        $this->config->set('app.name', 'Updated App');
        $this->assertEquals('Updated App', $this->config->get('app.name'));
        
        // Set nested value
        $this->config->set('database.connections.mysql.port', 3306);
        $this->assertEquals(3306, $this->config->get('database.connections.mysql.port'));
        
        // Set array value
        $newConnection = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'database' => 'testdb'
        ];
        $this->config->set('database.connections.pgsql', $newConnection);
        $this->assertEquals($newConnection, $this->config->get('database.connections.pgsql'));
    }
    
    public function testHasConfig()
    {
        $this->assertTrue($this->config->has('app.name'));
        $this->assertTrue($this->config->has('database.connections.mysql'));
        $this->assertFalse($this->config->has('non.existent.key'));
    }
    
    public function testGetAll()
    {
        // Get all configs from a file
        $appConfig = $this->config->all('app');
        $this->assertIsArray($appConfig);
        $this->assertArrayHasKey('name', $appConfig);
        $this->assertArrayHasKey('env', $appConfig);
    }
    
    public function testArrayAccess()
    {
        // Test array access getter
        $this->assertEquals('Test App', $this->config['app.name']);
        
        // Test array access setter
        $this->config['app.name'] = 'Updated App';
        $this->assertEquals('Updated App', $this->config['app.name']);
        
        // Test array access exists
        $this->assertTrue(isset($this->config['app.name']));
        
        // Test array access unset
        unset($this->config['app.name']);
        $this->assertFalse(isset($this->config['app.name']));
    }
    
    public function testEnvironmentSpecificConfig()
    {
        // Create environment-specific config
        file_put_contents($this->testConfigPath . '/testing/app.php', '<?php
            return [
                "debug" => true,
                "url" => "http://testing.localhost"
            ];
        ');
        
        $config = new ConfigService([
            'path' => $this->testConfigPath,
            'environment' => 'testing'
        ]);
        
        // Environment config should override default
        $this->assertEquals('http://testing.localhost', $config->get('app.url'));
    }
    
    public function testConfigCache()
    {
        // Enable config caching
        $config = new ConfigService([
            'path' => $this->testConfigPath,
            'cache_enabled' => true
        ]);
        
        // Initial load should cache
        $value1 = $config->get('app.name');
        
        // Modify file directly (shouldn't affect cached value)
        file_put_contents($this->testConfigPath . '/app.php', '<?php
            return ["name" => "Modified App"];
        ');
        
        // Should get cached value
        $value2 = $config->get('app.name');
        
        $this->assertEquals($value1, $value2);
        
        // Clear cache and reload
        $config->clearCache();
        $value3 = $config->get('app.name');
        
        $this->assertEquals('Modified App', $value3);
    }
    
    public function testConfigValidation()
    {
        $this->expectException(\Exception::class);
        
        // Create invalid config file
        file_put_contents($this->testConfigPath . '/invalid.php', '<?php
            // Invalid PHP that will cause error
            return [
                "key" => 
            ];
        ');
        
        // Attempting to load invalid config should throw exception
        $this->config->get('invalid.key');
    }
    
    protected function tearDown(): void
    {
        // Clean up test config files
        array_map('unlink', glob($this->testConfigPath . '/*'));
        rmdir($this->testConfigPath);
        
        parent::tearDown();
    }
}
