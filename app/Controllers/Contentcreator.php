<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Contentcreator extends ResourceController
{
    protected $contentCreatorModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->contentCreatorModel = new \App\Models\V1\ContentCreatorModel();
    }

    /**
     * Get list of content creators
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $creators = $this->contentCreatorModel->getContentCreators($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $creators,
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
