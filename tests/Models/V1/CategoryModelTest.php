<?php

namespace Tests\Models\V1;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\V1\CategoryModel;

class CategoryModelTest extends CIUnitTestCase
{
    protected $categoryModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load the model
        $this->categoryModel = new CategoryModel();
        
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
     * Test categoryList method
     */
    public function testCategoryList()
    {
        // Test with no condition
        $result = $this->categoryModel->categoryList('');
        
        // Assertions
        $this->assertIsArray($result);
        
        // Check if the result has expected structure
        if (!empty($result)) {
            $firstItem = $result[0];
            $this->assertArrayHasKey('category_id', $firstItem);
            $this->assertArrayHasKey('category_name', $firstItem);
            $this->assertArrayHasKey('status', $firstItem);
            $this->assertArrayHasKey('subject_id', $firstItem);
            $this->assertArrayHasKey('description', $firstItem);
            $this->assertArrayHasKey('path', $firstItem);
            $this->assertArrayHasKey('display_order', $firstItem);
            $this->assertArrayHasKey('content_count', $firstItem);
        }
    }

    /**
     * Test categoryList with condition
     */
    public function testCategoryListWithCondition()
    {
        // Test with status condition
        $condition = "WHERE status = 1";
        $result = $this->categoryModel->categoryList($condition);
        
        // Assertions
        $this->assertIsArray($result);
        
        // Check if all returned categories are active
        foreach ($result as $category) {
            $this->assertEquals(1, $category['status']);
        }
    }
} 