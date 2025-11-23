<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SelfRegistrationModel;
use App\Models\SchoolRegistrationAttributeConfigModel;
use App\Models\V1\CommonModel;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

class SelfRegistration extends BaseController
{
    use RestTrait;

    protected SelfRegistrationModel $selfRegistrationModel;
    protected CommonModel $commonModel;
    protected SchoolRegistrationAttributeConfigModel $attributeConfigModel;

    public function __construct()
    {
        $this->selfRegistrationModel = new SelfRegistrationModel();
        $this->commonModel = new CommonModel();
        $this->attributeConfigModel = new SchoolRegistrationAttributeConfigModel();
    }

    /**
     * Return branding/configuration for a school portal by key or domain.
     */
    public function config(string $identifier = ''): ResponseInterface
    {
        if (empty($identifier)) {
            return $this->errorResponse('School identifier is required', 400);
        }

        $normalizedIdentifier = $this->normalizeIdentifier($identifier);
        $school = $this->selfRegistrationModel->getSchoolByIdentifier($normalizedIdentifier);

        if (empty($school)) {
            return $this->errorResponse('School not found or portal disabled', 404);
        }

        if ((int) ($school['portal_enabled'] ?? 1) !== 1) {
            return $this->errorResponse('School portal is currently disabled', 403);
        }

        $courses = $this->selfRegistrationModel->getActiveCourses((int) $school['school_id']);

        // Load registration attributes from school_registration_attribute_configs
        $registrationAttributes = $this->resolveRegistrationAttributesForSchool((int) $school['school_id']);
        $attributePayload = $this->buildAttributePayload($registrationAttributes);

        $response = [
            'school' => [
                'id' => (int) $school['school_id'],
                'name' => $school['school_name'],
                'key' => $school['school_key'],
                'portal_domain' => $school['portal_domain'],
                'logo' => $school['profile_url'] ?? '',
                'logo_thumb' => $school['profile_thumb_url'] ?? '',
                'contact_email' => $school['portal_contact_email'] ?? '',
                'contact_phone' => $school['portal_contact_phone'] ?? '',
                'support_email' => $school['support_email'] ?? '',
                'support_phone' => $school['support_phone'] ?? '',
                'institution_type' => isset($school['institution_type']) ? (int) $school['institution_type'] : null,
                'address' => [
                    'line1' => $school['address1'] ?? '',
                    'line2' => $school['address2'] ?? '',
                    'city' => $school['city'] ?? '',
                    'state' => $school['state'] ?? '',
                    'country' => $school['country'] ?? '',
                    'postal_code' => $school['postal_code'] ?? ''
                ],
                'branding' => [
                    'primary_color' => $school['primary_color'] ?? '#0056B8',
                    'secondary_color' => $school['secondary_color'] ?? '#0A2540',
                    'accent_color' => $school['accent_color'] ?? '#FFB100',
                    'hero_title' => $school['hero_title'] ?? '',
                    'hero_subtitle' => $school['hero_subtitle'] ?? ''
                ],
                'policies' => [
                    'terms_url' => $school['terms_url'] ?? '',
                    'privacy_url' => $school['privacy_url'] ?? ''
                ],
                'options' => $school['options'] ?? [],
                'registration_attributes' => $registrationAttributes,
                'portal' => [
                    'registration_attributes' => $registrationAttributes
                ],
                'portal_settings' => [
                    'registration_attributes' => $registrationAttributes,
                    'options' => [
                        'registration_attributes' => $registrationAttributes,
                        'attribute_sections' => $registrationAttributes,
                        'sections' => $registrationAttributes
                    ]
                ]
            ],
            'courses' => $courses,
            'registration_attributes' => $registrationAttributes,
            'registration_attribute_sections' => $registrationAttributes,
            'attribute_sections' => $registrationAttributes,
            'attribute_config' => $attributePayload,
            'registration_field_config' => $attributePayload
        ];

        return $this->successResponse($response, 'Portal configuration loaded');
    }

    /**
     * Attempt to match an existing student by email for a given school portal.
     */
    public function lookup(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $identifier = $this->resolveIdentifierFromRequest($payload);

        if (empty($identifier)) {
            return $this->errorResponse('School key is required');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->errorResponse('Valid email is required');
        }

        $school = $this->selfRegistrationModel->getSchoolByIdentifier($identifier);
        if (empty($school)) {
            return $this->errorResponse('School not found', 404);
        }

        if ((int) ($school['portal_enabled'] ?? 1) !== 1) {
            return $this->errorResponse('School portal is currently disabled', 403);
        }

        $profile = $this->selfRegistrationModel->findExistingStudentProfile($email, (int) $school['school_id']);

        if (!$profile) {
            return $this->successResponse([
                'match_found' => false,
                'student' => null,
                'message' => 'No student or registration record found for this email.'
            ], 'Lookup complete');
        }

        $recordType = $profile['record_type'] ?? 'student';
        $message = $recordType === 'lead'
            ? 'We found an existing enrollment request linked to this email. You can continue or schedule a meeting.'
            : 'We found an existing student profile linked to this email.';

        return $this->successResponse([
            'match_found' => true,
            'student' => $profile,
            'message' => $message
        ], 'Lookup complete');
    }

    private function resolveIdentifierFromRequest(array $payload): string
    {
        $candidates = [
            $payload['school_key'] ?? null,
            $payload['school'] ?? null,
            $payload['portal'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeIdentifier((string) $candidate);
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        $origin = $this->request->getHeaderLine('Origin');
        if (!empty($origin)) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            $normalized = $this->normalizeIdentifier((string) $originHost);
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        $hostHeader = $this->request->getServer('HTTP_HOST');
        if (!empty($hostHeader)) {
            // Strip port if present (e.g., school.localhost:8211)
            $host = explode(':', $hostHeader)[0];
            $normalized = $this->normalizeIdentifier($host);
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        return '';
    }

    /**
     * Accept a self-registration submission.
     */
    public function submit(): ResponseInterface
    {
        // Check request size early to provide better error messages
        $contentLength = (int) ($this->request->getServer('CONTENT_LENGTH') ?? 0);
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        
        if ($contentLength > 0 && $contentLength > $postMaxSize) {
            return $this->errorResponse(
                sprintf(
                    'Request payload is too large (%s). Maximum allowed size is %s. Please reduce document file sizes or upload fewer documents.',
                    $this->formatBytes($contentLength),
                    $this->formatBytes($postMaxSize)
                ),
                413
            );
        }

        $payload = $this->request->getJSON(true) ?? [];

        if (empty($payload)) {
            // Check if this might be a size issue
            if ($contentLength > 0) {
                return $this->errorResponse(
                    'Request payload is too large or invalid. Please reduce document file sizes.',
                    413
                );
            }
            return $this->errorResponse('Invalid payload');
        }

        $identifier = $this->normalizeIdentifier($payload['school_key'] ?? $payload['portal'] ?? '');
        if (empty($identifier)) {
            return $this->errorResponse('School key or portal identifier is required');
        }

        $school = $this->selfRegistrationModel->getSchoolByIdentifier($identifier);
        if (empty($school)) {
            return $this->errorResponse('School not found', 404);
        }

        if ((int) ($school['portal_enabled'] ?? 1) !== 1) {
            return $this->errorResponse('School portal is currently disabled', 403);
        }

        $student = $payload['student'] ?? [];
        $payment = $payload['payment'] ?? [];
        $guardian = $payload['guardian'] ?? [];
        $courses = $payload['courses'] ?? [];
        $documents = $payload['documents'] ?? [];

        // Validate document sizes before processing
        $documentSizeErrors = $this->validateDocumentSizes($documents);
        if (!empty($documentSizeErrors)) {
            return $this->errorResponse(implode(' ', $documentSizeErrors), 413);
        }

        $institutionType = isset($school['institution_type']) ? (int) $school['institution_type'] : null;
        $validationErrors = $this->validateSubmission($student, $payment, $guardian, $institutionType);
        if (!empty($validationErrors)) {
            return $this->errorResponse(implode(', ', $validationErrors));
        }

        try {
            $registrationCode = $this->generateRegistrationCode();
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::submit - Unable to generate code: ' . $e->getMessage());
            return $this->errorResponse('Unable to process registration at this time', 500);
        }

        $registrationData = [
            'school_id' => (int) $school['school_id'],
            'school_key' => $school['school_key'],
            'registration_code' => $registrationCode,
            'student_first_name' => $student['first_name'],
            'student_last_name' => $student['last_name'],
            'date_of_birth' => $student['date_of_birth'] ?? null,
            'email' => strtolower($student['email']),
            'mobile' => $this->normalizePhone($student['mobile']),
            'address_line1' => $student['address']['line1'] ?? null,
            'address_line2' => $student['address']['line2'] ?? null,
            'city' => $student['address']['city'] ?? null,
            'state' => $student['address']['state'] ?? null,
            'postal_code' => $student['address']['postal_code'] ?? null,
            'country' => $student['address']['country'] ?? null,
            'is_minor' => !empty($student['is_minor']) ? 1 : 0,
            'guardian1_name' => $guardian['primary']['name'] ?? null,
            'guardian1_email' => isset($guardian['primary']['email']) ? strtolower($guardian['primary']['email']) : null,
            'guardian1_phone' => isset($guardian['primary']['phone']) ? $this->normalizePhone($guardian['primary']['phone']) : null,
            'guardian2_name' => $guardian['secondary']['name'] ?? null,
            'guardian2_email' => isset($guardian['secondary']['email']) ? strtolower($guardian['secondary']['email']) : null,
            'guardian2_phone' => isset($guardian['secondary']['phone']) ? $this->normalizePhone($guardian['secondary']['phone']) : null,
            'schedule_preference' => $payload['schedule_preference'] ?? null,
            'payment_method' => $payment['method'] ?? 'pending',
            'autopay_authorized' => !empty($payment['autopay']) ? 1 : 0,
            'payment_reference' => $payment['reference'] ?? null,
            'status' => 'pending',
            'metadata' => json_encode(array_merge(
                $payload['metadata'] ?? [],
                [
                    'termsAccepted' => !empty($payload['agreements']['terms']),
                    'privacyAccepted' => !empty($payload['agreements']['privacy']),
                    'utm' => $payload['utm'] ?? null,
                    'browser' => $payload['context']['userAgent'] ?? null,
                    'submittedFrom' => $payload['context']['host'] ?? null
                ]
            )),
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'last_status_at' => date('Y-m-d H:i:s')
        ];

        $courseRows = $this->mapCourseSelections($courses);
        $documentRows = $this->storeDocuments($documents, $registrationCode);

        try {
            $record = $this->selfRegistrationModel->createRegistration($registrationData, $courseRows, $documentRows);
        } catch (\Throwable $e) {
            log_message('error', 'SelfRegistration::submit - Save failed: ' . $e->getMessage());
            return $this->errorResponse('Unable to save registration');
        }

        $this->dispatchNotifications($record, $school, $courses);

        return $this->successResponse([
            'registration_code' => $registrationCode,
            'status' => 'pending'
        ], 'Registration received');
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $identifier = strtolower(trim($identifier));

        if (empty($identifier)) {
            return '';
        }

        // Convert hostnames to subdomain keys (e.g., school.edquill.com -> school)
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            $tld = strtolower(end($parts));
            $knownLocalHosts = ['localhost', 'local', 'test', 'dev'];

            if (count($parts) > 2 || in_array($tld, $knownLocalHosts, true)) {
                return $parts[0];
            }
        }

        return $identifier;
    }

    /**
     * Validate core requirements for a submission.
     */
    private function validateSubmission(array $student, array $payment, array $guardian, ?int $institutionType = null): array
    {
        $errors = [];

        if (empty($student['first_name'])) {
            $errors[] = 'First name is required';
        }
        if (empty($student['last_name'])) {
            $errors[] = 'Last name is required';
        }
        if (empty($student['email']) || !filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid student email is required';
        }
        if (empty($student['mobile'])) {
            $errors[] = 'Mobile number is required';
        }

        // Skip guardian validation for Adult coaching centers (institution_type = 6)
        $requiresGuardian = ($institutionType !== 6);
        
        $isMinor = !empty($student['is_minor']);
        if ($isMinor && $requiresGuardian) {
            $primaryGuardian = $guardian['primary'] ?? [];
            if (empty($primaryGuardian['name'])) {
                $errors[] = 'Primary guardian name is required for minors';
            }

            if (empty($primaryGuardian['email']) || !filter_var($primaryGuardian['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid primary guardian email is required for minors';
            }

            if (empty($primaryGuardian['phone'])) {
                $errors[] = 'Primary guardian phone is required for minors';
            }
        }

        if (!empty($payment['method']) && !in_array($payment['method'], ['card', 'ach', 'cash', 'check', 'waived', 'pending'], true)) {
            $errors[] = 'Payment method is invalid';
        }

        return $errors;
    }

    /**
     * Normalize phone numbers by removing non-numeric characters.
     */
    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        return $digits ?: null;
    }

    /**
     * Map course selections into rows for persistence.
     */
    private function mapCourseSelections(array $courses): array
    {
        $rows = [];
        foreach ($courses as $course) {
            if (empty($course)) {
                continue;
            }

            // Ensure schedule_id is properly converted to integer or null
            $scheduleId = null;
            if (isset($course['schedule_id'])) {
                $scheduleIdValue = $course['schedule_id'];
                if ($scheduleIdValue !== null && $scheduleIdValue !== '' && $scheduleIdValue !== '0') {
                    $scheduleId = (int) $scheduleIdValue;
                    // If conversion results in 0, it's invalid
                    if ($scheduleId === 0) {
                        $scheduleId = null;
                    }
                }
            }

            // Debug logging
            log_message('debug', sprintf(
                'SelfRegistration::mapCourseSelections - course_id=%s, schedule_id=%s (raw=%s), course_name=%s',
                $course['course_id'] ?? 'null',
                $scheduleId !== null ? (string)$scheduleId : 'null',
                json_encode($course['schedule_id'] ?? null),
                $course['course_name'] ?? 'null'
            ));

            $rows[] = [
                'course_id' => $course['course_id'] ?? null,
                'schedule_id' => $scheduleId,
                'course_name' => $course['course_name'] ?? null,
                'schedule_title' => $course['schedule_title'] ?? null,
                'fee_amount' => isset($course['fee_amount']) ? $course['fee_amount'] : null
            ];
        }

        return $rows;
    }

    /**
     * Decode and persist uploaded documents (base64) to disk.
     */
    private function storeDocuments(array $documents, string $registrationCode): array
    {
        if (empty($documents)) {
            return [];
        }

        $basePath = FCPATH . 'uploads/self-registration/' . $registrationCode . '/';
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $rows = [];
        foreach ($documents as $document) {
            $content = $document['content'] ?? '';
            $name = $document['name'] ?? 'document';
            $mime = $document['type'] ?? 'application/octet-stream';
            $size = $document['size'] ?? 0;

            if (empty($content) || !str_contains($content, ',')) {
                continue;
            }

            [$meta, $encoded] = explode(',', $content, 2);
            $binary = base64_decode($encoded, true);
            if ($binary === false) {
                continue;
            }

            $extension = $this->guessExtension($meta, $name);
            $fileName = uniqid('doc_', true) . '.' . $extension;
            $fullPath = $basePath . $fileName;

            if (file_put_contents($fullPath, $binary) === false) {
                continue;
            }

            $relativePath = 'uploads/self-registration/' . $registrationCode . '/' . $fileName;
            $rows[] = [
                'storage_path' => $relativePath,
                'original_name' => $name,
                'mime_type' => $mime,
                'file_size' => $size
            ];
        }

        return $rows;
    }

    private function guessExtension(string $meta, string $original): string
    {
        if (preg_match('/data:(.*?);base64/', $meta, $matches)) {
            $mime = $matches[1];
            $map = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];
            if (isset($map[$mime])) {
                return $map[$mime];
            }
        }

        $pathInfo = pathinfo($original);
        return strtolower($pathInfo['extension'] ?? 'dat');
    }

    /**
     * Notify stakeholders via email (best-effort).
     */
    private function dispatchNotifications(array $registration, array $school, array $courses): void
    {
        try {
            $subject = 'New registration received - ' . ($school['school_name'] ?? 'EdQuill');

            $courseLines = [];
            foreach ($courses as $course) {
                $courseLines[] = '- ' . ($course['course_name'] ?? 'Course');
            }
            $courseSummary = !empty($courseLines) ? implode("\n", $courseLines) : '- No course selected';

            $adminBody = sprintf(
                "Hello,\n\nA new registration has been submitted for %s.\n\nStudent: %s %s\nEmail: %s\nPhone: %s\nCourses:\n%s\n\nRegistration Code: %s\n\n-- EdQuill Portal",
                $school['school_name'] ?? 'your school',
                $registration['student_first_name'] ?? '',
                $registration['student_last_name'] ?? '',
                $registration['email'] ?? 'N/A',
                $registration['mobile'] ?? 'N/A',
                $courseSummary,
                $registration['registration_code'] ?? ''
            );

            $adminEmail = $school['portal_contact_email']
                ?? $school['support_email']
                ?? null;

            if ($adminEmail) {
                $this->commonModel->sendEmail($subject, $adminEmail, nl2br($adminBody));
            }

            if (!empty($registration['email'])) {
                $studentBody = sprintf(
                    "Hi %s,\n\nThank you for registering with %s. Our team will review your request and follow up shortly.\n\nRegistration Code: %s\nCourses:\n%s\n\nIf you have any questions, reply to this email.\n\nBest regards,\n%s",
                    $registration['student_first_name'] ?? 'there',
                    $school['school_name'] ?? 'EdQuill',
                    $registration['registration_code'] ?? '',
                    $courseSummary,
                    $school['school_name'] ?? 'EdQuill'
                );
                $this->commonModel->sendEmail(
                    'We received your registration',
                    $registration['email'],
                    nl2br($studentBody)
                );
            }
        } catch (\Throwable $e) {
            log_message('warning', 'SelfRegistration::dispatchNotifications - ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique registration code.
     */
    private function generateRegistrationCode(): string
    {
        $attempts = 0;

        do {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $exists = $this->selfRegistrationModel->findByCode($code);
            $attempts++;
        } while ($exists && $attempts < 10);

        if ($exists) {
            throw new \RuntimeException('Unable to generate unique registration code');
        }

        return $code;
    }

    /**
     * Get all countries
     * GET /self-registration/countries
     */
    public function countries(): ResponseInterface
    {
        $db = \Config\Database::connect();
        
        // Try the countries table first (with country_id, country_name, country_code)
        if ($db->tableExists('countries')) {
            $countries = $db->table('countries')
                ->select('country_id, country_name, country_code')
                ->where('status', 1)
                ->orderBy('country_name', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            // Fallback to country table (with id, name)
            $countries = $db->table('country')
                ->select('id as country_id, name as country_name, "" as country_code')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->successResponse($countries, 'Countries loaded');
    }

    /**
     * Get states for a country
     * GET /self-registration/states/{countryId}
     */
    public function states($countryId = null): ResponseInterface
    {
        if (!$countryId) {
            return $this->errorResponse('Country ID is required', 400);
        }

        $db = \Config\Database::connect();
        
        // Try the states table first (with state_id, state_name, country_id)
        if ($db->tableExists('states')) {
            $states = $db->table('states')
                ->select('state_id, state_name, country_id')
                ->where('country_id', $countryId)
                ->where('status', 1)
                ->orderBy('state_name', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            // Fallback to state table (with id, name, country_id)
            $states = $db->table('state')
                ->select('id as state_id, name as state_name, country_id')
                ->where('country_id', $countryId)
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->successResponse($states, 'States loaded');
    }

    /**
     * Parse the stored attribute definition for a school.
     */
    private function resolveRegistrationAttributesForSchool(int $schoolId): array
    {
        $record = $this->attributeConfigModel->where('school_id', $schoolId)->first();
        if (empty($record) || empty($record['definition'])) {
            return [];
        }

        $decoded = json_decode($record['definition'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Build attribute payload structure for frontend compatibility.
     */
    private function buildAttributePayload(array $sections): array
    {
        return [
            'sections' => $sections
        ];
    }

    /**
     * Validate document sizes to prevent payload size issues.
     * 
     * @param array $documents Array of document objects with 'content', 'size', 'name'
     * @return array Array of error messages (empty if validation passes)
     */
    private function validateDocumentSizes(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        $errors = [];
        // Base64 encoding increases size by ~33%, so we need to account for that
        // Get server's actual post_max_size to set realistic limits
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        // Reserve 200KB for JSON structure and other form data
        $reservedSize = 200 * 1024;
        $availableSize = max($postMaxSize - $reservedSize, 5 * 1024 * 1024); // At least 5MB
        
        // Limit original file size to account for base64 overhead
        // Base64 increases size by ~33%, so we can accept files up to ~75% of available size
        $maxDocumentSize = (int) ($availableSize * 0.75); // ~75% to account for base64 overhead
        $maxTotalSize = (int) ($availableSize * 0.75); // Same for total
        $totalSize = 0;
        $totalEncodedSize = 0; // Track encoded size for payload estimation

        foreach ($documents as $index => $document) {
            $documentSize = isset($document['size']) ? (int) $document['size'] : 0;
            $documentName = $document['name'] ?? "Document " . ($index + 1);
            $encodedSize = 0;
            
            // Calculate both original and encoded sizes
            if (!empty($document['content'])) {
                $content = $document['content'];
                $encodedSize = strlen($content); // Base64 encoded size in JSON
                
                if (str_contains($content, ',')) {
                    [, $encoded] = explode(',', $content, 2);
                    $encodedSize = strlen($encoded); // Just the base64 part
                    
                    // Decode to get actual file size
                    $decoded = base64_decode($encoded, true);
                    if ($decoded !== false) {
                        $documentSize = strlen($decoded);
                    } else {
                        // Estimate: base64 is ~4/3 of original size, so original is ~3/4 of encoded
                        $documentSize = (int) ($encodedSize * 0.75);
                    }
                }
            }

            // Check original file size limit
            if ($documentSize > $maxDocumentSize) {
                $encodedEstimate = (int) ($documentSize * 1.33); // Base64 overhead
                $errors[] = sprintf(
                    'Document "%s" is too large (%s). When encoded, this becomes approximately %s, which exceeds the server limit of %s. Maximum file size per document is %s. Please compress the file (reduce image quality or use PDF compression) or use a smaller file.',
                    $documentName,
                    $this->formatBytes($documentSize),
                    $this->formatBytes($encodedEstimate),
                    $this->formatBytes($postMaxSize),
                    $this->formatBytes($maxDocumentSize)
                );
            }

            $totalSize += $documentSize;
            $totalEncodedSize += $encodedSize;
        }

        // Check total original file size
        if ($totalSize > $maxTotalSize) {
            $errors[] = sprintf(
                'Total size of all documents (%s) exceeds the maximum allowed (%s). Please reduce file sizes or upload fewer documents.',
                $this->formatBytes($totalSize),
                $this->formatBytes($maxTotalSize)
            );
        }

        // Also check if encoded size (what's actually sent) would exceed post_max_size
        // Add 200KB buffer for other JSON data (student info, courses, etc.)
        $estimatedPayloadSize = $totalEncodedSize + $reservedSize;
        
        if ($estimatedPayloadSize > $postMaxSize) {
            $errors[] = sprintf(
                'The uploaded documents are too large when encoded (%s estimated payload). Server limit is %s. Please reduce file sizes or compress images before uploading.',
                $this->formatBytes($estimatedPayloadSize),
                $this->formatBytes($postMaxSize)
            );
        }

        return $errors;
    }

    /**
     * Parse PHP ini size string (e.g., "10M", "512K") to bytes.
     * 
     * @param string $size Size string from ini_get
     * @return int Size in bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $value = (int) $size;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // fall through
            case 'm':
                $value *= 1024;
                // fall through
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Format bytes to human-readable string.
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
