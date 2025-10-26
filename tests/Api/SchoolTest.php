<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class SchoolTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testSchoolList()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school list
        $result = $this->post('school/list', [], [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testSchoolListWithoutToken()
    {
        $result = $this->post('school/list');

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testSchoolAdd()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school add
        $data = [
            'school_name' => 'Test School',
            'email' => 'test@school.com',
            'phone' => '123-456-7890',
            'address' => '123 Test Street'
        ];

        $result = $this->post('school/add', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testSchoolAddWithMissingFields()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school add with missing required fields
        $data = [
            'school_name' => 'Test School'
            // Missing required fields
        ];

        $result = $this->post('school/add', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(400);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testSchoolEdit()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school edit
        $data = [
            'school_id' => 1
        ];

        $result = $this->post('school/edit', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testSchoolUpdate()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school update
        $data = [
            'school_id' => 1,
            'school_name' => 'Updated School Name',
            'email' => 'updated@school.com'
        ];

        $result = $this->post('school/update', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testSchoolRemove()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test school remove
        $data = [
            'school_id' => 1
        ];

        $result = $this->post('school/remove', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }
}
