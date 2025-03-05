<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ValidationService;

class ValidationTest extends TestCase
{
    protected $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationService();
    }
    
    public function testRequiredValidation()
    {
        // Test empty value
        $result = $this->validator->validate('', ['required']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('This field is required', $result['error']);
        
        // Test null value
        $result = $this->validator->validate(null, ['required']);
        $this->assertFalse($result['valid']);
        
        // Test valid value
        $result = $this->validator->validate('test', ['required']);
        $this->assertTrue($result['valid']);
    }
    
    public function testEmailValidation()
    {
        // Test invalid emails
        $invalidEmails = [
            'invalid',
            'test@',
            '@domain.com',
            'test@domain',
            'test@.com',
            'test@domain.'
        ];
        
        foreach ($invalidEmails as $email) {
            $result = $this->validator->validate($email, ['email']);
            $this->assertFalse($result['valid']);
            $this->assertEquals('Invalid email format', $result['error']);
        }
        
        // Test valid emails
        $validEmails = [
            'test@domain.com',
            'user.name@domain.com',
            'user+tag@domain.com',
            'user@sub.domain.com'
        ];
        
        foreach ($validEmails as $email) {
            $result = $this->validator->validate($email, ['email']);
            $this->assertTrue($result['valid']);
        }
    }
    
    public function testMinLengthValidation()
    {
        // Test too short
        $result = $this->validator->validate('abc', ['min:5']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Minimum length is 5 characters', $result['error']);
        
        // Test exact length
        $result = $this->validator->validate('abcde', ['min:5']);
        $this->assertTrue($result['valid']);
        
        // Test longer than minimum
        $result = $this->validator->validate('abcdef', ['min:5']);
        $this->assertTrue($result['valid']);
    }
    
    public function testMaxLengthValidation()
    {
        // Test too long
        $result = $this->validator->validate('abcdef', ['max:5']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Maximum length is 5 characters', $result['error']);
        
        // Test exact length
        $result = $this->validator->validate('abcde', ['max:5']);
        $this->assertTrue($result['valid']);
        
        // Test shorter than maximum
        $result = $this->validator->validate('abc', ['max:5']);
        $this->assertTrue($result['valid']);
    }
    
    public function testNumericValidation()
    {
        // Test non-numeric values
        $invalidValues = ['abc', '12.34.56', 'a123', '123a'];
        
        foreach ($invalidValues as $value) {
            $result = $this->validator->validate($value, ['numeric']);
            $this->assertFalse($result['valid']);
            $this->assertEquals('Must be a number', $result['error']);
        }
        
        // Test valid numeric values
        $validValues = ['123', '12.34', '-123', '-12.34', '0'];
        
        foreach ($validValues as $value) {
            $result = $this->validator->validate($value, ['numeric']);
            $this->assertTrue($result['valid']);
        }
    }
    
    public function testAlphaValidation()
    {
        // Test non-alpha values
        $result = $this->validator->validate('abc123', ['alpha']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Must contain only letters', $result['error']);
        
        // Test valid alpha values
        $result = $this->validator->validate('abcDEF', ['alpha']);
        $this->assertTrue($result['valid']);
    }
    
    public function testAlphaNumericValidation()
    {
        // Test non-alphanumeric values
        $result = $this->validator->validate('abc123!@#', ['alphanumeric']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Must contain only letters and numbers', $result['error']);
        
        // Test valid alphanumeric values
        $result = $this->validator->validate('abc123DEF456', ['alphanumeric']);
        $this->assertTrue($result['valid']);
    }
    
    public function testDateValidation()
    {
        // Test invalid dates
        $invalidDates = [
            '2023-13-01',  // Invalid month
            '2023-01-32',  // Invalid day
            '2023/01/01',  // Wrong format
            'invalid-date'
        ];
        
        foreach ($invalidDates as $date) {
            $result = $this->validator->validate($date, ['date']);
            $this->assertFalse($result['valid']);
            $this->assertEquals('Invalid date format (YYYY-MM-DD)', $result['error']);
        }
        
        // Test valid dates
        $validDates = [
            '2023-01-01',
            '2023-12-31',
            '2024-02-29'  // Leap year
        ];
        
        foreach ($validDates as $date) {
            $result = $this->validator->validate($date, ['date']);
            $this->assertTrue($result['valid']);
        }
    }
    
    public function testMultipleRules()
    {
        // Test multiple validation rules
        $result = $this->validator->validate('', ['required', 'email']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('This field is required', $result['error']);
        
        $result = $this->validator->validate('invalid', ['required', 'email']);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid email format', $result['error']);
        
        $result = $this->validator->validate('test@example.com', ['required', 'email']);
        $this->assertTrue($result['valid']);
    }
    
    public function testCustomValidation()
    {
        // Add custom validation rule
        $this->validator->addRule('uae_phone', function($value) {
            return preg_match('/^\+971[0-9]{9}$/', $value);
        }, 'Must be a valid UAE phone number');
        
        // Test invalid phone numbers
        $result = $this->validator->validate('+9721234567', ['uae_phone']);
        $this->assertFalse($result['valid']);
        
        // Test valid phone number
        $result = $this->validator->validate('+971501234567', ['uae_phone']);
        $this->assertTrue($result['valid']);
    }
}
