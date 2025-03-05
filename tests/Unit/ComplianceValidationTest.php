<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controllers\ComplianceController;

class ComplianceValidationTest extends TestCase
{
    protected $complianceController;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->complianceController = new ComplianceController(null);
    }
    
    public function testValidateComplianceRule()
    {
        // Test empty rule
        $result = $this->complianceController->validateRule([]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Compliance rule data is required', $result['error']);
        
        // Test missing required fields
        $result = $this->complianceController->validateRule([
            'title' => 'Test Rule'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Category and priority are required', $result['error']);
        
        // Test invalid category
        $result = $this->complianceController->validateRule([
            'title' => 'Test Rule',
            'category' => 'invalid_category',
            'priority' => 'high'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid compliance category', $result['error']);
        
        // Test invalid priority
        $result = $this->complianceController->validateRule([
            'title' => 'Test Rule',
            'category' => 'trade_license',
            'priority' => 'invalid_priority'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid priority level', $result['error']);
        
        // Test valid rule
        $result = $this->complianceController->validateRule([
            'title' => 'Test Rule',
            'description' => 'Test Description',
            'category' => 'trade_license',
            'priority' => 'high',
            'frequency' => 365
        ]);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateComplianceStatus()
    {
        // Test empty status
        $result = $this->complianceController->validateStatus('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Status is required', $result['error']);
        
        // Test invalid status
        $result = $this->complianceController->validateStatus('invalid_status');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid compliance status', $result['error']);
        
        // Test valid statuses
        $validStatuses = ['compliant', 'non_compliant', 'in_progress', 'not_applicable'];
        foreach ($validStatuses as $status) {
            $result = $this->complianceController->validateStatus($status);
            $this->assertTrue($result['valid']);
            $this->assertNull($result['error']);
        }
    }
    
    public function testValidateCheckDate()
    {
        // Test empty date
        $result = $this->complianceController->validateCheckDate('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Check date is required', $result['error']);
        
        // Test invalid date format
        $result = $this->complianceController->validateCheckDate('invalid-date');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid date format', $result['error']);
        
        // Test future date
        $futureDate = date('Y-m-d', strtotime('+1 day'));
        $result = $this->complianceController->validateCheckDate($futureDate);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Check date cannot be in the future', $result['error']);
        
        // Test valid date
        $validDate = date('Y-m-d');
        $result = $this->complianceController->validateCheckDate($validDate);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateComplianceNotes()
    {
        // Test empty notes
        $result = $this->complianceController->validateNotes('');
        $this->assertTrue($result['valid']); // Empty notes are allowed
        
        // Test notes too long
        $longNotes = str_repeat('a', 1001);
        $result = $this->complianceController->validateNotes($longNotes);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Notes must not exceed 1000 characters', $result['error']);
        
        // Test valid notes
        $result = $this->complianceController->validateNotes('Valid compliance notes');
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateFrequency()
    {
        // Test empty frequency
        $result = $this->complianceController->validateFrequency('');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Frequency is required', $result['error']);
        
        // Test non-numeric frequency
        $result = $this->complianceController->validateFrequency('invalid');
        $this->assertFalse($result['valid']);
        $this->assertEquals('Frequency must be a number', $result['error']);
        
        // Test negative frequency
        $result = $this->complianceController->validateFrequency(-1);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Frequency must be positive', $result['error']);
        
        // Test valid frequency
        $result = $this->complianceController->validateFrequency(30);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateComplianceUpdate()
    {
        // Test empty update data
        $result = $this->complianceController->validateUpdate([]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Update data is required', $result['error']);
        
        // Test missing required fields
        $result = $this->complianceController->validateUpdate([
            'status' => 'compliant'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Check date is required', $result['error']);
        
        // Test valid update
        $result = $this->complianceController->validateUpdate([
            'status' => 'compliant',
            'check_date' => date('Y-m-d'),
            'notes' => 'Compliance check completed'
        ]);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
    
    public function testValidateComplianceReport()
    {
        // Test empty report data
        $result = $this->complianceController->validateReportCriteria([]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Report criteria is required', $result['error']);
        
        // Test invalid date range
        $result = $this->complianceController->validateReportCriteria([
            'from_date' => '2024-01-01',
            'to_date' => '2023-12-31'
        ]);
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid date range', $result['error']);
        
        // Test valid report criteria
        $result = $this->complianceController->validateReportCriteria([
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
            'category' => 'trade_license',
            'status' => 'compliant'
        ]);
        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
    }
}
