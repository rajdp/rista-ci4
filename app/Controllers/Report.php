<?php
namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class Report extends ResourceController
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $format = 'json';
    protected $report_model;
    protected $common_model;
    protected $classes_model;
    protected $benchmark;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        
        $this->report_model = new \App\Models\V1\ReportModel();
        $this->common_model = new \App\Models\V1\CommonModel();
        $this->benchmark = \Config\Services::timer();

        // Authentication is handled by AuthFilter in CI4
        header("Access-Control-Allow-Origin: *");
        $this->controller = uri_string();
    }

    public function verifyAuthUrl()
    {
        $this->allowedRoutes = array(
            'v1/report/classList',
            'v1/report/studentList',
            'v1/report/assessmentList',
            'v1/report/assessmentReports',
            'v1/report/assessmentReportsNew',
            'v1/report/studentGradeSummary',
            'v1/report/assignmentList',
            'v1/report/assignmentReports',
            'v1/report/assignmentReportsNew',
            'v1/report/studentsContentReport',
            'v1/report/reportCard',
            'v1/report/gradeReport',
            'v1/report/studentPerformanceReport',
            'v1/report/studentReportClass',
            'v1/report/studentReportClasslast',
            'v1/report/classPerformanceList',
            'v1/report/studentPerformanceList',
            'v1/report/studentPerformanceContent',
            'v1/report/reportSendsParent',
            'v1/report/studentWiseClassReport',
            'v1/report/sendAdminReport',
            'v1/report/assignmentClassReports',
            'v1/report/assessmentClassReports',
            'v1/report/reportCardNew',
            'v1/report/studentReportClassPrint'
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
            'v1/report/classList'
        );
        foreach ($this->excludeRoutes as $routeString) {
            if ($this->controller == $routeString) {
                return true;
            }
        }
    }

    private function printjson($jsonarr)
    {
        $this->response->setHeader('Content-Type', 'application/json');
        echo json_encode($jsonarr);
        exit(); // Ensure no further output
    }

    public function classList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/report/classList','only request','classList');
            $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
            $gradeDaysLimit = -$prop['class_report_days'] .' days';
            $params['date_limit'] = Date('Y-m-d', strtotime($gradeDaysLimit));
            $list = $this->report_model->classList($params);
            $this->jsonarr['IsSuccess'] = true;
            if (count($list) > 0) {
                $this->jsonarr['ResponseObject'] = $list;
            } else {
                $this->jsonarr['ResponseObject'] = [];
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/classList',$this->jsonarr,'classList');
        $this->printjson($this->jsonarr);
    }

    public function studentList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentList','only request','studentList');
            $classStudent = $this->report_model->classStudent($params,0);
            $this->jsonarr['IsSuccess'] = true;;
            if (count($classStudent) > 0) {
                $this->jsonarr['ResponseObject'] = $classStudent;
            } else {
                $this->jsonarr['ResponseObject'] = [];
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/studentList',$this->jsonarr,'studentList');
        return $this->printjson($this->jsonarr);
    }

    public function assignmentList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assignmentList','only request','assignmentList');
            $classContent = $this->report_model->classContent($params,1);
            $this->jsonarr['IsSuccess'] = true;
            if (count($classContent) > 0) {
                $this->jsonarr['ResponseObject'] = $classContent;
            } else {
                $this->jsonarr['ResponseObject'] = [];
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/assignmentList',$this->jsonarr,'assignmentList');
        return $this->printjson($this->jsonarr);
    }

    public function assessmentList_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assessmentList','only request','assessmentList');
            $classContent = $this->report_model->classContent($params,2);
            $this->jsonarr['IsSuccess'] = true;;
            if (count($classContent) > 0) {
                $this->jsonarr['ResponseObject'] = $classContent;
            } else {
                $this->jsonarr['ResponseObject'] = [];
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/assessmentList',$this->jsonarr,'assessmentList');
        return $this->printjson($this->jsonarr);
    }

    public function assignmentReportNew_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $headers = $this->input->request_headers();
//        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assignmentReports','only request','assignmentReports');
            $contentList = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            foreach($params['content_id'] as $key => $value) {
                $contentList[$key] = $this->report_model->studentList($value, $params['class_id']);
                if (count($contentList[$key]) > 0) {
                    if ($contentList[$key][0]['points'] != 0) {
                        $totalMarksContent = $contentList[$key][0]['points'];
                    } else {
                        $totalMarksContent = $contentList[$key][0]['total_mark'];
                    }
                    $studentMarks = 0;
                    $totalMarks = 0;
                    $totalGraded = 0;
                    $totalAbsent = 0;
                    $studentPercentage = 0;
                    $marks = [];
                    foreach ($contentList[$key] as $value) {
                        if ($value['points'] != 0) {
                            $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                        } else {
                            $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                        }
                        $i++;
                        if ($value['points'] != 0) {
                            $studentMarks += $value['earned_points'];
                            $totalMarks += $value['points'];
                            $studentPercentage += round(($value['earned_points'] / $value['points']) * 100 , 2);
                        } else {
                            $studentMarks += $value['student_mark'];
                            $totalMarks += $value['total_mark'];
                            $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                        }
                        if ($value['points'] != 0) {
                            array_push($marks, $value['earned_points']);
                        } else {
                            array_push($marks, $value['student_mark']);
                        }
                        if ($value['status'] == 1 || $value['status'] == 2) {
                            $totalAbsent++;
                        } elseif ($value['status'] >= 3) {
                            $totalGraded++;
                        }
                    }
                    if (count($contentList[$key]) > 0) {
                        $contentRecord[$key]['class_id'] = $params['class_id'];
                        $contentRecord[$key]['content_id'] = $contentList[$key][0]['content_id'];
                        $contentRecord[$key]['content_name'] = $contentList[$key][0]['content_name'];
                        $contentRecord[$key]['average_student_score'] = round(count($contentList) != 0 ? ($studentPercentage / count($contentList[$key])) : 0, 2) . "%";
                        $averageStudentPercentage += round(count($contentList[$key]) != 0 ? ($studentPercentage / count($contentList[$key])) : 0, 2);
                        $contentRecord[$key]['assigned'] = count($contentList[$key]);
                        $contentRecord[$key]['graded'] = $totalGraded;
                        $contentRecord[$key]['absent'] = $totalAbsent;
                        $contentRecord[$key]['assignment_date'] = date('m-d-Y', strtotime($contentList[$key][0]['content_date']));
                        $contentRecord[$key]['min_score'] = min($marks) != "" ? min($marks) : 0;
                        $contentRecord[$key]['max_score'] = max($marks) != "" ? max($marks) : 0;
                        $contentRecord[$key]['average_score'] = round($studentPercentage / count($contentList[$key]), 2);
                        $contentRecord[$key]['totalpoint_average_score'] = round($totalMarks / count($contentList[$key]),2);
                        $contentRecord[$key]['totalmark_of_students'] = $studentMarks;
                        $contentRecord[$key]['totalmark_of_assignment'] = $totalMarksContent;
                        $contentRecord[$key]['totalmark_of_allStudentAssignment'] = $totalMarks;
                    }
                } else {
                    {
                        $contentData = $this->report_model->contentData($value,$params);
                        $contentRecord[$key]['class_id'] = $params['class_id'];
                        $contentRecord[$key]['content_id'] = $value;
                        $contentRecord[$key]['content_name'] = $contentData['content_name'];
                        $contentRecord[$key]['average_student_score'] = 0 ."%";
                        $contentRecord[$key]['assigned'] = 0;
                        $contentRecord[$key]['graded'] = 0;
                        $contentRecord[$key]['absent'] = 0;
                        $contentRecord[$key]['assignment_date'] = date('m-d-Y', strtotime($contentData['content_date']));
                        $contentRecord[$key]['min_score'] = 0;
                        $contentRecord[$key]['max_score'] = 0;
                        $contentRecord[$key]['average_score'] = 0;
                        $contentRecord[$key]['totalpoint_average_score'] = 0;
                        $contentRecord[$key]['totalmark_of_students'] = 0;
                        $contentRecord[$key]['totalmark_of_assignment'] = 0;
                        $contentRecord[$key]['totalmark_of_allStudentAssignment'] = 0;
                    }
                }
            }
            $totalStudentAssigned = 0;
            $totalStudentGraded = 0;
            $totalStudentsAbsent = 0;
            $totalAssignmentAverage = 0;
            $totalStudentsAverage = 0;
            $totalAllStudentAssignmentAverage = 0;
            if (count($contentRecord) > 0) {
                foreach ($contentRecord as $key => $value) {
                    $totalStudentAssigned += $value['assigned'];
                    $totalStudentGraded += $value['graded'];
                    $totalStudentsAbsent += $value['absent'];
                    $totalContentStudentAverage += $value['average_score'];
                    $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                    $totalAssignmentAverage += $value['totalmark_of_assignment'];
                    $totalStudentsAverage += $value['totalmark_of_students'];
                    $totalAllStudentAssignmentAverage += $value['totalmark_of_allStudentAssignment'];
                }
                $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                foreach($contentRecord as $key => $value) {
                    unset($contentRecord[$key]['totalmark_of_students']);
                    unset($contentRecord[$key]['totalmark_of_assignment']);
                    unset($contentRecord[$key]['totalmark_of_allStudentAssignment']);
                }
                $totalRecord['contentList'] = array_values($contentRecord);
                $chartValues['Master'] = count(array_keys($percentage, '1'));
                $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                $chartValues['Average'] = count(array_keys($percentage, '4'));
                $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                $totalRecord['chartValues'] = array($chartValues);
            }
            if (count($totalRecord) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $totalRecord;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/assignmentReports',$this->jsonarr,'assignmentReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function assignmentReports_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assignmentReports','only request','assignmentReports');
            $contentList = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            $implode = implode(',',$params['content_id']);

                $contentList1 = $this->report_model->studentList($implode, $params['class_id']);
             for ($z = 0; $z < count($params['content_id']); $z++) {
                 foreach ($contentList1 as $key1 => $value1) {
                     if ($params['content_id'][$z] == $value1['content_id']) {
                         array_push($contentList, $value1);
                     }
                 }

                 if (count($contentList) > 0) {
                         //foreach ($contentList as $key => $value) {
                             if ($contentList[0]['points'] != 0) {
                                 $totalMarksContent = $contentList[0]['points'];
                             } else {
                                 $totalMarksContent = $contentList[0]['total_mark'];
                             }
                         //}
                             $studentMarks = 0;
                             $totalMarks = 0;
                             $totalGraded = 0;
                             $totalAbsent = 0;
                             $studentPercentage = 0;
                             $marks = [];
                             foreach ($contentList as $key1 => $value) {
                             if ($value['points'] != 0) {
                                 $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                             } else {
                                 $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                             }
                             $i++;
                             if ($value['points'] != 0) {
                                 $studentMarks += $value['earned_points'];
                                 $totalMarks += $value['points'];
                                 $studentPercentage += round(($value['earned_points'] / $value['points']) * 100, 2);
                             } else {
                                 $studentMarks += $value['student_mark'];
                                 $totalMarks += $value['total_mark'];
                                 $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                             }
                             if ($value['points'] != 0) {
                                 array_push($marks, $value['earned_points']);
                             } else {
                                 array_push($marks, $value['student_mark']);
                             }
                             if ($value['status'] == 1 || $value['status'] == 2) {
                                 $totalAbsent++;
                             } elseif ($value['status'] >= 3) {
                                 $totalGraded++;
                             }
                             }
                             if (count($contentList) > 0) {
                                 $contentRecord[$z]['class_id'] = $params['class_id'];
                                 $contentRecord[$z]['content_id'] = $value['content_id'];
                                 $contentRecord[$z]['content_name'] = $value['content_name'];
                                 $contentRecord[$z]['average_student_score'] = round(count($contentList) != 0 ? ($studentPercentage / count($contentList)) : 0, 2) . "%";
                                 $averageStudentPercentage += round(count($contentList) != 0 ? ($studentPercentage / count($contentList)) : 0, 2);
                                 $contentRecord[$z]['assigned'] = count($contentList);
                                 $contentRecord[$z]['graded'] = $totalGraded;
                                 $contentRecord[$z]['absent'] = $totalAbsent;
                                 $contentRecord[$z]['assignment_date'] = $value['content_date'];
                                 $contentRecord[$z]['min_score'] = min($marks) != "" ? min($marks) : 0;
                                 $contentRecord[$z]['max_score'] = max($marks) != "" ? max($marks) : 0;
                                 $contentRecord[$z]['average_score'] = round($studentPercentage / count($contentList), 2);
                                 $contentRecord[$z]['totalpoint_average_score'] = round($totalMarks / count($contentList), 2);
                                 $contentRecord[$z]['totalmark_of_students'] = $studentMarks;
                                 $contentRecord[$z]['totalmark_of_assignment'] = $totalMarksContent;
                                 $contentRecord[$z]['totalmark_of_allStudentAssignment'] = $totalMarks;

                             }
                             unset($contentList);
                             $contentList = [];

                     } else {
                         {
                             $contentData = $this->report_model->contentData($params['content_id'][$z], $params);
                             $contentRecord[$z]['class_id'] = $params['class_id'];
                             $contentRecord[$z]['content_id'] = $params['content_id'][$z];
                             $contentRecord[$z]['content_name'] = $contentData['content_name'];
                             $contentRecord[$z]['average_student_score'] = 0 . "%";
                             $contentRecord[$z]['assigned'] = 0;
                             $contentRecord[$z]['graded'] = 0;
                             $contentRecord[$z]['absent'] = 0;
                             $contentRecord[$z]['assignment_date'] = $contentData['content_date'];
                             $contentRecord[$z]['min_score'] = 0;
                             $contentRecord[$z]['max_score'] = 0;
                             $contentRecord[$z]['average_score'] = 0;
                             $contentRecord[$z]['totalpoint_average_score'] = 0;
                             $contentRecord[$z]['totalmark_of_students'] = 0;
                             $contentRecord[$z]['totalmark_of_assignment'] = 0;
                             $contentRecord[$z]['totalmark_of_allStudentAssignment'] = 0;
                         }
                     }
             }
            //print_r($contentRecord);
            $totalStudentAssigned = 0;
            $totalStudentGraded = 0;
            $totalStudentsAbsent = 0;
            $totalAssignmentAverage = 0;
            $totalStudentsAverage = 0;
            $totalAllStudentAssignmentAverage = 0;
            if (count($contentRecord) > 0) {
                foreach ($contentRecord as $key => $value) {
                    $totalStudentAssigned += $value['assigned'];
                    $totalStudentGraded += $value['graded'];
                    $totalStudentsAbsent += $value['absent'];
                    $totalContentStudentAverage += $value['average_score'];
                    $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                    $totalAssignmentAverage += $value['totalmark_of_assignment'];
                    $totalStudentsAverage += $value['totalmark_of_students'];
                    $totalAllStudentAssignmentAverage += $value['totalmark_of_allStudentAssignment'];
                }
                $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                foreach($contentRecord as $key => $value) {
                    unset($contentRecord[$key]['totalmark_of_students']);
                    unset($contentRecord[$key]['totalmark_of_assignment']);
                    unset($contentRecord[$key]['totalmark_of_allStudentAssignment']);
                }
                $totalRecord['contentList'] = array_values($contentRecord);
                $chartValues['Master'] = count(array_keys($percentage, '1'));
                $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                $chartValues['Average'] = count(array_keys($percentage, '4'));
                $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                $totalRecord['chartValues'] = array($chartValues);
            }
            if (count($totalRecord) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $totalRecord;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/assignmentReports',$this->jsonarr,'assignmentReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function assessmentReportsNew_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $headers = $this->input->request_headers();
        //$this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assignmentReports','only request','assignmentReports');
            $contentList = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            $implode = implode(',',$params['content_id']);

            $contentList1 = $this->report_model->studentList($implode, $params['class_id']);
            for ($z = 0; $z < count($params['content_id']); $z++) {
                foreach ($contentList1 as $key1 => $value1) {
                    if ($params['content_id'][$z] == $value1['content_id']) {
                        array_push($contentList, $value1);
                    }
                }

                if (count($contentList) > 0) {
                    //foreach ($contentList as $key => $value) {
                    if ($contentList[0]['points'] != 0) {
                        $totalMarksContent = $contentList[0]['points'];
                    } else {
                        $totalMarksContent = $contentList[0]['total_mark'];
                    }
                    //}
                    $studentMarks = 0;
                    $totalMarks = 0;
                    $totalGraded = 0;
                    $totalAbsent = 0;
                    $studentPercentage = 0;
                    $marks = [];
                    foreach ($contentList as $key1 => $value) {
                        if ($value['points'] != 0) {
                            $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                        } else {
                            $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                        }
                        $i++;
                        if ($value['points'] != 0) {
                            $studentMarks += $value['earned_points'];
                            $totalMarks += $value['points'];
                            $studentPercentage += round(($value['earned_points'] / $value['points']) * 100, 2);
                        } else {
                            $studentMarks += $value['student_mark'];
                            $totalMarks += $value['total_mark'];
                            $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                        }
                        if ($value['points'] != 0) {
                            array_push($marks, $value['earned_points']);
                        } else {
                            array_push($marks, $value['student_mark']);
                        }
                        if ($value['status'] == 1 || $value['status'] == 2) {
                            $totalAbsent++;
                        } elseif ($value['status'] >= 3) {
                            $totalGraded++;
                        }
                    }
                    if (count($contentList) > 0) {
                        $contentRecord[$z]['class_id'] = $params['class_id'];
                        $contentRecord[$z]['content_id'] = $value['content_id'];
                        $contentRecord[$z]['content_name'] = $value['content_name'];
                        $contentRecord[$z]['average_student_score'] = round(count($contentList) != 0 ? ($studentPercentage / count($contentList)) : 0, 2) . "%";
                        $averageStudentPercentage += round(count($contentList) != 0 ? ($studentPercentage / count($contentList)) : 0, 2);
                        $contentRecord[$z]['assigned'] = count($contentList);
                        $contentRecord[$z]['graded'] = $totalGraded;
                        $contentRecord[$z]['absent'] = $totalAbsent;
                        $contentRecord[$z]['assignment_date'] = date('m-d-Y', strtotime($value['content_date']));
                        $contentRecord[$z]['min_score'] = min($marks) != "" ? min($marks) : 0;
                        $contentRecord[$z]['max_score'] = max($marks) != "" ? max($marks) : 0;
                        $contentRecord[$z]['average_score'] = round($studentMarks / count($contentList), 2);
                        $contentRecord[$z]['totalpoint_average_score'] = round($totalMarks / count($contentList), 2);
                        $contentRecord[$z]['totalmark_of_students'] = $studentMarks;
                        $contentRecord[$z]['totalmark_of_assessment'] = $totalMarksContent;
                        $contentRecord[$z]['totalmark_of_allStudentAssessment'] = $totalMarks;

                    }
                    unset($contentList);
                    $contentList = [];

                } else {
                    {
                        $contentData = $this->report_model->contentData($params['content_id'][$z], $params);
                        $contentRecord[$z]['class_id'] = $params['class_id'];
                        $contentRecord[$z]['content_id'] = $params['content_id'][$z];
                        $contentRecord[$z]['content_name'] = $contentData['content_name'];
                        $contentRecord[$z]['average_student_score'] = 0 . "%";
                        $contentRecord[$z]['assigned'] = 0;
                        $contentRecord[$z]['graded'] = 0;
                        $contentRecord[$z]['absent'] = 0;
                        $contentRecord[$z]['assignment_date'] = date('m-d-Y', strtotime($contentData['content_date']));
                        $contentRecord[$z]['min_score'] = 0;
                        $contentRecord[$z]['max_score'] = 0;
                        $contentRecord[$z]['average_score'] = 0;
                        $contentRecord[$z]['totalpoint_average_score'] = 0;
                        $contentRecord[$z]['totalmark_of_students'] = 0;
                        $contentRecord[$z]['totalmark_of_assessment'] = 0;
                        $contentRecord[$z]['totalmark_of_allStudentAssessment'] = 0;
                    }
                }
            }
            //print_r($contentRecord);
            $totalStudentAssigned = 0;
            $totalStudentGraded = 0;
            $totalStudentsAbsent = 0;
            $totalAssignmentAverage = 0;
            $totalStudentsAverage = 0;
            $totalAllStudentAssignmentAverage = 0;
            if (count($contentRecord) > 0) {
                foreach ($contentRecord as $key => $value) {
                    $totalStudentAssigned += $value['assigned'];
                    $totalStudentGraded += $value['graded'];
                    $totalStudentsAbsent += $value['absent'];
                    $totalContentStudentAverage += $value['average_score'];
                    $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                    $totalAssignmentAverage += $value['totalmark_of_assessment'];
                    $totalStudentsAverage += $value['totalmark_of_students'];
                    $totalAllStudentAssignmentAverage += $value['totalmark_of_allStudentAssessment'];
                }
                $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                foreach($contentRecord as $key => $value) {
                    unset($contentRecord[$key]['totalmark_of_students']);
                    unset($contentRecord[$key]['totalmark_of_assessment']);
                    unset($contentRecord[$key]['totalmark_of_allStudentAssessment']);
                }
                $totalRecord['contentList'] = array_values($contentRecord);
                $chartValues['Master'] = count(array_keys($percentage, '1'));
                $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                $chartValues['Average'] = count(array_keys($percentage, '4'));
                $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                $totalRecord['chartValues'] = array($chartValues);
            }
            if (count($totalRecord) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $totalRecord;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/assignmentReports',$this->jsonarr,'assignmentReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }



    public function assignmentClassReports_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $headers = $this->input->request_headers();
//        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assignmentClassReports','only request','assignmentClassReports');
            $contentList1 = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            $contentId = implode(',',$params['content_id']);
            $contentList = $this->report_model->studentList($contentId, $params['class_id']);
            //print_r($contentList); //exit();
            for ($a = 0; $a < count($params['content_id']); $a++) {
                //print_r($params['content_id'][$a]);
                $new = [];
            if (count($contentList) > 0) {
                    foreach ($contentList as $key1 => $value1) {
                        if ($value1['content_id'] == $params['content_id'][$a]) {
                            array_push($new, $value1);
                        }
                    }
                    //print_r($new);

                if ($new) {
                    foreach ($new as $key => $value) {
                        if ($value['points'] != 0) {
                            $totalMarksContent = $value['points'];
                        } else {
                            $totalMarksContent = $value['total_mark'];
                        }
                        $studentMarks = 0;
                        $totalMarks = 0;
                        $totalGraded = 0;
                        $totalAbsent = 0;
                        $studentPercentage = 0;
                        $marks = [];
                        //foreach ($contentList as $value) {
                        if ($value['points'] != 0) {
                            $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                        } else {
                            $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                        }
                        $i++;
                        if ($value['points'] != 0) {
                            $studentMarks += $value['earned_points'];
                            $totalMarks += $value['points'];
                            $studentPercentage += round(($value['earned_points'] / $value['points']) * 100, 2);
                        } else {
                            $studentMarks += $value['student_mark'];
                            $totalMarks += $value['total_mark'];
                            $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                        }
                        if ($value['points'] != 0) {
                            array_push($marks, $value['earned_points']);
                        } else {
                            array_push($marks, $value['student_mark']);
                        }
                        if ($value['status'] == 1 || $value['status'] == 2) {
                            $totalAbsent++;
                        } elseif ($value['status'] >= 3) {
                            $totalGraded++;
                        }
                         }
                        if (count($new) > 0) {
                            $contentRecord[$a]['class_id'] = $params['class_id'];
                            $contentRecord[$a]['content_id'] = $new[$key]['content_id'];
                            $contentRecord[$a]['content_name'] = $new[$key]['content_name'];
                            $contentRecord[$a]['average_student_score'] = round(count($new) != 0 ? ($studentPercentage / count($new)) : 0, 2) . "%";
                            $averageStudentPercentage += round(count($new) != 0 ? ($studentPercentage / count($new)) : 0, 2);
                            $contentRecord[$a]['assigned'] = count($new);
                            $contentRecord[$a]['graded'] = $totalGraded;
                            $contentRecord[$a]['absent'] = $totalAbsent;
                            $contentRecord[$a]['assignment_date'] = date('m-d-Y', strtotime($value['content_date']));
                            $contentRecord[$a]['min_score'] = min($marks) != "" ? min($marks) : 0;
                            $contentRecord[$a]['max_score'] = max($marks) != "" ? max($marks) : 0;
                            $contentRecord[$a]['average_score'] = round($studentPercentage / count($new), 2);
                            $contentRecord[$a]['totalpoint_average_score'] = round($totalMarks / count($new), 2);
                            $contentRecord[$a]['totalmark_of_students'] = $studentMarks;
                            $contentRecord[$a]['totalmark_of_assignment'] = $totalMarksContent;
                            $contentRecord[$a]['totalmark_of_allStudentAssignment'] = $totalMarks;
                        }
                    }

                unset($new);

            } else {
                    {
                        $contentData = $this->report_model->contentData($params['content_id'][$a], $params);
                        $contentRecord[$a]['class_id'] = $params['class_id'];
                        $contentRecord[$a]['content_id'] = $params['content_id'][$a];
                        $contentRecord[$a]['content_name'] = $contentData['content_name'];
                        $contentRecord[$a]['average_student_score'] = 0 . "%";
                        $contentRecord[$a]['assigned'] = 0;
                        $contentRecord[$a]['graded'] = 0;
                        $contentRecord[$a]['absent'] = 0;
                        $contentRecord[$a]['assignment_date'] = date('m-d-Y', strtotime($contentData['content_date']));
                        $contentRecord[$a]['min_score'] = 0;
                        $contentRecord[$a]['max_score'] = 0;
                        $contentRecord[$a]['average_score'] = 0;
                        $contentRecord[$a]['totalpoint_average_score'] = 0;
                        $contentRecord[$a]['totalmark_of_students'] = 0;
                        $contentRecord[$a]['totalmark_of_assignment'] = 0;
                        $contentRecord[$a]['totalmark_of_allStudentAssignment'] = 0;
                    }
                }
            //}
            }

            $totalStudentAssigned = 0;
            $totalStudentGraded = 0;
            $totalStudentsAbsent = 0;
            $totalAssignmentAverage = 0;
            $totalStudentsAverage = 0;
            $totalAllStudentAssignmentAverage = 0;
            if (count($contentRecord) > 0) {
                foreach ($contentRecord as $key => $value) {
                    $totalStudentAssigned += $value['assigned'];
                    $totalStudentGraded += $value['graded'];
                    $totalStudentsAbsent += $value['absent'];
                    $totalContentStudentAverage += $value['average_score'];
                    $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                    $totalAssignmentAverage += $value['totalmark_of_assignment'];
                    $totalStudentsAverage += $value['totalmark_of_students'];
                    $totalAllStudentAssignmentAverage += $value['totalmark_of_allStudentAssignment'];
                }
                $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                foreach($contentRecord as $key => $value) {
                    unset($contentRecord[$key]['totalmark_of_students']);
                    unset($contentRecord[$key]['totalmark_of_assignment']);
                    unset($contentRecord[$key]['totalmark_of_allStudentAssignment']);
                }
                $totalRecord['contentList'] = array_values($contentRecord);
                $chartValues['Master'] = count(array_keys($percentage, '1'));
                $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                $chartValues['Average'] = count(array_keys($percentage, '4'));
                $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                $totalRecord['chartValues'] = array($chartValues);
            }
            if (count($totalRecord) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $totalRecord;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/assignmentClassReports',$this->jsonarr,'assignmentClassReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function assessmentClassReports_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
        $headers = $this->input->request_headers();
       // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assessmentClassReports','only request','assessmentClassReports');
            $contentList1 = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            $contentId = implode(',',$params['content_id']);
            $contentList = $this->report_model->studentList($contentId, $params['class_id']);
            //print_r($contentList); //exit();
            for ($a = 0; $a < count($params['content_id']); $a++) {
                //print_r($params['content_id'][$a]);
                $new = [];
                if (count($contentList) > 0) {
                    foreach ($contentList as $key1 => $value1) {
                        if ($value1['content_id'] == $params['content_id'][$a]) {
                            array_push($new, $value1);
                        }
                    }
                    //print_r($new);

                    if ($new) {
                        foreach ($new as $key => $value) {
                            if ($value['points'] != 0) {
                                $totalMarksContent = $value['points'];
                            } else {
                                $totalMarksContent = $value['total_mark'];
                            }
                            $studentMarks = 0;
                            $totalMarks = 0;
                            $totalGraded = 0;
                            $totalAbsent = 0;
                            $studentPercentage = 0;
                            $marks = [];
                            //foreach ($contentList as $value) {
                            if ($value['points'] != 0) {
                                $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                            } else {
                                $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                            }
                            $i++;
                            if ($value['points'] != 0) {
                                $studentMarks += $value['earned_points'];
                                $totalMarks += $value['points'];
                                $studentPercentage += round(($value['earned_points'] / $value['points']) * 100, 2);
                            } else {
                                $studentMarks += $value['student_mark'];
                                $totalMarks += $value['total_mark'];
                                $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                            }
                            if ($value['points'] != 0) {
                                array_push($marks, $value['earned_points']);
                            } else {
                                array_push($marks, $value['student_mark']);
                            }
                            if ($value['status'] == 1 || $value['status'] == 2) {
                                $totalAbsent++;
                            } elseif ($value['status'] >= 3) {
                                $totalGraded++;
                            }
                        }
                        if (count($new) > 0) {
                            $contentRecord[$a]['class_id'] = $params['class_id'];
                            $contentRecord[$a]['content_id'] = $new[$key]['content_id'];
                            $contentRecord[$a]['content_name'] = $new[$key]['content_name'];
                            $contentRecord[$a]['average_student_score'] = round(count($new) != 0 ? ($studentPercentage / count($new)) : 0, 2) . "%";
                            $averageStudentPercentage += round(count($new) != 0 ? ($studentPercentage / count($new)) : 0, 2);
                            $contentRecord[$a]['assigned'] = count($new);
                            $contentRecord[$a]['graded'] = $totalGraded;
                            $contentRecord[$a]['absent'] = $totalAbsent;
                            $contentRecord[$a]['assignment_date'] = date('m-d-Y', strtotime($value['content_date']));
                            $contentRecord[$a]['min_score'] = min($marks) != "" ? min($marks) : 0;
                            $contentRecord[$a]['max_score'] = max($marks) != "" ? max($marks) : 0;
                            $contentRecord[$a]['average_score'] = round($studentPercentage / count($new), 2);
                            $contentRecord[$a]['totalpoint_average_score'] = round($totalMarks / count($new), 2);
                            $contentRecord[$a]['totalmark_of_students'] = $studentMarks;
                            $contentRecord[$a]['totalmark_of_assignment'] = $totalMarksContent;
                            $contentRecord[$a]['totalmark_of_allStudentAssignment'] = $totalMarks;
                        }
                    }

                    unset($new);

                } else {
                    {
                        $contentData = $this->report_model->contentData($params['content_id'][$a], $params);
                        $contentRecord[$a]['class_id'] = $params['class_id'];
                        $contentRecord[$a]['content_id'] = $params['content_id'][$a];
                        $contentRecord[$a]['content_name'] = $contentData['content_name'];
                        $contentRecord[$a]['average_student_score'] = 0 . "%";
                        $contentRecord[$a]['assigned'] = 0;
                        $contentRecord[$a]['graded'] = 0;
                        $contentRecord[$a]['absent'] = 0;
                        $contentRecord[$a]['assignment_date'] = date('m-d-Y', strtotime($contentData['content_date']));
                        $contentRecord[$a]['min_score'] = 0;
                        $contentRecord[$a]['max_score'] = 0;
                        $contentRecord[$a]['average_score'] = 0;
                        $contentRecord[$a]['totalpoint_average_score'] = 0;
                        $contentRecord[$a]['totalmark_of_students'] = 0;
                        $contentRecord[$a]['totalmark_of_assignment'] = 0;
                        $contentRecord[$a]['totalmark_of_allStudentAssignment'] = 0;
                    }
                }
                //}
            }

            $totalStudentAssigned = 0;
            $totalStudentGraded = 0;
            $totalStudentsAbsent = 0;
            $totalAssignmentAverage = 0;
            $totalStudentsAverage = 0;
            $totalAllStudentAssignmentAverage = 0;
            if (count($contentRecord) > 0) {
                foreach ($contentRecord as $key => $value) {
                    $totalStudentAssigned += $value['assigned'];
                    $totalStudentGraded += $value['graded'];
                    $totalStudentsAbsent += $value['absent'];
                    $totalContentStudentAverage += $value['average_score'];
                    $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                    $totalAssignmentAverage += $value['totalmark_of_assignment'];
                    $totalStudentsAverage += $value['totalmark_of_students'];
                    $totalAllStudentAssignmentAverage += $value['totalmark_of_allStudentAssignment'];
                }
                $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                foreach($contentRecord as $key => $value) {
                    unset($contentRecord[$key]['totalmark_of_students']);
                    unset($contentRecord[$key]['totalmark_of_assignment']);
                    unset($contentRecord[$key]['totalmark_of_allStudentAssignment']);
                }
                $totalRecord['contentList'] = array_values($contentRecord);
                $chartValues['Master'] = count(array_keys($percentage, '1'));
                $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                $chartValues['Average'] = count(array_keys($percentage, '4'));
                $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                $totalRecord['chartValues'] = array($chartValues);
            }
            if (count($totalRecord) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $totalRecord;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/assessmentClassReports',$this->jsonarr,'assessmentClassReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function assessmentReports_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/assessmentReports','only request','assessmentReports');
            $contentList = [];
            $contentRecord = [];
            $totalRecord = [];
            $percentage = [];
            $chartValues = [];
            $totalContentStudentAverage = 0;
            $totalpointContentStudentAverage = 0;
            $averageStudentPercentage = 0;
            $i = 0;
            foreach ($params['content_id'] as $key => $value) {
                $contentList[$key] = $this->report_model->studentList($value, $params['class_id']);
                if (count($contentList[$key]) > 0) {
                    if ($contentList[$key][0]['points'] != 0) {
                        $totalMarksContent = $contentList[$key][0]['points'];
                    } else {
                        $totalMarksContent = $contentList[$key][0]['total_mark'];
                    }
                    $studentMarks = 0;
                    $totalMarks = 0;
                    $totalGraded = 0;
                    $totalAbsent = 0;
                    $studentPercentage = 0;
                    $marks = [];
                    foreach ($contentList[$key] as $value) {
                        if ($value['points'] != 0) {
                            $percentage[$i] = $this->chartCalculation($value['earned_points'], $value['points']);
                        } else {
                            $percentage[$i] = $this->chartCalculation($value['student_mark'], $value['total_mark']);
                        }
                        $i++;
                        if ($value['points'] != 0) {
                            $studentMarks += $value['earned_points'];
                            $totalMarks += $value['points'];
                            $studentPercentage += round(($value['earned_points'] / $value['points']) * 100 , 2);
                        } else {
                            $studentMarks += $value['student_mark'];
                            $totalMarks += $value['total_mark'];
                            $studentPercentage += round($value['total_mark'] != 0 ? ($value['student_mark'] / $value['total_mark']) * 100 : 0, 2);
                        }
                        if ($value['points'] != 0) {
                            array_push($marks, $value['earned_points']);
                        } else {
                            array_push($marks, $value['student_mark']);
                        }
                        if ($value['status'] == 1 || $value['status'] == 2) {
                            $totalAbsent++;
                        } elseif ($value['status'] >= 3) {
                            $totalGraded++;
                        }
                    }
                    if (count($contentList[$key]) > 0) {
                        $contentRecord[$key]['class_id'] = $params['class_id'];
                        $contentRecord[$key]['content_id'] = $contentList[$key][0]['content_id'];
                        $contentRecord[$key]['content_name'] = $contentList[$key][0]['content_name'];
                        $contentRecord[$key]['average_student_score'] = round(count($contentList[$key]) != 0 ? ($studentPercentage / count($contentList[$key])) : 0, 2) . "%";
                        $averageStudentPercentage += round(count($contentList[$key]) != 0 ? ($studentPercentage / count($contentList[$key])) : 0, 2);
                        $contentRecord[$key]['assigned'] = count($contentList[$key]);
                        $contentRecord[$key]['graded'] = $totalGraded;
                        $contentRecord[$key]['absent'] = $totalAbsent;
                        $contentRecord[$key]['assessment_date'] = date('m-d-Y', strtotime($contentList[$key][0]['content_date']));
                        $contentRecord[$key]['min_score'] = min($marks) != "" ? min($marks) : 0;
                        $contentRecord[$key]['max_score'] = max($marks) != "" ? max($marks) : 0;
                        $contentRecord[$key]['average_score'] = round($studentMarks / count($contentList[$key]),2);
                        $contentRecord[$key]['totalpoint_average_score'] = round($totalMarks / count($contentList[$key]),2);
                        $contentRecord[$key]['totalmark_of_students'] = $studentMarks;
                        $contentRecord[$key]['totalmark_of_assessment'] = $totalMarksContent;
                        $contentRecord[$key]['totalmark_of_allStudentAssessment'] = $totalMarks;
                    }
                } else {
                    $contentData = $this->report_model->contentData($value,$params);
                    $contentRecord[$key]['class_id'] = $params['class_id'];
                    $contentRecord[$key]['content_id'] = $value;
                    $contentRecord[$key]['content_name'] = $contentData['content_name'];
                    $contentRecord[$key]['average_student_score'] = 0 ."%";
                    $contentRecord[$key]['assigned'] = 0;
                    $contentRecord[$key]['graded'] = 0;
                    $contentRecord[$key]['absent'] = 0;
                    $contentRecord[$key]['assessment_date'] = date('m-d-Y', strtotime($contentData['content_date']));
                    $contentRecord[$key]['min_score'] = 0;
                    $contentRecord[$key]['max_score'] = 0;
                    $contentRecord[$key]['average_score'] = 0;
                    $contentRecord[$key]['totalpoint_average_score'] = 0;
                    $contentRecord[$key]['totalmark_of_students'] = 0;
                    $contentRecord[$key]['totalmark_of_assessment'] = 0;
                    $contentRecord[$key]['totalmark_of_allStudentAssessment'] = 0;
                }
            }
                $totalStudentAssigned = 0;
                $totalStudentGraded = 0;
                $totalStudentsAbsent = 0;
                $totalAssessmentAverage = 0;
                $totalStudentsAverage = 0;
                $totalAllStudentAssessmentAverage = 0;
                if (count($contentRecord) > 0) {
                    foreach ($contentRecord as $key => $value) {
                        $totalStudentAssigned += $value['assigned'];
                        $totalStudentGraded += $value['graded'];
                        $totalStudentsAbsent += $value['absent'];
                        $totalContentStudentAverage += $value['average_score'];
                        $totalpointContentStudentAverage += $value['totalpoint_average_score'];
                        $totalAssessmentAverage += $value['totalmark_of_assessment'];
                        $totalStudentsAverage += $value['totalmark_of_students'];
                        $totalAllStudentAssessmentAverage += $value['totalmark_of_allStudentAssessment'];
                    }
                    $totalRecord['studentAverage'] = round(count($contentRecord) != 0 ? $totalContentStudentAverage / count($contentRecord) : 0,2);
                    $totalRecord['totalAverage'] = round(count($contentRecord) != 0 ? $totalpointContentStudentAverage / count($contentRecord) : 0,2);
                    $totalRecord['AverageStudentScore'] = round(count($contentRecord) != 0 ? ($averageStudentPercentage / count($contentRecord)) : 0, 2) . '%';
                    $totalRecord['totalStudentAssigned'] = $totalStudentAssigned;
                    $totalRecord['totalStudentGraded'] = $totalStudentGraded;
                    $totalRecord['totalAbsent'] = $totalStudentsAbsent;
                    foreach ($contentRecord as $key => $value) {
                        unset($contentRecord[$key]['totalmark_of_students']);
                        unset($contentRecord[$key]['totalmark_of_assessment']);
                        unset($contentRecord[$key]['totalmark_of_allStudentAssessment']);
                    }
                    $totalRecord['contentList'] = array_values($contentRecord);
                    $chartValues['Master'] = count(array_keys($percentage, '1'));
                    $chartValues['Excellent'] = count(array_keys($percentage, '2'));
                    $chartValues['Proficient'] = count(array_keys($percentage, '3'));
                    $chartValues['Average'] = count(array_keys($percentage, '4'));
                    $chartValues['belowAverage'] = count(array_keys($percentage, '5'));
                    $totalRecord['chartValues'] = array($chartValues);
                }
                if (count($totalRecord) != 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = $totalRecord;
                } else {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "No Records found";
                }
            }
        $this->common_model->createLog($params,'v1/report/assessmentReports',$this->jsonarr,'assessmentReports');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function chartCalculation($studentMark, $totalMark) {
        $average = $totalMark != 0 ? ($studentMark/$totalMark)*100 : 0;
        if ($average >= 90) {
            return 1;
        } elseif ($average >= 80 && $average <= 89.99) {
            return 2;
        } elseif ($average >=70 && $average <= 79.99) {
            return 3;
        } elseif ($average >= 50 && $average < 69.99) {
            return 4;
        } elseif ($average < 50) {
            return 5;
        }
    }

    public function studentGradeSummary_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif(count($params['content_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif(count($params['student_id']) == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Student Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentGradeSummary','only request','studentGradeSummary');
            $percentage = [];
            $chartValues = [];
            $studentList = [];
            $list = [];
            foreach($params['student_id'] as $key => $value) {
                $i = 0;
                $totalMarks = 0;
                $studentMarks = 0;
                $student = [];
                $totalAbsent = 0;
                $totalGraded = 0;
                $marks = [];
                foreach ($params['content_id'] as $key1 => $value1) {
                    $student[$key1] = $this->report_model->studentMarkList($value, $value1, $params['class_id']);
                    if ($student[$key1]['points'] != 0) {
                        $percentage[$i] = round($student[$key1]['points'] != 0 ? ($student[$key1]['earned_points']/$student[$key1]['points'])*100 : 0,2);
                    } else {
                        $percentage[$i] = round($student[$key1]['total_mark'] != 0 ? ($student[$key1]['student_mark']/$student[$key1]['total_mark'])*100 : 0,2);
                    }
                    $i++;
                    if ($student[$key1]['points'] != 0) {
                        $totalMarks += $student[$key1]['points'];
                        $studentMarks += $student[$key1]['earned_points'];
                    } else {
                        $totalMarks += $student[$key1]['total_mark'];
                        $studentMarks += $student[$key1]['student_mark'];
                    }
                    if ($student[$key1]['points'] != 0) {
                        array_push($marks, $student[$key1]['earned_points']);
                    } else {
                        array_push($marks, $student[$key1]['student_mark']);
                    }
                    if ($student[$key1]['status'] == 1 || $student[$key1]['status'] == 2) {
                        $totalAbsent ++;
                    } elseif ($student[$key1]['status'] == 3 || $student[$key1]['status'] == 5) {
                        $totalGraded ++;
                    }
                }
                $studentList[$key]['student_name'] = $student[0]['student_name'];
                $studentList[$key]['student_average_score'] = round($totalMarks != 0 ? ($studentMarks / $totalMarks) * 100 : 0,2) . "%";
                $studentList[$key]['assigned'] = count($student);
                $studentList[$key]['graded'] = $totalGraded;
                $studentList[$key]['absent'] = $totalAbsent;
                $studentList[$key]['min_score'] = min($marks)!= "" ? min($marks) : 0;
                $studentList[$key]['max_score'] = max($marks)!= "" ? max($marks) : 0;
                $studentList[$key]['average_score'] = $studentMarks/count($student);
                $chartValues['chartValues'][$key]["name"] = $student[0]['student_name'];
                $chartValues['chartValues'][$key]["data"] = $percentage;
            }
            $list['studentList'] = $studentList;
            $list['chart'] = $chartValues['chartValues'];
            if (count($list) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $list;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/studentGradeSummary',$this->jsonarr,'studentGradeSummary');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function studentsContentReport_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif($params['content_id'] == 0) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentsContentReport','only request','studentsContentReport');
            $classStudent = $this->report_model->studentContentData($params);
            $studentList = [];
            $chartValues = [];
            foreach ($classStudent as $key => $value) {
                $studentList[$key] = $this->report_model->studentMarkList($value['student_id'],$params['content_id'],$params['class_id']);

                $studentList[$key]['student_name'] = $value['student_name'];

                if ($studentList[$key]['points'] != 0) {
                    $studentList[$key]['student_average_score'] = round($studentList[$key]['points'] != 0 ? ($studentList[$key]['earned_points'] / $studentList[$key]['points']) * 100 : 0, 2) . "%";
                } else {
                    $studentList[$key]['student_average_score'] = round($studentList[$key]['total_mark'] != 0 ? ($studentList[$key]['student_mark'] / $studentList[$key]['total_mark']) * 100 : 0, 2) . "%";
                }
                $studentList[$key]['assigned'] = 1;
                if ($studentList[$key]['status'] == 1 || $studentList[$key]['status'] == 2) {
                    $studentList[$key]['absent'] = 1;
                } else {
                    $studentList[$key]['absent'] = 0;
                }
                if ($studentList[$key]['status'] >= 3) {
                    $studentList[$key]['graded'] = 1;
                } else {
                    $studentList[$key]['graded'] = 0;
                }
                if ($studentList[$key]['points'] != 0) {
                    $studentList[$key]['student_score'] = $studentList[$key]['earned_points'];
                } else {
                    $studentList[$key]['student_score'] = $studentList[$key]['student_mark'] != '' ? $studentList[$key]['student_mark'] : 0;
                }
                if ($studentList[$key]['points'] != 0) {
                    $studentList[$key]['total_score'] = $studentList[$key]['points'];
                } else {
                    $studentList[$key]['total_score'] = $studentList[$key]['total_mark'] != '' ? $studentList[$key]['total_mark'] : 0;
                }
                if ($studentList[$key]['points'] != 0) {
                    $percentage = round($studentList[$key]['points'] != 0 ? ($studentList[$key]['earned_points']/$studentList[$key]['points'])*100 : 0,2);
                } else {
                    $percentage = round($studentList[$key]['total_mark'] != 0 ? ($studentList[$key]['student_mark']/$studentList[$key]['total_mark'])*100 : 0,2);
                }
                $chartValues['chartValues'][$key]["name"] = $value['student_name'];
                $chartValues['chartValues'][$key]["data"] = array(round($percentage));
                unset($studentList[$key]['student_mark']);
                unset($studentList[$key]['total_mark']);
            }
            $list['studentList'] = $studentList;
            if (count($chartValues) > 0) {
                $list['chart'] = $chartValues['chartValues'];
            } else {
                $list['chart'] = [];
            }
            if (count($list) != 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $list;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "No Records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/studentsContentReport',$this->jsonarr,'studentsContentReport');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function reportCard1_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['student_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Student Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/reportCard','only request','reportCard');
            $data = array();
            $studentGrades = $this->report_model->getStudentGrades($params);
            foreach ($studentGrades as $key => $grade) {
                $data[$key] = array("grade_id" => $grade['grade'],
                    "grade_name" => $grade['grade_name'],
                    "subjects" => array());
                $subjects = $this->report_model->getGradeSubjects($params);
                foreach ($subjects as $key1 => $subject) {
                    $data[$key]['subjects'][$key1] = array("subject_id" => $subject['subject'],
                        "subject_name" => $subject['subject_name'],
                        "classes" => array());
                    $classes = $this->report_model->getSubjectClasses($params, $subject['subject']);
                    foreach ($classes as $key3 => $class) {
                        $data[$key]['subjects'][$key1]['classes'][$key3]['class_id'] = $class['class_id'];
                        $data[$key]['subjects'][$key1]['classes'][$key3]['class_name'] = $class['class_name'];
                        $contentAssignmentData = array();
                        $contentAssessmentData = array();
                        $contents = $this->report_model->getClassContent($params, $class['class_id']);
                        $i = 0;
                        $j = 0;
                        foreach ($contents as $contentKey => &$content) {
                            if ($content['status'] > 2) {
                                $contents[$contentKey]['graded'] = 'Yes';
                                $contents[$contentKey]['absent'] = 'No';
                            } else {
                                $contents[$contentKey]['graded'] = 'No';
                                $contents[$contentKey]['absent'] = 'Yes';
                            }
                            if ($content['content_type'] == 2) {
                                $contents[$contentKey]['start_date'] = $content['start_date'] != '0000-00-00' ? date('m-d-Y', strtotime($content['start_date'])) : '00-00-0000';
                                $contentAssignmentData[$i] = $content;
                                $i++;
                            } elseif ($content['content_type'] == 3) {
                                $contents[$contentKey]['start_date'] = $content['start_date'] != '0000-00-00' ? date('m-d-Y', strtotime($content['start_date'])) : '00-00-0000';
                                $contentAssessmentData[$j] = $content;
                                $j++;
                            }
                        }
                        $data[$key]['subjects'][$key1]['classes'][$key3]['assignment'] = $contentAssignmentData;
                        $data[$key]['subjects'][$key1]['classes'][$key3]['assessment'] = $contentAssessmentData;
                    }
                }
            }
            foreach ($data as $key1 => $value1) {
                $totalGradePercentage = 0;
                foreach ($value1['subjects'] as $key2 => $value2) {
                    $totalClassPercentage = 0;
                    foreach ($value2['classes'] as $key3 => $value3) {
                        $totalpointsAssignment = 0;
                        $earnedpointsAssignment = 0;
                        $totalpointsAssessment = 0;
                        $earnedpointsAssessment = 0;
                        $assignmentPercentage = 0;
                        $assessmentPercentage = 0;
                        $totalContentPercentage = 0;
                        foreach ($value3['assignment'] as $key4 => $value4) {
                            //$totalpointsAssignment += $value4['total_score'];
                            $assignmentPercentage += round($value4['total_score'] != 0 ? ($value4['your_score'] / $value4['total_score']) * 100 : 0, 2);
                            //$earnedpointsAssignment += $value4['your_score'];
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['assignment'][$key4]['percentage'] = round($value4['total_score'] != 0 ? ($value4['your_score'] / $value4['total_score']) * 100 : 0, 2) . "%";
                        }
                        foreach ($value3['assessment'] as $key5 => $value5) {
//                            $totalpointsAssessment += $value5['total_score'];
//                            $earnedpointsAssessment += $value5['your_score'];
                            $assessmentPercentage += round($value5['total_score'] != 0 ? ($value5['your_score'] / $value5['total_score']) * 100 : 0, 2);
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['assessment'][$key5]['percentage'] = round($value5['total_score'] != 0 ? ($value5['your_score'] / $value5['total_score']) * 100 : 0, 2) . "%";
                        }
                        //$earnedcontentpoints = $earnedpointsAssignment + $earnedpointsAssessment;
                        //$totalContentPoints = $totalpointsAssignment + $totalpointsAssessment;
                        $data[$key1]['subjects'][$key2]['classes'][$key3]['assignment_percentage'] = round(count($value3['assignment']) != 0 ? ($assignmentPercentage / count($value3['assignment'])) : 0, 2) . "%";
                        $data[$key1]['subjects'][$key2]['classes'][$key3]['assessment_percentage'] = round(count($value3['assessment']) != 0 ? ($assessmentPercentage / count($value3['assessment'])) : 0, 2) . "%";
                        $totalContentPercentage = round(count($value3['assignment']) != 0 ? ($assignmentPercentage / count($value3['assignment'])) : 0, 2) + round(count($value3['assessment']) != 0 ? ($assessmentPercentage / count($value3['assessment'])) : 0, 2);
                        if (count($value3['assignment']) > 0 && count($value3['assessment']) > 0) {
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['class_percentage'] = round(($totalContentPercentage / 2), 2) . "%";
                            $totalClassPercentage += round(($totalContentPercentage / 2), 2);
                        } elseif (count($value3['assignment']) == 0 || count($value3['assessment']) == 0) {
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['class_percentage'] = round(($totalContentPercentage), 2) . "%";
                            $totalClassPercentage += round(($totalContentPercentage), 2);
                        }
                        $data[$key1]['subjects'][$key2]['percentage'] = round(count($value2['classes']) != 0 ? $totalClassPercentage / count($value2['classes']) : 0, 2) . "%";
                        $totalGradePercentage += round(count($value2['classes']) != 0 ? $totalClassPercentage / count($value2['classes']) : 0, 2);
                    }
                    $data[$key1]['percentage'] = round(count($value1['subjects']) != 0 ? $totalGradePercentage / count($value1['subjects']) : 0, 2) . "%";
                }
            }
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $data;

        }
        $this->common_model->createLog($params,'v1/report/reportCard',$this->jsonarr,'reportCard');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function reportCard_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'),true);
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['student_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Student Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $studentGrades = $this->report_model->getStudentGrades1($params);
            foreach ($studentGrades as $key => $grade) {
                $data[$key] = array("grade_id" => $grade['grade'],
                    "grade_name" => $grade['grade_name'],
                    "subjects" => array());
                $subjects = $this->report_model->getGradeSubjects($params);
                foreach ($subjects as $key1 => $subject) {
                    $data[$key]['subjects'][$key1] = array("subject_id" => $subject['subject'],
                        "subject_name" => $subject['subject_name'],
                        "classes" => array());
                    $classes = $this->report_model->getSubjectClasses1($params, $subject['subject']);
                    foreach ($classes as $key3 => $class) {
                        $data[$key]['subjects'][$key1]['classes'][$key3]['class_id'] = $class['class_id'];
                        $data[$key]['subjects'][$key1]['classes'][$key3]['class_name'] = $class['class_name'];
                        $contentAssignmentData = array();
                        $contentAssessmentData = array();
                        $contents = $this->report_model->getClassContent($params, $class['class_id']);
                        $i = 0;
                        $j = 0;
                        foreach ($contents as $contentKey => &$content) {
                            if ($content['status'] > 2) {
                                $contents[$contentKey]['graded'] = 'Yes';
                                $contents[$contentKey]['absent'] = 'No';
                            } else {
                                $contents[$contentKey]['graded'] = 'No';
                                $contents[$contentKey]['absent'] = 'Yes';
                            }
                            if ($content['content_type'] == 2) {
                                $contents[$contentKey]['start_date'] = $content['start_date'] != '0000-00-00' ? date('m-d-Y', strtotime($content['start_date'])) : '00-00-0000';
                                $contentAssignmentData[$i] = $content;
                                $i++;
                            } elseif ($content['content_type'] == 3) {
                                $contents[$contentKey]['start_date'] = $content['start_date'] != '0000-00-00' ? date('m-d-Y', strtotime($content['start_date'])) : '00-00-0000';
                                $contentAssessmentData[$j] = $content;
                                $j++;
                            }
                        }
                        $data[$key]['subjects'][$key1]['classes'][$key3]['assignment'] = $contentAssignmentData;
                        $data[$key]['subjects'][$key1]['classes'][$key3]['assessment'] = $contentAssessmentData;
                    }
                }
            }
            foreach ($data as $key1 => $value1) {
                $totalGradePercentage = 0;
                foreach ($value1['subjects'] as $key2 => $value2) {
                    $totalClassPercentage = 0;
                    foreach ($value2['classes'] as $key3 => $value3) {
                        $totalpointsAssignment = 0;
                        $earnedpointsAssignment = 0;
                        $totalpointsAssessment = 0;
                        $earnedpointsAssessment = 0;
                        $assignmentPercentage = 0;
                        $assessmentPercentage = 0;
                        $totalContentPercentage = 0;
                        foreach ($value3['assignment'] as $key4 => $value4) {
                            //$totalpointsAssignment += $value4['total_score'];
                            $assignmentPercentage += round($value4['total_score'] != 0 ? ($value4['your_score'] / $value4['total_score']) * 100 : 0, 2);
                            //$earnedpointsAssignment += $value4['your_score'];
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['assignment'][$key4]['percentage'] = round($value4['total_score'] != 0 ? ($value4['your_score'] / $value4['total_score']) * 100 : 0, 2) . "%";
                        }
                        foreach ($value3['assessment'] as $key5 => $value5) {
//                            $totalpointsAssessment += $value5['total_score'];
//                            $earnedpointsAssessment += $value5['your_score'];
                            $assessmentPercentage += round($value5['total_score'] != 0 ? ($value5['your_score'] / $value5['total_score']) * 100 : 0, 2);
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['assessment'][$key5]['percentage'] = round($value5['total_score'] != 0 ? ($value5['your_score'] / $value5['total_score']) * 100 : 0, 2) . "%";
                        }
                        //$earnedcontentpoints = $earnedpointsAssignment + $earnedpointsAssessment;
                        //$totalContentPoints = $totalpointsAssignment + $totalpointsAssessment;
                        $data[$key1]['subjects'][$key2]['classes'][$key3]['assignment_percentage'] = round(count($value3['assignment']) != 0 ? ($assignmentPercentage / count($value3['assignment'])) : 0, 2) . "%";
                        $data[$key1]['subjects'][$key2]['classes'][$key3]['assessment_percentage'] = round(count($value3['assessment']) != 0 ? ($assessmentPercentage / count($value3['assessment'])) : 0, 2) . "%";
                        $totalContentPercentage = round(count($value3['assignment']) != 0 ? ($assignmentPercentage / count($value3['assignment'])) : 0, 2) + round(count($value3['assessment']) != 0 ? ($assessmentPercentage / count($value3['assessment'])) : 0, 2);
                        if (count($value3['assignment']) > 0 && count($value3['assessment']) > 0) {
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['class_percentage'] = round(($totalContentPercentage / 2), 2) . "%";
                            $totalClassPercentage += round(($totalContentPercentage / 2), 2);
                        } elseif (count($value3['assignment']) == 0 || count($value3['assessment']) == 0) {
                            $data[$key1]['subjects'][$key2]['classes'][$key3]['class_percentage'] = round(($totalContentPercentage), 2) . "%";
                            $totalClassPercentage += round(($totalContentPercentage), 2);
                        }
                        $data[$key1]['subjects'][$key2]['percentage'] = round(count($value2['classes']) != 0 ? $totalClassPercentage / count($value2['classes']) : 0, 2) . "%";
                        $totalGradePercentage += round(count($value2['classes']) != 0 ? $totalClassPercentage / count($value2['classes']) : 0, 2);
                    }
                    $data[$key1]['percentage'] = round(count($value1['subjects']) != 0 ? $totalGradePercentage / count($value1['subjects']) : 0, 2) . "%";
                }
            }

            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $data;

            }

        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function gradeReport_post()
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/gradeReport','only request','gradeReport');
            $params['type'] = 8;
            $studentList = [];
            $getAllClasses = $this->report_model->classListNew($params);
            $i = 0;
            foreach($getAllClasses as $classkey => $classvalue) {
                $getPercentages = 0;
                $getStudents = $this->report_model->getStudents($classvalue);
                if (count($getStudents) > 0) {
                    $studentList[$i]['class_name'] = $classvalue['classcode'];
                    $studentList[$i]['class_code'] = $classvalue['class_code'];
                    foreach($getStudents as $key => $value) {
                        $studentList[$i]['student_list'][$key] = array('student_id' => $value['student_id'],
                                                                        'student_name' => $value['student_name'],
                                                                        'assignment' => array(),
                                                                        'assessment' => array());
                        $getStudentContent = $this->report_model->getStudentContents($value,$params);
                        $j=0;
                        $l=0;
                        $contentAssignmentPercentage = 0;
                        $contentAssessmentPercentage = 0;
                        foreach ($getStudentContent as $contentKey => &$contentValue) {
                            if ($contentValue['content_type'] == 2) {
                                $contentAssignmentPercentage += $contentValue['percentage'];
                                if ($contentValue['percentage'] == '0.00') {
                                    $getStudentContent[$contentKey]['percentage'] = 0 . '%';
                                } else {
                                    $getStudentContent[$contentKey]['percentage'] = $getStudentContent[$contentKey]['percentage'] . '%';
                                }
                                $studentList[$i]['student_list'][$key]['assignment'][$j] = $contentValue;
                                $j++;
                            } elseif ($contentValue['content_type'] == 3) {
                                $contentAssessmentPercentage += $contentValue['percentage'];
                                if ($contentValue['percentage'] == '0.00') {
                                    $getStudentContent[$contentKey]['percentage'] = 0 . '%';
                                } else {
                                    $getStudentContent[$contentKey]['percentage'] = $getStudentContent[$contentKey]['percentage'] . '%';
                                }
                                $studentList[$i]['student_list'][$key]['assessment'][$l] = $contentValue;
                                $l++;
                            }
                        }
                        $studentList[$i]['student_list'][$key]['assignment_percentage'] = round(count($studentList[$i]['student_list'][$key]['assignment']) != 0 ? $contentAssignmentPercentage/count($studentList[$i]['student_list'][$key]['assignment']) : 0,2) . '%';
                        $studentList[$i]['student_list'][$key]['assessment_percentage'] = round(count($studentList[$i]['student_list'][$key]['assessment']) != 0 ? $contentAssessmentPercentage/count($studentList[$i]['student_list'][$key]['assessment']) : 0,2) . '%';
                        $getStudents[$key]['percentage'] = round($getStudents[$key]['total_points'] != 0 ? ($getStudents[$key]['earned_points']/$getStudents[$key]['total_points'])*100 : 0 ,2);
                        $getPercentages += $getStudents[$key]['percentage'];
                        $getStudents[$key]['percentage'] = $getStudents[$key]['percentage'] . '%';
                    }
                    $studentList[$i]['class_percentage'] = round($getPercentages/count($getStudents),2) . '%';
                    $i++;
                }
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $studentList;
        }
        $this->common_model->createLog($params,'v1/report/gradeReport',$this->jsonarr,'gradeReport');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function classPerformanceList_post() {
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/classPerformanceList','only request','classPerformanceList');
            $getClasses = $this->report_model->allClass($params);
            if (count($getClasses) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $getClasses;
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "No records Found";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/classPerformanceList',$this->jsonarr,'classPerformanceList');
        return $this->printjson($this->jsonarr);
    }

    public function studentPerformanceReport_post() {
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentPerformanceReport','only request','studentPerformanceReport');
            $getStudents = $this->report_model->allStudents($params);
            $getContents = $this->report_model->allContents($params);
            $result = [];
            $i = 0;
            $j = 0;
            foreach ($getStudents as $key => $value) {
                if (count($value) > 0) {
                    $result[$i]['student_id'] = $value['student_id'];
                    $result[$i]['student_name'] = $value['student_name'];
                    foreach ($getContents as $key1 => $value1) {
                        {
                            $result[$i]['content'][$j]['content_id'] = $value1['content_id'];
                            $result[$i]['content'][$j]['content_name'] = $value1['content_name'];
                            $j++;
                        }
                    }
                }
            }
                    //print_r($getStudents);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $result;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/studentPerformanceReport',$this->jsonarr,'studentPerformanceReport');
        return $this->printjson($this->jsonarr);
    }

    public function studentReportWiseClass_post(){
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['teacher_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "teacher Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentReportClass','only request','studentReportClass');
            $result=[];
            $j=0;
            $date = date('Y-m-d');
            $studentList=$this->report_model->studentLists($params);
            foreach ($studentList as $key1 => $value1) {
                $i = 0;
                $result[$j]['student_id'] = $value1['student_id'];
                $result[$j]['student_name'] = $value1['student_name'];
                $result[$j]['content'][$i] = array();
                //$students=$this->report_model->studentReport($params,$value1['student_id']);
                $totalAverage = 0;
                $checkAverage = 0;
                foreach ($params['content_id'] as $key2 => $value2) {
//                    $checkExists = $this->report_model->studentReport($params['class_id'], $value1['student_id'], $value2);
//                    if ($checkExists) {
                        $contentName = $this->report_model->getContentName($params,$value2);
                        $students = $this->report_model->studentReport($params['class_id'], $value1['student_id'], $value2);
                        $contentDate = $this->report_model->contentReport($params['class_id'], $value2, $value1['student_id'], $params['type'],$params['content_format'][$key2]);
                        if (count($contentDate) > 0) {
                            $endDate = strtotime($contentDate[0]['end_date']);
                            if ($contentDate[0]['status'] == 1) {
                                //yet to start
                                $contentDate[0]['status'] = "1";
                                $contentDate[0]['over_due'] = 0;
                            } elseif ($contentDate[0]['status'] == 2) {
                                //inprogress
                                $contentDate[0]['status'] = "2";
                                $contentDate[0]['over_due'] = 0;

                            } elseif ($contentDate[0]['status'] == 3) {
                                //completed
                                $contentDate[0]['status'] = "3";
                                $contentDate[0]['over_due'] = 0;

                            } elseif ($contentDate[0]['status'] == 4) {
                                //overdue
                                $contentDate[0]['status'] = "4";
                                $contentDate[0]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                            } elseif ($contentDate[0]['status'] == 5) {
                                //not started
                                $contentDate[0]['status'] = "5";
                                $contentDate[0]['over_due'] = 0;
                            } elseif ($contentDate[0]['status'] == 6) {
                                //overdue
                                $contentDate[0]['status'] = "6";
                                $contentDate[0]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                            }
                            if (isset($students['status']) && $students['status'] < 3) {
                                $totalAverage += 0;
                                $checkAverage++;
                            } else {
                                $totalAverage += 0;
                                // $checkAverage++;
                            }
                            $data = array();
                            if (isset($students['points']) && $students['points'] != 0) {
                                $result[$j]['content'][$i]['student_average_score'] = round($students['points'] ? ($students['earned_points'] / $students['points']) * 100 : 0, 2) . "%";
                                $totalAverage += round($students['points'] ? ($students['earned_points'] / $students['points']) * 100 : 0, 2);
                            } else {
                                $result[$j]['content'][$i]['student_average_score'] = 0 . "%";
                            }
                            $result[$j]['content'][$i]['content_name'] = isset($students['content_name']) ? $students['content_name'] : $contentName['content_name'];
                            $result[$j]['content'][$i]['content_id'] = isset($students['content_id']) ? $students['content_id'] : $value2;
                            $result[$j]['content'][$i]['student_id'] = $value1['student_id'];
                            $result[$j]['content'][$i]['class_id'] = $params['class_id'];
                            $result[$j]['content'][$i]['content_type'] = $contentName['content_type'];
                            $result[$j]['content'][$i]['content_format'] = isset($params['content_format'][$key2]) ? $params['content_format'][$key2] : 1;
                            //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                            $result[$j]['content'][$i]['color'] = isset($students['content_status']) ? $students['content_status'] : '4';
                            $result[$j]['content'][$i]['status'] = isset($contentDate[0]['status']) ? $contentDate[0]['status'] : 0;
                            $result[$j]['content'][$i]['over_due'] = isset($contentDate[0]['over_due']) ? $contentDate[0]['over_due'] : '';
                            $result[$j]['content'][$i]['student_score'] = isset($students['student_score']) ? $students['student_score'] : '';
                            $result[$j]['content'][$i]['total_score'] = isset($students['total_score']) ? $students['total_score'] : '';
                            $result[$j]['content'][$i]['end_date'] = isset($contentDate[0]['end_date']) ? $contentDate[0]['end_date'] : $contentName['end_date'];
                            $result[$j]['content'][$i]['attend_questions'] = isset($contentDate[0]['attend_questions']) ? $contentDate[0]['attend_questions'] : '';
                            $result[$j]['content'][$i]['total_questions'] = isset($contentDate[0]['total_questions']) ? $contentDate[0]['total_questions'] : '';
                            $result[$j]['content'][$i]['parents_notify_count'] = isset($students['parents_notify_count']) ? $students['parents_notify_count'] : '';
                            if (count($params['content_id']) == 0) {
                                $result[$j]['content'][$i] = array();
                            }
                            if (count($params['content_id']) == $checkAverage) {
                                $result[$j]['performance'] = '-';

                            } else {
                                $result[$j]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$j]['content']) : 0, 2);
                            }
                            $result[$j]['count'] = count($params['content_id']);
                            $i++;
                        } else {
                            $result[$j]['content'][$i]['student_average_score'] = 0 . "%";
                            $result[$j]['content'][$i]['content_name'] = isset($students['content_name']) ? $students['content_name'] : $contentName['content_name'];
                            $result[$j]['content'][$i]['content_id'] = isset($students['content_id']) ? $students['content_id'] : $value2;
                            $result[$j]['content'][$i]['student_id'] = $value1['student_id'];
                            $result[$j]['content'][$i]['class_id'] = $params['class_id'];
                            $result[$j]['content'][$i]['content_type'] = $contentName['content_type'];
                            $result[$j]['content'][$i]['content_format'] = isset($params['content_format'][$key2]) ? $params['content_format'][$key2] : 1;
                            $result[$j]['content'][$i]['color'] = isset($students['content_status']) ? $students['content_status'] : '4';
                            $result[$j]['content'][$i]['status'] = isset($contentDate[0]['status']) ? $contentDate[0]['status'] : 0;
                            $result[$j]['content'][$i]['over_due'] = isset($contentDate[0]['over_due']) ? $contentDate[0]['over_due'] : '';
                            $result[$j]['content'][$i]['student_score'] = isset($students['student_score']) ? $students['student_score'] : '';
                            $result[$j]['content'][$i]['total_score'] = isset($students['total_score']) ? $students['total_score'] : '';
                            $result[$j]['content'][$i]['end_date'] = isset($contentDate[0]['end_date']) ? $contentDate[0]['end_date'] : $contentName['end_date'];
                            $result[$j]['content'][$i]['attend_questions'] = 0;
                            $result[$j]['content'][$i]['total_questions'] = isset($contentDate[0]['total_questions']) ? $contentDate[0]['total_questions'] : '';
                            $result[$j]['content'][$i]['parents_notify_count'] = isset($students['parents_notify_count']) ? $students['parents_notify_count'] : '';

                            if (count($params['content_id']) == $checkAverage) {
                                $result[$j]['performance'] = '-';
                            } else {
                                $result[$j]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$j]['content']) : 0, 2);
                            }
                            $result[$j]['count'] = count($params['content_id']);
                            $i++;
                        }
//                    }
                }
//                if (count($result[$j]['content'][0]) == 0) {
//                    unset($result[$j]);
//                } else {
                    $j++;
//                }
            }
            $finalArray = array();
            foreach($result as $resultKey => $resultValue) {
                $z=0;
                foreach($resultValue['content'] as $valuKey => $contentValue) {
                    if ($contentValue['status'] != 0) {
                        //array_push($finalArray,$resultValue);
                        $z++;
                    }
                }
                if($z != 0) {
                    array_push($finalArray,$resultValue);
                }
            }
            if (isset($params['performance']) && $params['performance'] != 0) {
                $performance = [];
                $i = 0;
                foreach ($finalArray as $key => $item) {
                    if ($params['performance'] == 1) {
                        if ($item['performance'] != '-' && $item['performance'] >= 90) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 2) {
                        if($item['performance'] != '-' && $item['performance'] >= 80 && $item['performance'] <= 89.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 3) {
                        if ($item['performance'] != '-' && $item['performance'] >= 70 && $item['performance'] <= 70.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 4) {
                        if ($item['performance'] != '-' && $item['performance'] >= 50 && $item['performance'] < 69.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 5) {
                        if ($item['performance'] != '-' && $item['performance'] < 50) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 6) {
                        if ($item['performance'] == '-') {
                            $performance[$i] = $item;
                            $i++;
                        }
                    }
                }
                if (count($performance) > 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['data'] = $performance;
                    $this->jsonarr['ResponseObject']['total_count'] = count($performance);
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";
                }
            } else {
                if (count($finalArray) > 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['data'] = $finalArray;
                    $this->jsonarr['ResponseObject']['total_count'] = count($finalArray);
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";
                }
            }
        }
        $this->common_model->createLog($params,'v1/report/studentReportClass',$this->jsonarr,'studentReportClass');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function studentReportClasslast_post(){
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['teacher_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "teacher Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentWiseClassReport','only request','studentWiseClassReport');
            $result = [];
            $j = 0;
            $date = date('Y-m-d');
            $studentList = $this->report_model->studentLists($params);
            foreach ($studentList as $key1 => $value1) {
                $content = [];
                $i = 0;
               // print_r($value1['student_id']." ");
                $result[$j]['student_id'] = $value1['student_id'];
                $result[$j]['student_name'] = $value1['student_name'];
                $result[$j]['content'][$i] = array();
                $totalAverage = 0;
                $checkAverage = 0;
                $contentId = implode(',', $params['content_id']);
                $contentFormat = $params['content_format'];
                $contentData = $this->report_model->contentReport($params['class_id'], $contentId, $value1['student_id'], $params['type']);

                if ($contentData) {
                    for ($z = 0; $z < count($contentData); $z++) {
                        $availableContent = array_push($content, $contentData[$z]['content_id']);
                        $endDate = strtotime($contentData[$z]['end_date']);
                        if ($contentData[$z]['status'] == 1) {
                            //yet to start
                            $contentData[$z]['status'] = "1";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[0]['status'] == 2) {
                            //inprogress
                            $contentData[$z]['status'] = "2";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 3) {
                            //completed
                            $contentData[$z]['status'] = "3";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 4) {
                            //overdue
                            $contentData[$z]['status'] = "4";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                        } elseif ($contentData[0]['status'] == 5) {
                            //not started
                            $contentData[$z]['status'] = "5";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[$z]['status'] == 6) {
                            //overdue
                            $contentData[$z]['status'] = "6";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                        }
                        if (isset($contentData[$z]['student_status']) && $contentData[$z]['student_status'] < 3) {
                            $totalAverage += 0;
                            $checkAverage++;
                        } else {
                            $totalAverage += 0;
                            // $checkAverage++;
                        }
                        $data = array();
                        if (isset($contentDate[$z]['points']) && $contentData[$z]['points'] != 0) {
                            $result[$j]['content'][$i]['student_average_score'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2) . "%";
                            $totalAverage += round($contentData[0]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                        } else {
                            $result[$j]['content'][$i]['student_average_score'] = 0 . "%";
                        }
                        $result[$j]['content'][$i]['content_type'] = isset($contentData[$z]['content_type']) ? $contentData[$z]['content_type'] : $contentData[$z]['content_type'];
                        $result[$j]['content'][$i]['content_name'] = isset($contentData[$z]['content_name']) ? $contentData[$z]['content_name'] : $contentData[$z]['content_name'];
                        $result[$j]['content'][$i]['content_id'] = isset($contentData[$z]['content_id']) ? $contentData[$z]['content_id'] : 0;
                        $result[$j]['content'][$i]['student_id'] = $value1['student_id'];
                        $result[$j]['content'][$i]['class_id'] = $params['class_id'];
                        $result[$j]['content'][$i]['content_format'] = isset($contentFormat[$z]) ? $contentFormat[$z] : 1;
                        //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                        $result[$j]['content'][$i]['color'] = isset($contentData[$z]['content_status']) ? $contentData[$z]['content_status'] : '4';
                        $result[$j]['content'][$i]['status'] = isset($contentData[$z]['status']) ? $contentData[$z]['status'] : 0;
                        $result[$j]['content'][$i]['over_due'] = isset($contentData[$z]['over_due']) ? $contentData[$z]['over_due'] : '';
                        $result[$j]['content'][$i]['student_score'] = isset($contentData[$z]['student_score']) ? $contentData[$z]['student_score'] : '';
                        $result[$j]['content'][$i]['total_score'] = isset($contentData[$z]['total_score']) ? $contentData[$z]['total_score'] : '';
                        $result[$j]['content'][$i]['end_date'] = isset($contentData[$z]['end_date']) ? $contentData[$z]['end_date'] : $contentData[$z]['end_date'];
                        $result[$j]['content'][$i]['attend_questions'] = isset($contentData[$z]['attend_questions']) ? $contentData[$z]['attend_questions'] : '';
                        $result[$j]['content'][$i]['total_questions'] = isset($contentData[$z]['total_questions']) ? $contentData[$z]['total_questions'] : '';
                        $result[$j]['content'][$i]['parents_notify_count'] = isset($contentData[$z]['parents_notify_count']) ? $contentData[$z]['parents_notify_count'] : '';
                        if (count($params['content_id']) == 0) {
                            $result[$j]['content'][$i] = array();
                        }
                        if (count($params['content_id']) == $checkAverage) {
                            $result[$j]['performance'] = '-';

                        } else {
                            $result[$j]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$j]['content']) : 0, 2);
                        }
                        $result[$j]['count'] = count($params['content_id']);
                        $i++;
                    }
                }


                $unavailable_content = array_values(array_diff($params['content_id'], $content));

                if ($unavailable_content) {
                for ($x = 0; $x < count($unavailable_content); $x++) {
                    //  else {

                    $contentName = $this->report_model->getContentName($params, $unavailable_content[$x]);

                    $result[$j]['content'][$i]['student_average_score'] = 0 . "%";
                    $result[$j]['content'][$i]['content_name'] = $contentName['content_name'];
                    $result[$j]['content'][$i]['content_id'] = $unavailable_content[$x];
                    $result[$j]['content'][$i]['student_id'] = $value1['student_id'];
                    $result[$j]['content'][$i]['class_id'] = $params['class_id'];
                    $result[$j]['content'][$i]['content_type'] = $contentName['content_type'];
                    $result[$j]['content'][$i]['content_format'] = 1;
                    $result[$j]['content'][$i]['color'] = '4';
                    $result[$j]['content'][$i]['status'] = 0;
                    $result[$j]['content'][$i]['over_due'] = '';
                    $result[$j]['content'][$i]['student_score'] = '';
                    $result[$j]['content'][$i]['total_score'] = '';
                    $result[$j]['content'][$i]['end_date'] = $contentName['end_date'];
                    $result[$j]['content'][$i]['attend_questions'] = 0;
                    $result[$j]['content'][$i]['total_questions'] = '';
                    $result[$j]['content'][$i]['parents_notify_count'] = '';
                    if (count($params['content_id']) == $checkAverage) {
                        $result[$j]['performance'] = '-';
                    } else {
                        $result[$j]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$j]['content']) : 0, 2);
                    }
                    $result[$j]['count'] = count($params['content_id']);
                    $i++;
                }
                    unset($content);
            }
                $j++;
            }
           // print_r($result);
                $finalArray = array();
                foreach ($result as $resultKey => $resultValue) {
                    $z = 0;
//                    foreach ($resultValue['content'] as $valuKey => $contentValue) {
//                        if ($contentValue['status'] != 0) {
//                        //array_push($finalArray,$resultValue);
//                             $z++;
//                           }
//                    }
//                          if ($z != 0) {
                    // $search = array_search($resultValue,$finalArray,true);
                    //if (!$search) {
                    array_push($finalArray, $resultValue);
                    //  }

                    }


                if (isset($params['performance']) && $params['performance'] != 0) {
                    $performance = [];
                    $i = 0;
                    foreach ($finalArray as $key => $item) {
                        if ($params['performance'] == 1) {
                            if ($item['performance'] != '-' && $item['performance'] >= 90) {
                                $performance[$i] = $item;
                                $i++;
                            }
                        } elseif ($params['performance'] == 2) {
                            if ($item['performance'] != '-' && $item['performance'] >= 80 && $item['performance'] <= 89.99) {
                                $performance[$i] = $item;
                                $i++;
                            }
                        } elseif ($params['performance'] == 3) {
                            if ($item['performance'] != '-' && $item['performance'] >= 70 && $item['performance'] <= 70.99) {
                                $performance[$i] = $item;
                                $i++;
                            }
                        } elseif ($params['performance'] == 4) {
                            if ($item['performance'] != '-' && $item['performance'] >= 50 && $item['performance'] < 69.99) {
                                $performance[$i] = $item;
                                $i++;
                            }
                        } elseif ($params['performance'] == 5) {
                            if ($item['performance'] != '-' && $item['performance'] < 50) {
                                $performance[$i] = $item;
                                $i++;
                            }
                        } elseif ($params['performance'] == 6) {
                            if ($item['performance'] == '-') {
                                $performance[$i] = $item;
                                $i++;
                            }
                        }
                    }

                    if (count($performance) > 0) {
                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject']['data'] = $performance;
                        $this->jsonarr['ResponseObject']['total_count'] = count($performance);
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ResponseObject'] = "No Records found";
                    }
                } else {
                    if (count($finalArray) > 0) {
                       // print_r($finalArray);
                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject']['data'] = $finalArray;
                        $this->jsonarr['ResponseObject']['total_count'] = count($finalArray);
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ResponseObject'] = "No Records found";
                    }
                }

        }
        $this->common_model->createLog($params,'v1/report/studentWiseClassReport',$this->jsonarr,'studentWiseClassReport');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }


    public function studentReportClass_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
     //   $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['teacher_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "teacher Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentWiseClassReport','only request','studentWiseClassReport');
            $result = [];
            $studentId = [];
            $studentName = [];
            $j = 0;
            $date = date('Y-m-d');
            $studentList = $this->report_model->studentLists($params);
            foreach ($studentList as $key1 => $value1) {
                array_push($studentId, $value1['student_id']);
            }
            array_values(array_unique($studentId));
            $stuId = implode(',', $studentId);

            $content = [];

            // print_r($value1['student_id']." ");

            $totalAverage = 0;
            $checkAverage = 0;
            $contentId = implode(',', $params['content_id']);
            $contentFormat = $params['content_format'];
            $params['class_content_id'] = isset($params['class_content_id']) && $params['class_content_id'] != '' ? $params['class_content_id']:"";
            $contentData = $this->report_model->contentReport($params['class_id'], $contentId, $stuId, $params['type'],$params['class_content_id']);
            $getTotalAttendedQuestions = $this->report_model->getTotalAttendedQuestions($params['class_id'], $contentId, $stuId, $params['type']);
            for ($z = 0; $z < count($contentData); $z++) {
                $dataAvailable = false;
                for ($e = 0; $e < count($result); $e++) {
                    if ($contentData[$z]['student_id'] == $result[$e]['student_id']) {
                        $dataAvailable = true;
                    }
                }
                if (!$dataAvailable) {
                    $result[$e]['student_id'] = $contentData[$z]['student_id'];
                    for ($f =0; $f < count($studentList); $f++) {
                        if ($studentList[$f]['student_id'] == $contentData[$z]['student_id']) {
                            $result[$e]['student_name'] = $studentList[$f]['student_name'];
                        }
                    }
                    // $result[$e]['content'][$i] = array();

                    if ($contentData) {
                        //for ($z = 0; $z < count($contentData); $z++) {
                        $availableContent = array_push($content, $contentData[$z]['content_id']);
                        $endDate = strtotime($contentData[$z]['end_date']);
                        if ($contentData[$z]['status'] == 1) {
                            //yet to start
                            $contentData[$z]['status'] = "1";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[$z]['status'] == 2) {
                            //inprogress
                            $contentData[$z]['status'] = "2";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 3) {
                            //completed
                            $contentData[$z]['status'] = "3";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 4) {
                            //overdue
                            $contentData[$z]['status'] = "4";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                        } elseif ($contentData[$z]['status'] == 5) {
                            //not started
                            $contentData[$z]['status'] = "5";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[$z]['status'] == 6) {
                            //overdue
                            $contentData[$z]['status'] = "6";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);
                        } elseif ($contentData[$z]['status'] == 7) {
                            //correction pending
                            $contentData[$z]['status'] = "7";
                            $contentData[$z]['over_due'] = 0;
                        }
                        if (isset($contentData[$z]['student_status']) && $contentData[$z]['student_status'] < 3) {
                            $totalAverage += 0;
                            $checkAverage++;
                        } else {
                            $totalAverage += 0;
//                            $checkAverage++;
                        }
                        $data = array();
                        if (isset($contentData[$z]['points']) && $contentData[$z]['points'] != 0) {
                            $result[$e]['content'][0]['student_average_score'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2) . "%";
                            $result[$e]['content'][0]['percentage'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                            $totalAverage += round($contentData[0]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                        } else {
                            $result[$e]['content'][0]['student_average_score'] = 0 . "%";
                            $result[$e]['content'][0]['percentage'] = 0;
                        }
                        $result[$e]['content'][0]['content_type'] = isset($contentData[$z]['content_type']) ? $contentData[$z]['content_type'] : $contentData[$z]['content_type'];
                        $result[$e]['content'][0]['content_name'] = isset($contentData[$z]['content_name']) ? $contentData[$z]['content_name'] : $contentData[$z]['content_name'];
                        $result[$e]['content'][0]['content_id'] = isset($contentData[$z]['content_id']) ? $contentData[$z]['content_id'] : 0;
                        $result[$e]['content'][0]['student_id'] = $contentData[$z]['student_id'];
                        $result[$e]['content'][0]['class_content_id'] = $contentData[$z]['class_content_id'];
                        $result[$e]['content'][0]['student_content_id'] = $contentData[$z]['student_content_id'];
                        $result[$e]['content'][0]['class_id'] = $params['class_id'];
                        $result[$e]['content'][0]['content_format'] = isset($contentData[$z]['content_format']) ? $contentData[$z]['content_format'] : 1;
                        //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                        $result[$e]['content'][0]['color'] = isset($contentData[$z]['content_status']) ? $contentData[$z]['content_status'] : '4';
                        $result[$e]['content'][0]['status'] = isset($contentData[$z]['status']) ? $contentData[$z]['status'] : 0;
                        $result[$e]['content'][0]['over_due'] = isset($contentData[$z]['over_due']) ? $contentData[$z]['over_due'] : '';
                        $result[$e]['content'][0]['student_score'] = isset($contentData[$z]['earned_points']) ? $contentData[$z]['earned_points'] : '';
                        $result[$e]['content'][0]['total_score'] = isset($contentData[$z]['points']) ? $contentData[$z]['points'] : '';
                        $result[$e]['content'][0]['start_date'] = isset($contentData[$z]['start_date']) ? $contentData[$z]['start_date'] : $contentData[$z]['start_date'];
                        $result[$e]['content'][0]['end_date'] = isset($contentData[$z]['end_date']) ? $contentData[$z]['end_date'] : $contentData[$z]['end_date'];
                        $result[$e]['content'][0]['attend_questions'] = isset($contentData[$z]['attend_questions']) ? $contentData[$z]['attend_questions'] : 0;
                        $result[$e]['content'][0]['total_questions'] = isset($contentData[$z]['total_questions']) ? $contentData[$z]['total_questions'] : 0;
                        $result[$e]['content'][0]['parents_notify_count'] = isset($contentData[$z]['parents_notify_count']) ? $contentData[$z]['parents_notify_count'] : '';
                        if (count($params['content_id']) == 0) {
                            $result[$e]['content'][0] = array();
                        }
//                        if (count($params['content_id']) == $checkAverage) {
//                            $result[$e]['performance'] = '-';
//
//                        } else {
//                            $result[$e]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$e]['content']) : 0, 2);
//                        }
                        $result[$e]['count'] = count($params['content_id']);
                    }
                } else {
                    if ($contentData) {
                        for ($x = 0; $x < count($result); $x++) {
                            $count = count($result[$x]['content']);
                            if ($contentData[$z]['student_id'] == $result[$x]['student_id']) {
                                $availableContent = array_push($content, $contentData[$z]['content_id']);
                                $endDate = strtotime($contentData[$z]['end_date']);

                                if ($contentData[$z]['status'] == 1) {
                                    //yet to start
                                    $contentData[$z]['status'] = "1";
                                    $contentData[$z]['over_due'] = 0;
                                } elseif ($contentData[$z]['status'] == 2) {
                                    //inprogress
                                    $contentData[$z]['status'] = "2";
                                    $contentData[$z]['over_due'] = 0;

                                } elseif ($contentData[$z]['status'] == 3) {
                                    //completed
                                    $contentData[$z]['status'] = "3";
                                    $contentData[$z]['over_due'] = 0;

                                } elseif ($contentData[$z]['status'] == 4) {
                                    //overdue
                                    $contentData[$z]['status'] = "4";
                                    $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                                } elseif ($contentData[$z]['status'] == 5) {
                                    //not started
                                    $contentData[$z]['status'] = "5";
                                    $contentData[$z]['over_due'] = 0;
                                } elseif ($contentData[$z]['status'] == 6) {
                                    //overdue
                                    $contentData[$z]['status'] = "6";
                                    $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                                } elseif ($contentData[$z]['status'] == 7) {
                                    //correction pending
                                    $contentData[$z]['status'] = "7";
                                    $contentData[$z]['over_due'] = 0;
                                }
                                if (isset($contentData[$z]['student_status']) && $contentData[$z]['student_status'] < 3) {
                                    $totalAverage += 0;
                                    $checkAverage++;
                                } else {
                                    $totalAverage += 0;
//                                     $checkAverage++;
                                }
                                $data = array();
                                if (isset($contentData[$z]['points']) && $contentData[$z]['points'] != 0) {
                                    $result[$x]['content'][$count]['student_average_score'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2) . "%";
                                    $result[$x]['content'][$count]['percentage'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                                    $totalAverage += round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                                } else {
                                    $result[$x]['content'][$count]['student_average_score'] = 0 . "%";
                                    $result[$x]['content'][$count]['percentage'] = 0;
                                }
                                $result[$x]['content'][$count]['content_type'] = isset($contentData[$z]['content_type']) ? $contentData[$z]['content_type'] : $contentData[$z]['content_type'];
                                $result[$x]['content'][$count]['content_name'] = isset($contentData[$z]['content_name']) ? $contentData[$z]['content_name'] : $contentData[$z]['content_name'];
                                $result[$x]['content'][$count]['content_id'] = isset($contentData[$z]['content_id']) ? $contentData[$z]['content_id'] : 0;
                                $result[$x]['content'][$count]['student_id'] = $contentData[$z]['student_id'];
                                $result[$x]['content'][$count]['class_content_id'] = $contentData[$z]['class_content_id'];
                                $result[$x]['content'][$count]['student_content_id'] = $contentData[$z]['student_content_id'];
                                $result[$x]['content'][$count]['class_id'] = $params['class_id'];
                                $result[$x]['content'][$count]['content_format'] = isset($contentData[$z]['content_format']) ? $contentData[$z]['content_format'] : 1;
                                //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                                $result[$x]['content'][$count]['color'] = isset($contentData[$z]['content_status']) ? $contentData[$z]['content_status'] : '4';
                                $result[$x]['content'][$count]['status'] = isset($contentData[$z]['status']) ? $contentData[$z]['status'] : 0;
                                $result[$x]['content'][$count]['over_due'] = isset($contentData[$z]['over_due']) ? $contentData[$z]['over_due'] : '';
                                $result[$x]['content'][$count]['student_score'] = isset($contentData[$z]['earned_points']) ? $contentData[$z]['earned_points'] : '';
                                $result[$x]['content'][$count]['total_score'] = isset($contentData[$z]['points']) ? $contentData[$z]['points'] : '';
                                $result[$x]['content'][$count]['start_date'] = isset($contentData[$z]['start_date']) ? $contentData[$z]['start_date'] : $contentData[$z]['start_date'];
                                $result[$x]['content'][$count]['end_date'] = isset($contentData[$z]['end_date']) ? $contentData[$z]['end_date'] : $contentData[$z]['end_date'];
                                $result[$x]['content'][$count]['attend_questions'] = isset($contentData[$z]['attend_questions']) ? $contentData[$z]['attend_questions'] : 0;
                                $result[$x]['content'][$count]['total_questions'] = isset($contentData[$z]['total_questions']) ? $contentData[$z]['total_questions'] : 0;
                                $result[$x]['content'][$count]['parents_notify_count'] = isset($contentData[$z]['parents_notify_count']) ? $contentData[$z]['parents_notify_count'] : '';
                               if (count($params['content_id']) == 0) {
                                   $result[$x]['content'][$count] = array();
                               }
//                                if (count($params['content_id']) == $checkAverage) {
//                                    $result[$x]['performance'] = '-';
//
//                                } else {
//                                    $result[$x]['performance'] = round(count($result[$x]['content']) != 0 ? $totalAverage / count($result[$x]['content']) : 0, 2);
//                                }
                                $result[$x]['count'] = count($params['content_id']);
                                // $i++;
                            }
                        }
                    }
                }
            }
            //pushing total and attend questions to students
            for ($z = 0; $z < count($getTotalAttendedQuestions); $z++) {
                for ($i = 0; $i < count($result); $i++) {
                    for ($j = 0; $j < count($result[$i]['content']); $j++) {
                        if ($getTotalAttendedQuestions[$z]['student_id'] == $result[$i]['student_id']) {
                            if ($getTotalAttendedQuestions[$z]['content_id'] == $result[$i]['content'][$j]['content_id']) {
                                $result[$i]['content'][$j]['total_questions'] = $getTotalAttendedQuestions[$z]['total_questions'];
                                $result[$i]['content'][$j]['attend_questions'] = $getTotalAttendedQuestions[$z]['attend_questions'];
                                //$result[$i]['content'][$j]['percentage'] = round($result[$i]['content'][$j]['total_questions'] != 0 ? $result[$i]['content'][$j]['attend_questions'] / $result[$i]['content'][$j]['total_questions'] * 100 : 0, 2);
                            }
                        }
                    }
                }
            }
            //getting names of content
            $contentNames = $this->report_model->getContentNames(implode(',', $params['content_id']), $params['class_id']);
            for ($a = 0; $a < count($contentNames); $a++) {
                for ($c = 0; $c < count($result); $c++) {
                    if (array_search($contentNames[$a]['id'], array_column($result[$c]['content'], 'class_content_id')) === false) {
                        $b = count($result[$c]['content']);
                        $result[$c]['content'][$b]['student_average_score'] = 0 . "%";
                        $result[$c]['content'][$b]['content_name'] = $contentNames[$a]['content_name'];
                        $result[$c]['content'][$b]['content_id'] = $contentNames[$a]['content_id'];
                        $result[$c]['content'][$b]['student_id'] = $result[$c]['student_id'];
                        $result[$c]['content'][$b]['student_content_id'] = 0;
                        $result[$c]['content'][$b]['class_content_id'] = $contentNames[$a]['id'];
                        $result[$c]['content'][$b]['class_id'] = $params['class_id'];
                        $result[$c]['content'][$b]['content_type'] = $contentNames[$a]['content_type'];
                        $result[$c]['content'][$b]['content_format'] = 1;
                        $result[$c]['content'][$b]['color'] = '4';
                        $result[$c]['content'][$b]['status'] = 0;
                        $result[$c]['content'][$b]['over_due'] = '';
                        $result[$c]['content'][$b]['student_score'] = '';
                        $result[$c]['content'][$b]['total_score'] = '';
                        $result[$c]['content'][$b]['start_date'] = $contentNames[$a]['start_date'];
                        $result[$c]['content'][$b]['end_date'] = $contentNames[$a]['end_date'];
                        $result[$c]['content'][$b]['attend_questions'] = 0;
                        $result[$c]['content'][$b]['total_questions'] = 0;
                        $result[$c]['content'][$b]['parents_notify_count'] = '';
                        $result[$c]['content'][$b]['percentage'] = 0;
//                        if (count($params['content_id']) == $checkAverage) {
//                            $result[$c]['content'][$b]['performance'] = '-';
//                        } else {
//                            $result[$c]['content'][$b]['performance'] = round(count($result[$c]['content']) != 0 ? $totalAverage / count($result[$c]['content']) : 0, 2);
//                        }
                        $result[$c]['count'] = count($params['content_id']);
                    }
                }
            }
            foreach($result as $key => $value) {
                $sum = $check = 0;
                foreach ($value['content'] as $key1 => $item) {
                    if($item['status'] == 0 || $item['status'] == 5) {
                        $check++;
                    }
                    $sum += $item['percentage'];
                }
                if($check == count($value['content'])) {
                    $result[$key]['performance'] = '-';
                } else {
                    $result[$key]['performance'] = number_format($sum != 0 ? ($sum/count($value['content'])) : 0,2);
                }
            }

            // print_r($result); exit();
            $finalArray = array();
            foreach ($result as $resultKey => $resultValue) {
                $z = 0;
//                    foreach ($resultValue['content'] as $valuKey => $contentValue) {
//                        if ($contentValue['status'] != 0) {
//                        //array_push($finalArray,$resultValue);
//                             $z++;
//                           }
//                    }
//                          if ($z != 0) {
                // $search = array_search($resultValue,$finalArray,true);
                //if (!$search) {
                // print_r($resultValue);
                array_push($finalArray, $resultValue);
                //  }

            }

            if (isset($params['performance']) && $params['performance'] != 0) {
                $performance = [];
                $i = 0;
                foreach ($finalArray as $key => $item) {
                    if ($params['performance'] == 1) {
                        if ($item['performance'] != '-' && $item['performance'] >= 90) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 2) {
                        if ($item['performance'] != '-' && $item['performance'] >= 80 && $item['performance'] <= 89.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 3) {
                        if ($item['performance'] != '-' && $item['performance'] >= 70 && $item['performance'] <= 70.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 4) {
                        if ($item['performance'] != '-' && $item['performance'] >= 50 && $item['performance'] < 69.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 5) {
                        if ($item['performance'] != '-' && $item['performance'] < 50) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 6) {
                        if ($item['performance'] == '-') {
                            $performance[$i] = $item;
                            $i++;
                        }
                    }
                }

                if (count($performance) > 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['data'] = $performance;
                    $this->jsonarr['ResponseObject']['total_count'] = count($performance);
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";
                }
            } else {
                if (count($finalArray) > 0) {
                    // print_r($finalArray);
                    foreach ($finalArray as $key => $value){
                        $keys = array_column($finalArray[$key]['content'], 'end_date');
                        $keys1 = array_column($finalArray[$key]['content'], 'class_content_id');
                        array_multisort($keys, SORT_ASC,$keys1, SORT_ASC, $finalArray[$key]['content']);
                    }
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['data'] = $finalArray;
                    $this->jsonarr['ResponseObject']['total_count'] = count($finalArray);
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";
                }
            }

            //}
            //$this->common_model->createLog($params,'v1/report/studentWiseClassReport',$this->jsonarr,'studentWiseClassReport');
            $this->benchmark->mark('code_end');
            $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
            return $this->printjson($this->jsonarr);
        }
    }

    public function studentReportClassPrint_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
       // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['teacher_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "teacher Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentWiseClassReport','only request','studentWiseClassReport');
            $result = [];
            $studentId = [];
            $studentName = [];
            $j = 0;
            $date = date('Y-m-d');
            $studentList = $this->report_model->studentLists($params);
            foreach ($studentList as $key1 => $value1) {
                array_push($studentId, $value1['student_id']);
            }
            array_values(array_unique($studentId));
            $stuId = implode(',', $studentId);

            $content = [];

            // print_r($value1['student_id']." ");

            $totalAverage = 0;
            $checkAverage = 0;
            $contentId = implode(',', $params['content_id']);
            $contentFormat = $params['content_format'];
            $params['class_content_id'] = isset($params['class_content_id']) && $params['class_content_id'] != '' ? $params['class_content_id'] : "";
            $contentData = $this->report_model->contentReport($params['class_id'], $contentId, $stuId, $params['type'],$params['class_content_id']);
            $getTotalAttendedQuestions = $this->report_model->getTotalAttendedQuestions($params['class_id'], $contentId, $stuId, $params['type']);
            for ($z = 0; $z < count($contentData); $z++) {
                $dataAvailable = false;
                for ($e = 0; $e < count($result); $e++) {
                    if ($contentData[$z]['student_id'] == $result[$e]['student_id']) {
                        $dataAvailable = true;
                    }
                }
                if (!$dataAvailable) {
                    $result[$e]['student_id'] = $contentData[$z]['student_id'];
                    for ($f =0; $f < count($studentList); $f++) {
                        if ($studentList[$f]['student_id'] == $contentData[$z]['student_id']) {
                            $result[$e]['student_name'] = $studentList[$f]['student_name'];
                        }
                    }
                    // $result[$e]['content'][$i] = array();

                    if ($contentData) {
                        //for ($z = 0; $z < count($contentData); $z++) {
                        $availableContent = array_push($content, $contentData[$z]['content_id']);
                        $endDate = strtotime($contentData[$z]['end_date']);
                        if ($contentData[$z]['status'] == 1) {
                            //yet to start
                            $contentData[$z]['status'] = "1";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[$z]['status'] == 2) {
                            //inprogress
                            $contentData[$z]['status'] = "2";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 3) {
                            //completed
                            $contentData[$z]['status'] = "3";
                            $contentData[$z]['over_due'] = 0;

                        } elseif ($contentData[$z]['status'] == 4) {
                            //overdue
                            $contentData[$z]['status'] = "4";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                        } elseif ($contentData[$z]['status'] == 5) {
                            //not started
                            $contentData[$z]['status'] = "5";
                            $contentData[$z]['over_due'] = 0;
                        } elseif ($contentData[$z]['status'] == 6) {
                            //overdue
                            $contentData[$z]['status'] = "6";
                            $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);
                        } elseif ($contentData[$z]['status'] == 7) {
                            //correction pending
                            $contentData[$z]['status'] = "7";
                            $contentData[$z]['over_due'] = 0;
                        }
                        if (isset($contentData[$z]['student_status']) && $contentData[$z]['student_status'] < 3) {
                            $totalAverage += 0;
                            $checkAverage++;
                        } else {
                            $totalAverage += 0;
//                            $checkAverage++;
                        }
                        $data = array();
                        if (isset($contentData[$z]['points']) && $contentData[$z]['points'] != 0) {
                            $result[$e]['content'][0]['student_average_score'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2) . "%";
                            $result[$e]['content'][0]['percentage'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                            $totalAverage += round($contentData[0]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                        } else {
                            $result[$e]['content'][0]['student_average_score'] = 0 . "%";
                            $result[$e]['content'][0]['percentage'] = 0;
                        }
                        $result[$e]['content'][0]['content_type'] = isset($contentData[$z]['content_type']) ? $contentData[$z]['content_type'] : $contentData[$z]['content_type'];
                        $result[$e]['content'][0]['content_name'] = isset($contentData[$z]['content_name']) ? $contentData[$z]['content_name'] : $contentData[$z]['content_name'];
                        $result[$e]['content'][0]['content_id'] = isset($contentData[$z]['content_id']) ? $contentData[$z]['content_id'] : 0;
                        $result[$e]['content'][0]['student_id'] = $contentData[$z]['student_id'];
                        $result[$e]['content'][0]['class_content_id'] = $contentData[$z]['class_content_id'];
                        $result[$e]['content'][0]['student_content_id'] = $contentData[$z]['student_content_id'];
                        $result[$e]['content'][0]['class_id'] = $params['class_id'];
                        $result[$e]['content'][0]['content_format'] = isset($contentData[$z]['content_format']) ? $contentData[$z]['content_format'] : 1;
                        //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                        $result[$e]['content'][0]['color'] = isset($contentData[$z]['content_status']) ? $contentData[$z]['content_status'] : '4';
                        $result[$e]['content'][0]['status'] = isset($contentData[$z]['status']) ? $contentData[$z]['status'] : 0;
                        $result[$e]['content'][0]['over_due'] = isset($contentData[$z]['over_due']) ? $contentData[$z]['over_due'] : '';
                        $result[$e]['content'][0]['student_score'] = isset($contentData[$z]['student_score']) ? $contentData[$z]['student_score'] : '';
                        $result[$e]['content'][0]['total_score'] = isset($contentData[$z]['total_score']) ? $contentData[$z]['total_score'] : '';
                        $result[$e]['content'][0]['end_date'] = isset($contentData[$z]['end_date']) ? $contentData[$z]['end_date'] : $contentData[$z]['end_date'];
                        $result[$e]['content'][0]['attend_questions'] = isset($contentData[$z]['attend_questions']) ? $contentData[$z]['attend_questions'] : 0;
                        $result[$e]['content'][0]['total_questions'] = isset($contentData[$z]['total_questions']) ? $contentData[$z]['total_questions'] : 0;
                        $result[$e]['content'][0]['parents_notify_count'] = isset($contentData[$z]['parents_notify_count']) ? $contentData[$z]['parents_notify_count'] : '';
                        if (count($params['content_id']) == 0) {
                            $result[$e]['content'][0] = array();
                        }
//                        if (count($params['content_id']) == $checkAverage) {
//                            $result[$e]['performance'] = '-';
//
//                        } else {
//                            $result[$e]['performance'] = round(count($result[$j]['content']) != 0 ? $totalAverage / count($result[$e]['content']) : 0, 2);
//                        }
                        $result[$e]['count'] = count($params['content_id']);
                    }
                } else {
                    if ($contentData) {
                        for ($x = 0; $x < count($result); $x++) {
                            $count = count($result[$x]['content']);
                            if ($contentData[$z]['student_id'] == $result[$x]['student_id']) {
                                $availableContent = array_push($content, $contentData[$z]['content_id']);
                                $endDate = strtotime($contentData[$z]['end_date']);

                                if ($contentData[$z]['status'] == 1) {
                                    //yet to start
                                    $contentData[$z]['status'] = "1";
                                    $contentData[$z]['over_due'] = 0;
                                } elseif ($contentData[$z]['status'] == 2) {
                                    //inprogress
                                    $contentData[$z]['status'] = "2";
                                    $contentData[$z]['over_due'] = 0;

                                } elseif ($contentData[$z]['status'] == 3) {
                                    //completed
                                    $contentData[$z]['status'] = "3";
                                    $contentData[$z]['over_due'] = 0;

                                } elseif ($contentData[$z]['status'] == 4) {
                                    //overdue
                                    $contentData[$z]['status'] = "4";
                                    $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                                } elseif ($contentData[$z]['status'] == 5) {
                                    //not started
                                    $contentData[$z]['status'] = "5";
                                    $contentData[$z]['over_due'] = 0;
                                } elseif ($contentData[$z]['status'] == 6) {
                                    //overdue
                                    $contentData[$z]['status'] = "6";
                                    $contentData[$z]['over_due'] = ceil(abs($endDate - strtotime($date)) / 86400);

                                } elseif ($contentData[$z]['status'] == 7) {
                                    //correction pending
                                    $contentData[$z]['status'] = "7";
                                    $contentData[$z]['over_due'] = 0;
                                }
                                if (isset($contentData[$z]['student_status']) && $contentData[$z]['student_status'] < 3) {
                                    $totalAverage += 0;
                                    $checkAverage++;
                                } else {
                                    $totalAverage += 0;
//                                     $checkAverage++;
                                }
                                $data = array();
                                if (isset($contentData[$z]['points']) && $contentData[$z]['points'] != 0) {
                                    $result[$x]['content'][$count]['student_average_score'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2) . "%";
                                    $result[$x]['content'][$count]['percentage'] = round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                                    $totalAverage += round($contentData[$z]['points'] ? ($contentData[$z]['earned_points'] / $contentData[$z]['points']) * 100 : 0, 2);
                                } else {
                                    $result[$x]['content'][$count]['student_average_score'] = 0 . "%";
                                    $result[$x]['content'][$count]['percentage'] = 0;
                                }
                                $result[$x]['content'][$count]['content_type'] = isset($contentData[$z]['content_type']) ? $contentData[$z]['content_type'] : $contentData[$z]['content_type'];
                                $result[$x]['content'][$count]['content_name'] = isset($contentData[$z]['content_name']) ? $contentData[$z]['content_name'] : $contentData[$z]['content_name'];
                                $result[$x]['content'][$count]['content_id'] = isset($contentData[$z]['content_id']) ? $contentData[$z]['content_id'] : 0;
                                $result[$x]['content'][$count]['student_id'] = $contentData[$z]['student_id'];
                                $result[$x]['content'][$count]['class_content_id'] = $contentData[$z]['class_content_id'];
                                $result[$x]['content'][$count]['student_content_id'] = $contentData[$z]['student_content_id'];
                                $result[$x]['content'][$count]['class_id'] = $params['class_id'];
                                $result[$x]['content'][$count]['content_format'] = isset($contentData[$z]['content_format']) ? $contentData[$z]['content_format'] : 1;
                                //$score = $this->chartCalculation($students[$key2]['earned_points'], $students[$key2]['points']);
                                $result[$x]['content'][$count]['color'] = isset($contentData[$z]['content_status']) ? $contentData[$z]['content_status'] : '4';
                                $result[$x]['content'][$count]['status'] = isset($contentData[$z]['status']) ? $contentData[$z]['status'] : 0;
                                $result[$x]['content'][$count]['over_due'] = isset($contentData[$z]['over_due']) ? $contentData[$z]['over_due'] : '';
                                $result[$x]['content'][$count]['student_score'] = isset($contentData[$z]['student_score']) ? $contentData[$z]['student_score'] : '';
                                $result[$x]['content'][$count]['total_score'] = isset($contentData[$z]['total_score']) ? $contentData[$z]['total_score'] : '';
                                $result[$x]['content'][$count]['end_date'] = isset($contentData[$z]['end_date']) ? $contentData[$z]['end_date'] : $contentData[$z]['end_date'];
                                $result[$x]['content'][$count]['attend_questions'] = isset($contentData[$z]['attend_questions']) ? $contentData[$z]['attend_questions'] : 0;
                                $result[$x]['content'][$count]['total_questions'] = isset($contentData[$z]['total_questions']) ? $contentData[$z]['total_questions'] : 0;
                                $result[$x]['content'][$count]['parents_notify_count'] = isset($contentData[$z]['parents_notify_count']) ? $contentData[$z]['parents_notify_count'] : '';
                                if (count($params['content_id']) == 0) {
                                    $result[$x]['content'][$count] = array();
                                }
//                                if (count($params['content_id']) == $checkAverage) {
//                                    $result[$x]['performance'] = '-';
//
//                                } else {
//                                    $result[$x]['performance'] = round(count($result[$x]['content']) != 0 ? $totalAverage / count($result[$x]['content']) : 0, 2);
//                                }
                                $result[$x]['count'] = count($params['content_id']);
                                // $i++;
                            }
                        }
                    }
                }
            }
            //pushing total and attend questions to students
            for ($z = 0; $z < count($getTotalAttendedQuestions); $z++) {
                for ($i = 0; $i < count($result); $i++) {
                    for ($j = 0; $j < count($result[$i]['content']); $j++) {
                        if ($getTotalAttendedQuestions[$z]['student_id'] == $result[$i]['student_id']) {
                            if ($getTotalAttendedQuestions[$z]['content_id'] == $result[$i]['content'][$j]['content_id']) {
                                $result[$i]['content'][$j]['total_questions'] = $getTotalAttendedQuestions[$z]['total_questions'];
                                $result[$i]['content'][$j]['attend_questions'] = $getTotalAttendedQuestions[$z]['attend_questions'];
                                //$result[$i]['content'][$j]['percentage'] = round($result[$i]['content'][$j]['total_questions'] != 0 ? $result[$i]['content'][$j]['attend_questions'] / $result[$i]['content'][$j]['total_questions'] * 100 : 0, 2);
                            }
                        }
                    }
                }
            }
            //getting names of content
            $contentNames = $this->report_model->getContentNames(implode(',', $params['content_id']), $params['class_id']);
            for ($a = 0; $a < count($contentNames); $a++) {
                for ($c = 0; $c < count($result); $c++) {
                    if (array_search($contentNames[$a]['id'], array_column($result[$c]['content'], 'class_content_id')) === false) {
                        $b = count($result[$c]['content']);
                        $result[$c]['content'][$b]['student_average_score'] = 0 . "%";
                        $result[$c]['content'][$b]['content_name'] = $contentNames[$a]['content_name'];
                        $result[$c]['content'][$b]['content_id'] = $contentNames[$a]['content_id'];
                        $result[$c]['content'][$b]['student_id'] = $result[$c]['student_id'];
                        $result[$c]['content'][$b]['student_content_id'] = 0;
                        $result[$c]['content'][$b]['class_content_id'] = $contentNames[$a]['id'];
                        $result[$c]['content'][$b]['class_id'] = $params['class_id'];
                        $result[$c]['content'][$b]['content_type'] = $contentNames[$a]['content_type'];
                        $result[$c]['content'][$b]['content_format'] = 1;
                        $result[$c]['content'][$b]['color'] = '4';
                        $result[$c]['content'][$b]['status'] = 0;
                        $result[$c]['content'][$b]['over_due'] = '';
                        $result[$c]['content'][$b]['student_score'] = '';
                        $result[$c]['content'][$b]['total_score'] = '';
                        $result[$c]['content'][$b]['end_date'] = $contentNames[$a]['end_date'];
                        $result[$c]['content'][$b]['attend_questions'] = 0;
                        $result[$c]['content'][$b]['total_questions'] = 0;
                        $result[$c]['content'][$b]['parents_notify_count'] = '';
                        $result[$c]['content'][$b]['percentage'] = 0;
//                        if (count($params['content_id']) == $checkAverage) {
//                            $result[$c]['content'][$b]['performance'] = '-';
//                        } else {
//                            $result[$c]['content'][$b]['performance'] = round(count($result[$c]['content']) != 0 ? $totalAverage / count($result[$c]['content']) : 0, 2);
//                        }
                        $result[$c]['count'] = count($params['content_id']);
                    }
                }
            }
            foreach($result as $key => $value) {
                $sum = $check = 0;
                foreach ($value['content'] as $key1 => $item) {
                    if($item['status'] == 0 || $item['status'] == 5) {
                        $check++;
                    }
                    $sum += $item['percentage'];
                }
                if($check == count($value['content'])) {
                    $result[$key]['performance'] = '-';
                } else {
                    $result[$key]['performance'] = number_format($sum != 0 ? ($sum/count($value['content'])) : 0,2);
                }
            }

            // print_r($result); exit();
            $finalArray = array();
            foreach ($result as $resultKey => $resultValue) {
                $z = 0;
//                    foreach ($resultValue['content'] as $valuKey => $contentValue) {
//                        if ($contentValue['status'] != 0) {
//                        //array_push($finalArray,$resultValue);
//                             $z++;
//                           }
//                    }
//                          if ($z != 0) {
                // $search = array_search($resultValue,$finalArray,true);
                //if (!$search) {
                // print_r($resultValue);
                array_push($finalArray, $resultValue);
                //  }

            }


            if (isset($params['performance']) && $params['performance'] != 0) {
                $performance = [];
                $i = 0;
                foreach ($finalArray as $key => $item) {
                    if ($params['performance'] == 1) {
                        if ($item['performance'] != '-' && $item['performance'] >= 90) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 2) {
                        if ($item['performance'] != '-' && $item['performance'] >= 80 && $item['performance'] <= 89.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 3) {
                        if ($item['performance'] != '-' && $item['performance'] >= 70 && $item['performance'] <= 70.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 4) {
                        if ($item['performance'] != '-' && $item['performance'] >= 50 && $item['performance'] < 69.99) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 5) {
                        if ($item['performance'] != '-' && $item['performance'] < 50) {
                            $performance[$i] = $item;
                            $i++;
                        }
                    } elseif ($params['performance'] == 6) {
                        if ($item['performance'] == '-') {
                            $performance[$i] = $item;
                            $i++;
                        }
                    }
                }

                if (count($performance) > 0) {
                    /*$this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject']['data'] = $performance;
                    $this->jsonarr['ResponseObject']['total_count'] = count($performance);*/
                    $data = [];
                    $data['data'] = $performance;
                    $data['total_count'] = count($performance);
                    echo 'data:'.json_encode($data);
                    exit();
                } else {
                   /* $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";*/
                    echo  "data:No Records found";
                }
            } else {
                if (count($finalArray) > 0) {
                    // print_r($finalArray);
                    foreach ($finalArray as $key => $value){
                        $keys = array_column($finalArray[$key]['content'], 'end_date');
                        $keys1 = array_column($finalArray[$key]['content'], 'class_content_id');
                        array_multisort($keys, SORT_ASC, $keys1, SORT_ASC, $finalArray[$key]['content']);
                    }
                   $data = [];
                   $data['data'] = $finalArray;
                   $data['total_count'] = count($finalArray);
                   echo 'data:'.json_encode($data);
                   exit();
                } else {
                    /*$this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ResponseObject'] = "No Records found";*/
                    echo  "data:No Records found";
                }
            }

            //}
            //$this->common_model->createLog($params,'v1/report/studentWiseClassReport',$this->jsonarr,'studentWiseClassReport');
           // $this->benchmark->mark('code_end');
           // $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
           // return $this->printjson($this->jsonarr);
        }
    }


    public function studentPerformanceContent() {
        $this->benchmark->start('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->request->headers();
        
        if (!isset($params['platform']) || ($params['platform'] != "web" && $params['platform'] != "ios")) {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif (!isset($params['role_id']) || $params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif (!isset($params['user_id']) || $params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (!isset($params['class_id']) || $params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } else {
            $contentList = [];
            
            if(isset($params['date_filter']) && $params['date_filter'] == true) {
                if (isset($params['content_id']) && count($params['content_id']) > 0) {
                    foreach ($params['content_id'] as $key => $value) {
                        $getcontent = $this->report_model->contentLists($params, $value);
                        if ($getcontent && count($getcontent) > 0) {
                            if(isset($params['date_filter']) && $params['date_filter'] == 2){
                                $contentList[$key] = $getcontent[0];
                            } else {
                                $contentList = $getcontent;
                            }
                        }
                    }
                } else {
                    $contentList = $this->report_model->contentLists($params, '0');
                }
            } elseif(isset($params['date_filter']) && $params['date_filter'] == "") {
                if (isset($params['content_id']) && count($params['content_id']) > 0) {
                    foreach ($params['content_id'] as $key => $value) {
                        $getcontent = $this->report_model->contentLists($params, $value);
                        if ($getcontent && count($getcontent) > 0) {
                            $contentList[$key] = $getcontent;
                        }
                    }
                } else {
                    $contentList = $this->report_model->contentLists($params, '0');
                }
            } else {
                $contentList = $this->report_model->contentLists($params, '0');
            }
            
            if ($contentList && count($contentList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $contentList;
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = [];
                $this->jsonarr['ErrorObject'] = 'No Records Found';
            }
        }
        
        $this->benchmark->stop('code_start');
        $this->jsonarr["processing_time"] = $this->benchmark->getElapsedTime('code_start');
        return $this->printjson($this->jsonarr);
    }

    public function studentPerformanceContentNew_post() {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        //$this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            //$this->common_model->createLog($params,'v1/report/studentPerformanceContent','only request','studentPerformanceContent');
            $contentList = [];
            if(isset($params['date_filter']) && $params['date_filter'] == true) {
                if (count($params['content_id']) > 0) {
                    $implode = implode(',',$params['content_id']);
                    //foreach ($params['content_id'] as $key => $value) {
                    $getcontent = $this->report_model->contentLists($params,$implode);
                    if ($getcontent > 0) {
                        if($params['date_filter'] == 2){
                            $contentList = $getcontent[0];
                        } else {
                            $contentList = $getcontent;
                        }
                    }
                    //}
                } else {
                    $contentList = $this->report_model->contentLists($params, '0');
                }
            } elseif(isset($params['date_filter']) && $params['date_filter'] == "") {
                if (count($params['content_id']) > 0) {
                    $implode = implode(',',$params['content_id']);
                    //foreach ($params['content_id'] as $key => $value) {
                    $getcontent = $this->report_model->contentLists($params, $implode);
                    if ($getcontent > 0) {
                        $contentList = $getcontent;
                    }
                    //}
                } else {
                    $contentList = $this->report_model->contentLists($params, '0');
                }
            } else {
                $contentList = $this->report_model->contentLists($params, '0');
            }
            if (count($contentList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $contentList;
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "No Records Found";
            }

        }
        
        $this->benchmark->stop('code_start');
        $this->jsonarr["processing_time"] = $this->benchmark->getElapsedTime('code_start');
        return $this->printjson($this->jsonarr);
    }

    public function studentPerformanceList_post() {
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/studentPerformanceList','only request','studentPerformanceList');
            $studentList = $this->report_model->studentLists($params);
            if (count($studentList) > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $studentList;
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "No records found";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/studentPerformanceList',$this->jsonarr,'studentPerformanceList');
        return $this->printjson($this->jsonarr);
    }

    public function reportSendsParent_post(){
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
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif ($params['student_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Student Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params,'v1/report/reportSendsParent','only request','reportSendsParent');
            $reports = $this->report_model->reports($params);
            for ($i=0;$i<count($reports);$i++){
                $p1 = explode(",",$reports[0]['email_ids']);
                for ($j=0;$j<count($reports);$j++){
                    $p2=explode(",",$reports[1]['email_ids']);
                }
            }
            $To=[];
            $To=array_merge($p1,$p2);
            $To = implode(',',$To);
            $contentId=implode(',', $params['content_id']);
            $result = $this->report_model->contentname($params['student_id'],$contentId,$params['class_id'],$params['school_id']);
            $send=[];
            foreach ($result as $key => $value){
                $send['student_name']=$value['student_name'];
                $send['class_name']=$value['class_name'];
                $send['school_name']=$value['school_name'];
                $send[$key]['content_name']=$value['content_name'];
            }
            print_r($send);exit;
            $messageTemplates = $this->common_model->smsEmailTemplate('parent_notify', 'email');
            $emailMsg = $messageTemplates['template'];
            $this->load->library('bitly');
            $url = $this->config->item('user_password_url');
           // $urlLink = $this->bitly->shorten($url);
            $emailMsg = str_replace('%URL%', $url, $emailMsg);
            $messageTemplates['subject'] = str_replace('%SCHOOL%', $send['school_name'], $messageTemplates['subject']);
            $emailMsg = str_replace('%STUDENTNAME%', $send['student_name'], $emailMsg);
            $emailMsg = str_replace('%CLASSNAME%', $send['class_name'], $emailMsg);
            $emailMsg = str_replace('%RECORDS%', $send[0]['content_name'], $emailMsg);
            if ($this->config->item('user_send_email') == true) {
                $mailSent = $this->common_model->sendEmail($messageTemplates['subject'],$To,$emailMsg, '','');
            }
            print_r($mailSent);
            if ($mailSent > 0) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Email Send Successfully";
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "No records found";
            }
        }
        $this->common_model->createLog($params,'v1/report/reportSendsParent',$this->jsonarr,'reportSendsParent');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function sendAdminReport_post(){
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
      //  $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } else {
            $this->common_model->createLog($params,'v1/report/sendAdminReport','only request','sendAdminReport');
            $object_excel = new PHPExcel();
            $cnt = 0;
            $getUsers =  $this->report_model->getUsers($params);
            if($getUsers){
                $totalRow = count($getUsers);
                $object_excel->getActiveSheet(0)->getStyle("A1:F1")
                    ->getFont()
                    ->setSize(10)
                    ->setBold(true);
                $object_excel->getActiveSheet(0)->getStyle("A2:F2" . $totalRow)
                    ->getFont()
                    ->setSize(10)
                    ->setBold(false);
                $object_excel->getActiveSheet(0)->getStyle("A1:F1")
                    ->getFill()
                    ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('FBEF3D');
                $object_excel->getActiveSheet(0)->getRowDimension('1')->setRowHeight(17);
                for ($col = 'A'; $col !== 'M'; $col++) {
                    $object_excel->getActiveSheet(0)->getColumnDimension($col)->setAutoSize(true);
                }
                $object_excel->getActiveSheet(0)
                    ->getStyle('D1:D' . $totalRow)
                    ->getNumberFormat()
                    ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
                $object_excel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'S.NO')
                    ->setCellValue('B1', 'NAME')
                    ->setCellValue('C1', 'EMAIL')
                    ->setCellValue('D1', 'MOBILE')
                    ->setCellValue('E1', 'D.O.J')
                    ->setCellValue('F1', 'USER_ROLE');
                $body = 2;
                $sno = 0;
                foreach ($getUsers as $key => $value) {
                    $sno++;
                    $cnt++;
                    $object_excel->getActiveSheet(0)
                        ->setCellValue('A' . $body, $sno)
                        ->setCellValue('B' . $body, ($value['first_name'].' '.$value['last_name']))
                        ->setCellValue('C' . $body, $value['email_id'])
                        ->setCellValue('D' . $body, $value['mobile'])
                        ->setCellValue('E' . $body, $value['doj'])
                        ->setCellValue('F' . $body, $value['user_role']);
                    $body++;
                }
                $excelName = "School Details";
                $object_excel->getActiveSheet(0)->setTitle($excelName);
            }
            if ($getUsers) {
                $object_excel->setActiveSheetIndex(0);
                $objWriter = new PHPExcel_Writer_Excel2007($object_excel);
                $fileName = 'School_Report' . '-' . date('Y-m-d H:i:s') . '.xlsx';
                $path = dirname(FCPATH) . '/uploads/excel/';
                $filePath = $path . $fileName;
                $filePath = $path . $fileName;
                $objWriter->save($filePath);
                chmod($filePath, 0777);
                $email = $params['email_id'];
                $message = 'Dear sir/madam, <br> Find Attached File';
                $subject = "Detail report for school ";
                $var = $this->common_model->sendEmail($subject, $email,  $message,$filePath, '');
                print_r($var);
                exit;
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params,'v1/report/sendAdminReport',$this->jsonarr,'sendAdminReport');
        return $this->printjson($this->jsonarr);
    }

    /**
     * CI4-compatible method for classPerformanceList
     */
    public function classPerformanceList()
    {
        // Check if this is being called with CI4 routing
        if (class_exists('\CodeIgniter\HTTP\ResponseInterface')) {
            try {
                $request = service('request');
                $params = $request->getJSON(true) ?? $request->getPost() ?? [];
                
                // Validation
                if (empty($params['school_id'])) {
                    return service('response')->setJSON([
                        'IsSuccess' => false,
                        'ResponseObject' => null,
                        'ErrorObject' => 'School Id should not be empty'
                    ]);
                }

                $db = \Config\Database::connect();
                
                // Get classes for performance report
                $builder = $db->table('class c');
                $builder->select('DISTINCT(cs.class_id), c.class_name');
                $builder->join('class_schedule cs', 'c.class_id = cs.class_id', 'left');
                $builder->join('class_content cc', 'c.class_id = cc.class_id', 'left');
                $builder->join('content a', 'cc.content_id = a.content_id', 'left');
                $builder->where('c.school_id', $params['school_id']);
                $builder->where('c.status', 1);
                $builder->where('c.class_status', 0);
                $builder->where('cc.status', 1);
                $builder->where('a.status', 1);
                
                // Filter by teacher if provided
                if (!empty($params['teacher_id']) && $params['teacher_id'] > 0) {
                    $builder->where("FIND_IN_SET('{$params['teacher_id']}', cs.teacher_id) >", 0, false);
                }
                
                $builder->orderBy('c.class_name', 'ASC');
                
                $classes = $builder->get()->getResultArray();

                return service('response')->setJSON([
                    'IsSuccess' => true,
                    'ResponseObject' => $classes ?? [],
                    'ErrorObject' => ''
                ]);

            } catch (\Exception $e) {
                return service('response')->setJSON([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => $e->getMessage()
                ]);
            }
        } else {
            // Fallback to CI3 method
            return $this->classPerformanceList_post();
        }
    }

    /**
     * Get class list for reports (stub - needs full implementation)
     */
    public function classList()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // For now, return empty array - needs full implementation from CI3
            return service('response')->setJSON([
                'IsSuccess' => true,
                'ResponseObject' => [],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return service('response')->setJSON([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get grade report (stub - needs full implementation)
     */
    public function gradeReport()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // For now, return empty array - needs full implementation from CI3
            return service('response')->setJSON([
                'IsSuccess' => true,
                'ResponseObject' => [],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return service('response')->setJSON([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}
