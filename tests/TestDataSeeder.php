<?php
namespace Tests;

class TestDataSeeder
{
    protected $db;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Run all seeders
     */
    public function run()
    {
        $this->seedCompanies();
        $this->seedUsers();
        $this->seedComplianceRules();
        $this->seedDocuments();
        $this->seedNotifications();
    }
    
    /**
     * Seed companies
     */
    protected function seedCompanies()
    {
        $companies = [
            [
                'name' => 'Test Company LLC',
                'trade_license_no' => 'TEST-12345',
                'tax_registration_no' => 'TAX-12345',
                'address' => 'Test Address, Dubai, UAE',
                'phone' => '+971501234567',
                'email' => 'test@company.com',
                'industry_type' => 'trading',
                'company_type' => 'llc',
                'status' => 'active'
            ],
            [
                'name' => 'Demo Corp FZ-LLC',
                'trade_license_no' => 'DEMO-67890',
                'tax_registration_no' => 'TAX-67890',
                'address' => 'Demo Address, Abu Dhabi, UAE',
                'phone' => '+971502345678',
                'email' => 'demo@corp.com',
                'industry_type' => 'services',
                'company_type' => 'free_zone',
                'status' => 'active'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO companies (
                name, trade_license_no, tax_registration_no,
                address, phone, email, industry_type,
                company_type, status
            ) VALUES (
                :name, :trade_license_no, :tax_registration_no,
                :address, :phone, :email, :industry_type,
                :company_type, :status
            )
        ");
        
        foreach ($companies as $company) {
            $stmt->execute($company);
        }
    }
    
    /**
     * Seed users
     */
    protected function seedUsers()
    {
        $users = [
            [
                'company_id' => 1,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
                'status' => 'active'
            ],
            [
                'company_id' => 1,
                'first_name' => 'Manager',
                'last_name' => 'User',
                'email' => 'manager@example.com',
                'password' => password_hash('manager123', PASSWORD_DEFAULT),
                'role' => 'manager',
                'status' => 'active'
            ],
            [
                'company_id' => 1,
                'first_name' => 'Regular',
                'last_name' => 'User',
                'email' => 'user@example.com',
                'password' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'status' => 'active'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO users (
                company_id, first_name, last_name,
                email, password, role, status
            ) VALUES (
                :company_id, :first_name, :last_name,
                :email, :password, :role, :status
            )
        ");
        
        foreach ($users as $user) {
            $stmt->execute($user);
        }
    }
    
    /**
     * Seed compliance rules
     */
    protected function seedComplianceRules()
    {
        $rules = [
            [
                'title' => 'Trade License Renewal',
                'description' => 'Annual trade license renewal requirement',
                'category' => 'trade_license',
                'priority' => 'critical',
                'frequency' => 365
            ],
            [
                'title' => 'VAT Returns Filing',
                'description' => 'Quarterly VAT returns filing requirement',
                'category' => 'tax_registration',
                'priority' => 'high',
                'frequency' => 90
            ],
            [
                'title' => 'Employee Visa Renewal',
                'description' => 'Employee visa renewal requirement',
                'category' => 'immigration',
                'priority' => 'high',
                'frequency' => 60
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO compliance_rules (
                title, description, category,
                priority, frequency
            ) VALUES (
                :title, :description, :category,
                :priority, :frequency
            )
        ");
        
        foreach ($rules as $rule) {
            $stmt->execute($rule);
        }
    }
    
    /**
     * Seed documents
     */
    protected function seedDocuments()
    {
        $documents = [
            [
                'company_id' => 1,
                'type' => 'license',
                'title' => 'Trade License 2024',
                'file_path' => 'documents/1/trade_license.pdf',
                'expiry_date' => date('Y-m-d', strtotime('+6 months')),
                'status' => 'active'
            ],
            [
                'company_id' => 1,
                'type' => 'permit',
                'title' => 'Building Permit',
                'file_path' => 'documents/1/building_permit.pdf',
                'expiry_date' => date('Y-m-d', strtotime('+3 months')),
                'status' => 'active'
            ],
            [
                'company_id' => 1,
                'type' => 'certificate',
                'title' => 'ISO Certification',
                'file_path' => 'documents/1/iso_cert.pdf',
                'expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'status' => 'active'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO documents (
                company_id, type, title,
                file_path, expiry_date, status
            ) VALUES (
                :company_id, :type, :title,
                :file_path, :expiry_date, :status
            )
        ");
        
        foreach ($documents as $document) {
            $stmt->execute($document);
        }
    }
    
    /**
     * Seed notifications
     */
    protected function seedNotifications()
    {
        $notifications = [
            [
                'company_id' => 1,
                'user_id' => 1,
                'type' => 'document_expiring',
                'title' => 'Trade License Expiring Soon',
                'message' => 'Your trade license will expire in 30 days',
                'priority' => 'high'
            ],
            [
                'company_id' => 1,
                'user_id' => 1,
                'type' => 'compliance_update',
                'title' => 'New Compliance Requirement',
                'message' => 'New VAT return filing requirement added',
                'priority' => 'medium'
            ]
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO notifications (
                company_id, user_id, type,
                title, message, priority
            ) VALUES (
                :company_id, :user_id, :type,
                :title, :message, :priority
            )
        ");
        
        foreach ($notifications as $notification) {
            $stmt->execute($notification);
        }
    }
    
    /**
     * Clean up test data
     */
    public function cleanup()
    {
        $tables = [
            'notifications',
            'documents',
            'company_compliance',
            'compliance_rules',
            'users',
            'companies'
        ];
        
        foreach ($tables as $table) {
            $this->db->exec("DELETE FROM $table");
        }
    }
}
