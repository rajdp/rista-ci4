<?php

namespace App\Controllers\Appt;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class HostsController extends ApptController
{
    public function index(): ResponseInterface
    {
        $payload = $this->request->getGet();
        $token = $this->validateToken();
        // Convert false to null for type compatibility
        $token = $token ?: null;
        $schoolId = $this->resolveSchoolId($token, $payload);

        if (!$schoolId) {
            return $this->errorResponse('school_id or school_key is required');
        }

        return $this->successResponse($this->loadHosts($schoolId), 'Hosts loaded');
    }

    private function loadHosts(int $schoolId): array
    {
        $db = Database::connect();
        $builder = $db->table('user u');
        $builder->select('u.user_id AS admin_user_id, COALESCE(up.first_name, "") AS first_name, COALESCE(up.last_name, "") AS last_name, u.email_id');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        // Support both single school_id match and comma-separated school_id values
        $builder->groupStart()
            ->where('u.school_id', $schoolId)
            ->orWhere("FIND_IN_SET('{$schoolId}', u.school_id) >", 0, false)
            ->groupEnd();
        $builder->where('u.status', 1);
        // Filter for admin/staff roles (role_id 1 = admin, 2 = staff/teacher, 4 = registrar, etc.)
        // Exclude students (role_id 5) and parents (role_id 8)
        $builder->whereNotIn('u.role_id', [5, 8]);
        $builder->orderBy('up.first_name ASC');

        $rows = $builder->get()->getResultArray();

        return array_map(static function (array $row) {
            $displayName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($displayName === '') {
                $displayName = $row['email_id'] ?? 'Staff';
            }

            return [
                'admin_user_id' => (int) $row['admin_user_id'],
                'display_name' => $displayName,
                'email' => $row['email_id'] ?? null,
            ];
        }, $rows);
    }
}
