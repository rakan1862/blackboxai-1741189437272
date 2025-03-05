<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\LoggerService;

class LoggerTest extends TestCase
{
    protected $logger;
    protected $logPath;
    protected $testLogFile;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logPath = dirname(dirname(__DIR__)) . '/logs/test';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        $this->testLogFile = $this->logPath . '/test.log';
        
        $this->logger = new LoggerService([
            'path' => $this->logPath,
            'default_file' => 'test.log',
            'date_format' => 'Y-m-d H:i:s',
            'max_files' => 5,
            'max_size' => 5 * 1024 * 1024 // 5MB
        ]);
    }
    
    public function testInfoLogging()
    {
        $message = 'Test info message';
        $this->logger->info($message);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString('[INFO]', $logContent);
        $this->assertStringContainsString($message, $logContent);
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $logContent);
    }
    
    public function testErrorLogging()
    {
        $message = 'Test error message';
        $this->logger->error($message);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString('[ERROR]', $logContent);
        $this->assertStringContainsString($message, $logContent);
    }
    
    public function testWarningLogging()
    {
        $message = 'Test warning message';
        $this->logger->warning($message);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString('[WARNING]', $logContent);
        $this->assertStringContainsString($message, $logContent);
    }
    
    public function testDebugLogging()
    {
        $message = 'Test debug message';
        $this->logger->debug($message);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString('[DEBUG]', $logContent);
        $this->assertStringContainsString($message, $logContent);
    }
    
    public function testContextLogging()
    {
        $message = 'Test message with context';
        $context = ['user_id' => 123, 'action' => 'login'];
        
        $this->logger->info($message, $context);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString($message, $logContent);
        $this->assertStringContainsString(json_encode($context), $logContent);
    }
    
    public function testExceptionLogging()
    {
        $exception = new \Exception('Test exception');
        $this->logger->exception($exception);
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringContainsString('[EXCEPTION]', $logContent);
        $this->assertStringContainsString('Test exception', $logContent);
        $this->assertStringContainsString($exception->getFile(), $logContent);
        $this->assertStringContainsString((string)$exception->getLine(), $logContent);
    }
    
    public function testLogRotation()
    {
        // Create a large log entry to trigger rotation
        $largeMessage = str_repeat('a', 1024 * 1024); // 1MB
        
        for ($i = 0; $i < 6; $i++) {
            $this->logger->info($largeMessage);
        }
        
        // Check if rotation occurred
        $this->assertFileExists($this->testLogFile . '.1');
        
        // Check if old logs were cleaned up
        $this->assertFileDoesNotExist($this->testLogFile . '.6');
    }
    
    public function testCustomLogFile()
    {
        $customFile = 'custom.log';
        $message = 'Test message in custom file';
        
        $this->logger->to($customFile)->info($message);
        
        $logContent = file_get_contents($this->logPath . '/' . $customFile);
        
        $this->assertStringContainsString($message, $logContent);
    }
    
    public function testLogLevels()
    {
        $this->logger->setMinLevel('error');
        
        // This shouldn't be logged
        $this->logger->info('Test info message');
        
        // This should be logged
        $this->logger->error('Test error message');
        
        $logContent = file_get_contents($this->testLogFile);
        
        $this->assertStringNotContainsString('Test info message', $logContent);
        $this->assertStringContainsString('Test error message', $logContent);
    }
    
    public function testLogFormat()
    {
        $message = 'Test format message';
        $this->logger->info($message);
        
        $logContent = file_get_contents($this->testLogFile);
        $logLine = trim(explode("\n", $logContent)[0]);
        
        // Check log format: [DATE] [LEVEL] MESSAGE {CONTEXT}
        $this->assertMatchesRegularExpression(
            '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \[INFO\] Test format message$/',
            $logLine
        );
    }
    
    public function testBatchLogging()
    {
        $messages = [
            ['level' => 'info', 'message' => 'Info message'],
            ['level' => 'error', 'message' => 'Error message'],
            ['level' => 'warning', 'message' => 'Warning message']
        ];
        
        $this->logger->batch($messages);
        
        $logContent = file_get_contents($this->testLogFile);
        
        foreach ($messages as $message) {
            $this->assertStringContainsString($message['message'], $logContent);
            $this->assertStringContainsString('[' . strtoupper($message['level']) . ']', $logContent);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up log files
        array_map('unlink', glob($this->logPath . '/*'));
        rmdir($this->logPath);
        
        parent::tearDown();
    }
}
