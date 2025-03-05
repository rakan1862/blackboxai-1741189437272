<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DatabaseConfigTest extends TestCase
{
    protected $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = require dirname(dirname(__DIR__)) . '/config/database.php';
    }
    
    public function testDatabaseConfigExists()
    {
        $this->assertIsArray($this->config);
        $this->assertNotEmpty($this->config);
    }
    
    public function testRequiredConfigKeysExist()
    {
        $requiredKeys = [
            'db_driver',
            'db_host',
            'db_port',
            'db_name',
            'db_user',
            'db_pass',
            'db_charset',
            'db_collation',
            'db_prefix'
        ];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
    }
    
    public function testDatabaseOptionsExist()
    {
        $this->assertArrayHasKey('db_options', $this->config);
        $this->assertIsArray($this->config['db_options']);
        
        // Check PDO options
        $options = $this->config['db_options'];
        $this->assertArrayHasKey(\PDO::ATTR_ERRMODE, $options);
        $this->assertArrayHasKey(\PDO::ATTR_DEFAULT_FETCH_MODE, $options);
        $this->assertArrayHasKey(\PDO::ATTR_EMULATE_PREPARES, $options);
    }
    
    public function testTableDefinitionsExist()
    {
        $this->assertArrayHasKey('tables', $this->config);
        $this->assertIsArray($this->config['tables']);
        
        // Check required tables
        $requiredTables = [
            'users',
            'companies',
            'compliance_rules',
            'company_compliance',
            'documents',
            'notifications',
            'audit_logs',
            'password_resets',
            'failed_jobs'
        ];
        
        foreach ($requiredTables as $table) {
            $this->assertArrayHasKey($table, $this->config['tables']);
            $this->assertIsArray($this->config['tables'][$table]);
        }
    }
    
    public function testForeignKeyDefinitionsExist()
    {
        $this->assertArrayHasKey('foreign_keys', $this->config);
        $this->assertIsArray($this->config['foreign_keys']);
        
        // Check foreign key relationships
        $relationships = [
            'users' => ['company_id'],
            'company_compliance' => ['company_id', 'rule_id'],
            'documents' => ['company_id', 'employee_id'],
            'notifications' => ['company_id', 'user_id'],
            'audit_logs' => ['user_id', 'company_id']
        ];
        
        foreach ($relationships as $table => $keys) {
            $this->assertArrayHasKey($table, $this->config['foreign_keys']);
            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $this->config['foreign_keys'][$table]);
            }
        }
    }
    
    public function testIndexDefinitionsExist()
    {
        $this->assertArrayHasKey('indexes', $this->config);
        $this->assertIsArray($this->config['indexes']);
        
        // Check indexes for main tables
        $mainTables = [
            'users',
            'companies',
            'documents',
            'notifications'
        ];
        
        foreach ($mainTables as $table) {
            $this->assertArrayHasKey($table, $this->config['indexes']);
            $this->assertIsArray($this->config['indexes'][$table]);
        }
    }
    
    public function testBackupSettingsExist()
    {
        $backupKeys = [
            'backup_enabled',
            'backup_path',
            'backup_frequency',
            'backup_time',
            'backup_retention'
        ];
        
        foreach ($backupKeys as $key) {
            $this->assertArrayHasKey($key, $this->config);
        }
        
        $this->assertIsBool($this->config['backup_enabled']);
        $this->assertIsString($this->config['backup_path']);
        $this->assertIsString($this->config['backup_frequency']);
        $this->assertIsString($this->config['backup_time']);
        $this->assertIsInt($this->config['backup_retention']);
    }
    
    public function testConnectionSettings()
    {
        $this->assertArrayHasKey('db_persistent', $this->config);
        $this->assertIsBool($this->config['db_persistent']);
        
        $this->assertArrayHasKey('db_timeout', $this->config);
        $this->assertIsInt($this->config['db_timeout']);
        
        $this->assertArrayHasKey('db_pool_min', $this->config);
        $this->assertIsInt($this->config['db_pool_min']);
        
        $this->assertArrayHasKey('db_pool_max', $this->config);
        $this->assertIsInt($this->config['db_pool_max']);
    }
    
    public function testValidTableStructures()
    {
        foreach ($this->config['tables'] as $table => $columns) {
            foreach ($columns as $column => $definition) {
                $this->assertIsString($definition);
                $this->assertNotEmpty($definition);
            }
        }
    }
    
    public function testValidForeignKeyReferences()
    {
        foreach ($this->config['foreign_keys'] as $table => $relations) {
            foreach ($relations as $column => $reference) {
                $this->assertIsString($reference);
                $this->assertStringContainsString('(', $reference);
                $this->assertStringContainsString(')', $reference);
                $this->assertStringContainsString('ON', $reference);
            }
        }
    }
    
    public function testValidIndexDefinitions()
    {
        foreach ($this->config['indexes'] as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (is_array($columns)) {
                    foreach ($columns as $column) {
                        $this->assertIsString($column);
                    }
                } else {
                    $this->assertIsString($columns);
                }
            }
        }
    }
}
