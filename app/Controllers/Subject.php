<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Subject extends ResourceController
{
    protected $subjectModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->subjectModel = new \App\Models\V1\SubjectModel();
    }

    /**
     * Get list of subjects
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $subjects = $this->subjectModel->getSubjects($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $subjects,
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
}
