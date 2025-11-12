<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Common extends ResourceController
{
    protected $commonModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->commonModel = new \App\Models\V1\CommonModel();
    }

    /**
     * Get list of countries (singular alias for countries method)
     */
    public function country(): ResponseInterface
    {
        return $this->countries();
    }

    /**
     * Get list of countries
     */
    public function countries(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $countries = $this->commonModel->getCountries($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $countries,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of states
     */
    public function states(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $states = $this->commonModel->getStates($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $states,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of cities
     */
    public function cities(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $cities = $this->commonModel->getCities($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $cities,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of tags
     */
    public function tagsList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $tags = $this->commonModel->getTagsList($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $tags,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle file uploads (images, PDFs, etc.)
     * Base64 encoded file upload handler
     */
    public function fileUpload(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $uploadType = strtolower($params['uploadtype'] ?? '');
            $imagePaths = $params['image_path'] ?? [];
            
            if (empty($imagePaths)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'No files to upload'
                ]);
            }

            $uploadedImages = [];
            
            // Define upload paths based on type
            $basePath = 'uploads/';
            $pathMap = [
                'profile' => 'profile/',
                'school' => 'school/',
                'teacher' => 'teacher/',
                'student' => 'student/',
                'contentcreator' => 'contentcreator/',
                'content' => 'content/',
                'pdf' => 'content/pdf/',
                'roughimage' => 'content/roughimage/',
                'answer' => 'content/studentAnswer/',
                'offlineanswer' => 'content/offlineAnswer/',
                'answerkey' => 'content/answerKey/',
                'teacheranswerkey' => 'content/teacherAnswerKey/',
                'mailbox' => 'mailbox/',
                'course' => 'course/',
                'category' => 'category/',
                'content-category' => 'websiteContent/category/',
                'content-list' => 'websiteContent/content/'
            ];
            
            $path = $basePath . ($pathMap[$uploadType] ?? 'misc/');
            
            // Create directory if it doesn't exist
            $fullPath = FCPATH . $path;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // Process each file
            foreach ($imagePaths as $i => $fileData) {
                $filename = bin2hex(random_bytes(7));
                $base64 = $fileData['image'] ?? '';
                $type = $fileData['type'] ?? '';
                $originalName = $fileData['name'] ?? 'file';
                $size = $fileData['size'] ?? 0;
                
                // Extract extension from type
                $extensionArray = explode('/', $type);
                $extension = $extensionArray[1] ?? 'jpg';
                
                // Handle common extension variations
                if ($extension == 'jpeg') {
                    $extension = 'jpg';
                }
                
                // Validate extension
                $allowedExtensions = ['png', 'jpg', 'jpeg', 'pdf', 'doc', 'docx', 'xlsx', 'gif', 'webp'];
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    continue; // Skip invalid files
                }
                
                $filePath = $path . $filename . "." . $extension;
                
                // Decode and save base64 file
                $decodedData = base64_decode($base64, true);
                
                if ($decodedData === false) {
                    continue; // Skip if decode fails
                }
                
                $fullFilePath = FCPATH . $filePath;
                
                if (file_put_contents($fullFilePath, $decodedData) !== false) {
                    $uploadedImages[$i]['original_image_url'] = $filePath;
                    $uploadedImages[$i]['image'] = $originalName;
                    $uploadedImages[$i]['size'] = $size;
                    $uploadedImages[$i]['type'] = $type;
                    $uploadedImages[$i]['original_name'] = $originalName;
                    $uploadedImages[$i]['extension'] = $extension;
                    
                    // Set file permissions
                    chmod($fullFilePath, 0644);
                } else {
                    // File write failed
                    continue;
                }
            }
            
            if (count($uploadedImages) > 0) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => [
                        'imagepath' => $uploadedImages,
                        'message' => 'Files uploaded successfully'
                    ],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to upload files'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get settings list
     */
    public function settingList(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            $settingList = $this->commonModel->settingList($params);
            
            // Process settings
            for ($a = 0; $a < count($settingList); $a++) {
                $settingList[$a]['date'] = '';
                if ($settingList[$a]['name'] == 'date_format') {
                    $dateFormat = $this->commonModel->dateFormat($settingList[$a]['value']);
                    $settingList[$a]['date'] = $dateFormat != '' ? $dateFormat[0]['date_format'] : '';
                }
                if(isset($settingList[$a]['name']) && $settingList[$a]['name'] == 'zoom_user_email') {
                    $settingList[$a]['value'] = explode(',', $settingList[$a]['value']);
                }
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $settingList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate availability slots for overlap.
     */
    public function availabilityTimeCheck(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON(true) ?? []);

            $selectedSlots = $payload['selected_availabilityDate'] ?? [];
            $previousSlots = $payload['previous_availabilityDate'] ?? [];

            if (!is_array($selectedSlots)) {
                $selectedSlots = [];
            }
            if (!is_array($previousSlots)) {
                $previousSlots = [];
            }

            $conflicts = [];

            if (!empty($selectedSlots) && !empty($previousSlots)) {
                foreach ($previousSlots as $previous) {
                    foreach ($selectedSlots as $selected) {
                        if (($previous['slotday'] ?? null) === ($selected['slotday'] ?? null)) {
                            $prevStart = strtotime((string) ($previous['slotstarttime'] ?? ''));
                            $prevEnd = strtotime((string) ($previous['slotendtime'] ?? ''));
                            $selStart = strtotime((string) ($selected['slotstarttime'] ?? ''));
                            $selEnd = strtotime((string) ($selected['slotendtime'] ?? ''));

                            if ($prevStart === false || $prevEnd === false || $selStart === false || $selEnd === false) {
                                continue;
                            }

                            $overlaps =
                                ($prevStart <= $selStart && $prevEnd >= $selStart) ||
                                ($prevStart <= $selEnd && $prevEnd >= $selEnd) ||
                                ($prevStart >= $selStart && $prevEnd <= $selEnd);

                            if ($overlaps) {
                                $conflicts[] = sprintf(
                                    '%s - %s for %s',
                                    $selected['slotstarttime'] ?? '',
                                    $selected['slotendtime'] ?? '',
                                    $this->getDayName((int) ($selected['slotday'] ?? 0))
                                );
                            }
                        }
                    }
                }
            }

            if (empty($conflicts)) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Time Slot Added Successfully',
                    'ErrorObject' => ''
                ]);
            }

            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => 'Schedule for ' . implode(', ', $conflicts) . ' - Slot time already exists',
                'ErrorObject' => ''
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    private function getDayName(int $dayNumber): string
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        return $days[$dayNumber - 1] ?? 'Day ' . $dayNumber;
    }
}

