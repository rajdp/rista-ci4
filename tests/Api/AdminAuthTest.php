<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class AdminAuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testAdminTokenGeneration()
    {
        $result = $this->get('auth/token');

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
        
        $responseData = json_decode($result->getJSON(), true);
        $this->assertArrayHasKey('token', $responseData['ResponseObject']);
    }

    public function testAdminTokenValidation()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Now validate the token
        $result = $this->post('auth/token', [], [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testAdminTokenValidationWithInvalidToken()
    {
        $result = $this->post('auth/token', [], [
            'Accesstoken' => 'invalid-token'
        ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testAdminSettingsList()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test settings list
        $result = $this->post('settings/list', [], [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testAdminSettingsListWithoutToken()
    {
        $result = $this->post('settings/list');

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testAdminSettingsUpdate()
    {
        // First get a token
        $tokenResult = $this->get('auth/token');
        $tokenResult->assertStatus(200);

        $responseData = json_decode($tokenResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'];

        // Test settings update
        $data = [
            'settings' => [
                'site_name' => 'Test Site',
                'site_email' => 'test@example.com'
            ]
        ];

        $result = $this->post('settings/update', $data, [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }
}
