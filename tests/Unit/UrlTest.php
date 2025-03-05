<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\UrlService;

class UrlTest extends TestCase
{
    protected $url;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->url = new UrlService([
            'base_url' => 'https://example.com',
            'asset_url' => 'https://assets.example.com',
            'app_path' => '/app',
            'secure' => true
        ]);
    }
    
    public function testBasicUrlGeneration()
    {
        $url = $this->url->to('users');
        $this->assertEquals('https://example.com/users', $url);
        
        $url = $this->url->to('users/profile');
        $this->assertEquals('https://example.com/users/profile', $url);
    }
    
    public function testUrlWithQueryParameters()
    {
        $url = $this->url->to('search', [
            'q' => 'test query',
            'page' => 1
        ]);
        
        $this->assertEquals('https://example.com/search?q=test+query&page=1', $url);
    }
    
    public function testUrlWithSpecialCharacters()
    {
        $url = $this->url->to('users', [
            'name' => 'John & Jane',
            'tag' => '#test'
        ]);
        
        $this->assertEquals(
            'https://example.com/users?name=John+%26+Jane&tag=%23test',
            $url
        );
    }
    
    public function testAssetUrl()
    {
        $url = $this->url->asset('css/style.css');
        $this->assertEquals('https://assets.example.com/css/style.css', $url);
        
        $url = $this->url->asset('js/app.js', ['v' => '1.0']);
        $this->assertEquals('https://assets.example.com/js/app.js?v=1.0', $url);
    }
    
    public function testSecureUrl()
    {
        $url = $this->url->secure('checkout');
        $this->assertEquals('https://example.com/checkout', $url);
    }
    
    public function testCurrentUrl()
    {
        $_SERVER['REQUEST_URI'] = '/products';
        $_SERVER['QUERY_STRING'] = 'category=electronics';
        
        $url = $this->url->current();
        $this->assertEquals('https://example.com/products?category=electronics', $url);
    }
    
    public function testPreviousUrl()
    {
        $_SERVER['HTTP_REFERER'] = 'https://example.com/previous-page';
        
        $url = $this->url->previous();
        $this->assertEquals('https://example.com/previous-page', $url);
    }
    
    public function testRouteUrl()
    {
        $url = $this->url->route('user.profile', ['id' => 123]);
        $this->assertEquals('https://example.com/users/123/profile', $url);
        
        $url = $this->url->route('search', ['q' => 'test']);
        $this->assertEquals('https://example.com/search?q=test', $url);
    }
    
    public function testSignedUrl()
    {
        $url = $this->url->signedRoute('unsubscribe', [
            'user' => 123,
            'newsletter' => 'weekly'
        ]);
        
        $this->assertStringContainsString('signature=', $url);
        $this->assertTrue($this->url->hasValidSignature($url));
    }
    
    public function testTemporarySignedUrl()
    {
        $url = $this->url->temporarySignedRoute('download', 3600, [
            'file' => 'document.pdf'
        ]);
        
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertTrue($this->url->hasValidSignature($url));
    }
    
    public function testUrlComponents()
    {
        $components = $this->url->parse('https://user:pass@example.com:8080/path?query=1#fragment');
        
        $this->assertEquals('https', $components['scheme']);
        $this->assertEquals('user', $components['user']);
        $this->assertEquals('pass', $components['pass']);
        $this->assertEquals('example.com', $components['host']);
        $this->assertEquals('8080', $components['port']);
        $this->assertEquals('/path', $components['path']);
        $this->assertEquals('query=1', $components['query']);
        $this->assertEquals('fragment', $components['fragment']);
    }
    
    public function testUrlBuilder()
    {
        $url = $this->url->build()
            ->scheme('https')
            ->host('api.example.com')
            ->path('v1/users')
            ->query(['status' => 'active'])
            ->fragment('top')
            ->toString();
        
        $this->assertEquals(
            'https://api.example.com/v1/users?status=active#top',
            $url
        );
    }
    
    public function testUrlValidation()
    {
        $this->assertTrue($this->url->isValid('https://example.com'));
        $this->assertTrue($this->url->isValid('http://localhost:8080'));
        $this->assertFalse($this->url->isValid('not-a-url'));
    }
    
    public function testUrlFormatting()
    {
        $url = $this->url->format('users/{id}/profile', ['id' => 123]);
        $this->assertEquals('https://example.com/users/123/profile', $url);
        
        $url = $this->url->format('{module}/{action}', [
            'module' => 'users',
            'action' => 'create'
        ]);
        $this->assertEquals('https://example.com/users/create', $url);
    }
    
    protected function tearDown(): void
    {
        $_SERVER = [];
        parent::tearDown();
    }
}
