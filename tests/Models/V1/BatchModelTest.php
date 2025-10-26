<?php

namespace Tests\Models\V1;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\V1\BatchModel;

class BatchModelTest extends CIUnitTestCase
{
    protected $batchModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load the model
        $this->batchModel = new BatchModel();
        
        // Get database connection
        $this->db = \Config\Database::connect();
        
        // Start transaction
        $this->db->transStart();
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        $this->db->transRollback();
        
        parent::tearDown();
    }

    /**
     * Test batchList method
     */
    public function testBatchList()
    {
        // Test data
        $params = [
            'role_id' => 2,
            'school_id' => 1,
            'type' => '2'
        ];

        // Call the method
        $result = $this->batchModel->batchList($params);

        // Assertions
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Check if the result has expected structure
        if (!empty($result)) {
            $firstItem = $result[0];
            $this->assertArrayHasKey('batch_id', $firstItem);
            $this->assertArrayHasKey('batch_name', $firstItem);
            $this->assertArrayHasKey('school_id', $firstItem);
            $this->assertArrayHasKey('school_name', $firstItem);
            $this->assertArrayHasKey('status', $firstItem);
        }
    }

    /**
     * Test getBatchList method
     */
    public function testGetBatchList()
    {
        // Test data
        $params = [
            'role_id' => 2,
            'school_id' => 1,
            'type' => '2'
        ];

        // Call the method
        $result = $this->batchModel->getBatchList($params);

        // Assertions
        $this->assertIsArray($result);
        
        // Check if the result has expected structure
        if (!empty($result)) {
            $firstItem = $result[0];
            $this->assertArrayHasKey('batch_id', $firstItem);
            $this->assertArrayHasKey('batch_name', $firstItem);
            $this->assertArrayHasKey('school_id', $firstItem);
            $this->assertArrayHasKey('school_name', $firstItem);
            $this->assertArrayHasKey('status', $firstItem);
        }
    }

    /**
     * Test checkBatch method
     */
    public function testCheckBatch()
    {
        // Test data
        $params = [
            'school_id' => 1,
            'batch_name' => 'Test Batch',
            'parent_batch_id' => 0
        ];

        // Call the method
        $result = $this->batchModel->checkBatch($params, 'add');

        // Assertions
        $this->assertIsArray($result);
    }

    /**
     * Test checkBatchPermission method
     */
    public function testCheckBatchPermission()
    {
        // Test data
        $params = [
            'batch_id' => 1,
            'role_id' => 2,
            'school_id' => 1,
            'user_id' => 1
        ];

        // Call the method
        $result = $this->batchModel->checkBatchPermission($params);

        // Assertions
        $this->assertIsArray($result);
    }
} 