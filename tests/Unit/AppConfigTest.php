<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AppConfigTest extends TestCase
{
    protected $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = require dirname(dirname(__DIR__)) . '/config/app.php';
    }
    
    public function testAppConfigExists()
    {
        $this->assertIsArray($this->config);
        $this->assertNotEmpty($this->config);
    }
    
    public function testApplicationSettings()
    {
        $requiredKeys = [
            'app_name',
            'app_env',
            'app_debug',
            'app_url',
            'app_timezone',
            'app_locale'
        ];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
        
        $this->assertIsString($this->config['app_name']);
        $this->assertIsString($this->config['app_env']);
        $this->assertIsBool($this->config['app_debug']);
        $this->assertIsString($this->config['app_url']);
        $this->assertEquals('Asia/Dubai', $this->config['app_timezone']);
    }
    
    public function testSecuritySettings()
    {
        $securityKeys = [
            'session_lifetime',
            'password_min_length',
            'max_login_attempts',
            'lockout_time',
            'jwt_secret',
            'jwt_expiry'
        ];
        
        foreach ($securityKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
        
        $this->assertIsInt($this->config['session_lifetime']);
        $this->assertIsInt($this->config['password_min_length']);
        $this->assertGreaterThan(0, $this->config['password_min_length']);
    }
    
    public function testFileUploadSettings()
    {
        $this->assertArrayHasKey('max_file_size', $this->config);
        $this->assertArrayHasKey('allowed_file_types', $this->config);
        $this->assertArrayHasKey('upload_path', $this->config);
        
        $this->assertIsInt($this->config['max_file_size']);
        $this->assertIsArray($this->config['allowed_file_types']);
        $this->assertIsString($this->config['upload_path']);
    }
    
    public function testEmailSettings()
    {
        $emailKeys = [
            'mail_driver',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name'
        ];
        
        foreach ($emailKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
    }
    
    public function testNotificationSettings()
    {
        $this->assertArrayHasKey('notification_channels', $this->config);
        $this->assertIsArray($this->config['notification_channels']);
        
        $this->assertArrayHasKey('sms_provider', $this->config);
        $this->assertArrayHasKey('sms_from', $this->config);
    }
    
    public function testCacheSettings()
    {
        $this->assertArrayHasKey('cache_driver', $this->config);
        $this->assertArrayHasKey('cache_prefix', $this->config);
        $this->assertArrayHasKey('cache_lifetime', $this->config);
        
        $this->assertIsString($this->config['cache_driver']);
        $this->assertIsString($this->config['cache_prefix']);
        $this->assertIsInt($this->config['cache_lifetime']);
    }
    
    public function testQueueSettings()
    {
        $this->assertArrayHasKey('queue_driver', $this->config);
        $this->assertArrayHasKey('queue_table', $this->config);
        $this->assertArrayHasKey('failed_jobs_table', $this->config);
    }
    
    public function testApiSettings()
    {
        $apiKeys = [
            'api_prefix',
            'api_version',
            'api_debug',
            'api_timeout',
            'rate_limit'
        ];
        
        foreach ($apiKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
        
        $this->assertIsString($this->config['api_prefix']);
        $this->assertIsString($this->config['api_version']);
        $this->assertIsBool($this->config['api_debug']);
        $this->assertIsInt($this->config['api_timeout']);
        $this->assertIsInt($this->config['rate_limit']);
    }
    
    public function testComplianceSettings()
    {
        $this->assertArrayHasKey('compliance_check_interval', $this->config);
        $this->assertArrayHasKey('compliance_reminder_days', $this->config);
        $this->assertArrayHasKey('compliance_categories', $this->config);
        $this->assertArrayHasKey('compliance_priorities', $this->config);
        
        $this->assertIsInt($this->config['compliance_check_interval']);
        $this->assertIsArray($this->config['compliance_reminder_days']);
        $this->assertIsArray($this->config['compliance_categories']);
        $this->assertIsArray($this->config['compliance_priorities']);
    }
    
    public function testDocumentSettings()
    {
        $this->assertArrayHasKey('document_types', $this->config);
        $this->assertArrayHasKey('document_statuses', $this->config);
        
        $this->assertIsArray($this->config['document_types']);
        $this->assertIsArray($this->config['document_statuses']);
    }
    
    public function testAuditSettings()
    {
        $this->assertArrayHasKey('enable_audit_log', $this->config);
        $this->assertArrayHasKey('audit_events', $this->config);
        
        $this->assertIsBool($this->config['enable_audit_log']);
        $this->assertIsArray($this->config['audit_events']);
    }
    
    public function testCompanySettings()
    {
        $this->assertArrayHasKey('company_types', $this->config);
        $this->assertArrayHasKey('industry_types', $this->config);
        
        $this->assertIsArray($this->config['company_types']);
        $this->assertIsArray($this->config['industry_types']);
    }
    
    public function testUserRolesAndPermissions()
    {
        $this->assertArrayHasKey('user_roles', $this->config);
        $this->assertIsArray($this->config['user_roles']);
        
        $roles = ['admin', 'manager', 'user'];
        foreach ($roles as $role) {
            $this->assertArrayHasKey($role, $this->config['user_roles']);
            $this->assertIsArray($this->config['user_roles'][$role]);
        }
    }
    
    public function testReportSettings()
    {
        $this->assertArrayHasKey('report_types', $this->config);
        $this->assertArrayHasKey('export_formats', $this->config);
        
        $this->assertIsArray($this->config['report_types']);
        $this->assertIsArray($this->config['export_formats']);
    }
    
    public function testSupportSettings()
    {
        $this->assertArrayHasKey('support_email', $this->config);
        $this->assertArrayHasKey('support_phone', $this->config);
        $this->assertArrayHasKey('working_hours', $this->config);
        
        $this->assertIsString($this->config['support_email']);
        $this->assertIsString($this->config['support_phone']);
        $this->assertIsString($this->config['working_hours']);
    }
    
    public function testSocialMediaLinks()
    {
        $this->assertArrayHasKey('social_links', $this->config);
        $this->assertIsArray($this->config['social_links']);
        
        $platforms = ['facebook', 'twitter', 'linkedin'];
        foreach ($platforms as $platform) {
            $this->assertArrayHasKey($platform, $this->config['social_links']);
            $this->assertIsString($this->config['social_links'][$platform]);
        }
    }
}
