<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin\SettingsModel;
use App\Models\V1\CommonModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Settings extends BaseController
{
    protected SettingsModel $settingsModel;
    protected CommonModel $commonModel;

    public function __construct()
    {
        $this->settingsModel = model(SettingsModel::class);
        $this->commonModel = model(CommonModel::class);
    }

    public function list(): ResponseInterface
    {
        $start = microtime(true);
        $payload = $this->getRequestData();

        $validation = Services::validation();
        $validation->setRules([
            'platform' => 'required',
            'role_id' => 'required|integer',
            'user_id' => 'required|integer',
        ], [
            'platform' => [
                'required' => 'Platform should not be empty',
            ],
            'role_id' => [
                'required' => 'Role Id should not be empty',
                'integer' => 'Role Id should not be empty',
            ],
            'user_id' => [
                'required' => 'User Id should not be empty',
                'integer' => 'User Id should not be empty',
            ],
        ]);

        if (! $validation->run($payload)) {
            return $this->legacyError(reset($validation->getErrors()), 200, $start);
        }

        if ($response = $this->ensureAuthorized('admin/settings/list', (int) $payload['role_id'], (int) $payload['user_id'])) {
            return $response;
        }

        $settings = $this->settingsModel->settingList();

        return $this->legacySuccess([
            'IsSuccess' => true,
            'ResponseObject' => $settings,
        ], $start);
    }

    public function update(): ResponseInterface
    {
        $start = microtime(true);
        $payload = $this->getRequestData();

        $validation = Services::validation();
        $validation->setRules([
            'platform' => 'required',
            'role_id' => 'required|integer',
            'user_id' => 'required|integer',
            'adminid' => 'required|integer',
            'adminvalue' => 'required',
        ], [
            'platform' => [
                'required' => 'Platform should not be empty',
            ],
            'role_id' => [
                'required' => 'Role Id should not be empty',
                'integer' => 'Role Id should not be empty',
            ],
            'user_id' => [
                'required' => 'User Id should not be empty',
                'integer' => 'User Id should not be empty',
            ],
            'adminid' => [
                'required' => 'Admin Id should not be empty',
                'integer' => 'Admin Id should not be empty',
            ],
            'adminvalue' => [
                'required' => 'Admin value should not be empty',
            ],
        ]);

        if (! $validation->run($payload)) {
            return $this->legacyError(reset($validation->getErrors()), 200, $start);
        }

        if ($response = $this->ensureAuthorized('admin/settings/update', (int) $payload['role_id'], (int) $payload['user_id'])) {
            return $response;
        }

        $updated = $this->settingsModel->updateById((int) $payload['adminid'], (string) $payload['adminvalue']);

        if (! $updated) {
            return $this->legacyError('Unable to update settings. Please try after some time', 200, $start);
        }

        return $this->legacySuccess([
            'IsSuccess' => true,
            'ResponseObject' => 'Settings value updated Successfully',
        ], $start);
    }

    protected function ensureAuthorized(string $controllerUri, int $roleId, int $userId): ?ResponseInterface
    {
        if (! $this->commonModel->checkControllerPermission($controllerUri, $roleId)) {
            return $this->legacyError("You don't have permission to access this page", 403);
        }

        $accessToken = $this->request->getHeaderLine('Accesstoken');
        if ($accessToken === '') {
            return $this->legacyError('Unauthorised User', 401);
        }

        $tokenValidation = $this->commonModel->verifyAccessToken($userId, $accessToken);
        if (! $tokenValidation['success']) {
            $statusCode = ($tokenValidation['message'] === 'Unauthorised User') ? 401 : 440; // 440 Login Timeout (legacy behaviour)
            return $this->legacyError($tokenValidation['message'], $statusCode);
        }

        return null;
    }

    protected function legacySuccess(array $payload, float $start): ResponseInterface
    {
        $payload['processing_time'] = number_format(microtime(true) - $start, 4);
        return $this->respond($payload, 200);
    }

    protected function legacyError(string $message, int $statusCode = 400, ?float $start = null): ResponseInterface
    {
        $response = [
            'IsSuccess' => false,
            'ErrorObject' => $message,
        ];

        if ($start !== null) {
            $response['processing_time'] = number_format(microtime(true) - $start, 4);
        }

        return $this->respond($response, $statusCode);
    }
}
