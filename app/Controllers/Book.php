<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Book extends ResourceController
{
    protected $jsonarr = [];
    protected $format = 'json';
    protected $bookModel;
    protected $commonModel;
    protected $contentModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->bookModel = new \App\Models\V1\BookModel();
        $this->commonModel = new \App\Models\V1\CommonModel();
        $this->contentModel = new \App\Models\V1\ContentModel();
    }

    /**
     * Add a new book
     */
    public function add(): ResponseInterface
    {
        try {
            $params = $this->request->getPost();
            
            if (empty($params)) {
                $params = $this->request->getJSON(true) ?? [];
            }

            // Validation
            if (empty($params['platform']) || !in_array($params['platform'], ['web', 'ios'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['status'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Status should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'School id should not be empty'
                ]);
            }

            // Check if it's multiple file upload (type = 2)
            if (isset($params['type']) && $params['type'] == 2) {
                $data = [];
                $z = 0;
                $content_ids = [];
                $existsData = [];
                
                if (!isset($params['file_path']) || !is_array($params['file_path'])) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'File path should not be empty'
                    ]);
                }

                foreach ($params['file_path'] as $filePath) {
                    $bookData = [];
                    $bookData['publication_code'] = $params['publication_code'] ?? '';
                    
                    $name = $filePath['image'] ?? '';
                    $newName = substr($name, 0, (strrpos($name, ".")));
                    $bookData['name'] = $newName;
                    $bookData['description'] = $params['description'] ?? '';
                    $bookData['grade'] = implode(',', $params['grade'] ?? []);
                    $bookData['subject'] = implode(',', $params['subject'] ?? []);
                    $bookData['school_id'] = $params['school_id'];
                    $bookData['file_path'] = json_encode([$filePath]);
                    
                    if ($params['role_id'] == 6) {
                        $bookData['corporate_id'] = $params['corporate_id'] ?? 0;
                    }
                    
                    $bookData['answerkey_path'] = isset($params['answerkey_path']) && !empty($params['answerkey_path']) 
                        ? json_encode($params['answerkey_path']) 
                        : '';
                    
                    $bookData['content_type'] = $filePath['resource_type'] ?? '1';
                    $bookData['content_format'] = 1;
                    $bookData['download'] = $params['download'] ?? 0;
                    
                    if (isset($params['other_links']) && count($params['other_links']) > 0) {
                        $bookData['links'] = json_encode($params['other_links']);
                    } else {
                        $bookData['links'] = '';
                    }
                    
                    $bookData['access'] = $params['access'] ?? 1;
                    $bookData['status'] = $params['status'];
                    $bookData['created_by'] = $params['user_id'];
                    $bookData['created_date'] = date('Y-m-d H:i:s');
                    
                    // Check if book exists
                    $checkBook = $this->bookModel->checkBook($bookData);
                    if (count($checkBook) == 0) {
                        $db = \Config\Database::connect();
                        $insertId = $db->table('content')->insert($bookData);
                        if ($insertId) {
                            $content_ids[] = $db->insertID();
                        }
                    } else {
                        if ($checkBook[0]['status'] != 1) {
                            $db = \Config\Database::connect();
                            $db->table('content')
                               ->where('content_id', $checkBook[0]['content_id'])
                               ->update(['status' => 1]);
                            $content_ids[] = $checkBook[0]['content_id'];
                        } else {
                            $existsData[] = $bookData['name'];
                        }
                    }
                }

                // Insert into classroom_content if batch_id is provided
                if (isset($params['batch_id']) && !empty($params['batch_id'])) {
                    $classroomContent = [];
                    foreach ($params['batch_id'] as $batchId) {
                        foreach ($content_ids as $contentId) {
                            $batchContentExists = $this->contentModel->checkBatchContent($batchId, $contentId);
                            if (count($batchContentExists) == 0) {
                                $classroomContent[] = [
                                    'batch_id' => $batchId,
                                    'school_id' => $params['school_id'],
                                    'content_id' => $contentId,
                                    'status' => 1,
                                    'created_by' => $params['user_id'],
                                    'created_date' => date('Y-m-d H:i:s')
                                ];
                            }
                        }
                    }
                    
                    if (count($classroomContent) > 0) {
                        $db = \Config\Database::connect();
                        $db->table('classroom_content')->insertBatch($classroomContent);
                    }
                }

                // Prepare response
                $implode = implode(',', $existsData);
                if (count($content_ids) > 0) {
                    $msg = '';
                    if ($implode != '') {
                        $msg = " and $implode already Exists";
                    }
                    
                    // Get content details
                    $db = \Config\Database::connect();
                    $contentDetails = $db->table('content')
                                        ->whereIn('content_id', $content_ids)
                                        ->get()
                                        ->getResultArray();
                    
                    return $this->respond([
                        'IsSuccess' => true,
                        'ResponseObject' => "Book added Successfully$msg",
                        'content' => $contentDetails,
                        'ErrorObject' => ''
                    ]);
                } else {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Book already Exists'
                    ]);
                }
            } else {
                // Single book upload (type = 1)
                if (empty($params['name'])) {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Name should not be empty'
                    ]);
                }

                $data = [];
                $data['publication_code'] = $params['publication_code'] ?? '';
                $data['name'] = $params['name'];
                $data['description'] = $params['description'] ?? '';
                $data['grade'] = implode(',', $params['grade'] ?? []);
                $data['subject'] = implode(',', $params['subject'] ?? []);
                $data['school_id'] = $params['school_id'];
                $data['file_path'] = isset($params['file_path']) && !empty($params['file_path']) 
                    ? json_encode($params['file_path']) 
                    : '';
                
                if ($params['role_id'] == 6) {
                    $data['corporate_id'] = $params['corporate_id'] ?? 0;
                }
                
                $data['answerkey_path'] = isset($params['answerkey_path']) && !empty($params['answerkey_path']) 
                    ? json_encode($params['answerkey_path']) 
                    : '';
                
                $data['content_type'] = $params['content_type'] ?? '1';
                $data['content_format'] = 1;
                $data['download'] = $params['download'] ?? 0;
                
                if (isset($params['other_links']) && count($params['other_links']) > 0) {
                    $data['links'] = json_encode($params['other_links']);
                } else {
                    $data['links'] = '';
                }
                
                $data['access'] = $params['access'] ?? 1;
                $data['status'] = $params['status'];
                $data['created_by'] = $params['user_id'];
                $data['created_date'] = date('Y-m-d H:i:s');

                // Check if book exists
                $checkBook = $this->bookModel->checkBook($data);
                if (count($checkBook) == 0) {
                    $db = \Config\Database::connect();
                    $db->table('content')->insert($data);
                    $insertId = $db->insertID();
                    
                    if ($insertId > 0) {
                        return $this->respond([
                            'IsSuccess' => true,
                            'ResponseObject' => 'Book added Successfully',
                            'ErrorObject' => ''
                        ]);
                    } else {
                        return $this->respond([
                            'IsSuccess' => false,
                            'ResponseObject' => null,
                            'ErrorObject' => 'Book not added'
                        ]);
                    }
                } else {
                    return $this->respond([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'Book already Exists'
                    ]);
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Book add error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}
