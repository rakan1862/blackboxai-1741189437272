<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\StringService;

class StringTest extends TestCase
{
    protected $str;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->str = new StringService();
    }
    
    public function testLength()
    {
        $this->assertEquals(5, $this->str->length('hello'));
        $this->assertEquals(11, $this->str->length('hello world'));
        $this->assertEquals(4, $this->str->length('مرحبا')); // UTF-8 string
    }
    
    public function testUpperLowerCase()
    {
        $this->assertEquals('HELLO', $this->str->upper('hello'));
        $this->assertEquals('hello', $this->str->lower('HELLO'));
        $this->assertEquals('Hello World', $this->str->title('hello world'));
        $this->assertEquals('Hello World', $this->str->ucfirst('hello world'));
    }
    
    public function testTrim()
    {
        $this->assertEquals('hello', $this->str->trim(' hello '));
        $this->assertEquals('hello', $this->str->ltrim(' hello'));
        $this->assertEquals('hello', $this->str->rtrim('hello '));
        $this->assertEquals('hello', $this->str->trim('***hello***', '*'));
    }
    
    public function testSubstring()
    {
        $this->assertEquals('hel', $this->str->substr('hello', 0, 3));
        $this->assertEquals('lo', $this->str->substr('hello', -2));
        $this->assertEquals('el', $this->str->substr('hello', 1, 2));
    }
    
    public function testReplace()
    {
        $this->assertEquals('hi world', $this->str->replace('hello', 'hi', 'hello world'));
        $this->assertEquals('hi earth', $this->str->replace(['hello', 'world'], ['hi', 'earth'], 'hello world'));
        $this->assertEquals('h*ll*', $this->str->replace('e', '*', 'hello', 1));
    }
    
    public function testSplit()
    {
        $this->assertEquals(['hello', 'world'], $this->str->split('hello world'));
        $this->assertEquals(['hello', 'world'], $this->str->split('hello,world', ','));
        $this->assertEquals(['h', 'e', 'l', 'l', 'o'], $this->str->split('hello', ''));
    }
    
    public function testSlug()
    {
        $this->assertEquals('hello-world', $this->str->slug('Hello World'));
        $this->assertEquals('hello-world', $this->str->slug('Hello & World'));
        $this->assertEquals('hello-world', $this->str->slug('Hello_World'));
        $this->assertEquals('hello-world', $this->str->slug('Hello@World'));
    }
    
    public function testCamelCase()
    {
        $this->assertEquals('helloWorld', $this->str->camel('hello_world'));
        $this->assertEquals('helloWorld', $this->str->camel('hello-world'));
        $this->assertEquals('helloWorldTest', $this->str->camel('hello world test'));
    }
    
    public function testSnakeCase()
    {
        $this->assertEquals('hello_world', $this->str->snake('helloWorld'));
        $this->assertEquals('hello_world_test', $this->str->snake('HelloWorldTest'));
        $this->assertEquals('hello_world', $this->str->snake('Hello World'));
    }
    
    public function testKebabCase()
    {
        $this->assertEquals('hello-world', $this->str->kebab('helloWorld'));
        $this->assertEquals('hello-world-test', $this->str->kebab('HelloWorldTest'));
        $this->assertEquals('hello-world', $this->str->kebab('Hello World'));
    }
    
    public function testContains()
    {
        $this->assertTrue($this->str->contains('hello world', 'hello'));
        $this->assertTrue($this->str->contains('hello world', ['hello', 'world']));
        $this->assertFalse($this->str->contains('hello world', 'bye'));
    }
    
    public function testStartsEndsWith()
    {
        $this->assertTrue($this->str->startsWith('hello world', 'hello'));
        $this->assertTrue($this->str->endsWith('hello world', 'world'));
        $this->assertFalse($this->str->startsWith('hello world', 'world'));
        $this->assertFalse($this->str->endsWith('hello world', 'hello'));
    }
    
    public function testLimit()
    {
        $this->assertEquals('hello...', $this->str->limit('hello world', 5));
        $this->assertEquals('hello>>>>', $this->str->limit('hello world', 5, '>>>>'));
    }
    
    public function testRandom()
    {
        $random = $this->str->random(16);
        $this->assertEquals(16, strlen($random));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $random);
    }
    
    public function testMask()
    {
        $this->assertEquals('****lo', $this->str->mask('hello', '*', 0, 4));
        $this->assertEquals('he***', $this->str->mask('hello', '*', 2));
        $this->assertEquals('******3456', $this->str->mask('1234563456', '*', 0, 6));
    }
    
    public function testPadding()
    {
        $this->assertEquals('00123', $this->str->padLeft('123', 5, '0'));
        $this->assertEquals('123  ', $this->str->padRight('123', 5));
        $this->assertEquals('__123__', $this->str->pad('123', 7, '_'));
    }
    
    public function testWordCount()
    {
        $this->assertEquals(2, $this->str->wordCount('hello world'));
        $this->assertEquals(3, $this->str->wordCount('hello beautiful world'));
    }
    
    public function testWordWrap()
    {
        $text = 'The quick brown fox jumps over the lazy dog';
        $wrapped = $this->str->wordWrap($text, 20);
        $this->assertStringContainsString("\n", $wrapped);
    }
    
    public function testRemoveHtml()
    {
        $html = '<p>Hello <b>World</b></p>';
        $this->assertEquals('Hello World', $this->str->stripHtml($html));
    }
    
    protected function tearDown(): void
    {
        $this->str = null;
        parent::tearDown();
    }
}
