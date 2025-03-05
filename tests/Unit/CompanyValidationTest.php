<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\CompanyController;

class CompanyValidationTest extends TestCase
{
    protected $companyController;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->companyController = new CompanyController(null);
    }
    
    public function testValidateCompanyRegistration()
    {
        // Test empty data
        $result = $this->companyController->validateRegistration([]);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
        
        // Test missing required fields
        $result = $this->companyController->validateRegistration([
            'name' => 'Test Company'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('required', $result['error']);
        
        // Test invalid trade license format
        $result = $this->companyController->validateRegistration([
            'name' => 'Test Company',
            'trade_license_no' => 'invalid-format',
            'email' => 'test@company.com'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('license', $result['error']);
        
        // Test valid registration
        $result = $this->companyController->validateRegistration([
            'name' => 'Test Company LLC',
            'trade_license_no' => 'TL-123456',
            'tax_registration_no' => 'TRN-123456',
            'address' => 'Test Address, Dubai, UAE',
            'phone' => '+971501234567',
            'email' => 'test@company.com',
            'industry_type' => 'trading',
            'company_type' => 'llc'
        ]);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateTradeLicenseNumber()
    {
        // Test empty license number
        $result = $this->companyController->validateTradeLicense('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Trade license number is required', $result['error']);
        
        // Test invalid format
        $result = $this->companyController->validateTradeLicense('123');
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('format', $result['error']);
        
        // Test valid formats
        $validFormats = ['TL-123456', 'LLC-789012', 'FZ-345678'];
        foreach ($validFormats as $format) {
            $result = $this->companyController->validateTradeLicense($format);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
    
    public function testValidatePhoneNumber()
    {
        // Test empty phone
        $result = $this->companyController->validatePhone('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Phone number is required', $result['error']);
        
        // Test invalid formats
        $invalidPhones = [
            '12345',
            '+971',
            '501234567',
            '+97150123456x'
        ];
        
        foreach ($invalidPhones as $phone) {
            $result = $this->companyController->validatePhone($phone);
            $this->assertFalse($result['valid']);
            $this->assertStringContainsString('format', $result['error']);
        }
        
        // Test valid UAE phone numbers
        $validPhones = [
            '+971501234567',
            '+971559876543',
            '+971541234567'
        ];
        
        foreach ($validPhones as $phone) {
            $result = $this->companyController->validatePhone($phone);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
    
    public function testValidateCompanyType()
    {
        // Test empty type
        $result = $this->companyController->validateCompanyType('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Company type is required', $result['error']);
        
        // Test invalid type
        $result = $this->companyController->validateCompanyType('invalid_type');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid company type', $result['error']);
        
        // Test valid types
        $validTypes = ['llc', 'sole_proprietorship', 'partnership', 'free_zone', 'branch'];
        foreach ($validTypes as $type) {
            $result = $this->companyController->validateCompanyType($type);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
    
    public function testValidateIndustryType()
    {
        // Test empty type
        $result = $this->companyController->validateIndustryType('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Industry type is required', $result['error']);
        
        // Test invalid type
        $result = $this->companyController->validateIndustryType('invalid_type');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid industry type', $result['error']);
        
        // Test valid types
        $validTypes = ['trading', 'manufacturing', 'services', 'construction', 'technology'];
        foreach ($validTypes as $type) {
            $result = $this->companyController->validateIndustryType($type);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
    
    public function testValidateCompanyUpdate()
    {
        // Test empty update data
        $result = $this->companyController->validateUpdate([]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Update data is required', $result['error']);
        
        // Test invalid email update
        $result = $this->companyController->validateUpdate([
            'email' => 'invalid-email'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('email', $result['error']);
        
        // Test valid update
        $result = $this->companyController->validateUpdate([
            'name' => 'Updated Company LLC',
            'address' => 'New Address, Dubai, UAE',
            'phone' => '+97150987654',
            'email' => 'updated@company.com'
        ]);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateCompanyStatus()
    {
        // Test empty status
        $result = $this->companyController->validateStatus('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Status is required', $result['error']);
        
        // Test invalid status
        $result = $this->companyController->validateStatus('invalid_status');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid company status', $result['error']);
        
        // Test valid statuses
        $validStatuses = ['active', 'inactive', 'suspended'];
        foreach ($validStatuses as $status) {
            $result = $this->companyController->validateStatus($status);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
}
