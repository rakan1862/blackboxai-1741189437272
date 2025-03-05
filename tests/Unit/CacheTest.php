<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CacheService;

class CacheTest extends TestCase
{
    protected $cacheService;
    protected $cachePath;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cachePath = dirname(dirname(__DIR__)) . '/storage/test/cache';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        
        $this->cacheService = new CacheService([
            'driver' => 'file',
            'path' => $this->cachePath,
            'prefix' => 'test_',
            'default_ttl' => 3600
        ]);
    }
    
    public function testSetAndGetCache()
    {
        $key = 'test_key';
        $value = 'test_value';
        
        // Set cache
        $result = $this->cacheService->set($key, $value);
        $this->assertTrue($result);
        
        // Get cache
        $cached = $this->cacheService->get($key);
        $this->assertEquals($value, $cached);
    }
    
    public function testCacheExpiration()
    {
        $key = 'expiring_key';
        $value = 'expiring_value';
        
        // Set cache with 1 second TTL
        $this->cacheService->set($key, $value, 1);
        
        // Verify value exists
        $this->assertEquals($value, $this->cacheService->get($key));
        
        // Wait for expiration
        sleep(2);
        
        // Verify value is gone
        $this->assertNull($this->cacheService->get($key));
    }
    
    public function testCacheArrayData()
    {
        $key = 'array_key';
        $value = [
            'key1' => 'value1',
            'key2' => [
                'nested' => 'value2'
            ]
        ];
        
        // Set array in cache
        $this->cacheService->set($key, $value);
        
        // Get array from cache
        $cached = $this->cacheService->get($key);
        
        $this->assertEquals($value, $cached);
        $this->assertEquals('value2', $cached['key2']['nested']);
    }
    
    public function testCacheRemoval()
    {
        $key = 'remove_key';
        $value = 'remove_value';
        
        // Set cache
        $this->cacheService->set($key, $value);
        
        // Remove cache
        $result = $this->cacheService->delete($key);
        $this->assertTrue($result);
        
        // Verify removal
        $this->assertNull($this->cacheService->get($key));
    }
    
    public function testCacheExists()
    {
        $key = 'exists_key';
        
        // Check non-existent key
        $this->assertFalse($this->cacheService->has($key));
        
        // Set cache
        $this->cacheService->set($key, 'exists_value');
        
        // Check existing key
        $this->assertTrue($this->cacheService->has($key));
    }
    
    public function testCacheClear()
    {
        // Set multiple cache items
        $this->cacheService->set('key1', 'value1');
        $this->cacheService->set('key2', 'value2');
        
        // Clear all cache
        $result = $this->cacheService->clear();
        $this->assertTrue($result);
        
        // Verify all cache is cleared
        $this->assertNull($this->cacheService->get('key1'));
        $this->assertNull($this->cacheService->get('key2'));
    }
    
    public function testCacheIncrement()
    {
        $key = 'counter';
        
        // Set initial value
        $this->cacheService->set($key, 1);
        
        // Increment
        $result = $this->cacheService->increment($key);
        $this->assertEquals(2, $result);
        
        // Verify new value
        $this->assertEquals(2, $this->cacheService->get($key));
    }
    
    public function testCacheDecrement()
    {
        $key = 'counter';
        
        // Set initial value
        $this->cacheService->set($key, 10);
        
        // Decrement
        $result = $this->cacheService->decrement($key);
        $this->assertEquals(9, $result);
        
        // Verify new value
        $this->assertEquals(9, $this->cacheService->get($key));
    }
    
    public function testCacheMultiple()
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3'
        ];
        
        // Set multiple items
        $result = $this->cacheService->setMultiple($items);
        $this->assertTrue($result);
        
        // Get multiple items
        $cached = $this->cacheService->getMultiple(array_keys($items));
        $this->assertEquals($items, $cached);
    }
    
    public function testCachePrefix()
    {
        $key = 'prefix_test';
        $value = 'prefix_value';
        
        $this->cacheService->set($key, $value);
        
        // Verify file exists with prefix
        $this->assertFileExists($this->cachePath . '/test_' . $key);
    }
    
    public function testCacheInvalidation()
    {
        $key = 'invalidate_key';
        $value = 'invalidate_value';
        
        // Set cache with tags
        $this->cacheService->set($key, $value, null, ['tag1', 'tag2']);
        
        // Invalidate by tag
        $result = $this->cacheService->invalidateTag('tag1');
        $this->assertTrue($result);
        
        // Verify cache is invalidated
        $this->assertNull($this->cacheService->get($key));
    }
    
    protected function tearDown(): void
    {
        // Clean up cache directory
        array_map('unlink', glob($this->cachePath . '/*'));
        rmdir($this->cachePath);
        
        parent::tearDown();
    }
}
