<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\EmailService;
use App\Controllers\NotificationController;

class EmailNotificationTest extends TestCase
{
    protected $emailService;
    protected $notificationController;
    protected $db;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = new \PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_DATABASE') . "_test",
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        
        $this->emailService = new EmailService([
            'host' => getenv('MAIL_HOST'),
            'port' => getenv('MAIL_PORT'),
            'username' => getenv('MAIL_USERNAME'),
            'password' => getenv('MAIL_PASSWORD'),
            'encryption' => getenv('MAIL_ENCRYPTION'),
            'from_address' => getenv('MAIL_FROM_ADDRESS'),
            'from_name' => getenv('MAIL_FROM_NAME')
        ]);
        
        $this->notificationController = new NotificationController($this->db, $this->emailService);
    }
    
    public function testSendDocumentExpiryNotification()
    {
        $documentData = [
            'title' => 'Trade License',
            'type' => 'license',
            'expiry_date' => date('Y-m-d', strtotime('+30 days')),
            'company_name' => 'Test Company LLC'
        ];
        
        $userEmail = 'test@example.com';
        
        $result = $this->notificationController->sendDocumentExpiryNotification(
            $userEmail,
            $documentData
        );
        
        $this->assertTrue($result['success']);
        $this->assertEmailWasSent($userEmail);
        $this->assertEmailContains($documentData['title']);
        $this->assertEmailContains($documentData['expiry_date']);
    }
    
    public function testSendComplianceUpdateNotification()
    {
        $complianceData = [
            'rule_title' => 'VAT Return Filing',
            'status' => 'non_compliant',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'notes' => 'Action required'
        ];
        
        $userEmail = 'manager@example.com';
        
        $result = $this->notificationController->sendComplianceUpdateNotification(
            $userEmail,
            $complianceData
        );
        
        $this->assertTrue($result['success']);
        $this->assertEmailWasSent($userEmail);
        $this->assertEmailContains($complianceData['rule_title']);
        $this->assertEmailContains($complianceData['status']);
    }
    
    public function testSendPasswordResetNotification()
    {
        $userData = [
            'email' => 'user@example.com',
            'reset_token' => 'test-reset-token-123',
            'name' => 'Test User'
        ];
        
        $result = $this->notificationController->sendPasswordResetNotification(
            $userData['email'],
            $userData
        );
        
        $this->assertTrue($result['success']);
        $this->assertEmailWasSent($userData['email']);
        $this->assertEmailContains($userData['reset_token']);
    }
    
    public function testSendWelcomeEmail()
    {
        $userData = [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'company_name' => 'Test Company LLC'
        ];
        
        $result = $this->notificationController->sendWelcomeEmail(
            $userData['email'],
            $userData
        );
        
        $this->assertTrue($result['success']);
        $this->assertEmailWasSent($userData['email']);
        $this->assertEmailContains('Welcome');
        $this->assertEmailContains($userData['name']);
    }
    
    public function testSendBulkNotification()
    {
        $recipients = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com'
        ];
        
        $notificationData = [
            'subject' => 'System Maintenance',
            'message' => 'Scheduled maintenance notification'
        ];
        
        $result = $this->notificationController->sendBulkNotification(
            $recipients,
            $notificationData
        );
        
        $this->assertTrue($result['success']);
        foreach ($recipients as $email) {
            $this->assertEmailWasSent($email);
        }
    }
    
    public function testEmailTemplateRendering()
    {
        $templateData = [
            'user_name' => 'Test User',
            'company_name' => 'Test Company',
            'action_required' => 'Document Update'
        ];
        
        $renderedEmail = $this->emailService->renderTemplate(
            'notification',
            $templateData
        );
        
        $this->assertStringContainsString($templateData['user_name'], $renderedEmail);
        $this->assertStringContainsString($templateData['company_name'], $renderedEmail);
        $this->assertStringContainsString($templateData['action_required'], $renderedEmail);
    }
    
    public function testEmailQueueing()
    {
        // Enable email queueing for test
        $this->emailService->enableQueueing();
        
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Queued Email',
            'message' => 'Test message'
        ];
        
        $result = $this->emailService->queue($emailData);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['queue_id']);
        
        // Verify email was queued
        $stmt = $this->db->prepare("SELECT * FROM email_queue WHERE id = ?");
        $stmt->execute([$result['queue_id']]);
        $queuedEmail = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals($emailData['to'], $queuedEmail['recipient']);
        $this->assertEquals($emailData['subject'], $queuedEmail['subject']);
    }
    
    protected function assertEmailWasSent($email)
    {
        $sentEmails = $this->emailService->getSentEmails();
        $emailWasSent = false;
        
        foreach ($sentEmails as $sentEmail) {
            if ($sentEmail['to'] === $email) {
                $emailWasSent = true;
                break;
            }
        }
        
        $this->assertTrue($emailWasSent, "Email was not sent to {$email}");
    }
    
    protected function assertEmailContains($text)
    {
        $sentEmails = $this->emailService->getSentEmails();
        $lastEmail = end($sentEmails);
        
        $this->assertStringContainsString(
            $text,
            $lastEmail['message'],
            "Email does not contain expected text: {$text}"
        );
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        $this->db->exec("DELETE FROM email_queue");
        $this->emailService->clearSentEmails();
        
        parent::tearDown();
    }
}
