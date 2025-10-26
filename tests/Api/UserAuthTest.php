<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class UserAuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    public function testUserLogin()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $result = $this->post('user/login', $data);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testUserLoginWithInvalidCredentials()
    {
        $data = [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword'
        ];

        $result = $this->post('user/login', $data);

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testUserLoginWithMissingFields()
    {
        $data = [
            'email' => 'test@example.com'
            // Missing password
        ];

        $result = $this->post('user/login', $data);

        $result->assertStatus(400);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testUserProfileWithValidToken()
    {
        // First login to get token
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $loginResult = $this->post('user/login', $loginData);
        $loginResult->assertStatus(200);

        $responseData = json_decode($loginResult->getJSON(), true);
        $token = $responseData['ResponseObject']['token'] ?? null;

        $this->assertNotNull($token);

        // Now test profile endpoint with token
        $result = $this->get('user/profile', [
            'Accesstoken' => $token
        ]);

        $result->assertStatus(200);
        $result->assertJSONFragment(['IsSuccess' => true]);
    }

    public function testUserProfileWithoutToken()
    {
        $result = $this->get('user/profile');

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }

    public function testUserProfileWithInvalidToken()
    {
        $result = $this->get('user/profile', [
            'Accesstoken' => 'invalid-token'
        ]);

        $result->assertStatus(401);
        $result->assertJSONFragment(['IsSuccess' => false]);
    }
}
