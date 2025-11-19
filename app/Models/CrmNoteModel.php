<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class CrmNoteModel extends Model
{
    protected $table = 'crm_notes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'entity_type',
        'entity_id',
        'registration_id',
        'student_user_id',
        'contact_id',
        'note_type',
        'interaction_type',
        'channel',
        'origin',
        'visibility',
        'title',
        'body',
        'metadata',
        'tags',
        'plugin_source',
        'created_by',
        'created_by_name',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    public function createNote(array $data): int
    {
        $payload = $this->normalizePayload($data);
        $this->db->table($this->table)->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function getNotes(array $filters = []): array
    {
        $builder = $this->db->table($this->table);
        $this->applyFilters($builder, $filters);

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 200;
        $limit = $limit > 0 ? min($limit, 500) : 200;

        return $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function linkRegistrationNotesToStudent(int $registrationId, int $studentUserId): void
    {
        if ($registrationId <= 0 || $studentUserId <= 0) {
            return;
        }

        $this->db->table($this->table)
            ->where('registration_id', $registrationId)
            ->groupStart()
                ->where('student_user_id IS NULL', null, false)
                ->orWhere('student_user_id', 0)
            ->groupEnd()
            ->set('student_user_id', $studentUserId)
            ->update();
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizePayload(array $data): array
    {
        $entityType = strtolower((string) ($data['entity_type'] ?? 'registration'));
        $entityId = isset($data['entity_id']) && $data['entity_id'] !== ''
            ? (string) $data['entity_id']
            : (isset($data['registration_id']) ? (string) $data['registration_id'] : '');

        $payload = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'registration_id' => isset($data['registration_id']) ? (int) $data['registration_id'] ?: null : null,
            'student_user_id' => isset($data['student_user_id']) ? (int) $data['student_user_id'] ?: null : null,
            'contact_id' => isset($data['contact_id']) ? (int) $data['contact_id'] ?: null : null,
            'note_type' => strtolower((string) ($data['note_type'] ?? 'internal')),
            'interaction_type' => strtolower((string) ($data['interaction_type'] ?? 'workflow')),
            'channel' => strtolower((string) ($data['channel'] ?? 'internal')),
            'origin' => strtolower((string) ($data['origin'] ?? 'manual')),
            'visibility' => strtolower((string) ($data['visibility'] ?? 'internal')),
            'title' => $data['title'] ?? null,
            'body' => trim((string) ($data['body'] ?? $data['message'] ?? '')),
            'metadata' => $this->encodeJson($data['metadata'] ?? null),
            'tags' => $this->encodeJson($data['tags'] ?? null),
            'plugin_source' => $data['plugin_source'] ?? null,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] ?: null : null,
            'created_by_name' => $data['created_by_name'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $data['updated_at'] ?? null,
        ];

        if ($payload['entity_id'] === '') {
            $payload['entity_id'] = uniqid($payload['entity_type'] . '_', true);
        }

        return $payload;
    }

    /**
     * @param BaseBuilder $builder
     * @param array<string,mixed> $filters
     */
    private function applyFilters(BaseBuilder $builder, array $filters): void
    {
        if (!empty($filters['registration_id'])) {
            $builder->where('registration_id', (int) $filters['registration_id']);
        }

        if (!empty($filters['student_user_id'])) {
            $builder->where('student_user_id', (int) $filters['student_user_id']);
        }

        if (!empty($filters['contact_id'])) {
            $builder->where('contact_id', (int) $filters['contact_id']);
        }

        if (!empty($filters['entity_type'])) {
            $builder->where('entity_type', strtolower((string) $filters['entity_type']));
        }

        if (!empty($filters['entity_id'])) {
            $builder->where('entity_id', (string) $filters['entity_id']);
        }

        if (!empty($filters['note_types']) && is_array($filters['note_types'])) {
            $builder->whereIn('note_type', array_map('strtolower', $filters['note_types']));
        }

        if (!empty($filters['channels']) && is_array($filters['channels'])) {
            $builder->whereIn('channel', array_map('strtolower', $filters['channels']));
        }

        if (!empty($filters['origin'])) {
            $builder->where('origin', strtolower((string) $filters['origin']));
        }
    }

    /**
     * @param mixed $value
     */
    private function encodeJson($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $encoded === false ? null : $encoded;
    }
}
