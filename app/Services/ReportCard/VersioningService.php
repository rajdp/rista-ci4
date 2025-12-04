<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardModel;
use App\Models\ReportCardVersionModel;

class VersioningService
{
    protected $reportCardModel;
    protected $versionModel;

    public function __construct()
    {
        $this->reportCardModel = new ReportCardModel();
        $this->versionModel = new ReportCardVersionModel();
    }

    /**
     * Create new immutable version of report card
     *
     * @param int $rcId Report card ID
     * @param array $payload New payload data
     * @param int $userId User creating the version
     * @return array Result
     */
    public function createNewVersion(int $rcId, array $payload, int $userId): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get current report card
            $reportCard = $this->reportCardModel->find($rcId);
            if (!$reportCard) {
                return [
                    'IsSuccess' => false,
                    'Message' => 'Report card not found',
                ];
            }

            // Create new version
            $rendererService = new RendererService();
            $summary = $rendererService->calculateSummary($payload);

            $newVersionNumber = $this->versionModel->createVersion(
                $rcId,
                json_encode($payload),
                json_encode($summary),
                $userId
            );

            if (!$newVersionNumber) {
                $db->transRollback();
                return [
                    'IsSuccess' => false,
                    'Message' => 'Failed to create version',
                ];
            }

            // Update current_version in report card
            $this->reportCardModel->update($rcId, [
                'current_version' => $newVersionNumber,
            ]);

            $db->transComplete();

            return [
                'IsSuccess' => true,
                'Data' => [
                    'rc_id' => $rcId,
                    'version' => $newVersionNumber,
                ],
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            return [
                'IsSuccess' => false,
                'Message' => 'Error creating version: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get version history for a report card
     *
     * @param int $rcId
     * @return array
     */
    public function getVersionHistory(int $rcId): array
    {
        $versions = $this->versionModel->getAllVersions($rcId);

        $history = [];
        foreach ($versions as $version) {
            $summary = json_decode($version['summary_json'] ?? '{}', true);
            $history[] = [
                'version' => $version['version'],
                'created_at' => $version['created_at'],
                'created_by' => $version['created_by'],
                'summary' => $summary,
            ];
        }

        return $history;
    }

    /**
     * Compare two versions (generate diff)
     *
     * @param int $rcId
     * @param int $version1
     * @param int $version2
     * @return array Differences
     */
    public function compareVersions(int $rcId, int $version1, int $version2): array
    {
        $v1 = $this->versionModel->getVersion($rcId, $version1);
        $v2 = $this->versionModel->getVersion($rcId, $version2);

        if (!$v1 || !$v2) {
            return [];
        }

        $payload1 = json_decode($v1['payload_json'], true);
        $payload2 = json_decode($v2['payload_json'], true);

        $differences = $this->arrayDiff($payload1, $payload2);

        return [
            'version1' => $version1,
            'version2' => $version2,
            'differences' => $differences,
        ];
    }

    /**
     * Recursive array diff
     */
    protected function arrayDiff(array $array1, array $array2, string $path = ''): array
    {
        $differences = [];

        foreach ($array1 as $key => $value) {
            $currentPath = $path ? "$path.$key" : $key;

            if (!array_key_exists($key, $array2)) {
                $differences[] = [
                    'path' => $currentPath,
                    'type' => 'removed',
                    'old_value' => $value,
                ];
            } elseif (is_array($value) && is_array($array2[$key])) {
                $nestedDiff = $this->arrayDiff($value, $array2[$key], $currentPath);
                $differences = array_merge($differences, $nestedDiff);
            } elseif ($value !== $array2[$key]) {
                $differences[] = [
                    'path' => $currentPath,
                    'type' => 'changed',
                    'old_value' => $value,
                    'new_value' => $array2[$key],
                ];
            }
        }

        // Check for new keys in array2
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $currentPath = $path ? "$path.$key" : $key;
                $differences[] = [
                    'path' => $currentPath,
                    'type' => 'added',
                    'new_value' => $value,
                ];
            }
        }

        return $differences;
    }
}
