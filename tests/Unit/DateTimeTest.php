<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DateTimeService;

class DateTimeTest extends TestCase
{
    protected $datetime;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->datetime = new DateTimeService([
            'timezone' => 'Asia/Dubai',
            'locale' => 'en',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s'
        ]);
    }
    
    public function testNow()
    {
        $now = $this->datetime->now();
        
        $this->assertInstanceOf(\DateTime::class, $now);
        $this->assertEquals('Asia/Dubai', $now->getTimezone()->getName());
    }
    
    public function testFormatDate()
    {
        $date = '2024-01-15';
        
        // Default format
        $formatted = $this->datetime->format($date);
        $this->assertEquals('2024-01-15', $formatted);
        
        // Custom format
        $formatted = $this->datetime->format($date, 'd/m/Y');
        $this->assertEquals('15/01/2024', $formatted);
        
        // With time
        $formatted = $this->datetime->format($date, 'd/m/Y H:i');
        $this->assertEquals('15/01/2024 00:00', $formatted);
    }
    
    public function testParseDate()
    {
        // Parse standard format
        $date = $this->datetime->parse('2024-01-15');
        $this->assertEquals('2024-01-15', $date->format('Y-m-d'));
        
        // Parse custom format
        $date = $this->datetime->parse('15/01/2024', 'd/m/Y');
        $this->assertEquals('2024-01-15', $date->format('Y-m-d'));
    }
    
    public function testModifyDate()
    {
        $date = $this->datetime->parse('2024-01-15');
        
        // Add days
        $modified = $this->datetime->modify($date, '+5 days');
        $this->assertEquals('2024-01-20', $modified->format('Y-m-d'));
        
        // Subtract months
        $modified = $this->datetime->modify($date, '-2 months');
        $this->assertEquals('2023-11-15', $modified->format('Y-m-d'));
    }
    
    public function testDifference()
    {
        $date1 = $this->datetime->parse('2024-01-15');
        $date2 = $this->datetime->parse('2024-02-15');
        
        // Difference in days
        $diff = $this->datetime->diff($date1, $date2);
        $this->assertEquals(31, $diff->days);
        
        // Difference in months
        $this->assertEquals(1, $diff->m);
    }
    
    public function testIsWeekend()
    {
        // Friday in Dubai (weekend)
        $friday = $this->datetime->parse('2024-01-19');
        $this->assertTrue($this->datetime->isWeekend($friday));
        
        // Monday in Dubai (weekday)
        $monday = $this->datetime->parse('2024-01-22');
        $this->assertFalse($this->datetime->isWeekend($monday));
    }
    
    public function testIsHoliday()
    {
        // New Year's Day
        $newYear = $this->datetime->parse('2024-01-01');
        $this->assertTrue($this->datetime->isHoliday($newYear));
        
        // Regular day
        $regularDay = $this->datetime->parse('2024-01-15');
        $this->assertFalse($this->datetime->isHoliday($regularDay));
    }
    
    public function testBusinessDays()
    {
        $start = $this->datetime->parse('2024-01-15');
        $end = $this->datetime->parse('2024-01-22');
        
        // Calculate business days
        $days = $this->datetime->getBusinessDays($start, $end);
        $this->assertEquals(5, $days); // Excluding weekends
    }
    
    public function testFormatRelative()
    {
        $now = $this->datetime->now();
        
        // Just now
        $date = $this->datetime->modify($now, '-30 seconds');
        $this->assertEquals('just now', $this->datetime->formatRelative($date));
        
        // Minutes ago
        $date = $this->datetime->modify($now, '-5 minutes');
        $this->assertEquals('5 minutes ago', $this->datetime->formatRelative($date));
        
        // Hours ago
        $date = $this->datetime->modify($now, '-2 hours');
        $this->assertEquals('2 hours ago', $this->datetime->formatRelative($date));
    }
    
    public function testTimezoneConversion()
    {
        $date = $this->datetime->parse('2024-01-15 12:00:00');
        
        // Convert to different timezone
        $converted = $this->datetime->convertTimezone($date, 'UTC');
        $this->assertEquals('08:00:00', $converted->format('H:i:s'));
    }
    
    public function testQuarters()
    {
        $date = $this->datetime->parse('2024-02-15');
        
        // Get quarter
        $quarter = $this->datetime->getQuarter($date);
        $this->assertEquals(1, $quarter);
        
        // Start of quarter
        $start = $this->datetime->startOfQuarter($date);
        $this->assertEquals('2024-01-01', $start->format('Y-m-d'));
        
        // End of quarter
        $end = $this->datetime->endOfQuarter($date);
        $this->assertEquals('2024-03-31', $end->format('Y-m-d'));
    }
    
    public function testAgeCalculation()
    {
        $birthdate = $this->datetime->parse('1990-01-15');
        $now = $this->datetime->parse('2024-01-15');
        
        $age = $this->datetime->calculateAge($birthdate, $now);
        $this->assertEquals(34, $age);
    }
    
    public function testDateValidation()
    {
        $this->assertTrue($this->datetime->isValid('2024-01-15'));
        $this->assertTrue($this->datetime->isValid('15/01/2024', 'd/m/Y'));
        $this->assertFalse($this->datetime->isValid('2024-13-45'));
    }
    
    protected function tearDown(): void
    {
        date_default_timezone_set('UTC'); // Reset timezone
        parent::tearDown();
    }
}
