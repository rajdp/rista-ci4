<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ReportCardTemplateModel;
use App\Models\ReportCardScaleModel;
use App\Models\ReportCardModel;
use App\Models\ReportCardVersionModel;
use App\Services\ReportCard\TemplateService;
use App\Services\ReportCard\ScaleService;
use App\Services\ReportCard\GeneratorService;
use App\Services\ReportCard\IssuerService;
use App\Services\ReportCard\EmailService;
use App\Services\ReportCard\PdfService;
use App\Services\ReportCard\AuditService;
use App\Services\ReportCard\VersioningService;

class ReportCard extends BaseController
{
    protected $templateModel;
    protected $scaleModel;
    protected $reportCardModel;
    protected $versionModel;

    protected $templateService;
    protected $scaleService;
    protected $generatorService;
    protected $issuerService;
    protected $emailService;
    protected $pdfService;
    protected $auditService;
    protected $versioningService;

    public function __construct()
    {
        // Models
        $this->templateModel = new ReportCardTemplateModel();
        $this->scaleModel = new ReportCardScaleModel();
        $this->reportCardModel = new ReportCardModel();
        $this->versionModel = new ReportCardVersionModel();

        // Services
        $this->templateService = new TemplateService();
        $this->scaleService = new ScaleService();
        $this->generatorService = new GeneratorService();
        $this->issuerService = new IssuerService();
        $this->emailService = new EmailService();
        $this->pdfService = new PdfService();
        $this->auditService = new AuditService();
        $this->versioningService = new VersioningService();
    }

    protected function getAuthContext()
    {
        $token = $this->request->getHeaderLine('Accesstoken');
        if (empty($token)) {
            return null;
        }

        $decoded = \AUTHORIZATION::validateToken($token);
        if (!$decoded) {
            return null;
        }

        return [
            'user_id' => $decoded->id ?? null,
            'school_id' => $decoded->school_id ?? null,
            'role_id' => $decoded->role_id ?? null,
        ];
    }

    // ==================== TEMPLATE ENDPOINTS ====================

    public function templateCreate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();

        // Validate schema
        $schema = json_decode($data['schema_json'] ?? '{}', true);
        $validation = $this->templateService->validateSchema($schema);

        if (!$validation['isValid']) {
            return $this->fail(['errors' => $validation['errors']], 400);
        }

        $insertData = [
            'school_id' => $data['school_id'] ?? $auth['school_id'],
            'name' => $data['name'],
            'version' => 1,
            'schema_json' => $data['schema_json'],
            'is_active' => $data['is_active'] ?? 1,
            'created_by' => $auth['user_id'],
        ];

        $templateId = $this->templateModel->insert($insertData);

        if ($templateId) {
            return $this->respond([
                'IsSuccess' => true,
                'Data' => $this->templateModel->find($templateId),
                'Message' => 'Template created successfully',
            ]);
        }

        return $this->fail('Failed to create template', 500);
    }

    public function templateList()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $schoolId = $data['school_id'] ?? $auth['school_id'];
        $activeOnly = $data['active'] ?? null;

        $builder = $this->templateModel->where('school_id', $schoolId);

        if ($activeOnly !== null) {
            $builder->where('is_active', $activeOnly);
        }

        $templates = $builder->orderBy('name', 'ASC')->findAll();

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $templates,
            'Message' => 'Templates retrieved successfully',
        ]);
    }

    public function templateDetail()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $templateId = $data['template_id'];
        $version = $data['version'] ?? null;

        $template = $this->templateModel->getTemplateVersion($templateId, $version);

        if (!$template || $template['school_id'] != $auth['school_id']) {
            return $this->fail('Template not found', 404);
        }

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $template,
            'Message' => 'Template retrieved successfully',
        ]);
    }

    public function templateUpdate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $templateId = $data['template_id'];

        $template = $this->templateModel->find($templateId);
        if (!$template || $template['school_id'] != $auth['school_id']) {
            return $this->fail('Template not found', 404);
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];

        if (!empty($updateData)) {
            $this->templateModel->update($templateId, $updateData);
        }

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $this->templateModel->find($templateId),
            'Message' => 'Template updated successfully',
        ]);
    }

    public function templatePreview()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $schema = json_decode($data['schema_json'] ?? '{}', true);

        $html = $this->templateService->generatePreview($schema);

        return $this->respond([
            'IsSuccess' => true,
            'Data' => ['html' => $html],
            'Message' => 'Preview generated successfully',
        ]);
    }

    // ==================== SCALE ENDPOINTS ====================

    public function scaleList()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $schoolId = $data['school_id'] ?? $auth['school_id'];

        $scales = $this->scaleModel->getActiveScales($schoolId);

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $scales,
            'Message' => 'Scales retrieved successfully',
        ]);
    }

    public function scaleCreate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();

        // Validate scale
        $scale = json_decode($data['scale_json'] ?? '[]', true);
        $validation = $this->scaleService->validateScale($scale);

        if (!$validation['isValid']) {
            return $this->fail(['errors' => $validation['errors']], 400);
        }

        $insertData = [
            'school_id' => $data['school_id'] ?? $auth['school_id'],
            'name' => $data['name'],
            'scale_json' => $data['scale_json'],
            'is_active' => $data['is_active'] ?? 1,
        ];

        $scaleId = $this->scaleModel->insert($insertData);

        if ($scaleId) {
            return $this->respond([
                'IsSuccess' => true,
                'Data' => $this->scaleModel->find($scaleId),
                'Message' => 'Scale created successfully',
            ]);
        }

        return $this->fail('Failed to create scale', 500);
    }

    public function scaleUpdate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $scaleId = $data['scale_id'];

        $scale = $this->scaleModel->find($scaleId);
        if (!$scale || $scale['school_id'] != $auth['school_id']) {
            return $this->fail('Scale not found', 404);
        }

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['scale_json'])) {
            // Validate new scale
            $scaleData = json_decode($data['scale_json'], true);
            $validation = $this->scaleService->validateScale($scaleData);
            if (!$validation['isValid']) {
                return $this->fail(['errors' => $validation['errors']], 400);
            }
            $updateData['scale_json'] = $data['scale_json'];
        }
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];

        if (!empty($updateData)) {
            $this->scaleModel->update($scaleId, $updateData);
        }

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $this->scaleModel->find($scaleId),
            'Message' => 'Scale updated successfully',
        ]);
    }

    // ==================== REPORT CARD GENERATION ====================

    public function generate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();

        $params = [
            'school_id' => $auth['school_id'],
            'template_id' => $data['template_id'],
            'template_version' => $data['template_version'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'student_ids' => $data['student_ids'] ?? [],
            'term' => $data['term'],
            'academic_year' => $data['academic_year'],
            'user_id' => $auth['user_id'],
            'options' => $data['options'] ?? [],
        ];

        $result = $this->generatorService->bulkGenerate($params);

        return $this->respond($result);
    }

    // ==================== REPORT CARD MANAGEMENT ====================

    public function reportCardList()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();

        $filters = [
            'school_id' => $auth['school_id'],
            'student_id' => $data['student_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'term' => $data['term'] ?? null,
            'academic_year' => $data['academic_year'] ?? null,
            'status' => $data['status'] ?? null,
            'issued_from' => $data['issued_from'] ?? null,
            'issued_to' => $data['issued_to'] ?? null,
            'limit' => $data['limit'] ?? 50,
            'offset' => $data['offset'] ?? 0,
        ];

        $result = $this->reportCardModel->searchReportCards($filters);

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $result,
            'Message' => 'Report cards retrieved successfully',
        ]);
    }

    public function reportCardDetail()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        // Get latest version
        $version = $this->versionModel->getLatestVersion($rcId);

        if ($version) {
            $reportCard['payload'] = json_decode($version['payload_json'], true);
            $reportCard['summary'] = json_decode($version['summary_json'] ?? '{}', true);
        }

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $reportCard,
            'Message' => 'Report card retrieved successfully',
        ]);
    }

    public function reportCardUpdate()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        if ($reportCard['status'] !== 'draft') {
            return $this->fail('Only draft report cards can be edited', 400);
        }

        // Create new version
        $payload = $data['payload_json'] ?? [];
        $result = $this->versioningService->createNewVersion($rcId, $payload, $auth['user_id']);

        return $this->respond($result);
    }

    public function reportCardStatus()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];
        $status = $data['status'];

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        $result = null;
        switch ($status) {
            case 'ready':
                $result = $this->issuerService->markReady($rcId, $auth['school_id'], $auth['user_id']);
                break;
            case 'issued':
                $result = $this->issuerService->issue($rcId, $auth['school_id'], $auth['user_id']);
                break;
            case 'revoked':
                $reason = $data['reason'] ?? '';
                $result = $this->issuerService->revoke($rcId, $auth['school_id'], $auth['user_id'], $reason);
                break;
            default:
                return $this->fail('Invalid status', 400);
        }

        return $this->respond($result);
    }

    // ==================== EMAIL & PDF ====================

    public function sendEmail()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];
        $recipients = $data['recipients'] ?? [];
        $includePdf = $data['include_pdf'] ?? true;

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        $result = $this->emailService->sendEmail($rcId, $recipients, $auth['user_id'], $includePdf);

        return $this->respond($result);
    }

    public function bulkEmail()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcIds = $data['rc_ids'] ?? [];
        $includePdf = $data['include_pdf'] ?? true;

        $result = $this->emailService->bulkSend($rcIds, $auth['user_id'], $includePdf);

        return $this->respond($result);
    }

    public function downloadPdf()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $rcId = $this->request->getGet('rc_id');
        $expires = $this->request->getGet('expires');
        $signature = $this->request->getGet('signature');

        // Verify signed URL
        $reportCard = $this->reportCardModel->find($rcId);
        if (!$reportCard) {
            return $this->fail('Report card not found', 404);
        }

        $filename = "report_card_{$reportCard['student_id']}.pdf";

        if (!$this->pdfService->verifySignedUrl($rcId, $filename, $expires, $signature)) {
            return $this->fail('Invalid or expired download link', 403);
        }

        // Generate PDF
        $result = $this->pdfService->generate($rcId);

        if ($result['IsSuccess']) {
            $path = $result['Data']['path'];
            return $this->response->download($path, null)->setFileName($filename);
        }

        return $this->fail('PDF generation failed', 500);
    }

    // ==================== STUDENT PORTAL ====================

    public function studentReportCards()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $studentId = $data['student_id'] ?? $auth['user_id'];

        // Verify student can only view their own cards
        if ($auth['role_id'] == 5 && $studentId != $auth['user_id']) {
            return $this->fail('Unauthorized', 403);
        }

        $reportCards = $this->reportCardModel->getStudentReportCards($studentId, $auth['school_id'], 'issued');

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $reportCards,
            'Message' => 'Report cards retrieved successfully',
        ]);
    }

    public function viewReportCard()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        // Check student access
        if ($auth['role_id'] == 5 && $reportCard['student_id'] != $auth['user_id']) {
            return $this->fail('Unauthorized', 403);
        }

        // Log portal view
        $this->auditService->logPortalView($rcId, $auth['user_id']);

        // Get latest version
        $version = $this->versionModel->getLatestVersion($rcId);

        if ($version) {
            $reportCard['payload'] = json_decode($version['payload_json'], true);
        }

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $reportCard,
            'Message' => 'Report card retrieved successfully',
        ]);
    }

    // ==================== EVENTS & AUDIT ====================

    public function events()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $rcId = $data['rc_id'];

        $reportCard = $this->reportCardModel->find($rcId);

        if (!$reportCard || $reportCard['school_id'] != $auth['school_id']) {
            return $this->fail('Report card not found', 404);
        }

        $events = $this->auditService->getEventTimeline($rcId);

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $events,
            'Message' => 'Events retrieved successfully',
        ]);
    }

    public function analytics()
    {
        $auth = $this->getAuthContext();
        if (!$auth) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->getRequestData();
        $dateFrom = $data['from'] ?? null;
        $dateTo = $data['to'] ?? null;

        $analytics = $this->auditService->getAnalytics($auth['school_id'], $dateFrom, $dateTo);

        return $this->respond([
            'IsSuccess' => true,
            'Data' => $analytics,
            'Message' => 'Analytics retrieved successfully',
        ]);
    }
}
