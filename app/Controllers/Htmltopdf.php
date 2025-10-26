<?php
defined('BASEPATH') OR exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';
require 'vendor/autoload.php';

class Htmltopdf extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        $this->load->model("book_model");
        $this->load->model("common_model");

        header("Access-Control-Allow-Origin: *");
        $this->controller = uri_string();
        $urlAuth = $this->verifyAuthUrl();
        $headers = $this->input->request_headers();
        if ($urlAuth) {
            $excludeurl = $this->excludefunction();
            if ($excludeurl != 'true') {
                if (isset($headers['Accesstoken'])) {
                    $this->output->set_status_header(200);
                    $headers['Accesstoken'];
                } else {
                    $this->jsonarr['ErrorObject'] = "Unauthorized User";
                    $this->jsonarr['IsSuccess'] = false;
                    $this->printjson($this->jsonarr);
                    $this->output->set_status_header(401);
                    exit();
                }

            } else {
                $this->output->set_status_header(200);
                return true;
            }
        } else {
            $this->output->set_status_header(200);
            $this->jsonarr['ErrorObject'] = "The requested url is not found.";
            $this->jsonarr['IsSuccess'] = false;
            $this->printjson($this->jsonarr);
            exit();
        }
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/htmltopdf/pdf'
        );
        foreach ($this->allowedRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
        return false;
    }

    public function excludefunction()
    {
        $this->excludeRoutes = array(
            'v1/htmltopdf/pdf'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    public function add_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['code'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "code should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "name should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "status should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "school_id should not be empty";
        }
        elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/htmltopdf/add','only request','add');
            $data = [];
                $data['code'] = $params['code'];
                $data['name'] = $params['name'];
                $data['description'] = $params['description'];
                $data['status'] = $params['status'];
                if($params['school_id'] == 0) {
                    $data['school_id'] = 0;
                } else {
                $data['school_id'] = $params['school_id'];
                }
            $checkBook = $this->book_model->checkBook($data['code'],$data['school_id']);
            if(count($checkBook) == 0){
                $insert = $this->common_model->insert('book', $data);
                if($insert > 0) {
                    $this->jsonarr['IsSuccess'] = True;
                    $this->jsonarr['ErrorObject'] = "Book added Successfully";
                } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Book not added";
            }
            }  else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Book already Exists";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/htmltopdf/add',$this->jsonarr,'add');
        return $this->printjson($this->jsonarr);
    }

    public function pdf_post() {
        $this->benchmark->mark('code_start');
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '3000');
        ini_set("pcre.backtrack_limit", "1000000");
        $config = [
            'mode' => '',
            'format' => 'A4',
            'default_font_size' => 0,
            'default_font' => '',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'orientation' => 'P',
        ];
        try {

        $pdf = new \Mpdf\Mpdf($config);
        $pdfFilePath = "../uploads/content/pdf/index1s1.pdf";
        $html = file_get_contents("../uploads/content/html/sample.html");
       // $html =  htmlspecialchars($html);
       // $html =  mysql_real_escape_string($html);
        $pdf->WriteHTML($html);
        $pdf->Output($pdfFilePath,'F');
            $pdf->showImageErrors = true;
       }
        catch (\Mpdf\MpdfException $e) {
            echo $e->getMessage();
        }
        print_r($pdfFilePath);
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

}
