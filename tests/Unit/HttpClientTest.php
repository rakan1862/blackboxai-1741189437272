<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\HttpClientService;

class HttpClientTest extends TestCase
{
    protected $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = new HttpClientService([
            'base_url' => 'https://api.example.com',
            'timeout' => 30,
            'verify_ssl' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'TestClient/1.0'
            ]
        ]);
    }
    
    public function testGetRequest()
    {
        $response = $this->client->get('/users', [
            'page' => 1,
            'limit' => 10
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertIsArray($response->json());
        $this->assertArrayHasKey('data', $response->json());
    }
    
    public function testPostRequest()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];
        
        $response = $this->client->post('/users', $data);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('id', $response->json());
    }
    
    public function testPutRequest()
    {
        $data = [
            'name' => 'Updated User',
            'email' => 'updated@example.com'
        ];
        
        $response = $this->client->put('/users/1', $data);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Updated User', $response->json()['name']);
    }
    
    public function testDeleteRequest()
    {
        $response = $this->client->delete('/users/1');
        
        $this->assertEquals(204, $response->getStatusCode());
    }
    
    public function testRequestWithHeaders()
    {
        $response = $this->client->withHeaders([
            'Authorization' => 'Bearer test-token',
            'X-Custom-Header' => 'test-value'
        ])->get('/protected-resource');
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testRequestWithQueryParameters()
    {
        $response = $this->client->get('/search', [
            'q' => 'test query',
            'filter' => ['status' => 'active'],
            'sort' => 'created_at'
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('results', $response->json());
    }
    
    public function testFileUpload()
    {
        $file = [
            'name' => 'test.pdf',
            'contents' => 'test content',
            'filename' => 'test.pdf'
        ];
        
        $response = $this->client->post('/upload', [
            'file' => $file,
            'type' => 'document'
        ], true);
        
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('file_url', $response->json());
    }
    
    public function testRequestTimeout()
    {
        $this->expectException(\Exception::class);
        
        $client = new HttpClientService([
            'base_url' => 'https://api.example.com',
            'timeout' => 1 // 1 second timeout
        ]);
        
        $client->get('/slow-endpoint');
    }
    
    public function testRetryMechanism()
    {
        $client = new HttpClientService([
            'base_url' => 'https://api.example.com',
            'retry_attempts' => 3,
            'retry_delay' => 1
        ]);
        
        $response = $client->get('/unstable-endpoint');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThanOrEqual(3, $client->getAttemptCount());
    }
    
    public function testRequestEvents()
    {
        $events = [];
        
        $this->client->beforeRequest(function($request) use (&$events) {
            $events[] = 'before';
        });
        
        $this->client->afterRequest(function($response) use (&$events) {
            $events[] = 'after';
        });
        
        $this->client->get('/users');
        
        $this->assertEquals(['before', 'after'], $events);
    }
    
    public function testErrorHandling()
    {
        try {
            $this->client->get('/non-existent');
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            $this->assertEquals(404, $e->getCode());
        }
    }
    
    public function testResponseMacros()
    {
        $this->client->addResponseMacro('isSuccess', function() {
            return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
        });
        
        $response = $this->client->get('/users');
        
        $this->assertTrue($response->isSuccess());
    }
    
    public function testMiddleware()
    {
        $this->client->addMiddleware(function($request, $next) {
            $request->withHeader('X-Test', 'test-value');
            return $next($request);
        });
        
        $response = $this->client->get('/users');
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }
}
