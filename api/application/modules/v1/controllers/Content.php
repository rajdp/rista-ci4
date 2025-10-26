<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require APPPATH . '/libraries/REST_Controller.php';

class Content extends REST_Controller
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;

    function __construct()
    {
        parent::__construct();
        $this->load->model("content_model");
        $this->load->model("common_model");
        $this->load->model("classes_model");
        $this->load->model("student_model");
        $this->load->model("subject_model");
        ini_set('memory_limit', '-1');
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
            'v1/content/add',
            'v1/content/edit',
            'v1/content/list',
            'v1/content/specifiedClassList',
            'v1/content/question_types',
            'v1/content/detail',
            'v1/content/sortMaster',
            'v1/content/deleteContent',
            'v1/content/contentDetail',
            'v1/content/addStudentAnswer',
            'v1/content/contentCreatorList',
            'v1/content/questiontypes',
            'v1/content/addQuestion',
            'v1/content/editQuestion',
            'v1/content/contentRepositoryDelete',
            'v1/content/deleteAssignedContent',
            'v1/content/cloneContent',
            'v1/content/uploadCurriculum',
            'v1/content/deleteQuestion',
            'v1/content/questionDelete',
            'v1/content/scoreReleaseCheck',
            'v1/content/updateAnswerKey',
            'v1/content/answerkeyRequestList',
            'v1/content/addPassage',
            'v1/content/listPassage',
            'v1/content/editPassage',
            'v1/content/deletePassage',
            'v1/content/editContentType',
            'v1/content/questionTopic',
            'v1/content/questionSubTopic',
            'v1/content/questionSkill'
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
        $this->excludeRoutes = array('v1/content/cloneContent',
                                     'v1/content/addScratchQuestion');
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
            $this->jsonarr["ErrorObject"] = "platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User_Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/add', 'only request', 'add');
            if ($params['assign'] == 0) {
                $data = array();
                $tag = array();
                $data["name"] = $params['name'];
                $data['description'] = $params['description'];
                $data["school_id"] = $params['school_id'];
                $data["grade"] = implode(',', $params['grade']);
                $data["subject"] = implode(',', $params['subject']);
                if ($params['role_id'] == 6) {
                    $data['corporate_id'] = $params['corporate_id'];
                }
                if (count($params['tags']) > 0) {
                    $data["tags"] = implode(',', $params['tags']);
                } else {
                    $data['tags'] = "";
                }
                $data["content_type"] = $params['content_type'];
                $data["content_format"] = $params['content_format'];
                $data["editor_type"] = $params['editor_type'];
                $data["file_path"] = $params['file_path'] != '' ? json_encode($params['file_path']) : '';
                $data["answerkey_path"] = isset($params['answerkey_path']) && is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '';
                $data["teacher_version"] = isset($params['teacher_version']) && $params['teacher_version'] != '' ? json_encode($params['teacher_version']) : '';
                $data["allow_answer_key"] =  isset($params['allow_answer_key']) ? $params['allow_answer_key'] : 0;
                if ($params['content_format'] != 3) {
                    $data["base64_data"] = $this->base64Conversion($params['file_path']);
                }
                if (count($params["links"]) > 0) {
                    $data["links"] = json_encode($params['links']);
                } else {
                    $data["links"] = "";
                }
                $data["file_text"] = $params['file_text'];
                $data['download'] = isset($params['download']) ? $params['download'] : 0;
                $data["access"] = $params['access'];
                $data["status"] = $params['status'];
                if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                    $data['profile_url'] = $params['profile_url'];
                    $data['profile_thumb_url'] = $params['profile_thumb_url'];
                }
                $data["created_by"] = $params['user_id'];
                $data["created_date"] = date('Y-m-d H:i:s');
                $contentExists = $this->content_model->checkContent($params, '');
                if (count($contentExists) == 0) {
                    $contentId = $this->common_model->insert('content', $data);
                    if (isset($params["questionAnnotation"]) && $params["questionAnnotation"] != '' && $params["questionAnnotation"] != '[]') {
                        $folder = "../uploads/questionAnnotation/";
                        $fileName = "question-annotation-" . $contentId . '.json';
                        $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['questionAnnotation']);
                        $questionAnnotation = "uploads/questionAnnotation/" . $fileName;
                    } else {
                        $questionAnnotation = '';
                    }
                    if (isset($params["annotation"]) && $params["annotation"] != '' && $params["annotation"] != '[]') {
                        $folder = "../uploads/contentAnnotation/";
                        $fileName = "content-annotation-" . $contentId . '.json';
                        $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['annotation']);
                        $annotation = "uploads/contentAnnotation/" . $fileName;
                    } else {
                        $annotation = '';
                    }
                    $annotationData = array('questionAnnotation' => $questionAnnotation, 'annotation' => $annotation);
                    $updateCondition = array('content_id' => $contentId);
                    $this->common_model->update("content", $annotationData, $updateCondition);
                    //  if($params['role_id'] == 6){
                    $classroomContent = [];
                    $i = 0;
                    if (isset($params['batch_id']) && $params['batch_id'] != '') {
                        foreach ($params['batch_id'] as $key => $value) {
                            $batchContentExists = $this->content_model->checkBatchContent($value, $contentId);
                            if (count($batchContentExists) == 0) {
                                $classroomContent[$i]['batch_id'] = $value;
                                $classroomContent[$i]['school_id'] = $params['school_id'];
                                $classroomContent[$i]['content_id'] = $contentId;
                                $classroomContent[$i]['status'] = 1;
                                $classroomContent[$i]['start_time'] = '00:00:00';
                                $classroomContent[$i]['end_time'] = '23:59:00';
                                $classroomContent[$i]['created_by'] = $params['user_id'];
                                $classroomContent[$i]['created_date'] = date('Y-m-d H:i:s');
                                $i++;
                            }
                        }
                        if (count($classroomContent) > 0) {
                            $this->common_model->bulkInsert('classroom_content', $classroomContent);
                        }
                    }
                    if (isset($params['answers']) && count($params['answers']) != 0) {
                        $answers = [];
                        $subAnswers = [];
                        for ($i = 0; $i < count($params["answers"]); $i++) {
                            for ($z = 0; $z < count($params['answers'][$i]['section']); $z++) {
                                for ($j = 0; $j < count($params['answers'][$i]['section'][$z]['sub_questions']); $j++) {
                                    if ($params['answers'][$i]['section'][$z]['has_sub_question'] == 1) {
                                        $subAnswers[$j]['content_id'] = $contentId;
                                        $subAnswers[$j]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                        $subAnswers[$j]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                        $subAnswers[$j]['section_heading'] = $params['answers'][$i]['heading'];
                                        $subAnswers[$j]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                        $subAnswers[$j]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                        $subAnswers[$j]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                        $subAnswers[$j]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                        $subAnswers[$j]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                        $subAnswers[$j]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                        $mobOptions = [];
                                        foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                            if (is_array($value)) {
                                                array_push($mobOptions, $value['choice']);
                                            } else {
                                                array_push($mobOptions, $value);
                                            }
                                        }
                                        $subAnswers[$j]['mob_options'] = json_encode($mobOptions);
                                        unset($mobOptions);
                                        if (
                                            $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                            $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                        ) {
                                            $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            $subAnswers[$j]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                        } else {
                                            $subAnswers[$j]['answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                            $subAnswers[$j]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                        }
                                        $subAnswers[$j]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                        $subAnswers[$j]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                        $subAnswers[$j]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                        $subAnswers[$j]['allow_exact_match'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                        $subAnswers[$j]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                        $subAnswers[$j]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                        $subAnswers[$j]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                        $subAnswers[$j]['created_by'] = $params['user_id'];
                                        $subAnswers[$j]['created_date'] = date('Y-m-d H:i:s');
                                        $this->common_model->insert('answers', $subAnswers[$j]);
                                    } elseif ($params['answers'][$i]['section'][$z]['has_sub_question'] == 0) {
                                        $answers[$z]['content_id'] = $contentId;
                                        $answers[$z]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                        $answers[$z]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                        $answers[$z]['section_heading'] = $params['answers'][$i]['heading'];
                                        $answers[$z]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                        $answers[$z]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                        $answers[$z]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                        $answers[$z]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                        $answers[$z]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                        $answers[$z]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                        $mobOptions = [];
                                        foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                            if (is_array($value)) {
                                                array_push($mobOptions, $value['choice']);
                                            } else {
                                                array_push($mobOptions, $value);
                                            }
                                        }
                                        $answers[$z]['mob_options'] = json_encode($mobOptions);
                                        unset($mobOptions);
                                        if (
                                            $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                            $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                        ) {
                                            $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            $answers[$z]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                        } else {
                                            $answers[$z]['answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                            $answers[$z]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                        }
                                        $answers[$z]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                        $answers[$z]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                        $answers[$z]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                        $answers[$z]['allow_exact_match	'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                        $answers[$z]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                        $answers[$z]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                        $answers[$z]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                        $answers[$z]['created_by'] = $params['user_id'];
                                        $answers[$z]['created_date'] = date('Y-m-d H:i:s');
                                        $this->common_model->insert('answers', $answers[$z]);
                                    }
                                }
                            }
                        }
                    }
                    if (count($params['tags']) > 0) {
                        for ($i = 0; $i < count($params["tags"]); $i++) {
                            $tag[$i]['tag_name'] = $params['tags'][$i];
                            $tag[$i]['school_id'] = $params['school_id'];
                            $tag[$i]['user_id'] = $params['user_id'];
                            $tag[$i]['content_id'] = $contentId;
                            $tagSearch[$i] = $this->content_model->tagSearch($params['tags'][$i], $params['user_id'], '');
                            if (count($tagSearch[$i]) == 0) {
                                $this->common_model->insert('tags', $tag[$i]);
                            }
                        }
                    }
                    $params['content_id'] = $contentId;
                    $contentdetails = $this->content_model->contentIdList($params);
                    $contentdetails["links"] = explode(',', $contentdetails["links"]);
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["Contentdetails"] = $contentdetails;
                    $this->jsonarr["ResponseObject"] = "Content added successfully";
                    $getTeacher = $this->common_model->getTeacher($params['user_id'], $params['school_id']);
                    if ($params['role_id'] == 4 && $getTeacher['individual_teacher'] != 1) {
                        switch ($params['content_type']) {
                            case '1':
                                $type = 'Resource';
                                break;
                            case '2':
                                $type = 'Assignment';
                                break;
                            case '3':
                                $type = 'Assessment';
                                break;
                            default:
                                $type = '';
                                break;
                        }
                        $records = "Content Name: {$params['name']} <br> Content Type: {$type}<br>Teacher Name: {$getTeacher['teacher_name']}";
                        $this->emailNotification($params, $contentId, 'added', $records);
                    }
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ResponseObject"] = "Content already Exists";
                }
            } elseif ($params['assign'] == 1) {
                foreach ($params['classdetails'] as $details) {
                    $startDate = $details['start_date'];
                    $endDate = !empty($details['end_date']) ? $details['end_date'] : '0000-00-00';
                    $checkClass = $this->content_model->checkClassAssign($details['class_id'], $details['content_id'], $startDate, $endDate);
                    if(isset($details['auto_review']) && $details['auto_review'] != 0){
                       $getContent =  $this->content_model->checkContentId($details['content_id']);
                        if($getContent[0]['content_format'] == 3){
                            $checkAutoGrade = $this->content_model->getQuestionDetails($details['content_id']);
                            if (count($checkAutoGrade) > 0) {
                                $i = 0;
                                foreach ($checkAutoGrade as $key => $value) {
                                    if ($value['audo_grade'] == 1 || $value['question_type_id'] == 55) {
                                        $i++;
                                    }
                                }
                                $details['auto_review'] = count($checkAutoGrade) == $i ? $details['auto_review'] : 0;
                            } else {
                                $details['auto_review'] = 0;
                            }
                        } elseif($getContent[0]['content_format'] == 1){
                            $details['auto_review'] = $details['auto_review'] == 2 ? 1 : $details['auto_review'];
                            $checkPdfAutoGrade = $this->content_model->getQuestionExists($details);
                            if (count($checkPdfAutoGrade) > 0) {
                                $j = 0;
                                foreach ($checkPdfAutoGrade as $key => $value) {
                                    if ($value['auto_grade'] == 1) {
                                        $j++;
                                    }
                                }
                                $details['auto_review'] = count($checkPdfAutoGrade) == $j ? $details['auto_review'] : 0;
                            } else {
                                $details['auto_review'] = 0;
                            }
                        } 
                    }

                    if(isset($details['download']) && $details['download'] != 0){
                        $getContent =  $this->content_model->checkContentId($details['content_id']);
                        $details['download'] = count($getContent) > 0 && $getContent[0]['download'] == 1 ? $details['download'] : 0;
                    }
                    
                    $classContent = [
                        'class_id' => $details['class_id'],
                        'content_id' => $details['content_id'],
                        'school_id' => $params['school_id'],
                        'start_date' => $startDate,
                        'start_time' => $details['start_time'] != '' ? $details['start_time']['hour'] . ":" . $details['start_time']['minute'] . ":" . $details['start_time']['second'] : '',
                        'end_date' => $endDate,
                        'end_time' => $details['end_time'] != '' ? $details['end_time']['hour'] . ":" . $details['end_time']['minute'] . ":" . $details['end_time']['second'] : '',
                        'status' => 1,
                        'auto_review' => isset($details['auto_review']) ? $details['auto_review'] : 0,
                        'all_student' => $details['all_student'] == 0 ? 0 : 1,
                        'notes' => $details['notes'],
                        'downloadable' => $details['download'],
                        'topic_id' => isset($details['topic_id']) ? $details['topic_id'] : 0,
                        'is_accessible' => isset($details['is_accessible']) ? $details['is_accessible'] : 0,
                        'allow_workspace' => isset($details['allow_workspace']) ? $details['allow_workspace'] : 0,
                        'allow_feedback' => isset($details['allow_feedback']) ? $details['allow_feedback'] : 0,
                        'created_by' => $params['user_id'],
                        'created_date' => date('Y-m-d H:i:s')
                    ];
                    if (count($checkClass) == 0) {
                        $classContentId = $this->common_model->insert('class_content', $classContent);
                        $classContentDetails = $this->content_model->classContentDetails($classContentId);

                        if (count($classContentDetails) > 0) {
                            $this->common_model->insert('class_content_log', $classContentDetails[0]);
                        }
                    } else {
                        if ($checkClass[0]['status'] == 2) {
                            $updateStatus = array('status' => 1);
                            $updateCondition = array('id' => $checkClass[0]['id']);
                            $this->common_model->update('class_content', $updateStatus, $updateCondition);
                        } elseif ($checkClass[0]['status'] == 1 && !empty($details['topic_id'])) {
                            $condition = "WHERE topic_id = {$details['topic_id']} AND id = {$checkClass[0]['id']}";
                            $checkTopic = $this->classes_model->getTopicClasses($condition);

                            if (count($checkTopic) == 0) {
                                $updateStatus = array('topic_id' => $details['topic_id']);
                                $updateCondition = array('id' => $checkClass[0]['id']);
                                $this->common_model->update('class_content', $updateStatus, $updateCondition);
                            } else {
                                $this->jsonarr["IsSuccess"] = false;
                                $this->jsonarr["ErrorObject"] = "Content Already Exists In This Topic";
                                return $this->printjson($this->jsonarr);
                            }
                        }
                        $classContentId = $checkClass[0]['id'];
                        $classContentDetails = $this->content_model->classContentDetails($classContentId);
                        if (count($classContentDetails) > 0) {
                            $insert_log = $this->common_model->insert('class_content_log', $classContentDetails[0]);
                        }
                    }
                    if ($details['all_student'] == 0) {
                        if(count($params['classdetails']) == 1){
                            $studentClass = $studentWork = $students = [];
                            $z = $y = 0;
                            $x = 0;
                            for ($j = 0; $j < count($details['student_id']); $j++) {
                                $checkStudentContent = $this->classes_model->checkStudentContent($details['class_id'], $details['student_id'][$j], $details['content_id'], $startDate, $endDate);
                                $checkStudentWork = $this->content_model->checkStudentWork($details['class_id'], $details['student_id'][$j], $details['content_id']);
                                $studentExists = $this->classes_model->checkStudent($details['class_id'], $details['student_id'][$j]);
                                $getStudentGrade = $this->classes_model->studentGrade($params['school_id'], $details['student_id'][$j]);
                                if (count($checkStudentContent) == 0) {
                                    $studentClass[$z]['class_id'] = $details['class_id'];
                                    $studentClass[$z]['student_id'] = $details['student_id'][$j];
                                    $studentClass[$z]['content_id'] = $details['content_id'];
                                    $studentClass[$z]['start_date'] = $details['start_date'];
                                    $studentClass[$z]['end_date'] = $details['end_date'];
                                    $studentClass[$z]['class_content_id'] = $classContentId;
                                    $studentClass[$z]['grade_id'] = count($getStudentGrade) > 0 ? $getStudentGrade[0]['grade_id'] : 0;
                                    $studentClass[$z]['status'] = 1;
                                    $studentClass[$z]['created_by'] = $params['user_id'];
                                    $studentClass[$z]['created_date'] = date('Y-m-d H:i:s');
                                    //insert student_work table
                                    //   if(count($checkStudentWork) == 0) {
                                    $type = $this->content_model->getContentType($details['content_id']);
                                    $name = $this->content_model->name($details['content_id'], $details['student_id'][$j], $details['class_id']);
                                    $studentWork[$y]['class_id'] = $details['class_id'];
                                    $studentWork[$y]['class_name'] = $name[0]['class_name'];
                                    $studentWork[$y]['student_id'] = $details['student_id'][$j];
                                    $studentWork[$y]['student_name'] = $name[0]['student_name'];
                                    $studentWork[$y]['content_id'] = $details['content_id'];
                                    $studentWork[$y]['content_start_date'] = $details['start_date'];
                                    $studentWork[$y]['content_end_date'] = $details['end_date'];
                                    $studentWork[$y]['content_name'] = $name[0]['content_name'];
                                    $studentWork[$y]['content_type'] = $type['content_type'];
                                    $studentWork[$y]['content_format'] = $type['content_format'];
                                    $studentWork[$y]['status'] = 1;
                                    $studentWork[$y]['created_by'] = $params['user_id'];
                                    $studentWork[$y]['created_date'] = date('Y-m-d H:i:s');
                                    $y++;
                                }
                                $z++;
                                //   }
                                if (count($studentExists) == 0) {
                                    $students[$x]['class_id'] = $details['class_id'];
                                    $students[$x]['student_id'] = $details['student_id'][$j];
                                    $students[$x]['validity'] = date('Y-m-d');
                                    $students[$x]['status'] = 1;
                                    $students[$x]['created_by'] = $params['user_id'];
                                    $students[$x]['created_date'] = date('Y-m-d H:i:s');
                                    $x++;
                                }
                            }
                            if (count($studentClass) > 0) {
                                $this->common_model->bulkInsert('student_content', $studentClass);
                            }
                            if (count($studentWork) > 0) {
                                $this->common_model->bulkInsert('student_work', $studentWork);
                            }
                            if (count($students) > 0) {
                                $this->common_model->bulkInsert('student_class', $students);
                            }
                        } else {
                            $studentClass = $studentWork = [];
                            $z = $y = 0;
                            for ($j = 0; $j < count($details['student_id']); $j++) {
                                $studentExists = $this->classes_model->checkStudent($details['class_id'], $details['student_id'][$j]);
                                if(count($studentExists) > 0 && $studentExists[0]['status'] == 1){
                                    $checkStudentContent = $this->classes_model->checkStudentContent($details['class_id'], $details['student_id'][$j], $details['content_id'], $startDate, $endDate);
                                    $checkStudentWork = $this->content_model->checkStudentWork($details['class_id'], $details['student_id'][$j], $details['content_id']);
                                    $getStudentGrade = $this->classes_model->studentGrade($params['school_id'], $details['student_id'][$j]);
                                    if (count($checkStudentContent) == 0) {
                                        $studentClass[$z]['class_id'] = $details['class_id'];
                                        $studentClass[$z]['student_id'] = $details['student_id'][$j];
                                        $studentClass[$z]['content_id'] = $details['content_id'];
                                        $studentClass[$z]['start_date'] = $details['start_date'];
                                        $studentClass[$z]['end_date'] = $details['end_date'];
                                        $studentClass[$z]['class_content_id'] = $classContentId;
                                        $studentClass[$z]['grade_id'] = count($getStudentGrade) > 0 ? $getStudentGrade[0]['grade_id'] : 0;
                                        $studentClass[$z]['status'] = 1;
                                        $studentClass[$z]['created_by'] = $params['user_id'];
                                        $studentClass[$z]['created_date'] = date('Y-m-d H:i:s');
                                        //insert student_work table
                                        $type = $this->content_model->getContentType($details['content_id']);
                                        $name = $this->content_model->name($details['content_id'], $details['student_id'][$j], $details['class_id']);
                                        $studentWork[$y]['class_id'] = $details['class_id'];
                                        $studentWork[$y]['class_name'] = $name[0]['class_name'];
                                        $studentWork[$y]['student_id'] = $details['student_id'][$j];
                                        $studentWork[$y]['student_name'] = $name[0]['student_name'];
                                        $studentWork[$y]['content_id'] = $details['content_id'];
                                        $studentWork[$y]['content_start_date'] = $details['start_date'];
                                        $studentWork[$y]['content_end_date'] = $details['end_date'];
                                        $studentWork[$y]['content_name'] = $name[0]['content_name'];
                                        $studentWork[$y]['content_type'] = $type['content_type'];
                                        $studentWork[$y]['content_format'] = $type['content_format'];
                                        $studentWork[$y]['status'] = 1;
                                        $studentWork[$y]['created_by'] = $params['user_id'];
                                        $studentWork[$y]['created_date'] = date('Y-m-d H:i:s');
                                        $y++;
                                    }
                                    $z++;
                                }

                            }
                            if (count($studentClass) > 0) {
                                $this->common_model->bulkInsert('student_content', $studentClass);
                            }
                            if (count($studentWork) > 0) {
                                $this->common_model->bulkInsert('student_work', $studentWork);
                            }

                        }

                    } elseif ($details['all_student'] == 1) {
                        $studentClass = $studentWork = [];
                        $getStudentClass = $this->content_model->getStudentClass($details['class_id']);
                        if (count($getStudentClass) > 0) {
                            $z = $y = 0;
                            for ($j = 0; $j < count($getStudentClass); $j++) {
                                $checkStudentContent = $this->classes_model->checkStudentContent($details['class_id'], $getStudentClass[$j]['student_id'], $details['content_id'], $startDate, $endDate);
                                $checkStudentWork = $this->content_model->checkStudentWork($details['class_id'], $getStudentClass[$j]['student_id'], $details['content_id']);
                                $getStudentGrade = $this->classes_model->studentGrade($params['school_id'], $getStudentClass[$j]['student_id']);
                                if (count($checkStudentContent) == 0) {
                                    $studentClass[$z]['class_id'] = $details['class_id'];
                                    $studentClass[$z]['student_id'] = $getStudentClass[$j]['student_id'];
                                    $studentClass[$z]['content_id'] = $details['content_id'];
                                    $studentClass[$z]['start_date'] = $details['start_date'];
                                    $studentClass[$z]['end_date'] = $details['end_date'];
                                    $studentClass[$z]['class_content_id'] = $classContentId;
                                    $studentClass[$z]['grade_id'] = count($getStudentGrade) > 0 ? $getStudentGrade[0]['grade_id'] : 0;
                                    $studentClass[$z]['status'] = 1;
                                    $studentClass[$z]['created_by'] = $params['user_id'];
                                    $studentClass[$z]['created_date'] = date('Y-m-d H:i:s');
                                    //   if(count($checkStudentWork) == 0) {
                                    $type = $this->content_model->getContentType($details['content_id']);
                                    $name = $this->content_model->name($details['content_id'], $getStudentClass[$j]['student_id'], $details['class_id']);
                                    $studentWork[$y]['class_id'] = $details['class_id'];
                                    $studentWork[$y]['class_name'] = $name[0]['class_name'];
                                    $studentWork[$y]['student_id'] = $getStudentClass[$j]['student_id'];
                                    $studentWork[$y]['student_name'] = $name[0]['student_name'];
                                    $studentWork[$y]['content_id'] = $details['content_id'];
                                    $studentWork[$y]['content_start_date'] = $details['start_date'];
                                    $studentWork[$y]['content_end_date'] = $details['end_date'];
                                    $studentWork[$y]['content_name'] = $name[0]['content_name'];
                                    $studentWork[$y]['content_type'] = $type['content_type'];
                                    $studentWork[$y]['content_format'] = $type['content_format'];
                                    $studentWork[$y]['status'] = 1;
                                    $studentWork[$y]['created_by'] = $params['user_id'];
                                    $studentWork[$y]['created_date'] = date('Y-m-d H:i:s');
                                    $y++;
                                }
                                $z++;
                                //   }
                            }
                            if (count($studentClass) > 0) {
                                $this->common_model->bulkInsert('student_content', $studentClass);
                            }
                            if (count($studentWork) > 0) {
                                $this->common_model->bulkInsert('student_work', $studentWork);
                            }
                        }
                    }
                }
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Content Assigned Successfully";
            } elseif ($params['assign'] == 2) {
                $classroomContent = [];
                $classContentId = [];
                for ($i = 0; $i < count($params['classroomDetails'][0]['content_id']); $i++) {
                    $classContentId = $this->content_model->checkClassroomContent($params['classroomDetails'][0]['batch_id'][0], $params['classroomDetails'][0]['content_id'][$i]);
                    if (count($classContentId) == 0) {
                        $classroomContent[$i]['batch_id'] = $params['classroomDetails'][0]['batch_id'][0];
                        $classroomContent[$i]['school_id'] = $params['school_id'];
                        $classroomContent[$i]['content_id'] = $params['classroomDetails'][0]['content_id'][$i];
                        $classroomContent[$i]['start_date'] = $params['classroomDetails'][0]['start_date'];
                        $classroomContent[$i]['start_time'] = $params['classroomDetails'][0]['start_time'] != '' ? $params['classroomDetails'][0]['start_time']['hour'] . ":" . $params['classroomDetails'][0]['start_time']['minute'] . ":" . $params['classroomDetails'][0]['start_time']['second'] : '';
                        $classroomContent[$i]['end_date'] = $params['classroomDetails'][0]['end_date'];
                        $classroomContent[$i]['end_time'] = $params['classroomDetails'][0]['end_time'] != '' ? $params['classroomDetails'][0]['end_time']['hour'] . ":" . $params['classroomDetails'][0]['end_time']['minute'] . ":" . $params['classroomDetails'][0]['end_time']['second'] : '';
                        $classroomContent[$i]['auto_review'] = isset($params['classdetails'][0]['auto_review']) ? $params['classdetails'][0]['auto_review'] : 0;
                        $classroomContent[$i]['status'] = 1;
                        $classroomContent[$i]['created_by'] = $params['user_id'];
                        $classroomContent[$i]['created_date'] = date('Y-m-d H:i:s');
                    }
                }
                if (count($classroomContent) > 0) {
                    $this->common_model->bulkInsert('classroom_content', $classroomContent);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Content Assigned Successfully";
                } elseif (count($classContentId) > 0) {
                    if ($classContentId[0]['status'] == 2) {
                        $condition = array('batch_id' => $params['classroomDetails'][0]['batch_id'][0], 'content_id' => $params['classroomDetails'][0]['content_id'][0]);
                        $status = array('status' => 1);
                        $update = $this->common_model->update('classroom_content', $status, $condition);
                        if ($update) {
                            $this->jsonarr['IsSuccess'] = true;
                            $this->jsonarr['ResponseObject'] = "Content Assigned Successfully";
                        }
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Content Already Assinged";
                    }
                }
            } elseif ($params['assign'] == 3) {
                $params['class_id'] = 0;
                $params['student_id'] = is_array($params['student_id']) ? $params['student_id'] : explode(',', $params['student_id']);
                foreach ($params['student_id'] as $key => $value) {
                    $params['student_id'] = $value;
                    $checkContentExists = $this->content_model->getStudentPlatform($params);
                    if (count($checkContentExists) == 0) {
                        $getStudentGrade = $this->classes_model->studentGrade($params['school_id'], $params['student_id']);
                        $studentContent = [];
                        $studentContent['class_id'] = 0;
                        $studentContent['grade_id'] = $getStudentGrade[0]['grade_id'];
                        $studentContent['student_id'] = $params['student_id'];
                        $studentContent['content_id'] = $params['content_id'];
                        $studentContent['start_date'] = $params['start_date'];
                        $studentContent['end_date'] = $params['end_date'];
                        $studentContent['status'] = 1;
                        $studentContent['created_by'] = $params['user_id'];
                        $studentContent['created_date'] =  date('Y-m-d H:i:s');
                        $assignStudentContent = $this->common_model->insert('student_content', $studentContent);
                        if ($assignStudentContent > 0) {
                            $this->jsonarr['IsSuccess'] = true;
                            $this->jsonarr['ResponseObject'] = "Content Assigned Successfully";
                        } else {
                            $this->jsonarr['IsSuccess'] = false;
                            $this->jsonarr['ErrorObject'] = "Failed To Assign Content ";
                        }
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Content Already Assigned To This Student";
                    }
                }
            }
        }
        $this->common_model->createLog($params, 'v1/content/add', $this->jsonarr, 'add');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function base64Conversion($filepath)
    {
        if ($filepath != '') {
            if (count($filepath) > 0) {
                if (isset($filepath[0]['base64_data'])) {
                    $r = $filepath[0]['base64_data'];
                } else {
                    $r = '';
                }
            }
        } else {
            $r = '';
        }
        return $r;
    }

    public function answers_post()
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
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content_Id should not be empty";
        } elseif ($params['answers'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "answers should not be empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/answers', 'only request', 'answers');
            $answerlist = $this->content_model->answer($params);
            $var = array('total_points_taken' => 0, 'total_points' => 0, 'percentage' => 0);
            $a = 0;
            for ($i = 0; $i < count($params['answers']); $i++) {
                for ($j = 0; $j < count($params['answers'][$i]['sub_questions']); $j++) {
                    if ($answerlist[$a]['answer'] == $params['answers'][$i]['sub_questions'][$j]['given_answer']) {
                        $answer[$a]['question_no'] = $answerlist[$a]['question_no'];
                        $answer[$a]['sub_question_no'] = $answerlist[$a]['sub_question_no'];
                        $answer[$a]['answer_id'] = $answerlist[$a]['answer_id'];
                        $answer[$a]['answer'] = $answerlist[$a]['answer'];
                        $answer[$a]['actual_points'] = $answerlist[$a]['points'];
                        $answer[$a]['points_taken'] = $answerlist[$a]['points'];
                        $a++;
                    } else {
                        $answer[$a]['question_no'] = $answerlist[$a]['question_no'];
                        $answer[$a]['sub_question_no'] = $answerlist[$a]['sub_question_no'];
                        $answer[$a]['answer_id'] = $answerlist[$a]['answer_id'];
                        $answer[$a]['answer'] = $answerlist[$a]['answer'];
                        $answer[$a]['actual_points'] = $answerlist[$a]['points'];
                        $answer[$a]['points_taken'] = 0;
                        $a++;
                    }
                }
            }
            foreach ($answer as $value) {
                $var['total_points_taken'] += $value['points_taken'];
                $var['total_points'] += $value['actual_points'];
                $i++;
            }
            $var['percentage'] = round(($var['total_points_taken'] / $var['total_points']) * 100);
            $answers['answers'] = $answer;
            $answers['overall_score'] = $var['total_points_taken'];
            $answers['percentage'] = $var['percentage'];
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = $answers;
        }
        $this->common_model->createLog($params, 'v1/content/answers', $this->jsonarr, 'answers');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function list_post()
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
        } else {
            $this->common_model->createLog($params, 'v1/content/list', 'only request', 'list');
            $contentList = $this->content_model->contentList($params);
            $i = 0;
            if (count($contentList) > 0) {
                $data = [];
                foreach ($contentList as $value) {
                    $data[$i] = $value;
                    $links = explode(',', $value['links']);
                    $tags = explode(',', $value['tags']);
                    $links = array_filter($links);
                    $tags = array_filter($tags);
                    $data[$i]['links'] = $links;
                    $data[$i]['tags'] = $tags;
                    $i++;
                }
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = $data;
            } else {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = $contentList;
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/list', $this->jsonarr, 'list');
        return $this->printjson($this->jsonarr);
    }

    public function specifiedClassList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Class Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/specifiedClassList', 'only request', 'specifiedClassList');
            $params['class_id'] = implode(',', $params['class_id']);
            $classList = $this->content_model->classList($params);
            if (count($classList) == 0 && isset($params['school_id'])) {
                $classList = $this->content_model->allSchoolStudents($params['school_id']);
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $classList;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/specifiedClassList', $this->jsonarr, 'specifiedClassList');
        return $this->printjson($this->jsonarr);
    }

    public function edit_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        if ($params["platform"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["content_id"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Content Id should not be empty";
        } else if ($params["grade"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Grade should not be empty";
        } else if ($params["subject"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Subject should not be empty";
        } else if ($params["content_type"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Content Type should not be empty";
        } else if ($params["tags"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Tags should not be empty";
        } else if ($params["status"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else if ($params["access"] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = "Access should not be empty";
        } else if (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/edit', 'only request', 'edit');
            $params["grade"] = implode(",", $params["grade"]);
            $params["subject"] = implode(",", $params["subject"]);
            $checkContent = $this->content_model->checkContent($params, 'edit');
            if (count($checkContent) > 0) {
                $condition = array('content_id' => $params['content_id']);
                $data = [];
                $data["name"] = $params['name'];
                $data["description"] = $params['description'];
                $data["grade"] = $params['grade'];
                $data["subject"] = $params['subject'];
                if (count($params['tags']) > 0) {
                    $data["tags"] = implode(",", $params["tags"]);
                } else {
                    $data["tags"] = "";
                }
                $data["content_type"] = $params["content_type"];
                $data["content_format"] = $params['content_format'];
                $data["editor_type"] = $params['editor_type'] != '' ? $params['editor_type'] : 1;
                $data["file_path"] = count($params['file_path']) > 0 ? json_encode($params['file_path']) : '';
                $data["answerkey_path"] = isset($params['answerkey_path']) && is_array($params['answerkey_path']) && count($params['answerkey_path']) > 0 ? json_encode($params['answerkey_path']) : '';
                $data["teacher_version"] = isset($params['teacher_version']) && $params['teacher_version'] != '' ? json_encode($params['teacher_version']) : '';
                $data["allow_answer_key"] =  isset($params['allow_answer_key']) ? $params['allow_answer_key'] : 0;
                if ($params['content_format'] != 3) {
                    $data["base64_data"] = $this->base64Conversion($params['file_path']);
                }
                if (count($params['links']) > 0) {
                    $data["links"] = json_encode($params['links']);
                } else {
                    $data['links'] = "";
                }
                $data["file_text"] = $params['file_text'];
                $data['download'] = isset($params['download']) ? $params['download'] : 0;
                if (isset($params["questionAnnotation"]) && $params["questionAnnotation"] != '' && $params["questionAnnotation"] != '[]') {
                    $folder = "../uploads/questionAnnotation/";
                    $fileName = "question-annotation-" . $params['content_id'] . '.json';
                    $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['questionAnnotation']);
                    $data["questionAnnotation"] = "uploads/questionAnnotation/" . $fileName;
                } else {
                    $data["questionAnnotation"] = '';
                }
                if (isset($params["annotation"]) && $params["annotation"] != '' && $params["annotation"] != '[]') {
                    $folder = "../uploads/contentAnnotation/";
                    $fileName = "content-annotation-" . $params['content_id'] . '.json';
                    $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['annotation']);
                    $data["annotation"] = "uploads/contentAnnotation/" . $fileName;
                } else {
                    $data["annotation"] = '';
                }
                $data["status"] = $params["status"];
                $data["access"] = $params["access"];
                if (isset($params['profile_url']) && isset($params['profile_thumb_url'])) {
                    $data['profile_url'] = $params['profile_url'];
                    $data['profile_thumb_url'] = $params['profile_thumb_url'];
                }
                $data["modified_by"] = $params["user_id"];
                $data["modified_date"] = date('Y-m-d H:i:s');
                $updated = $this->common_model->update('content', $data, $condition);
                //  if($params['role_id'] == 6){
                if (isset($params['batch_id']) && $params['batch_id'] != '') {
                    $classroomContent = [];
                    $i = 0;
                    $getBatchId = $this->content_model->batchDetail($params['content_id'], '');
                    $contentList['batch_id'] = explode(',', $getBatchId[0]['batch_id']);
                    $diff = array_diff($contentList['batch_id'], $params['batch_id']);
                    if (count($diff) > 0) {
                        foreach ($diff as $key => $value) {
                            $batchStatus = array('status' => 2);
                            $batchCondition = array(
                                'content_id' => $params['content_id'],
                                'batch_id' => $value
                            );
                            $this->common_model->update('classroom_content', $batchStatus, $batchCondition);
                        }
                    }
                    foreach ($params['batch_id'] as $key => $value) {
                        $batchContentExists = $this->content_model->checkBatchContent($value, $params['content_id']);
                        if (count($batchContentExists) == 0) {
                            $classroomContent[$i]['batch_id'] = $value;
                            $classroomContent[$i]['school_id'] = $params['school_id'];
                            $classroomContent[$i]['content_id'] = $params['content_id'];
                            $classroomContent[$i]['status'] = 1;
                            $classroomContent[$i]['start_time'] = '00:00:00';
                            $classroomContent[$i]['end_time'] = '23:59:00';
                            $classroomContent[$i]['created_by'] = $params['user_id'];
                            $classroomContent[$i]['created_date'] = date('Y-m-d H:i:s');
                            $i++;
                        } else {
                            $data1 = array('status' => 1);
                            $batchCondition = array(
                                'content_id' => $params['content_id'],
                                'batch_id' => $value
                            );
                            $this->common_model->update('classroom_content', $data1, $batchCondition);
                        }
                    }
                    if (count($classroomContent) > 0) {
                        $this->common_model->bulkInsert('classroom_content', $classroomContent);
                    }
                }
                $work = array(
                    'content_name' => $params['name']
                );
                $studentworkupdated = $this->common_model->update('student_work', $work, $condition);
                if ($updated) {
                    $tag = array();
                    if (count($params['tags']) > 0) {
                        for ($i = 0; $i < count($params["tags"]); $i++) {
                            $tag[$i]['tag_name'] = $params['tags'][$i];
                            $tag[$i]['school_id'] = $params['school_id'];
                            $tag[$i]['user_id'] = $params['user_id'];
                            $tag[$i]['content_id'] = $params['content_id'];
                            $tagSearch[$i] = $this->content_model->tagSearch($params['tags'][$i], $params['user_id'], $params['content_id']);
                            if (count($tagSearch[$i]) > 0) {
                                $condition = array('content_id' => $params['content_id']);
                                $this->common_model->delete('tags', $condition);
                            } else if (count($tagSearch[$i]) == 0) {
                                $this->common_model->insert('tags', $tag[$i]);
                            }
                        }
                    }
                    if (isset($params['answers']) && count($params['answers']) == 0) {
                        $condition = array('content_id' => $params['content_id']);
                        $deletequestion = $this->common_model->delete('answers', $condition);
                    }
                    if (isset($params['answers']) && count($params['answers']) != 0) {
                        //                        $condition = array('content_id' => $params['content_id']);
                        //                        $deletequestion = $this->common_model->delete('answers', $condition);
                        $answers = [];
                        $subAnswers = [];
                        for ($i = 0; $i < count($params["answers"]); $i++) {
                            for ($z = 0; $z < count($params['answers'][$i]['section']); $z++) {
                                for ($j = 0; $j < count($params['answers'][$i]['section'][$z]['sub_questions']); $j++) {
                                    if ($params['answers'][$i]['section'][$z]['has_sub_question'] == 1) {
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'])) {
                                            $checkQuestion = $this->content_model->checkQuestionExists($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id']);
                                            if (count($checkQuestion) == 0) {
                                                $subAnswers[$j]['content_id'] = $params['content_id'];
                                                $subAnswers[$j]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                                $subAnswers[$j]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                                $subAnswers[$j]['section_heading'] = $params['answers'][$i]['heading'];
                                                $subAnswers[$j]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                                $subAnswers[$j]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                                $subAnswers[$j]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                                $subAnswers[$j]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                                $subAnswers[$j]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                                $subAnswers[$j]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                                $mobOptions = [];
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                    if (is_array($value)) {
                                                        array_push($mobOptions, $value['choice']);
                                                    } else {
                                                        array_push($mobOptions, $value);
                                                    }
                                                }
                                                $subAnswers[$j]['mob_options'] = json_encode($mobOptions);
                                                unset($mobOptions);
                                                if (
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                                ) {
                                                    $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                    $subAnswers[$j]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                                } else {
                                                  //  $subAnswers[$j]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                    $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                }
                                                if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                    $subAnswers[$j]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                                }
                                                $subAnswers[$j]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                                $subAnswers[$j]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                $subAnswers[$j]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                                $subAnswers[$j]['allow_exact_match'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                                $subAnswers[$j]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                                $subAnswers[$j]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                                $subAnswers[$j]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                                $subAnswers[$j]['created_by'] = $params['user_id'];
                                                $subAnswers[$j]['created_date'] = date('Y-m-d H:i:s');
                                                $this->common_model->insert('answers', $subAnswers[$j]);
                                            } else {
                                                $subAnswers[$j]['content_id'] = $params['content_id'];
                                                $subAnswers[$j]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                                $subAnswers[$j]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                                $subAnswers[$j]['section_heading'] = $params['answers'][$i]['heading'];
                                                $subAnswers[$j]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                                $subAnswers[$j]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                                $subAnswers[$j]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                                $subAnswers[$j]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                                $subAnswers[$j]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                                $subAnswers[$j]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                                $mobOptions = [];
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                    if (is_array($value)) {
                                                        array_push($mobOptions, $value['choice']);
                                                    } else {
                                                        array_push($mobOptions, $value);
                                                    }
                                                }
                                                $subAnswers[$j]['mob_options'] = json_encode($mobOptions);
                                                unset($mobOptions);
                                                if (
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                                ) {
                                                    $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                    $subAnswers[$j]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                                } else {
                                                  //  $subAnswers[$j]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                    $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                }
                                                if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                    $subAnswers[$j]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                                }
                                                $subAnswers[$j]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                                $subAnswers[$j]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                $subAnswers[$j]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                                $subAnswers[$j]['allow_exact_match'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                                $subAnswers[$j]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                                $subAnswers[$j]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                                $subAnswers[$j]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                                $subAnswers[$j]['modified_by'] = $params['user_id'];
                                                $subAnswers[$j]['modified_date'] = date('Y-m-d H:i:s');
                                                $answerId = array('answer_id' => $checkQuestion[0]['answer_id']);
                                                $this->common_model->update('answers', $subAnswers[$j], $answerId);
                                            }
                                        } else {
                                            $subAnswers[$j]['content_id'] = $params['content_id'];
                                            $subAnswers[$j]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                            $subAnswers[$j]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                            $subAnswers[$j]['section_heading'] = $params['answers'][$i]['heading'];
                                            $subAnswers[$j]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                            $subAnswers[$j]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                            $subAnswers[$j]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                            $subAnswers[$j]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                            $subAnswers[$j]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                            $subAnswers[$j]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                            $mobOptions = [];
                                            foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                if (is_array($value)) {
                                                    array_push($mobOptions, $value['choice']);
                                                } else {
                                                    array_push($mobOptions, $value);
                                                }
                                            }
                                            $subAnswers[$j]['mob_options'] = json_encode($mobOptions);
                                            unset($mobOptions);
                                            if (
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                            ) {
                                                $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                $subAnswers[$j]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                            } else {
                                              //  $subAnswers[$j]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                $subAnswers[$j]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            }
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                $subAnswers[$j]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                            }
                                            $subAnswers[$j]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                            $subAnswers[$j]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                            $subAnswers[$j]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                            $subAnswers[$j]['allow_exact_match'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                            $subAnswers[$j]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                            $subAnswers[$j]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                            $subAnswers[$j]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                            $subAnswers[$j]['created_by'] = $params['user_id'];
                                            $subAnswers[$j]['created_date'] = date('Y-m-d H:i:s');
                                            $this->common_model->insert('answers', $subAnswers[$j]);
                                        }
                                    } elseif ($params['answers'][$i]['section'][$z]['has_sub_question'] == 0) {
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'])) {
                                            $checkQuestion = $this->content_model->checkQuestionExists($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id']);
                                            if (count($checkQuestion) == 0) {
                                                $answers[$z]['content_id'] = $params['content_id'];
                                                $answers[$z]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                                $answers[$z]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                                $answers[$z]['section_heading'] = $params['answers'][$i]['heading'];
                                                $answers[$z]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                                $answers[$z]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                                $answers[$z]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                                $answers[$z]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                                $answers[$z]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                                $answers[$z]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                                $mobOptions = [];
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                    if (is_array($value)) {
                                                        array_push($mobOptions, $value['choice']);
                                                    } else {
                                                        array_push($mobOptions, $value);
                                                    }
                                                }
                                                $answers[$z]['mob_options'] = json_encode($mobOptions);
                                                unset($mobOptions);
                                                if (
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                                ) {
                                                    $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                    $answers[$z]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                                } else {
                                                   // $answers[$z]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                    $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                }
                                                if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                    $answers[$z]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                                }
                                                $answers[$z]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                                $answers[$z]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                $answers[$z]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                                $answers[$z]['allow_exact_match	'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                                $answers[$z]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                                $answers[$z]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                                $answers[$z]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                                $answers[$z]['created_by'] = $params['user_id'];
                                                $answers[$z]['created_date'] = date('Y-m-d H:i:s');
                                                $this->common_model->insert('answers', $answers[$z]);
                                            } else {
                                                $answers[$z]['content_id'] = $params['content_id'];
                                                $answers[$z]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                                $answers[$z]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                                $answers[$z]['section_heading'] = $params['answers'][$i]['heading'];
                                                $answers[$z]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                                $answers[$z]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                                $answers[$z]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                                $answers[$z]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                                $answers[$z]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                                $answers[$z]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                                $mobOptions = [];
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                    if (is_array($value)) {
                                                        array_push($mobOptions, $value['choice']);
                                                    } else {
                                                        array_push($mobOptions, $value);
                                                    }
                                                }
                                                $answers[$z]['mob_options'] = json_encode($mobOptions);
                                                unset($mobOptions);
                                                if (
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                    $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                                ) {
                                                    $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                    $answers[$z]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                                } else {
                                                  //  $answers[$z]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                    $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                }
                                                if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                    $answers[$z]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                                }
                                                $answers[$z]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                                $answers[$z]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                $answers[$z]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                                $answers[$z]['allow_exact_match	'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                                $answers[$z]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                                $answers[$z]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                                $answers[$z]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                                $answers[$z]['modified_by'] = $params['user_id'];
                                                $answers[$z]['modified_date'] = date('Y-m-d H:i:s');
                                                $answerId = array('answer_id' => $checkQuestion[0]['answer_id']);
                                                $this->common_model->update('answers', $answers[$z], $answerId);
                                            }
                                        } else {
                                            $answers[$z]['content_id'] = $params['content_id'];
                                            $answers[$z]['has_sub_question'] = $params['answers'][$i]['section'][$z]['has_sub_question'];
                                            $answers[$z]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_no'];
                                            $answers[$z]['section_heading'] = $params['answers'][$i]['heading'];
                                            $answers[$z]['question'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question'];
                                            $answers[$z]['sub_question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                            $answers[$z]['question_type_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'];
                                            $answers[$z]['page_no'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['page_no'] : 0;
                                            $answers[$z]['options'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['options'];
                                            $answers[$z]['array'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array']);
                                            $mobOptions = [];
                                            foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['array'] as $value) {
                                                if (is_array($value)) {
                                                    array_push($mobOptions, $value['choice']);
                                                } else {
                                                    array_push($mobOptions, $value);
                                                }
                                            }
                                            $answers[$z]['mob_options'] = json_encode($mobOptions);
                                            unset($mobOptions);
                                            if (
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40 ||
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54
                                            ) {
                                                $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                                $answers[$z]['editor_answer'] = isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['editor_answer'] : '';
                                            } else {
                                             //   $answers[$z]['answer'] = !is_null($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']) ? $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'] : '';
                                                $answers[$z]['answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            }
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'])) {
                                                $answers[$z]['answer_explanation'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_explanation'];
                                            }
                                            $answers[$z]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                            $answers[$z]['points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                            $answers[$z]['difficulty'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['difficulty'];
                                            $answers[$z]['allow_exact_match	'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_exact_match'];
                                            $answers[$z]['allow_any_text'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['allow_any_text'];
                                            $answers[$z]['match_case'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['match_case'];
                                            $answers[$z]['minimum_line'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['minimum_line'];
                                            $answers[$z]['created_by'] = $params['user_id'];
                                            $answers[$z]['created_date'] = date('Y-m-d H:i:s');
                                            $this->common_model->insert('answers', $answers[$z]);
                                        }
                                    }
                                }
                            }
                        }
                        $getTotalPoints = $this->content_model->totalContentScore($params);
                        if ($getTotalPoints) {
                            $update = array(
                                'points' => $getTotalPoints[0]['points']
                            );
                            $updateCondition = array('content_id' => $params['content_id']);
                            $this->common_model->update('student_content', $update, $updateCondition);
                        }
                    }
                    if (isset($params['questions']) && count($params['questions']) != 0) {
                        $condition = array('question_id' => $params['questions'][0]['question_id']);
                        if ($deletequestion) {
                            $text = [];
                            $text['content_id'] = $params['content_id'];
                            $text['question_type_id'] = $params['questions'][0]['question_type_id'];
                            $text['editor_context'] = $params['questions'][0]['editor_context'];
                            $text['question'] = $params['questions'][0]['question'];
                            $text['options'] = json_encode($params['questions'][0]['options']);
                            $text['answer'] = json_encode($params['questions'][0]['answer']);
                            if ($params['questions'][0]['question_type_id'] == 5 || $params['questions'][0]['question_type_id'] == 7) {
                                $text['heading_option'] = json_decode($params['questions'][0]['heading_option']);
                            } else {
                                $text['heading_option'] = '';
                            }
                            $text['multiple_response'] = $params['questions'][0]['multiple_response'];
                            $text['points'] = $params['questions'][0]['points'];
                            $text['exact_match'] = $params['questions'][0]['exact_match'];
                            $text['hint'] = $params['questions'][0]['hint'];
                            $text['explanation'] = $params['questions'][0]['explanation'];
                            $text['word_limit'] = $params['questions'][0]['word_limit'];
                            $text['scoring_instruction'] = $params['questions'][0]['scoring_instruction'];
                            $text['source'] = $params['questions'][0]['source'];
                            $text['target'] = $params['questions'][0]['target'];
                            $text['created_by'] = $params['user_id'];
                            $text['created_date'] = date('Y-m-d H:i:s');
                            $this->common_model->update('text_questions', $text, $condition);
                        }
                    }
                    $contentdetails = $this->content_model->contentIdList($params);
                    $contentdetails["links"] = explode(',', $contentdetails["links"]);
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["Contentdetails"] = $contentdetails;
                    $this->jsonarr["ResponseObject"] = "Content Updated Successfully";
                    $getTeacher = $this->common_model->getTeacher($params['user_id'], $params['school_id']);
                    if ($params['role_id'] == 4 && $getTeacher['individual_teacher'] != 1) {
                        switch ($params['content_type']) {
                            case '1':
                                $type = 'Resource';
                                break;
                            case '2':
                                $type = 'Assignment';
                                break;
                            case '3':
                                $type = 'Assessment';
                                break;
                            default:
                                $type = '';
                                break;
                        }
                        $records = "Content Name: {$params['name']}<br>Content Type: {$type}<br>Teacher Name: {$getTeacher['teacher_name']}";
                        //$this->emailNotification($params, $params['content_id'], 'edited', $records);
                    }
                } else {
                    $this->jsonarr["IsSuccess"] = false;
                    $this->jsonarr["ErrorObject"] = "Failed to update content";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "content not found";
            }
        }
        $this->common_model->createLog($params, 'v1/content/edit', $this->jsonarr, 'edit');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function question_types_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/question_types', 'only request', 'question_types');
            $questionList = $this->content_model->questionTypeList();
            usort($questionList, function ($a, $b) {
                return $a['resource_type_id'] <=> $b['resource_type_id'];
            });
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $questionList;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/question_types', $this->jsonarr, 'question_types');
        return $this->printjson($this->jsonarr);
    }

    public function addQuestion_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (isset($params['questions']) && count($params['questions']) != 0) {
            $this->common_model->createLog($params, 'v1/content/addQuestion', 'only request', 'addQuestion');
            $text = [];
            $count = 1;
            if ($params['questions'][0]['question_type_id'] != 24) {
                for ($i = 0; $i < count($params['questions']); $i++) {
                    $contentExists = $this->content_model->checkTextQuestions($params['content_id']);
                    if ($contentExists > 0) {
                        $count = $contentExists['question_no'] + $count;
                    }
                    $data = array(
                        'total_questions' => $count
                    );
                    $condition = array("content_id" => $params['content_id']);
                    $update = $this->common_model->update('content', $data, $condition);
                    $text[$i]['content_id'] = $params['content_id'];
                    $text[$i]['question_type_id'] = $params['questions'][$i]['question_type_id'];
                    if (isset($params['editor_context'])) {
                        $text[$i]['editor_context'] = $params['questions'][$i]['editor_context'];
                    }
                    if (isset($params['questions'][$i]['editor_type'])) {
                        $text[$i]['editor_type'] = $params['questions'][$i]['editor_type'];
                    }
                    $text[$i]['question_no'] = $count;
                    $text[$i]['sub_question_no'] = $count;
                    $text[$i]['has_sub_question'] = 0;
                    $text[$i]['question'] = $params['questions'][$i]['question'];
                    $text[$i]['editor_answer'] = isset($params['questions'][$i]['editor_answer']) ? $params['questions'][$i]['editor_answer'] : '';
                    $text[$i]['options'] = json_encode($params['questions'][$i]['options']);
                    $text[$i]['answer'] = json_encode($params['questions'][$i]['answer']);
                    $text[$i]['level'] = isset($params['questions'][$i]['level']) ? $params['questions'][$i]['level'] : 0;
                    if ($params['questions'][$i]['question_type_id'] == 5 || $params['questions'][$i]['question_type_id'] == 7) {
                        $text[$i]['heading_option'] = json_encode($params['questions'][$i]['heading_option']);
                    } else {
                        $text[$i]['heading_option'] = '';
                    }
                    $text[$i]['multiple_response'] = $params['questions'][$i]['multiple_response'];
                    $text[$i]['audo_grade'] = $params['questions'][$i]['autograde'];
                    $text[$i]['points'] = $params['questions'][$i]['points'];
                    $text[$i]['exact_match'] = $params['questions'][$i]['exact_match'];
                    $text[$i]['hint'] = json_encode($params['questions'][$i]['hint']);
                    $text[$i]['explanation'] = $params['questions'][$i]['explanation'];
                    $text[$i]['word_limit'] = $params['questions'][$i]['word_limit'];
                    $text[$i]['scoring_instruction'] = $params['questions'][$i]['scoring_instruction'];
                    $text[$i]['source'] = $params['questions'][$i]['source'];
                    $text[$i]['target'] = $params['questions'][$i]['target'];
                    $text[$i]['subject_id'] = isset($params['questions'][$i]['subject_id']) && $params['questions'][$i]['subject_id'] > 0 ? $params['questions'][$i]['subject_id'] : NULL;
                    $text[$i]['question_topic_id'] = isset($params['questions'][$i]['question_topic_id']) && $params['questions'][$i]['question_topic_id'] > 0 ? $params['questions'][$i]['question_topic_id'] : NULL;
                    $text[$i]['question_standard'] = isset($params['questions'][$i]['question_standard']) && $params['questions'][$i]['question_standard'] > 0 ? $params['questions'][$i]['question_standard'] : NULL;
                    $text[$i]['skill'] = isset($params['questions'][$i]['skill']) && count($params['questions'][$i]['skill']) > 0 ? json_encode($params['questions'][$i]['skill']) : NULL;
                    if (isset($params['questions'][$i]['skill']) && count($params['questions'][$i]['skill']) > 0) {
                        $this->addSkills($params['questions'][$i]['skill'], $params);
                    }
                    $text[$i]['created_by'] = $params['user_id'];
                    $text[$i]['created_date'] = date('Y-m-d H:i:s');
                    $count++;
                }
            } else {
                $quesNum = 1;
                $contentExists = $this->content_model->checkTextQuestions($params['content_id']);
                if ($contentExists > 0) {
                    $count = $contentExists['question_no'] + $count;
                }
                $data = array(
                    'total_questions' => $count
                );
                $condition = array("content_id" => $params['content_id']);
                $update = $this->common_model->update('content', $data, $condition);
                for ($i = 0; $i < count($params['questions'][0]['subQuestions']); $i++) {
                    $text[$i]['content_id'] = $params['content_id'];
                    $text[$i]['question_type_id'] = $params['questions'][0]['question_type_id'];
                    $text[$i]['passage_id'] = isset($params['questions'][0]['passage_id']) ? $params['questions'][0]['passage_id'] : 0;
                    $text[$i]['sub_question_type_id'] = $params['questions'][0]['subQuestions'][$i]['question_type_id'];
                    if (isset($params['questions'][0]['editor_context'])) {
                        $text[$i]['editor_context'] = $params['questions'][0]['editor_context'];
                    }
                    if (isset($params['questions'][0]['subQuestions'][$i]['editor_type'])) {
                        $text[$i]['editor_type'] = $params['questions'][0]['subQuestions'][$i]['editor_type'];
                    }
                    $text[$i]['question_no'] = $count;
                    $text[$i]['sub_question_no'] = $quesNum;
                    $text[$i]['has_sub_question'] = 1;
                    $text[$i]['question'] = $params['questions'][0]['subQuestions'][$i]['question'];
                    $text[$i]['editor_answer'] = isset($params['questions'][0]['subQuestions'][$i]['editor_answer']) ? $params['questions'][0]['subQuestions'][$i]['editor_answer'] : '';
                    $text[$i]['options'] = json_encode($params['questions'][0]['subQuestions'][$i]['options']);
                    $text[$i]['answer'] = json_encode($params['questions'][0]['subQuestions'][$i]['answer']);
                    $text[$i]['level'] = isset($params['questions'][0]['subQuestions'][$i]['level']) ? $params['questions'][0]['subQuestions'][$i]['level'] : 0;
                    if ($params['questions'][0]['subQuestions'][$i]['question_type_id'] == 5 || $params['questions'][0]['subQuestions'][$i]['question_type_id'] == 7) {
                        $text[$i]['heading_option'] = json_encode($params['questions'][0]['subQuestions'][$i]['heading_option']);
                    } else {
                        $text[$i]['heading_option'] = '';
                    }
                    $text[$i]['multiple_response'] = $params['questions'][0]['subQuestions'][$i]['multiple_response'];
                    $text[$i]['audo_grade'] = $params['questions'][0]['subQuestions'][$i]['autograde'];
                    $text[$i]['points'] = $params['questions'][0]['subQuestions'][$i]['points'];
                    $text[$i]['exact_match'] = $params['questions'][0]['subQuestions'][$i]['exact_match'];
                    $text[$i]['hint'] = json_encode($params['questions'][0]['subQuestions'][$i]['hint']);
                    $text[$i]['explanation'] = $params['questions'][0]['subQuestions'][$i]['explanation'];
                    $text[$i]['word_limit'] = $params['questions'][0]['subQuestions'][$i]['word_limit'];
                    $text[$i]['scoring_instruction'] = $params['questions'][0]['subQuestions'][$i]['scoring_instruction'];
                    $text[$i]['source'] = $params['questions'][0]['subQuestions'][$i]['source'];
                    $text[$i]['target'] = $params['questions'][0]['subQuestions'][$i]['target'];
                    $text[$i]['subject_id'] = isset($params['questions'][0]['subQuestions'][$i]['subject_id']) && $params['questions'][0]['subQuestions'][$i]['subject_id'] > 0 ? $params['questions'][0]['subQuestions'][$i]['subject_id'] : NULL;
                    $text[$i]['question_topic_id'] = isset($params['questions'][0]['subQuestions'][$i]['question_topic_id']) && $params['questions'][0]['subQuestions'][$i]['question_topic_id'] > 0 ? $params['questions'][0]['subQuestions'][$i]['question_topic_id'] : NULL;
                    $text[$i]['question_standard'] = isset($params['questions'][0]['subQuestions'][$i]['question_standard']) && $params['questions'][0]['subQuestions'][$i]['question_standard'] > 0 ? $params['questions'][0]['subQuestions'][$i]['question_standard'] : NULL;
                    $text[$i]['skill'] = isset($params['questions'][0]['subQuestions'][$i]['skill']) && count($params['questions'][0]['subQuestions'][$i]['skill']) > 0 ? json_encode($params['questions'][0]['subQuestions'][$i]['skill']) : NULL;
                    if (isset($params['questions'][0]['subQuestions'][$i]['skill']) && count($params['questions'][0]['subQuestions'][$i]['skill']) > 0) {
                        $this->addSkills($params['questions'][0]['subQuestions'][$i]['skill'], $params);
                    }
                    $text[$i]['created_by'] = $params['user_id'];
                    $text[$i]['created_date'] = date('Y-m-d H:i:s');
                    $quesNum++;
                }
            }
            //            $contentExists = $this->content_model->checkContent($text, 'edit');
            //            if (count($contentExists) == 0) {
            $this->common_model->bulkInsert('text_questions', $text);
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = "Question added Successfully";
        } else {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr['ErrorObject'] = " Question not added ";
        }
        //            else{
        //                $this->jsonarr["IsSuccess"]=true;
        //                $this->jsonarr["ResponseObject"]=" Question already Exists";
        //            }
        //        }
        $this->common_model->createLog($params, 'v1/content/addQuestion', $this->jsonarr, 'addQuestion');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function editQuestion_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (isset($params['questions']) && count($params['questions']) != 0) {
            $this->common_model->createLog($params, 'v1/content/editQuestion', 'only request', 'editQuestion');
            for ($r = 0; $r < count($params['questions']); $r++) {
                if ($params['questions'][$r]['question_type_id'] != 24) {
                    $questionExists = $this->content_model->checkquestion($params['questions'][$r]['question_id']);
                    if (count($questionExists) > 0) {
                        $text = [];
                        $text['question_type_id'] = $params['questions'][$r]['question_type_id'];
                        if (isset($params['questions'][$r]['editor_context'])) {
                            $text['editor_context'] = $params['questions'][$r]['editor_context'];
                        }
                        if (isset($params['questions'][$r]['editor_type'])) {
                            $text['editor_type'] = $params['questions'][$r]['editor_type'];
                        }
                        $text['question'] = $params['questions'][$r]['question'];
                        $text['editor_answer'] = isset($params['questions'][$r]['editor_answer']) ? $params['questions'][$r]['editor_answer'] : '';
                        $text['options'] = json_encode($params['questions'][$r]['options']);
                        $text['answer'] = json_encode($params['questions'][$r]['answer']);
                        $text['level'] = $params['questions'][$r]['level'];
                        if ($params['questions'][$r]['question_type_id'] == 5 || $params['questions'][$r]['question_type_id'] == 7) {
                            $text['heading_option'] = json_encode($params['questions'][$r]['heading_option']);
                        } else {
                            $text['heading_option'] = '';
                        }
                        $text['multiple_response'] = $params['questions'][$r]['multiple_response'];
                        $text['audo_grade'] = $params['questions'][$r]['autograde'];
                        $text['points'] = $params['questions'][$r]['points'];
                        $text['exact_match'] = $params['questions'][$r]['exact_match'];
                        $text['hint'] = json_encode($params['questions'][$r]['hint']);
                        $text['explanation'] = $params['questions'][$r]['explanation'];
                        $text['word_limit'] = $params['questions'][$r]['word_limit'];
                        $text['scoring_instruction'] = $params['questions'][$r]['scoring_instruction'];
                        $text['source'] = $params['questions'][$r]['source'];
                        $text['target'] = $params['questions'][$r]['target'];
                        $text['subject_id'] = isset($params['questions'][$r]['subject_id']) && $params['questions'][$r]['subject_id'] > 0 ? $params['questions'][$r]['subject_id'] : NULL;
                        $text['question_topic_id'] = isset($params['questions'][$r]['question_topic_id']) && $params['questions'][$r]['question_topic_id'] > 0 ? $params['questions'][$r]['question_topic_id'] : NULL;
                        $text['question_standard'] = isset($params['questions'][$r]['question_standard']) && $params['questions'][$r]['question_standard'] > 0 ? $params['questions'][$r]['question_standard'] : NULL;
                        $text['skill'] = isset($params['questions'][$r]['skill']) && count($params['questions'][$r]['skill']) > 0 ? json_encode($params['questions'][$r]['skill']) : NULL;
                        if(isset($params['questions'][$r]['skill']) && count($params['questions'][$r]['skill']) > 0){
                            $this->addSkills($params['questions'][$r]['skill'],$params);
                        }
                        $text['modified_by'] = $params['user_id'];
                        $text['modified_date'] = date('Y-m-d H:i:s');;
                        $condition = array('question_id' => $questionExists[0]['question_id']);
                        $this->common_model->update('text_questions', $text, $condition);
                    }
                } else {
                    for ($i = 0; $i < count($params['questions'][$r]['subQuestions']); $i++) {
                        $text = [];
                        $questionExists = $this->content_model->checkquestion($params['questions'][$r]['subQuestions'][$i]['question_id']);
                        if (count($questionExists) > 0) {
                            $text['sub_question_type_id'] = $params['questions'][$r]['subQuestions'][$i]['question_type_id'];
                            if (isset($params['questions'][$r]['editor_context'])) {
                                $text['editor_context'] = $params['questions'][$r]['editor_context'];
                            }
                            if (isset($params['questions'][$r]['subQuestions'][$i]['editor_type'])) {
                                $text['editor_type'] = $params['questions'][$r]['subQuestions'][$i]['editor_type'];
                            }
                            $text['question'] = $params['questions'][$r]['subQuestions'][$i]['question'];
                            $text['editor_answer'] = isset($params['questions'][$r]['subQuestions'][$i]['editor_answer']) ? $params['questions'][$r]['subQuestions'][$i]['editor_answer'] : '';
                            $text['options'] = json_encode($params['questions'][$r]['subQuestions'][$i]['options']);
                            $text['answer'] = json_encode($params['questions'][$r]['subQuestions'][$i]['answer']);
                            $text['level'] = isset($params['questions'][$r]['subQuestions'][$i]['level']) ? $params['questions'][$r]['subQuestions'][$i]['level'] : 0;
                            if ($params['questions'][$r]['subQuestions'][$i]['question_type_id'] == 5 || $params['questions'][$r]['subQuestions'][$i]['question_type_id'] == 7) {
                                $text['heading_option'] = json_encode($params['questions'][$r]['subQuestions'][$i]['heading_option']);
                            } else {
                                $text['heading_option'] = '';
                            }
                            $text['multiple_response'] = $params['questions'][$r]['subQuestions'][$i]['multiple_response'];
                            $text['audo_grade'] = $params['questions'][$r]['subQuestions'][$i]['autograde'];
                            $text['points'] = $params['questions'][$r]['subQuestions'][$i]['points'];
                            $text['exact_match'] = $params['questions'][$r]['subQuestions'][$i]['exact_match'];
                            $text['hint'] = json_encode($params['questions'][$r]['subQuestions'][$i]['hint']);
                            $text['explanation'] = $params['questions'][$r]['subQuestions'][$i]['explanation'];
                            $text['word_limit'] = $params['questions'][$r]['subQuestions'][$i]['word_limit'];
                            $text['scoring_instruction'] = $params['questions'][$r]['subQuestions'][$i]['scoring_instruction'];
                            $text['source'] = $params['questions'][$r]['subQuestions'][$i]['source'];
                            $text['target'] = $params['questions'][$r]['subQuestions'][$i]['target'];
                            $text['subject_id'] = isset($params['questions'][$r]['subQuestions'][$i]['subject_id']) && $params['questions'][$r]['subQuestions'][$i]['subject_id'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['subject_id'] : NULL;
                            $text['question_topic_id'] = isset($params['questions'][$r]['subQuestions'][$i]['question_topic_id']) && $params['questions'][$r]['subQuestions'][$i]['question_topic_id'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['question_topic_id'] : NULL;
                            $text['question_standard'] = isset($params['questions'][$r]['subQuestions'][$i]['question_standard']) && $params['questions'][$r]['subQuestions'][$i]['question_standard'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['question_standard'] : NULL;
                            $text['skill'] = isset($params['questions'][$r]['subQuestions'][$i]['skill']) && count($params['questions'][$r]['subQuestions'][$i]['skill']) > 0 ? json_encode($params['questions'][$r]['subQuestions'][$i]['skill']) : NULL;
                            if (isset($params['questions'][$r]['subQuestions'][$i]['skill']) && count($params['questions'][$r]['subQuestions'][$i]['skill']) > 0) {
                                $this->addSkills($params['questions'][$r]['subQuestions'][$i]['skill'], $params);
                            }
                            $text['passage_id'] = isset($params['questions'][$r]['passage_id']) ? $params['questions'][$r]['passage_id'] : 0;
                            $text['modified_by'] = $params['user_id'];
                            $text['modified_date'] = date('Y-m-d H:i:s');
                            $condition = array('question_id' => $questionExists[0]['question_id']);
                            $this->common_model->update('text_questions', $text, $condition);
                        } elseif (count($questionExists) == 0) {
                            $getSubQuestionNumber = $this->content_model->getSubQuestionNumber($params['content_id'], $params['questions'][$r]['question_no']);
                            if ($getSubQuestionNumber > 0) {
                                $subCount = $getSubQuestionNumber['sub_question_no'] + 1;
                            }
                            $text['content_id'] = $params['content_id'];
                            $text['question_type_id'] = $params['questions'][$r]['question_type_id'];
                            $text['sub_question_type_id'] = $params['questions'][$r]['subQuestions'][$i]['question_type_id'];
                            if (isset($params['questions'][$r]['editor_context'])) {
                                $text['editor_context'] = $params['questions'][$r]['editor_context'];
                            }
                            if (isset($params['questions'][$r]['subQuestions'][$i]['editor_type'])) {
                                $text['editor_type'] = $params['questions'][$r]['subQuestions'][$i]['editor_type'];
                            }
                            $text['question_no'] = $params['questions'][$r]['question_no'];
                            $text['sub_question_no'] = $subCount;
                            $text['has_sub_question'] = 1;
                            $text['question'] = $params['questions'][$r]['subQuestions'][$i]['question'];
                            $text['editor_answer'] = isset($params['questions'][$r]['subQuestions'][$i]['editor_answer']) ? $params['questions'][$r]['subQuestions'][$i]['editor_answer'] : '';
                            $text['options'] = json_encode($params['questions'][$r]['subQuestions'][$i]['options']);
                            $text['answer'] = json_encode($params['questions'][$r]['subQuestions'][$i]['answer']);
                            $text['level'] = isset($params['questions'][$r]['subQuestions'][$i]['level']) ? $params['questions'][$r]['subQuestions'][$i]['level'] : 0;
                            if ($params['questions'][$r]['subQuestions'][$i]['question_type_id'] == 5 || $params['questions'][$r]['subQuestions'][$i]['question_type_id'] == 7) {
                                $text['heading_option'] = json_encode($params['questions'][$r]['subQuestions'][$i]['heading_option']);
                            } else {
                                $text['heading_option'] = '';
                            }
                            $text['multiple_response'] = $params['questions'][$r]['subQuestions'][$i]['multiple_response'];
                            $text['audo_grade'] = $params['questions'][$r]['subQuestions'][$i]['autograde'];
                            $text['points'] = $params['questions'][$r]['subQuestions'][$i]['points'];
                            $text['exact_match'] = $params['questions'][$r]['subQuestions'][$i]['exact_match'];
                            $text['hint'] = json_encode($params['questions'][$r]['subQuestions'][$i]['hint']);
                            $text['explanation'] = $params['questions'][$r]['subQuestions'][$i]['explanation'];
                            $text['word_limit'] = $params['questions'][$r]['subQuestions'][$i]['word_limit'];
                            $text['scoring_instruction'] = $params['questions'][$r]['subQuestions'][$i]['scoring_instruction'];
                            $text['source'] = $params['questions'][$r]['subQuestions'][$i]['source'];
                            $text['target'] = $params['questions'][$r]['subQuestions'][$i]['target'];
                            $text['subject_id'] = isset($params['questions'][$r]['subQuestions'][$i]['subject_id']) && $params['questions'][$r]['subQuestions'][$i]['subject_id'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['subject_id'] : NULL;
                            $text['question_topic_id'] = isset($params['questions'][$r]['subQuestions'][$i]['question_topic_id']) && $params['questions'][$r]['subQuestions'][$i]['question_topic_id'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['question_topic_id'] : NULL;
                            $text['question_standard'] = isset($params['questions'][$r]['subQuestions'][$i]['question_standard']) && $params['questions'][$r]['subQuestions'][$i]['question_standard'] > 0 ? $params['questions'][$r]['subQuestions'][$i]['question_standard'] : NULL;
                            $text['skill'] = isset($params['questions'][$r]['subQuestions'][$i]['skill']) && count($params['questions'][$r]['subQuestions'][$i]['skill']) > 0 ? json_encode($params['questions'][$r]['subQuestions'][$i]['skill']) : NULL;
                            if (isset($params['questions'][$r]['subQuestions'][$i]['skill']) && count($params['questions'][$r]['subQuestions'][$i]['skill']) > 0) {
                                $this->addSkills($params['questions'][$r]['subQuestions'][$i]['skill'], $params);
                            }
                            $text['passage_id'] = isset($params['questions'][$r]['passage_id']) ? $params['questions'][$r]['passage_id'] : 0;
                            $text['created_by'] = $params['user_id'];
                            $text['created_date'] = date('Y-m-d H:i:s');
                            $this->common_model->insert('text_questions', $text);
                        }
                    }
                }
            }
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = "Questions updated successfully";
        }
        $this->common_model->createLog($params, 'v1/content/editQuestion', $this->jsonarr, 'editQuestion');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function deleteQuestion_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/deleteQuestion', 'only request', 'deleteQuestion');
            $questionId = implode(',', $params['question_id']);
            $condition = "question_id IN ({$questionId})";
            $deleteQuestion = $this->common_model->delete('text_questions', $condition);
            if ($deleteQuestion) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = 'Question Deleted Successfully';
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = 'Unable to delete question';
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/deleteQuestion', $this->jsonarr, 'deleteQuestion');
        return $this->printjson($this->jsonarr);
    }

    public function addStudentAnswer_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/addStudentAnswer', 'only request', 'addStudentAnswer');
            $getStudentPlatform = $this->content_model->getStudentPlatform($params);
            $platform = $params['platform'] == 'web' ? 1 : 2;
            if ($getStudentPlatform[0]['platform'] != 0) {
                if ($getStudentPlatform[0]['platform'] != $platform) {
                    $platform = 3;
                }
            }
            $checkContentStatus = $this->content_model->getRedoStatus($params);
            if (($checkContentStatus['redo_test'] == 0 && $checkContentStatus['status'] != 4) || ($checkContentStatus['redo_test'] == 1 || $checkContentStatus['redo_test'] == 2)) {
                $status = $params['status'];
            } else {
                $status = $checkContentStatus['status'];
            }
            $condition = array('id' => $params['student_content_id']);
            $data = array(
                'status' => $status, 'student_feedback' => $params['overall_student_feedback'],
                'upload_answer' => isset($params['upload_answer']) && $params['upload_answer'] != '' ? json_encode($params['upload_answer']) : '',
                'answer_completed_date' => date('Y-m-d H:i:s'),
                'platform' => $platform,
                'laq_id' => isset($params['laq_id']) ? $params['laq_id'] : 0
            );
            $update = $this->common_model->update('student_content', $data, $condition);
            $work = array(
                'student_content_status' => $status,
                'answer_completed_date' => date('Y-m-d H:i:s')
            );
            $workCondition = array('student_content_id' => $params['student_content_id']);
            $studentworkupdated = $this->common_model->update('student_work', $work, $workCondition);
            if ($update) {
                if ($params['content_format'] != 3) {
                    $updateData = $insertData = $insertData1 = $updateData1 = [];
                    $k = $l = $m = $n = 0;
                    for ($i = 0; $i < count($params['answers']); $i++) {
                        for ($z = 0; $z < count($params['answers'][$i]['section']); $z++) {
                            for ($j = 0; $j < count($params['answers'][$i]['section'][$z]['sub_questions']); $j++) {
                                $checkStudentAnswer = $this->content_model->checkStudentAnswer($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'], $params['student_content_id']);
                                if (count($checkStudentAnswer) == 0) {
                                    $getClassContentId = $this->content_model->getClassContentId($params);
                                    if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40) {
                                        $insertData1[$m]['answer_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'];
                                        $insertData1[$m]['content_id'] = $params['content_id'];
                                        if (isset($params['class_id'])) {
                                            $insertData1[$m]['class_id'] = $params['class_id'];
                                        }
                                        $insertData1[$m]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                        $insertData1[$m]['student_id'] = $params['student_id'];
                                        $insertData1[$m]['correct_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                        $insertData1[$m]['actual_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['earned_points'])) {
                                            $insertData1[$m]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['earned_points'];
                                        }
                                        if ($params['status'] == 4) {
                                            $insertData1[$m]['answer_status'] = 4;
                                        } else {
                                            $insertData1[$m]['answer_status'] = 0;
                                        }
                                        if ($params['platform'] == 'web') {
                                            $insertData1[$m]['answer_attended'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_attended'];
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) && count($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) > 0) {
                                                $folder = "../uploads/studentWorkArea/";
                                                $fileName = "student-workarea-" . $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'] . -$params['content_id'] . -$params['class_id'] . -$params['student_id'] . '.json';
                                                $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']);
                                                $path = "uploads/studentWorkArea/" . $fileName;
                                                $insertData1[$m]['workarea'] = $path;
                                            } else {
                                                $insertData1[$m]['workarea'] =  '';
                                            }
                                        } else {
                                            $insertData1[$m]['answer_attended'] = 0;
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'])) {
                                            $insertData1[$m]['jiixdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'];
                                        } else {
                                            $insertData1[$m]['jiixdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'])) {
                                            $insertData1[$m]['roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'];
                                        } else {
                                            $insertData1[$m]['roughdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'])) {
                                            $insertData1[$m]['rough_image_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'];
                                        } else {
                                            $insertData1[$m]['rough_image_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'])) {
                                            $insertData1[$m]['rough_image_thumb_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'];
                                        } else {
                                            $insertData1[$m]['rough_image_thumb_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'])) {
                                            $insertData1[$m]['student_answer_image'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'];
                                        } else {
                                            $insertData1[$m]['student_answer_image'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'])) {
                                            $insertData1[$m]['student_roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'];
                                        } else {
                                            $insertData1[$m]['student_roughdata'] = '';
                                        }
                                        if ($params['platform'] == 'ios') {
                                            $insertData1[$m]['editor_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answerdata'];
                                        }
                                        $insertData1[$m]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                        $insertData1[$m]['created_by'] = $params['user_id'];
                                        $insertData1[$m]['created_date'] = date('Y-m-d H:i:s');
                                        $insertData1[$m]['student_content_id'] = $params['student_content_id'];
                                        $insertData1[$m]['class_content_id'] = $getClassContentId[0]['id'];
                                        //                                        $updateData[$k]['content_id'] = $params['content_id'];
                                        //                                        $updateData[$k]['student_id'] = $params['student_id'];
                                        //                                        $updateData[$k]['class_id'] = $params['class_id'];
                                        $m++;
                                        //                                        $condition = array('answer_id' => $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'], 'content_id' => $params['content_id'], 'student_id' => $params['student_id'], 'class_id' => $params['class_id']);
                                        //                                        $this->common_model->update('student_answers', $data, $condition);
                                        //                                        unset($data);
                                    } else {
                                        $insertData[$l]['answer_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'];
                                        $insertData[$l]['content_id'] = $params['content_id'];
                                        if (isset($params['class_id'])) {
                                            $insertData[$l]['class_id'] = $params['class_id'];
                                        }
                                        $insertData[$l]['question_no'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['sub_question_no'];
                                        $insertData[$l]['student_id'] = $params['student_id'];
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54) {
                                            $insertData[$l]['correct_answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                        } else {
                                            $insertData[$l]['correct_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                        }
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                $insertData[$l]['student_answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['mob_options']);
                                                $insertData[$l]['student_answer'] = addcslashes($insertData[$l]['student_answer'], '\\');
                                            } else {
                                                // $insertData[$l]['student_answer'] = addslashes(json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']));
                                                //  $insertData[$l]['student_answer'] = trim(str_replace('\n', '', json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'])));
                                                $insertData[$l]['student_answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']);
                                                $insertData[$l]['student_answer'] = addcslashes($insertData[$l]['student_answer'], '\\');
                                            }
                                        } else {
                                            if (
                                                isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer1']) &&
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 30
                                            ) {
                                                $insertData[$l]['student_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer1'];
                                            } else {
                                                $insertData[$l]['student_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'];
                                            }
                                        }
                                        $insertData[$l]['actual_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['earned_points'])) {
                                            $insertData[$l]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['earned_points'];
                                        }
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] == '') {
                                            if ($params['status'] == 4) {
                                                $insertData[$l]['answer_status'] = 4;
                                            } else {
                                                $insertData[$l]['answer_status'] = 0;
                                            }
                                            $insertData[$l]['earned_points'] = 0;
                                        } elseif ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54) {
                                            $count = $correct = $wrong = 0;
                                            if ($params['platform'] == 'web') {
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] as $index => $get) {
                                                    if ($get != '') {
                                                        if (strtolower($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'][$index]['value']) == strtolower(trim($get))) {
                                                            $correct++;
                                                        } else {
                                                            $wrong++;
                                                        }
                                                        $count++;
                                                    }
                                                }
                                            } else {
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['mob_options'] as $get) {
                                                    if ($get != '') {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            if ($params['platform'] == 'web') {
                                                $data = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                            } else {
                                                $data = json_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            }
                                            if ($count > 0 && $correct == count($data)) {
                                                if ($params['status'] == 4) {
                                                    $insertData[$l]['answer_status'] = 2;
                                                } else {
                                                    $insertData[$l]['answer_status'] = 0;
                                                }
                                                $insertData[$l]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                            } elseif ($count > 0 && $wrong != 0) {
                                                if ($params['status'] == 4) {
                                                    $insertData[$l]['answer_status'] = 1;
                                                } else {
                                                    $insertData[$l]['answer_status'] = 0;
                                                }
                                                $insertData[$l]['earned_points'] = 0;
                                            } else {
                                                if ($params['status'] == 4) {
                                                    $insertData[$l]['answer_status'] = 4;
                                                } else {
                                                    $insertData[$l]['answer_status'] = 0;
                                                }
                                                $insertData[$l]['earned_points'] = 0;
                                            }
                                        } elseif ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] != '') {
                                            if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'] == 1) {
                                                $correctAnswer = base64_decode(base64_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']));
                                                $givenAnswer = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'];
                                                $correctAnswer = json_decode($correctAnswer);
                                                $correctAnswer = array_map('strtolower', $correctAnswer);
                                                if (in_array(strtolower($givenAnswer), $correctAnswer)) {
                                                    $insertData[$l]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];

                                                    if ($params['status'] == 4) {
                                                        $insertData[$l]['answer_status'] = 2;  // Correct answer
                                                    } else {
                                                        $insertData[$l]['answer_status'] = 0;
                                                    }
                                                } else {
                                                    if ($params['status'] == 4) {
                                                        $insertData[$l]['answer_status'] = 1;  // Incorrect answer
                                                    } else {
                                                        $insertData[$l]['answer_status'] = 0;
                                                    }
                                                    $insertData[$l]['earned_points'] = 0;
                                                }
                                                // if (strtolower(base64_decode(base64_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']))) == strtolower(trim($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']))) {
                                                //     $insertData[$l]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                //     if ($params['status'] == 4) {
                                                //         $insertData[$l]['answer_status'] = 2;
                                                //     } else {
                                                //         $insertData[$l]['answer_status'] = 0;
                                                //     }
                                                // } else {
                                                //     $insertData[$l]['earned_points'] = 0;
                                                //     if ($params['status'] == 4) {
                                                //         $insertData[$l]['answer_status'] = 1;
                                                //     } else {
                                                //         $insertData[$l]['answer_status'] = 0;
                                                //     }
                                                // }
                                            } else {
                                                $insertData[$l]['earned_points'] = 0;
                                                if ($params['status'] == 4) {
                                                    $insertData[$l]['answer_status'] = 4;
                                                } else {
                                                    $insertData[$l]['answer_status'] = 0;
                                                }
                                            }
                                        }
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] == '') {
                                            $insertData[$l]['earned_points'] = 0;
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'])) {
                                            $insertData[$l]['jiixdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'];
                                        } else {
                                            $insertData[$l]['jiixdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'])) {
                                            $insertData[$l]['roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'];
                                        } else {
                                            $insertData[$l]['roughdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'])) {
                                            $insertData[$l]['rough_image_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'];
                                        } else {
                                            $insertData[$l]['rough_image_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'])) {
                                            $insertData[$l]['rough_image_thumb_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'];
                                        } else {
                                            $insertData[$l]['rough_image_thumb_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'])) {
                                            $insertData[$l]['student_answer_image'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'];
                                        } else {
                                            $insertData[$l]['student_answer_image'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'])) {
                                            $insertData[$l]['student_roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'];
                                        } else {
                                            $insertData[$l]['student_roughdata'] = '';
                                        }
                                        if ($params['platform'] == 'web') {
                                            $insertData[$l]['answer_attended'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_attended'];
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) && count($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) > 0) {
                                                $folder = "../uploads/studentWorkArea/";
                                                $fileName = "student-workarea-" . $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'] . -$params['content_id'] . -$params['class_id'] . -$params['student_id'] . '.json';
                                                $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']);
                                                $path = "uploads/studentWorkArea/" . $fileName;
                                                $insertData[$l]['workarea'] = $path;
                                            } else {
                                                $insertData[$l]['workarea'] =  '';
                                            }
                                        } else {
                                            $insertData[$l]['answer_attended'] = 0;
                                        }
                                        $insertData[$l]['auto_grade'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'];
                                        $insertData[$l]['created_by'] = $params['user_id'];
                                        $insertData[$l]['created_date'] = date('Y-m-d H:i:s');
                                        $insertData[$l]['class_content_id'] = $getClassContentId[0]['id'];
                                        $insertData[$l]['student_content_id'] = $params['student_content_id'];
                                        $l++;
                                        //                                        $this->common_model->insert('student_answers', $data);
                                        //                                        unset($data);
                                    }
                                } else {
                                    if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 40) {
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'])) {
                                            $data = array('jiixdata' => $params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata']);
                                            $condition = array(
                                                'answer_id' => $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'],
                                                'class_id' => $params['class_id'],
                                                'content_id' => $params['content_id'],
                                                'student_id' => $params['student_id'],
                                                'student_content_id' => $params['student_content_id']
                                            );
                                            $this->common_model->update('student_answers', $data, $condition);
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'])) {
                                            $updateData1[$n]['roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'];
                                        } else {
                                            $updateData1[$n]['roughdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'])) {
                                            $updateData1[$n]['rough_image_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'];
                                        } else {
                                            $updateData1[$n]['rough_image_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'])) {
                                            $updateData1[$n]['rough_image_thumb_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'];
                                        } else {
                                            $updateData1[$n]['rough_image_thumb_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'])) {
                                            $updateData1[$n]['student_answer_image'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'];
                                        } else {
                                            $updateData1[$n]['student_answer_image'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'])) {
                                            $updateData1[$n]['student_roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'];
                                        } else {
                                            $updateData1[$n]['student_roughdata'] = '';
                                        }
                                        if ($params['platform'] == 'web') {
                                            $updateData1[$n]['answer_attended'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_attended'];
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) && count($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) > 0) {
                                                $folder = "../uploads/studentWorkArea/";
                                                $fileName = "student-workarea-" . $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'] . -$params['content_id'] . -$params['class_id'] . -$params['student_id'] . '.json';
                                                $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']);
                                                $path = "uploads/studentWorkArea/" . $fileName;
                                                $updateData1[$n]['workarea'] = $path;
                                            } else {
                                                $updateData1[$n]['workarea'] = '';
                                            }
                                        } else {
                                            $updateData1[$n]['answer_attended'] = 0;
                                        }
                                        if ($params['platform'] == 'ios') {
                                            $updateData1[$n]['editor_answer'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answerdata'];
                                        }
                                        $updateData1[$n]['answer_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'];

                                        //                                        $updateData[$k]['content_id'] = $params['content_id'];
                                        //                                        $updateData[$k]['student_id'] = $params['student_id'];
                                        //                                        $updateData[$k]['class_id'] = $params['class_id'];
                                        $n++;
                                        //                                            $condition = array("answer_id" => $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'], 'content_id' => $params['content_id'], 'student_id' => $params['student_id'], 'class_id' => $params['class_id']);
                                        //                                            $this->common_model->update('student_answers', $data, $condition);
                                        //                                            unset($data);

                                    } else {
                                        //                                        $condition = array("answer_id" => $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'], 'content_id' => $params['content_id'], 'student_id' => $params['student_id'], 'class_id' => $params['class_id']);
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                $updateData[$k]['student_answer'] = addslashes(json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['mob_options']));
                                            } else {
                                                //  $updateData[$k]['student_answer'] = trim(str_replace('\n', '', json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'])));
                                                $updateData[$k]['student_answer'] = addslashes(json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']));
                                                //  $updateData[$k]['student_answer'] = json_encode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']);
                                                //  $updateData[$k]['student_answer'] = addcslashes($updateData[$k]['student_answer'], '\\');
                                            }
                                        } else {
                                            if (
                                                isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer1']) &&
                                                $params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 30
                                            ) {
                                                $updateData[$k]['student_answer'] = addslashes($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer1']);
                                            } else {
                                                $updateData[$k]['student_answer'] = addslashes($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']);
                                            }
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'])) {
                                            $updateData[$k]['jiixdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['jiixdata'];
                                        } else {
                                            $updateData[$k]['jiixdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'])) {
                                            $updateData[$k]['roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['roughdata'];
                                        } else {
                                            $updateData[$k]['roughdata'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'])) {
                                            $updateData[$k]['rough_image_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_url'];
                                        } else {
                                            $updateData[$k]['rough_image_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'])) {
                                            $updateData[$k]['rough_image_thumb_url'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['rough_image_thumb_url'];
                                        } else {
                                            $updateData[$k]['rough_image_thumb_url'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'])) {
                                            $updateData[$k]['student_answer_image'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_answer_image'];
                                        } else {
                                            $updateData[$k]['student_answer_image'] = '';
                                        }
                                        if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'])) {
                                            $updateData[$k]['student_roughdata'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['student_roughdata'];
                                        } else {
                                            $updateData[$k]['student_roughdata'] = '';
                                        }
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] == '') {
                                            if ($params['status'] == 4) {
                                                $updateData[$k]['answer_status'] = 4;
                                            }
                                            $updateData[$k]['earned_points'] = 0;
                                        } elseif ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['question_type_id'] == 54) {
                                            $count = $correct = $wrong = 0;
                                            if ($params['platform'] == 'web') {
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] as $index => $get) {
                                                    if ($get != '') {
                                                        if (strtolower($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'][$index]['value']) == strtolower(trim($get))) {
                                                            $correct++;
                                                        } else {
                                                            $wrong++;
                                                        }
                                                        $count++;
                                                    }
                                                }
                                            } else {
                                                foreach ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['mob_options'] as $get) {
                                                    if ($get != '') {
                                                        $count++;
                                                    }
                                                }
                                            }
                                            if ($params['platform'] == 'web') {
                                                $data = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer'];
                                            } else {
                                                $data = json_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']);
                                            }
                                            if ($count > 0 && $correct == count($data)) {
                                                if ($params['status'] == 4) {
                                                    $updateData[$k]['answer_status'] = 2;
                                                }
                                                $updateData[$k]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                            } elseif ($count > 0 && $wrong != 0) {
                                                if ($params['status'] == 4) {
                                                    $updateData[$k]['answer_status'] = 1;
                                                }
                                                $updateData[$k]['earned_points'] = 0;
                                            } else {
                                                if ($params['status'] == 4) {
                                                    $updateData[$k]['answer_status'] = 4;
                                                }
                                                $updateData[$k]['earned_points'] = 0;
                                            }
                                        } elseif ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] != '') {
                                            if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['auto_grade'] == 1) {
                                                $correctAnswer = base64_decode(base64_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']));
                                                $givenAnswer = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'];
                                                $correctAnswer = json_decode($correctAnswer);
                                                $correctAnswer = array_map('strtolower', $correctAnswer);
                                                if (in_array(strtolower($givenAnswer), $correctAnswer)) {
                                                    $updateData[$k]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];

                                                    if ($params['status'] == 4) {
                                                        $updateData[$k]['answer_status'] = 2;  // Correct answer
                                                    }
                                                } else {
                                                    if ($params['status'] == 4) {
                                                        $updateData[$k]['answer_status'] = 1;  // Incorrect answer
                                                    }
                                                    $updateData[$k]['earned_points'] = 0;
                                                }
                                                // if (strtolower(base64_decode(base64_decode($params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer']))) == strtolower(trim($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer']))) {
                                                //     $updateData[$k]['earned_points'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['points'];
                                                //     if ($params['status'] == 4) {
                                                //         $updateData[$k]['answer_status'] = 2;
                                                //     }
                                                // } else {
                                                //     if ($params['status'] == 4) {
                                                //         $updateData[$k]['answer_status'] = 1;
                                                //     }
                                                //     $updateData[$k]['earned_points'] = 0;
                                                // }
                                            } else {
                                                $updateData[$k]['earned_points'] = 0;
                                                if ($params['status'] == 4) {
                                                    $updateData[$k]['answer_status'] = 4;
                                                }
                                            }
                                        }
                                        if ($params['answers'][$i]['section'][$z]['sub_questions'][$j]['given_answer'] == '') {
                                            $updateData[$k]['earned_points'] = 0;
                                        }
                                        if ($params['platform'] == 'web') {
                                            $updateData[$k]['answer_attended'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_attended'];
                                            if (isset($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) && count($params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']) > 0) {
                                                $folder = "../uploads/studentWorkArea/";
                                                $fileName = "student-workarea-" . $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'] . -$params['content_id'] . -$params['class_id'] . -$params['student_id'] . '.json';
                                                $createAnnotationFile = $this->common_model->createFlatFile($folder, $fileName, $params['answers'][$i]['section'][$z]['sub_questions'][$j]['workarea']);
                                                $path = "uploads/studentWorkArea/" . $fileName;
                                                $updateData[$k]['workarea'] = $path;
                                            } else {
                                                $updateData[$k]['workarea'] = '';
                                            }
                                        } else {
                                            $updateData[$k]['answer_attended'] = 0;
                                        }
                                        $updateData[$k]['modified_by'] = $params['user_id'];
                                        $updateData[$k]['modified_date'] = date('Y-m-d H:i:s');
                                        $updateData[$k]['answer_id'] = $params['answers'][$i]['section'][$z]['sub_questions'][$j]['answer_id'];
                                        //                                        $updateData[$l]['content_id'] = $params['content_id'];
                                        //                                        $updateData[$l]['student_id'] = $params['student_id'];
                                        //                                        $updateData[$l]['class_id'] = $params['class_id'];
                                        $k++;
                                        //                                        $this->common_model->update('student_answers', $data, $condition);
                                        //                                        unset($data);
                                    }
                                }
                            }
                        }
                    }
                    if (count($insertData) > 0) {
                        $this->common_model->bulkInsert('student_answers', $insertData);
                    }
                    if (count($insertData1) > 0) {
                        $this->common_model->bulkInsert('student_answers', $insertData1);
                    }
                    if (count($updateData) > 0) {
                        //                        if (count($updateData) > 100) {
                        //
                        //                            $chunk_result = array_chunk($updateData, 100, true);
                        //                            foreach ($chunk_result as $chunk) {
                        //                                $this->common_model->updateBatchMultiple('student_answers', $chunk, 'answer_id', $params['class_id'], $params['student_id'], $params['content_id']);
                        //                            }
                        //                        } else {
                        //$this->common_model->updateBatchMultiple('student_answers', $updateData, 'answer_id', $params['class_id'], $params['student_id'], $params['content_id']);
                        $query = '';
                        foreach ($updateData as $key => $value) {
                            $getOldAnswers = $this->content_model->getOldAnswers($value['answer_id'], $params);
                            // $value['student_answer'] = str_replace("'", "\'", $value['student_answer']);
                            // $value['student_answer'] = str_replace('"', '\"', $value['student_answer']);
                            // if($params['platform'] == 'ios') {
                            //     $value['student_answer'] = '"'.$value['student_answer'].'"';
                            // }
                            $value['jiixdata'] = str_replace("'", "\'", $value['jiixdata']);
                            $value['roughdata'] = str_replace("'", "\'", $value['roughdata']);
                            $value['rough_image_url'] = str_replace("'", "\'", $value['rough_image_url']);
                            $value['rough_image_thumb_url'] = str_replace("'", "\'", $value['rough_image_thumb_url']);
                            $value['student_answer_image'] = str_replace("'", "\'", $value['student_answer_image']);
                            $value['student_roughdata'] = str_replace("'", "\'", $value['student_roughdata']);
                            $value['answer_status'] = isset($value['answer_status']) ? $value['answer_status'] : $getOldAnswers[0]['answer_status'];
                            $workarea = isset($value['workarea']) ? $value['workarea'] : '';
                            $query = $query . "update student_answers set student_answer = '{$value['student_answer']}', jiixdata = '{$value['jiixdata']}', roughdata = '{$value['roughdata']}', rough_image_url = '{$value['rough_image_url']}', rough_image_thumb_url = '{$value['rough_image_thumb_url']}', student_answer_image = '{$value['student_answer_image']}',student_roughdata = '{$value['student_roughdata']}',earned_points = '{$value['earned_points']}',answer_status = '{$value['answer_status']}',answer_attended = '{$value['answer_attended']}',modified_by = '{$value['modified_by']}',workarea ='{$workarea}',modified_date = '{$value['modified_date']}' 
                              where answer_id = '{$value['answer_id']}' and student_content_id = '{$params['student_content_id']}';";
                        }
                        //mysql normal query execution
                        //mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                        $database = $this->content_model->getdb();
                        if ($_SERVER['HTTP_HOST'] == 'uthkal.com') {
                            $mysqli = new mysqli("uthkal.com", "root", "india@123", $database['db']);
                        } else {
                            $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
                            $mysqli = new mysqli($prop['hostname'], $prop['username'], $prop['password'], $prop['database']);
                        }
                        //                        else {
                        //                            $mysqli = new mysqli("edquill.com", "root", "edquill@321", "xtracurriculum_live");
                        //                        }

                        $mysqli->multi_query($query);



                        //                            $this->common_model->queryUpdate('student_answers', $updateData, 'answer_id', $params['class_id'], $params['student_id'], $params['content_id']);
                        //                        }
                    }
                    if (count($updateData1) > 0) {
                        if (count($updateData1) > 100) {

                            $chunk_result = array_chunk($updateData1, 100, true);
                            foreach ($chunk_result as $chunk) {
                                $this->common_model->updateBatchMultiple('student_answers', $chunk, 'answer_id', $params['student_content_id']);
                            }
                        } else {
                            $this->common_model->updateBatchMultiple('student_answers', $updateData1, 'answer_id', $params['student_content_id']);
                        }
                    }
                    if ($params['content_format'] != 3) {
                        if (($params['status'] == 4) || ($params['platform'] == "ios" && $params['status'] == 4)) {
                            $getStudentAnswer = $this->content_model->getStudentAnswer1($params);
                            if (count($getStudentAnswer) > 0) {
                                foreach ($getStudentAnswer as $key => $value) {
                                    $getAnswer = $this->content_model->getContentAnswer($value['answer_id']);
                                    if (count($getAnswer) > 0) {
                                        if ($getAnswer[0]['question_type_id'] == 54) {
                                            $correct = 0;
                                            $getContentAnswer = json_decode($getAnswer[0]['answer']);
                                            $value['student_answer'] = (json_decode($value['student_answer'], true) == NULL) ? json_decode(stripslashes($value['student_answer'])) : json_decode($value['student_answer'], true);
                                            foreach ($value['student_answer'] as $key1 => $value1) {
                                                if (strtolower(trim($value1)) == strtolower(trim($getContentAnswer[$key1]->value))) {
                                                    $correct++;
                                                }
                                            }
                                            if (count($getContentAnswer) == $correct) {
                                                $answerStatus = 2;
                                                $points = $getAnswer[0]['points'];
                                            } else {
                                                $answerStatus = 1;
                                                $points = 0;
                                            }
                                        } else {
                                            $points = 0;
                                            $answerStatus = 1;
                                            $getAnswer[0]['answer'] = array_map('strtolower', json_decode($getAnswer[0]['answer']));
                                            if (in_array(strtolower($value['student_answer']), $getAnswer[0]['answer'])) {
                                                $answerStatus = 2;
                                                $points = $getAnswer[0]['points'];
                                            }
                                        }
                                        $answerData = array(
                                            'answer_status' => $answerStatus,
                                            'earned_points' => $points
                                        );
                                        $answerStatusCondition = array('id' => $value['id']);
                                        $this->common_model->update('student_answers', $answerData, $answerStatusCondition);
                                    }
                                }
                            }
                        }
                    }
                    $totalScoreUpdate = $this->content_model->totalScoreUpdate($params);
                    $totalContentScore = $this->content_model->totalContentScore($params);
                    if ($totalScoreUpdate && $totalContentScore) {
                        $update = array(
                            'earned_points' => $totalScoreUpdate[0]['earned_points'],
                            'points' => $totalContentScore[0]['points']
                        );
                        $updateCondition = array('id' => $params['student_content_id']);
                        $updateCondition1 = array('student_content_id' => $params['student_content_id']);
                        $this->common_model->update('student_content', $update, $updateCondition);
                        $studentWork = array(
                            'obtained_score' => $totalScoreUpdate[0]['earned_points'],
                            'total_score' => $totalContentScore[0]['points']
                        );
                        $this->common_model->update('student_work', $studentWork, $updateCondition1);
                    }
                    if ($params['status'] == 4) {
                        $this->updateScoreRelease($params);
                    }
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = $params['status'] == 4 ? "Answers Submitted Successfully" : "Answers Saved Successfully";
                } else {
                    for ($i = 0; $i < count($params['questions']); $i++) {
                        if (isset($params['questions'][$i]['subQuestions'])) {
                            for ($j = 0; $j < count($params['questions'][$i]['subQuestions']); $j++) {
                                $checkStudentAnswer = $this->content_model->checkStudentAnswer($params['questions'][$i]['subQuestions'][$j]['question_id'], $params['student_content_id']);
                                $data = [];
                                if (count($checkStudentAnswer) == 0) {
                                    if (
                                        $params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 40 ||
                                        $params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 41
                                    ) {
                                        $getGraphQuestions = $this->content_model->getGraphQuestions($params['student_id'], $params['questions'][$i]['subQuestions'][$j]['question_id'], $params['class_id']);
                                        if (!empty($getGraphQuestions)) {
                                            $data = $getGraphQuestions;
                                            $condition = array('answer_id' => $getGraphQuestions['answer_id'], 'student_id' => $getGraphQuestions['student_id'], 'class_id' => $getGraphQuestions['class_id']);
                                            $this->common_model->delete('graph_answers', $condition);
                                            $this->common_model->insert('student_answers', $data);
                                            unset($data);
                                        }
                                    } else {
                                        $data['answer_id'] = $params['questions'][$i]['subQuestions'][$j]['question_id'];
                                        $data['content_id'] = $params['content_id'];
                                        if (isset($params['class_id'])) {
                                            $data['class_id'] = $params['class_id'];
                                        }
                                        $data['question_no'] = $params['questions'][$i]['subQuestions'][$j]['question_no'];
                                        $data['student_id'] = $params['student_id'];
                                        $data['student_content_id'] = $params['student_content_id'];
                                        $data['correct_answer'] = json_encode($params['questions'][$i]['subQuestions'][$j]['answer']);
                                        $data['student_answer'] = json_encode($params['questions'][$i]['subQuestions'][$j]['given_answer']);
                                        $data['options'] = json_encode($params['questions'][$i]['subQuestions'][$j]['options']);
                                        $data['actual_points'] = $params['questions'][$i]['subQuestions'][$j]['points'];
                                        $data['student_answer_image'] = isset($params['questions'][$i]['subQuestions'][$j]['student_answer_image']) ? $params['questions'][$i]['subQuestions'][$j]['student_answer_image'] : '';
                                        $data['rough_image_thumb_url'] = isset($params['questions'][$i]['subQuestions'][$j]['rough_image_thumb_url']) ? $params['questions'][$i]['subQuestions'][$j]['rough_image_thumb_url'] : '';
                                        $data['jiixdata'] = isset($params['questions'][$i]['subQuestions'][$j]['jiixdata']) ? $params['questions'][$i]['subQuestions'][$j]['jiixdata'] : '';
                                        $data['roughdata'] = isset($params['questions'][$i]['subQuestions'][$j]['roughdata']) ? $params['questions'][$i]['subQuestions'][$j]['roughdata'] : '';
                                        $data['rough_image_url'] = isset($params['questions'][$i]['subQuestions'][$j]['rough_image_url']) ? $params['questions'][$i]['subQuestions'][$j]['rough_image_url'] : '';
                                        $data['student_roughdata'] = isset($params['questions'][$i]['subQuestions'][$j]['student_roughdata']) ? $params['questions'][$i]['subQuestions'][$j]['student_roughdata'] : '';
                                        $data['time_taken'] = isset($params['questions'][$i]['subQuestions'][$j]['time_taken']) ? $params['questions'][$i]['subQuestions'][$j]['time_taken'] : '';
                                        $data['is_correct'] = isset($params['questions'][$i]['subQuestions'][$j]['is_correct']) ? $params['questions'][$i]['subQuestions'][$j]['is_correct'] : '';
                                        $data['no_of_attempt'] = isset($params['questions'][$i]['subQuestions'][$j]['no_of_attempt']) ? $params['questions'][$i]['subQuestions'][$j]['no_of_attempt'] : 1;
                                        $data['marked_review'] = isset($params['questions'][$i]['subQuestions'][$j]['markedAsReview']) ? ($params['questions'][$i]['subQuestions'][$j]['markedAsReview'] ? 1 : 0) : NULL;
                                        $data['optionsCopy'] = isset($params['questions'][$i]['subQuestions'][$j]['optionsCopy']) ? json_encode($params['questions'][$i]['subQuestions'][$j]['optionsCopy']) : '';
                                        if (isset($params['questions'][$i]['subQuestions'][$j]['earned_points'])) {
                                            $data['earned_points'] = $params['questions'][$i]['subQuestions'][$j]['earned_points'];
                                        }
                                        $c = 0;
                                        if ($params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 10) {
                                            foreach ($params['questions'][$i]['subQuestions'][$j]['given_answer'] as $key => $value) {
                                                if ($value['isSelected'] != '') {
                                                    $c++;
                                                }
                                            }
                                        } else if ($params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 16) {
                                            if ($params['questions'][$i]['subQuestions'][$j]['options'] != $params['questions'][$i]['subQuestions'][$j]['given_answer']) {
                                                $c++;
                                            }
                                        } else {
                                            foreach ($params['questions'][$i]['subQuestions'][$j]['given_answer'] as $key => $value) {
                                                if ($value['isSelected'] != '') {
                                                    $c++;
                                                }
                                            }
                                        }
                                        if ($c == 0) {
                                            $data['answer_status'] = 4;
                                        } else {
                                            $data['answer_status'] = 5;
                                        }
                                        if ($data['student_answer'] == '') {
                                            $data['answer_status'] = 4;
                                        } elseif (
                                            $data['student_answer'] != ''
                                            || (isset($params['questions'][$i]['subQuestions'][$j]['student_answer_image'])
                                                && $params['questions'][$i]['subQuestions'][$j]['student_answer_image'] != '')
                                        ) {
                                            $data['answer_status'] = 5;
                                        }
                                        $data['created_by'] = $params['user_id'];
                                        $data['created_date'] = date('Y-m-d H:i:s');
                                        $this->common_model->insert('student_answers', $data);
                                        unset($data);
                                    }
                                } else {
                                    if (
                                        $params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 40 ||
                                        $params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 41
                                    ) {
                                        $getGraphQuestions = $this->content_model->getGraphQuestions($params['student_id'], $params['questions'][$i]['subQuestions'][$j]['question_id'], $params['class_id']);
                                        if (!empty($getGraphQuestions)) {
                                            $data = $getGraphQuestions;
                                            $condition = array('answer_id' => $getGraphQuestions['answer_id'], 'student_id' => $getGraphQuestions['student_id'], 'class_id' => $getGraphQuestions['class_id']);
                                            $this->common_model->delete('graph_answers', $condition);
                                            $condition = array("answer_id" => $params['questions'][$i]['subQuestions'][$j]['question_id'], 'student_content_id' => $params['student_content_id']);
                                            $this->common_model->update('student_answers', $data, $condition);
                                            unset($data);
                                        }
                                    } else {
                                        $data['student_answer_image'] = isset($params['questions'][$i]['subQuestions'][$j]['student_answer_image']) ? $params['questions'][$i]['subQuestions'][$j]['student_answer_image'] : '';
                                        $data['rough_image_thumb_url'] = isset($params['questions'][$i]['subQuestions'][$j]['rough_image_thumb_url']) ? $params['questions'][$i]['subQuestions'][$j]['rough_image_thumb_url'] : '';
                                        $data['rough_image_url'] = isset($params['questions'][$i]['subQuestions'][$j]['rough_image_url']) ? $params['questions'][$i]['subQuestions'][$j]['rough_image_url'] : '';
                                        $data['student_answer'] = json_encode($params['questions'][$i]['subQuestions'][$j]['given_answer']);
                                        $data['options'] = json_encode($params['questions'][$i]['subQuestions'][$j]['options']);
                                        $data['jiixdata'] = isset($params['questions'][$i]['subQuestions'][$j]['jiixdata']) ? $params['questions'][$i]['subQuestions'][$j]['jiixdata'] : '';
                                        $data['roughdata'] = isset($params['questions'][$i]['subQuestions'][$j]['roughdata']) ? $params['questions'][$i]['subQuestions'][$j]['roughdata'] : '';
                                        $data['student_roughdata'] = isset($params['questions'][$i]['subQuestions'][$j]['student_roughdata']) ? $params['questions'][$i]['subQuestions'][$j]['student_roughdata'] : '';
                                        $data['time_taken'] = isset($params['questions'][$i]['subQuestions'][$j]['time_taken']) ? $params['questions'][$i]['subQuestions'][$j]['time_taken'] : '';
                                        $data['is_correct'] = isset($params['questions'][$i]['subQuestions'][$j]['is_correct']) ? $params['questions'][$i]['subQuestions'][$j]['is_correct'] : '';
                                        $data['no_of_attempt'] = isset($params['questions'][$i]['subQuestions'][$j]['no_of_attempt']) ? $params['questions'][$i]['subQuestions'][$j]['no_of_attempt'] : 1;
                                        $data['marked_review'] = isset($params['questions'][$i]['subQuestions'][$j]['markedAsReview']) ? ($params['questions'][$i]['subQuestions'][$j]['markedAsReview'] ? 1 : 0) : NULL;
                                        $data['optionsCopy'] = isset($params['questions'][$i]['subQuestions'][$j]['optionsCopy']) ? json_encode($params['questions'][$i]['subQuestions'][$j]['optionsCopy']) : '';
                                        if (isset($params['questions'][$i]['subQuestions'][$j]['earned_points'])) {
                                            $data['earned_points'] = $params['questions'][$i]['subQuestions'][$j]['earned_points'];
                                        }
                                        $condition = array("answer_id" => $params['questions'][$i]['subQuestions'][$j]['question_id'], 'student_id' => $params['student_id'], 'class_id' => $params['class_id']);
                                        $c = 0;
                                        if ($params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 10) {
                                            foreach ($params['questions'][$i]['subQuestions'][$j]['given_answer'] as $key => $value) {
                                                if ($value['isSelected'] != '') {
                                                    $c++;
                                                }
                                            }
                                        } else if ($params['questions'][$i]['subQuestions'][$j]['question_type_id'] == 16) {
                                            if ($params['questions'][$i]['subQuestions'][$j]['options'] != $params['questions'][$i]['subQuestions'][$j]['given_answer']) {
                                                $c++;
                                            }
                                        } else {
                                            foreach ($params['questions'][$i]['subQuestions'][$j]['given_answer'] as $key => $value) {
                                                if ($value['isSelected'] != '') {
                                                    $c++;
                                                }
                                            }
                                        }
                                        if (
                                            $params['questions'][$i]['subQuestions'][$j]['question_type_id'] != 5 &&
                                            $params['questions'][$i]['subQuestions'][$j]['question_type_id'] != 7
                                        ) {
                                            if ($c == 0) {
                                                $data['answer_status'] = 4;
                                            } else {
                                                $data['answer_status'] = 5;
                                            }
                                            if ($data['student_answer'] == '') {
                                                $data['answer_status'] = 4;
                                            } elseif (
                                                $data['student_answer'] != ''
                                                || (isset($params['questions'][$i]['subQuestions'][$j]['student_answer_image'])
                                                    && $params['questions'][$i]['subQuestions'][$j]['student_answer_image'] != '')
                                            ) {
                                                $data['answer_status'] = 5;
                                            }
                                        }
                                        $data['modified_by'] = $params['user_id'];
                                        $data['modified_date'] = date('Y-m-d H:i:s');
                                        $this->common_model->update('student_answers', $data, $condition);
                                        unset($data);
                                    }
                                }
                            }
                        } else {
                            $checkStudentAnswer = $this->content_model->checkStudentAnswer($params['questions'][$i]['question_id'], $params['student_content_id']);
                            $data = [];
                            if (count($checkStudentAnswer) == 0) {
                                if (
                                    $params['questions'][$i]['question_type_id'] == 40 ||
                                    $params['questions'][$i]['question_type_id'] == 41
                                ) {
                                    $getGraphQuestions = $this->content_model->getGraphQuestions($params['student_id'], $params['questions'][$i]['question_id'], $params['class_id']);
                                    if (!empty($getGraphQuestions)) {
                                        $data = $getGraphQuestions;
                                        $condition = array('answer_id' => $getGraphQuestions['answer_id'], 'student_id' => $getGraphQuestions['student_id'], 'class_id' => $getGraphQuestions['class_id']);
                                        $this->common_model->delete('graph_answers', $condition);
                                        $this->common_model->insert('student_answers', $data);
                                        unset($data);
                                    }
                                } else {
                                    $data['answer_id'] = $params['questions'][$i]['question_id'];
                                    $data['content_id'] = $params['content_id'];
                                    if (isset($params['class_id'])) {
                                        $data['class_id'] = $params['class_id'];
                                    }
                                    $data['question_no'] = $params['questions'][$i]['question_no'];
                                    $data['student_id'] = $params['student_id'];
                                    $data['student_content_id'] = $params['student_content_id'];
                                    $data['correct_answer'] = json_encode($params['questions'][$i]['answer']);
                                    $data['student_answer'] = json_encode($params['questions'][$i]['given_answer']);
                                    $data['options'] = json_encode($params['questions'][$i]['options']);
                                    $data['actual_points'] = $params['questions'][$i]['points'];
                                    $data['student_answer_image'] = isset($params['questions'][$i]['student_answer_image']) ? $params['questions'][$i]['student_answer_image'] : '';
                                    $data['rough_image_thumb_url'] = isset($params['questions'][$i]['rough_image_thumb_url']) ? $params['questions'][$i]['rough_image_thumb_url'] : '';
                                    $data['rough_image_url'] = isset($params['questions'][$i]['rough_image_url']) ? $params['questions'][$i]['rough_image_url'] : '';
                                    $data['jiixdata'] = isset($params['questions'][$i]['jiixdata']) ? $params['questions'][$i]['jiixdata'] : '';
                                    $data['roughdata'] = isset($params['questions'][$i]['roughdata']) ? $params['questions'][$i]['roughdata'] : '';
                                    $data['student_roughdata'] = isset($params['questions'][$i]['student_roughdata']) ? $params['questions'][$i]['student_roughdata'] : '';
                                    $data['time_taken'] = isset($params['questions'][$i]['time_taken']) ? $params['questions'][$i]['time_taken'] : '';
                                    $data['is_correct'] = isset($params['questions'][$i]['is_correct']) ? $params['questions'][$i]['is_correct'] : '';
                                    $data['no_of_attempt'] = isset($params['questions'][$i]['no_of_attempt']) ? $params['questions'][$i]['no_of_attempt'] : 1;
                                    $data['marked_review'] = isset($params['questions'][$i]['markedAsReview']) ? ($params['questions'][$i]['markedAsReview'] ? 1 : 0) : NULL;
                                    $data['optionsCopy'] = isset($params['questions'][$i]['optionsCopy']) ? json_encode($params['questions'][$i]['optionsCopy']) : '';
                                    if (isset($params['questions'][$i]['earned_points'])) {
                                        $data['earned_points'] = $params['questions'][$i]['earned_points'];
                                    }
                                    $c = 0;
                                    if ($params['questions'][$i]['question_type_id'] == 10) {
                                        foreach ($params['questions'][$i]['given_answer'] as $key => $value) {
                                            if ($value['isSelected'] != '') {
                                                $c++;
                                            }
                                        }
                                    } else if ($params['questions'][$i]['question_type_id'] == 16) {
                                        if ($params['questions'][$i]['options'] != $params['questions'][$i]['given_answer']) {
                                            $c++;
                                        }
                                    } else {
                                        foreach ($params['questions'][$i]['given_answer'] as $key => $value) {
                                            if ($value['isSelected'] != '') {
                                                $c++;
                                            }
                                        }
                                    }
                                    if ($c == 0) {
                                        $data['answer_status'] = 4;
                                    } else {
                                        $data['answer_status'] = 5;
                                    }
                                    if ($data['student_answer'] == '') {
                                        $data['answer_status'] = 4;
                                    } elseif (
                                        $data['student_answer'] != ''
                                        || (isset($params['questions'][$i]['student_answer_image'])
                                            && $params['questions'][$i]['student_answer_image'] != '')
                                    ) {
                                        $data['answer_status'] = 5;
                                    }
                                    $data['created_by'] = $params['user_id'];
                                    $data['created_date'] = date('Y-m-d H:i:s');
                                    $this->common_model->insert('student_answers', $data);
                                    unset($data);
                                }
                            } else {
                                if (
                                    $params['questions'][$i]['question_type_id'] == 40 ||
                                    $params['questions'][$i]['question_type_id'] == 41
                                ) {
                                    $getGraphQuestions = $this->content_model->getGraphQuestions($params['student_id'], $params['questions'][$i]['question_id'], $params['class_id']);
                                    if (!empty($getGraphQuestions)) {
                                        $data = $getGraphQuestions;
                                        $condition = array('answer_id' => $getGraphQuestions['answer_id'], 'student_id' => $getGraphQuestions['student_id'], 'class_id' => $getGraphQuestions['class_id']);
                                        $this->common_model->delete('graph_answers', $condition);
                                        $condition = array("answer_id" => $params['questions'][$i]['question_id'], 'student_id' => $params['student_id'], 'class_id' => $params['class_id']);
                                        $this->common_model->update('student_answers', $data, $condition);
                                        unset($data);
                                    }
                                } else {
                                    $data['student_answer_image'] = isset($params['questions'][$i]['student_answer_image']) ? $params['questions'][$i]['student_answer_image'] : '';
                                    $data['rough_image_thumb_url'] = isset($params['questions'][$i]['rough_image_thumb_url']) ? $params['questions'][$i]['rough_image_thumb_url'] : '';
                                    $data['rough_image_url'] = isset($params['questions'][$i]['rough_image_url']) ? $params['questions'][$i]['rough_image_url'] : '';
                                    $data['student_answer'] = json_encode($params['questions'][$i]['given_answer']);
                                    $data['options'] = json_encode($params['questions'][$i]['options']);
                                    $data['jiixdata'] = isset($params['questions'][$i]['jiixdata']) ? $params['questions'][$i]['jiixdata'] : '';
                                    $data['roughdata'] = isset($params['questions'][$i]['roughdata']) ? $params['questions'][$i]['roughdata'] : '';
                                    $data['student_roughdata'] = isset($params['questions'][$i]['student_roughdata']) ? $params['questions'][$i]['student_roughdata'] : '';
                                    $data['time_taken'] = isset($params['questions'][$i]['time_taken']) ? $params['questions'][$i]['time_taken'] : '';
                                    $data['is_correct'] = isset($params['questions'][$i]['is_correct']) ? $params['questions'][$i]['is_correct'] : '';
                                    $data['no_of_attempt'] = isset($params['questions'][$i]['no_of_attempt']) ? $params['questions'][$i]['no_of_attempt'] : 1;
                                    $data['marked_review'] = isset($params['questions'][$i]['markedAsReview']) ? ($params['questions'][$i]['markedAsReview'] ? 1 : 0) : NULL;
                                    $data['optionsCopy'] = isset($params['questions'][$i]['optionsCopy']) ? json_encode($params['questions'][$i]['optionsCopy']) : '';
                                    if (isset($params['questions'][$i]['earned_points'])) {
                                        $data['earned_points'] = $params['questions'][$i]['earned_points'];
                                    }
                                    $condition = array("answer_id" => $params['questions'][$i]['question_id'], 'student_content_id' => $params['student_content_id']);
                                    $c = 0;
                                    if ($params['questions'][$i]['question_type_id'] == 10) {
                                        foreach ($params['questions'][$i]['given_answer'] as $key => $value) {
                                            if ($value['isSelected'] != '') {
                                                $c++;
                                            }
                                        }
                                    } else if ($params['questions'][$i]['question_type_id'] == 16) {
                                        if ($params['questions'][$i]['options'] != $params['questions'][$i]['given_answer']) {
                                            $c++;
                                        }
                                    } else {
                                        foreach ($params['questions'][$i]['given_answer'] as $key => $value) {
                                            if ($value['isSelected'] != '') {
                                                $c++;
                                            }
                                        }
                                    }
                                    if (
                                        $params['questions'][$i]['question_type_id'] != 5 &&
                                        $params['questions'][$i]['question_type_id'] != 7
                                    ) {
                                        if ($c == 0) {
                                            $data['answer_status'] = 4;
                                        } else {
                                            $data['answer_status'] = 5;
                                        }
                                        if ($data['student_answer'] == '') {
                                            $data['answer_status'] = 4;
                                        } elseif (
                                            $data['student_answer'] != ''
                                            || (isset($params['questions'][$i]['student_answer_image'])
                                                && $params['questions'][$i]['student_answer_image'] != '')
                                        ) {
                                            $data['answer_status'] = 5;
                                        }
                                    }
                                    $data['modified_by'] = $params['user_id'];
                                    $data['modified_date'] = date('Y-m-d H:i:s');
                                    $this->common_model->update('student_answers', $data, $condition);
                                    unset($data);
                                }
                            }
                        }
                    }
                    $totalScoreUpdate = $this->content_model->totalScoreUpdate($params);
                    $totalContentScore = $this->content_model->totalContentPoint($params);
                    if ($totalScoreUpdate && $totalContentScore) {
                        $update = array(
                            'earned_points' => $totalScoreUpdate[0]['earned_points'],
                            'points' => $totalContentScore[0]['points']
                        );
                        $updateCondition = array('id' => $params['student_content_id']);
                        $this->common_model->update('student_content', $update, $updateCondition);
                    }
                    if ($params['status'] == 4) {
                        $this->updateScoreRelease($params);
                    }
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = $params['status'] == 4 ? "Answers Submitted Successfully" : "Answers Saved Successfully";
                }
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr['ErrorObject'] = "Unable to Submit Answers";
            }
        }
        $this->common_model->createLog($params, 'v1/content/addStudentAnswer', $this->jsonarr, 'addStudentAnswer');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function updateScoreRelease($params)
    {
        $checkAutoReview = $this->content_model->getAutoReview($params);
        if (($params['content_format'] == 3 && $params['all_autograde'] == 1 && $checkAutoReview[0]['auto_review'] != 0) || ($params['content_format'] != 3 && $checkAutoReview[0]['auto_review'] == 1) || $checkAutoReview[0]['auto_review'] == 3) {
            if ($checkAutoReview[0]['auto_review'] == 3) {
                if ($params['content_format'] == 3) {
                    $totalContentScore = $this->content_model->totalContentPoint($params);
                } else {
                    $totalContentScore = $this->content_model->totalContentScore($params);
                }
                if ($totalContentScore) {
                    $update = array(
                        'earned_points' => $totalContentScore[0]['points'],
                        'points' => $totalContentScore[0]['points']
                    );
                    $updateCondition = array('id' => $params['student_content_id']);
                    $this->common_model->update('student_content', $update, $updateCondition);
                }
            }
            $data = array(
                'release_score' => 1,
                'status' => 3,
                'score_release_date' => date('Y-m-d H:i:s')
            );
            $condition = array('id' => $params['student_content_id']);
            $condition1 = array('student_content_id' => $params['student_content_id']);
            $update = $this->common_model->update('student_content', $data, $condition);
            $studentWork = array('student_content_status' => 3, 'score_release_date' => date('Y-m-d H:i:s'));
            $this->common_model->update('student_work', $studentWork, $condition1);
            if ($update) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function questiontypes_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/questiontypes', 'only request', 'questiontypes');
            $questionTypes = $this->content_model->questiontypes();
            if (isset($params['content_format']) && $params['content_format'] == 3) {
                foreach ($questionTypes as $key => $value) {
                    if ($value['question_type_id'] == 54) {
                        unset($questionTypes[$key]);
                    }
                }
            }
            $questionTypes = array_values($questionTypes);
            $question = [];
            for ($i = 0; $i < count($questionTypes); $i++) {
                $dataAvailable = false;
                for ($j = 0; $j < count($question); $j++) {
                    for ($l = 0; $l < count($question[$j]['types']); $l++) {
                        if ($question[$j]['types'][$l]['resource_type'] == $questionTypes[$i]['resource_type']) {
                            $dataAvailable = true;
                        }
                    }
                }
                if (!$dataAvailable) {
                    $c = count($question);
                    $question[$c]['resource_type'] = $questionTypes[$i]['resource_type'];
                    $question[$c]['types'][0]['resource_type'] = $questionTypes[$i]['resource_type'];
                    $question[$c]['types'][0]['question_type_id'] = $questionTypes[$i]['question_type_id'];
                    $question[$c]['types'][0]['resource_type_id'] = $questionTypes[$i]['resource_type_id'];
                    $question[$c]['types'][0]['question_type'] = $questionTypes[$i]['question_type'];
                    $question[$c]['types'][0]['image_path'] = $questionTypes[$i]['image_path'];
                } else {
                    //$tempSubQuestionsArray = [];
                    for ($k = 0; $k < count($question); $k++) {
                        $cnt = 0;
                        $tempSubQuestionsArray = [];
                        for ($m = 0; $m < count($question[$k]['types']); $m++) {
                            if ($question[$k]['types'][$m]['resource_type'] == $questionTypes[$i]['resource_type']) {
                                $tempSubQuestionsArray[$cnt]['resource_type'] = $questionTypes[$i]['resource_type'];
                                $tempSubQuestionsArray[$cnt]['question_type_id'] = $questionTypes[$i]['question_type_id'];
                                $tempSubQuestionsArray[$cnt]['resource_type_id'] = $questionTypes[$i]['resource_type_id'];
                                $tempSubQuestionsArray[$cnt]['question_type'] = $questionTypes[$i]['question_type'];
                                $tempSubQuestionsArray[$cnt]['image_path'] = $questionTypes[$i]['image_path'];
                                $cnt++;
                            }
                        }
                        if (count($tempSubQuestionsArray) > 0) {
                            array_push($question[$k]['types'], $tempSubQuestionsArray[0]);
                        }
                    }
                }
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $question;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/questiontypes', $this->jsonarr, 'questiontypes');
        return $this->printjson($this->jsonarr);
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

    public function contentDetail_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/contentDetail', 'only request', 'contentDetail');
            $this->benchmark->mark('code_start');
            if (isset($params['student_id'])) {
                $redoStatus = $this->content_model->getRedoStatus($params);
            }
            $data = true;
            //            if (isset($params['student_id'])) {
            //                $getStudentClass = $this->content_model->checkStatus($params);
            //                if ($getStudentClass) {
            //                    $data = false;
            //                } else {
            //                    $data = true;
            //                }
            //            } else {
            //                $data = true;
            //            }
            if ($data) {
                $contentList = $this->content_model->contentIdList($params);
                if ($params['platform'] != "ios") {
                    if ($params['role_id'] == 5) {
                        $checkAutoGrade = $this->content_model->getQuestionDetails($params['content_id']);
                        if (count($checkAutoGrade) > 0) {
                            $i = 0;
                            foreach ($checkAutoGrade as $key => $value) {
                                if ($value['audo_grade'] == 1) {
                                    $i++;
                                }
                            }
                            $contentList['all_autograde'] = count($checkAutoGrade) == $i ? 1 : 0;
                        } else {
                            $contentList['all_autograde'] = 0;
                        }
                    }
                    $getBatchId = $this->content_model->batchDetail($contentList['content_id'], 'Active');
                    $contentList['batch_id'] = $getBatchId[0]['batch_id'] != '' ? explode(',', $getBatchId[0]['batch_id']) : [];
                }
                if ($this->config->item('filePathBase64') == true) {
                    $base64FilePath = base64_encode(base64_encode(base64_encode(base64_encode($contentList['file_path']))));
                }
                //            if ($contentList['file_path'] != '') {
                if ($this->config->item('filePathBase64') == true && $params['platform'] != "ios") {
                    if ($contentList['file_path'] != '' && $contentList['file_path'] != '[]') {
                        $contentList['file_path'] = $base64FilePath;
                    } else {
                        $contentList['file_path'] = [];
                    }
                } else {
                    if ($contentList['file_path'] != '' && $contentList['file_path'] != '[]') {
                        $contentList['file_path'] = json_decode($contentList['file_path'], true);
                        if (!isset($contentList['file_path'][0]['original_image_url'])) {
                            $contentList['file_path'][0]['original_image_url'] = '';
                            $contentList['file_path'][0]['image'] = '';
                            $contentList['file_path'][0]['size'] = 0;
                            $contentList['file_path'][0]['type'] = '';
                        }
                    } else {
                        $contentList['file_path'] = [];
                    }
                }

                if ($params['platform'] != "ios" && isset($contentList['answerkey_path'])) {
                    if ($this->config->item('filePathBase64') == true) {
                        $base64AnswerKey = base64_encode(base64_encode(base64_encode(base64_encode($contentList['answerkey_path']))));
                    }


                    if ($this->config->item('filePathBase64') == true) {
                        if ($contentList['answerkey_path'] != '' && $contentList['answerkey_path'] != '[]') {
                            $contentList['answerkey_path'] = $base64AnswerKey;
                        } else {
                            $contentList['answerkey_path'] = [];
                        }
                    } else {
                        if ($contentList['answerkey_path'] != '' && $contentList['answerkey_path'] != '[]') {
                            $contentList['answerkey_path'] = json_decode($contentList['answerkey_path']);
                        } else {
                            $contentList['answerkey_path'] = [];
                        }
                    }
                }
                // $contentList['file_path'] = $this->config->item('filePathBase64') == true ? $base64FilePath : $contentList['file_path'] != '' ? json_decode($contentList['file_path']) : [];
                //            } else if ($contentList['file_path'] == '') {
                //                $contentList['file_path'] = ;
                //            }
                //            if ($params['platform'] != 'ios') {
                if (isset($params['student_id'])) {
                    if ($this->config->item('filePathBase64') == true) {
                        $base64UploadAnswer = base64_encode(base64_encode(base64_encode(base64_encode($contentList['upload_answer']))));
                    }

                    if ($this->config->item('filePathBase64') == true && $params['platform'] != "ios") {
                        if ($contentList['upload_answer'] != '' && $contentList['upload_answer'] != '[]') {
                            $contentList['upload_answer'] = $base64UploadAnswer;
                        } else {
                            $contentList['upload_answer'] = [];
                        }
                    } else {
                        if ($contentList['upload_answer'] != '' && $contentList['upload_answer'] != '[]') {
                            $contentList['upload_answer'] = json_decode($contentList['upload_answer']);
                        } else {
                            $contentList['upload_answer'] = [];
                        }
                    }
                    $getContentPercentage = $this->student_model->contentPercentage($params);
                    $contentList['percentage'] = count($getContentPercentage) > 0 && !is_null($getContentPercentage[0]['percentage']) ? $getContentPercentage[0]['percentage'] : 0;
                }
                //            }
                if ($params['platform'] == 'web') {
                    if ($contentList['teacher_version'] != '') {
                        $contentList['teacher_version'] = json_decode($contentList['teacher_version']);
                    } else {
                        $contentList['teacher_version'] = [];
                    }
                }
                if ($contentList['links'] != '') {
                    $contentList['links'] = json_decode($contentList['links']);
                } else if ($contentList['links'] == '') {
                    $contentList['links'] = [];
                }
                if ($contentList['annotation'] != '' && $contentList['annotation'] != '[]') {
                    $annotation = $this->common_model->annotation($contentList['annotation']);
                    $contentList['annotation'] = json_decode($annotation);
                } else {
                    $contentList['annotation'] = [];
                }
                if ($contentList['questionAnnotation'] != '' && $contentList['questionAnnotation'] != '[]') {
                    $questionAnnotation = $this->common_model->annotation($contentList['questionAnnotation']);
                    $contentList['questionAnnotation'] = json_decode($questionAnnotation);
                } else {
                    $contentList['questionAnnotation'] = [];
                }
                if ((isset($params['content_format']) && $params['content_format'] == 3)) {
                    $allAnswerList = $this->content_model->textList($params);
                    if (count($allAnswerList) > 0) {
                        $questionList = [];
                        for ($i = 0; $i < count($allAnswerList); $i++) {
                            $dataAvailable = false;
                            for ($j = 0; $j < count($questionList); $j++) {
                                for ($l = 0; $l < count($questionList[$j]['questions']); $l++) {
                                    if ($questionList[$j]['questions'][$l]['content_id'] == $allAnswerList[$i]['content_id']) {
                                        $dataAvailable = true;
                                    }
                                }
                            }
                            if (!$dataAvailable) {
                                $c = count($questionList);
                                if ($allAnswerList[$i]['question_type_id'] != 24) {
                                    $questionList[$c]['questions'][0]['question_id'] = $allAnswerList[$i]['question_id'];
                                    $questionList[$c]['questions'][0]['content_id'] = $allAnswerList[$i]['content_id'];
                                    $questionList[$c]['questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                    $questionList[$c]['questions'][0]['editor_context'] = $allAnswerList[$i]['editor_context'];
                                    $questionList[$c]['questions'][0]['editor_type'] = $allAnswerList[$i]['editor_type'];
                                    if ($params['platform'] == 'web' && ($allAnswerList[$i]['question_type_id'] == 40 ||
                                        $allAnswerList[$i]['question_type_id'] == 41)) {
                                        $questionList[$c]['questions'][0]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                    }
                                    $questionList[$c]['questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $questionList[$c]['questions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                    $questionList[$c]['questions'][0]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                    $questionList[$c]['questions'][0]['question'] = $allAnswerList[$i]['question'];
                                    $questionList[$c]['questions'][0]['options'] = json_decode($allAnswerList[$i]['options']);
                                    $questionList[$c]['questions'][0]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                    $questionList[$c]['questions'][0]['level'] = $allAnswerList[$i]['level'];
                                    $questionList[$c]['questions'][0]['passage_id'] = $allAnswerList[$i]['passage_id'];
                                    $questionList[$c]['questions'][0]['passage_title'] = $allAnswerList[$i]['passage_title'];
                                    $questionList[$c]['questions'][0]['passage'] = isset($allAnswerList[$i]['passage']) && $allAnswerList[$i]['passage'] != '' ? json_decode($allAnswerList[$i]['passage']) : '';
                                    if (isset($allAnswerList[$i]['student_content_status']) && $allAnswerList[$i]['student_content_status'] > 1) {
                                        if ($allAnswerList[$i]['student_answer'] != "") {
                                            $questionList[$c]['questions'][0]['given_answer'] = json_decode($allAnswerList[$i]['student_answer']);
                                        } else {
                                            $questionList[$c]['questions'][0]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                        }
                                        if ($allAnswerList[$i]['student_options'] != "") {
                                            $questionList[$c]['questions'][0]['student_options'] = json_decode($allAnswerList[$i]['student_options']);
                                        } else {
                                            $questionList[$c]['questions'][0]['student_options'] = [];
                                        }
                                        if ($params['platform'] == 'web' && ($allAnswerList[$i]['question_type_id'] == 40 ||
                                            $allAnswerList[$i]['question_type_id'] == 41)) {
                                            $questionList[$c]['questions'][0]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                            if ($allAnswerList[$i]['student_answer'] == "") {
                                                $questionList[$c]['questions'][0]['given_answer'] = array((object)array("isSelected" => ''));
                                            }
                                        }
                                    } else {
                                        if (count($questionList[$c]['questions'][0]['options']) > 0) {
                                            $questionList[$c]['questions'][0]['given_answer'] = json_decode($allAnswerList[$i]['options']);;
                                            $questionList[$c]['questions'][0]['student_options'] = json_decode($allAnswerList[$i]['options']);
                                        } else {
                                            $questionList[$c]['questions'][0]['given_answer'] = array((object)array("isSelected" => ''));
                                            $questionList[$c]['questions'][0]['student_options'] = [];
                                        }
                                    }
                                    if (isset($params['student_id'])) {
                                        $questionList[$c]['questions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                        $questionList[$c]['questions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                        $questionList[$c]['questions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                        $questionList[$c]['questions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                        $questionList[$c]['questions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                        $questionList[$c]['questions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                        if ($params['platform'] == 'web') {
                                            $questionList[$c]['questions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                            $questionList[$c]['questions'][0]['is_correct'] = $allAnswerList[$i]['is_correct'];
                                            $questionList[$c]['questions'][0]['no_of_attempt'] = $allAnswerList[$i]['no_of_attempt'];
                                            $questionList[$c]['questions'][0]['time_taken'] = $allAnswerList[$i]['time_taken'];
                                            $questionList[$c]['questions'][0]['markedAsReview'] = $allAnswerList[$i]['markedAsReview'];
                                        }
                                        if ($params['platform'] == 'ios') {
                                            $questionList[$c]['questions'][0]['optionsCopy'] = $allAnswerList[$i]['optionsCopy'] != '' ? json_decode($allAnswerList[$i]['optionsCopy']) : [];
                                        }
                                    }
                                    if ($allAnswerList[$i]['question_type_id'] == 5 || $allAnswerList[$i]['question_type_id'] == 7) {
                                        $questionList[$c]['questions'][0]['heading_option'] = json_decode($allAnswerList[$i]['heading_option']);
                                    } else {
                                        $questionList[$c]['questions'][0]['heading_option'] = [];
                                    }
                                    $questionList[$c]['questions'][0]['multiple_response'] = $allAnswerList[$i]['multiple_response'];
                                    $questionList[$c]['questions'][0]['audo_grade'] = $allAnswerList[$i]['audo_grade'];
                                    $questionList[$c]['questions'][0]['points'] = $allAnswerList[$i]['points'];
                                    $questionList[$c]['questions'][0]['exact_match'] = $allAnswerList[$i]['exact_match'];
                                    if ($allAnswerList[$i]['hint'] != []) {
                                        $questionList[$c]['questions'][0]['hint'] = json_decode($allAnswerList[$i]['hint']);
                                    } else {
                                        $questionList[$c]['questions'][0]['hint'] = [];
                                    }
                                    $questionList[$c]['questions'][0]['explanation'] = $allAnswerList[$i]['explanation'];
                                    $questionList[$c]['questions'][0]['word_limit'] = $allAnswerList[$i]['word_limit'];
                                    $questionList[$c]['questions'][0]['scoring_instruction'] = $allAnswerList[$i]['scoring_instruction'];
                                    $questionList[$c]['questions'][0]['source'] = $allAnswerList[$i]['source'];
                                    $questionList[$c]['questions'][0]['target'] = $allAnswerList[$i]['target'];
                                } else {
                                    $questionList[$c]['questions'][0]['content_id'] = $allAnswerList[$i]['content_id'];
                                    $questionList[$c]['questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                    $questionList[$c]['questions'][0]['editor_context'] = $allAnswerList[$i]['editor_context'];
                                    $questionList[$c]['questions'][0]['editor_type'] = $allAnswerList[$i]['editor_type'];
                                    $questionList[$c]['questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['question_id'] = $allAnswerList[$i]['question_id'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['question_type_id'] = $allAnswerList[$i]['sub_question_type_id'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['question'] = $allAnswerList[$i]['question'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['options'] = json_decode($allAnswerList[$i]['options']);
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['level'] = $allAnswerList[$i]['level'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['passage_id'] = $allAnswerList[$i]['passage_id'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['passage_title'] = $allAnswerList[$i]['passage_title'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['passage'] = isset($allAnswerList[$i]['passage']) && $allAnswerList[$i]['passage'] != '' ? json_decode($allAnswerList[$i]['passage']) : '';

                                    if (isset($allAnswerList[$i]['student_content_status']) && $allAnswerList[$i]['student_content_status'] > 1) {
                                        if ($allAnswerList[$i]['student_answer'] != "") {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['given_answer'] = json_decode($allAnswerList[$i]['student_answer']);
                                        } else {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                        }
                                        if ($allAnswerList[$i]['student_options'] != "") {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['student_options'] = json_decode($allAnswerList[$i]['student_options']);
                                        } else {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['student_options'] = [];
                                        }
                                    } else {
                                        if (count($questionList[$c]['questions'][0]['subQuestions'][0]['options']) > 0) {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['given_answer'] = json_decode($allAnswerList[$i]['options']);;
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['student_options'] = json_decode($allAnswerList[$i]['options']);
                                        } else {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['given_answer'] = array((object)array("isSelected" => ''));
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['student_options'] = [];
                                        }
                                    }
                                    if (isset($params['student_id'])) {
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                        if ($params['platform'] == 'web') {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['is_correct'] = $allAnswerList[$i]['is_correct'];
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['no_of_attempt'] = $allAnswerList[$i]['no_of_attempt'];
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['time_taken'] = $allAnswerList[$i]['time_taken'];
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['markedAsReview'] = $allAnswerList[$i]['markedAsReview'];
                                        }
                                        if ($params['platform'] == 'ios') {
                                            $questionList[$c]['questions'][0]['subQuestions'][0]['optionsCopy'] = $allAnswerList[$i]['optionsCopy'] != '' ? json_decode($allAnswerList[$i]['optionsCopy']) : [];
                                        }
                                    }
                                    if ($allAnswerList[$i]['sub_question_type_id'] == 5 || $allAnswerList[$i]['sub_question_type_id'] == 7) {
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['heading_option'] = json_decode($allAnswerList[$i]['heading_option']);
                                    } else {
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['heading_option'] = [];
                                    }
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['multiple_response'] = $allAnswerList[$i]['multiple_response'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['audo_grade'] = $allAnswerList[$i]['audo_grade'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['points'] = $allAnswerList[$i]['points'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['exact_match'] = $allAnswerList[$i]['exact_match'];
                                    if ($allAnswerList[$i]['hint'] != []) {
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['hint'] = json_decode($allAnswerList[$i]['hint']);
                                    } else {
                                        $questionList[$c]['questions'][0]['subQuestions'][0]['hint'] = [];
                                    }
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['explanation'] = $allAnswerList[$i]['explanation'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['word_limit'] = $allAnswerList[$i]['word_limit'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['scoring_instruction'] = $allAnswerList[$i]['scoring_instruction'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['source'] = $allAnswerList[$i]['source'];
                                    $questionList[$c]['questions'][0]['subQuestions'][0]['target'] = $allAnswerList[$i]['target'];
                                }
                            } else {
                                $tempSubQuestionsArray = [];
                                $tempSubQuestionsArray1 = [];
                                for ($k = 0; $k < count($questionList); $k++) {
                                    $subQ = 0;
                                    $count = 0;
                                    for ($m = 0; $m < count($questionList[$k]['questions']); $m++) {
                                        if ($questionList[$k]['questions'][$m]['content_id'] == $allAnswerList[$i]['content_id']) {
                                            if ($allAnswerList[$i]['question_type_id'] != 24) {
                                                $tempSubQuestionsArray[$subQ]['question_id'] = $allAnswerList[$i]['question_id'];
                                                $tempSubQuestionsArray[$subQ]['content_id'] = $allAnswerList[$i]['content_id'];
                                                $tempSubQuestionsArray[$subQ]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                                $tempSubQuestionsArray[$subQ]['editor_context'] = $allAnswerList[$i]['editor_context'];
                                                $tempSubQuestionsArray[$subQ]['editor_type'] = $allAnswerList[$i]['editor_type'];
                                                $tempSubQuestionsArray[$subQ]['question_no'] = $allAnswerList[$i]['question_no'];
                                                $tempSubQuestionsArray[$subQ]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                                $tempSubQuestionsArray[$subQ]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                                $tempSubQuestionsArray[$subQ]['question'] = $allAnswerList[$i]['question'];
                                                if (
                                                    $params['platform'] == 'web' && $allAnswerList[$i]['question_type_id'] == 40 ||
                                                    $allAnswerList[$i]['question_type_id'] == 41
                                                ) {
                                                    $tempSubQuestionsArray[$subQ]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                                }
                                                $tempSubQuestionsArray[$subQ]['options'] = json_decode($allAnswerList[$i]['options']);
                                                $tempSubQuestionsArray[$subQ]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                                $tempSubQuestionsArray[$subQ]['level'] = $allAnswerList[$i]['level'];
                                                $tempSubQuestionsArray[$subQ]['passage_id'] = $allAnswerList[$i]['passage_id'];
                                                $tempSubQuestionsArray[$subQ]['passage_title'] = $allAnswerList[$i]['passage_title'];
                                                $tempSubQuestionsArray[$subQ]['passage'] = isset($allAnswerList[$i]['passage']) && $allAnswerList[$i]['passage'] != '' ? json_decode($allAnswerList[$i]['passage']) : '';
                                                if (isset($allAnswerList[$i]['student_content_status']) && $allAnswerList[$i]['student_content_status'] > 1) {
                                                    if ($allAnswerList[$i]['student_answer'] != "") {
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = json_decode($allAnswerList[$i]['student_answer']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                                    }
                                                    if ($allAnswerList[$i]['student_options'] != "") {
                                                        $tempSubQuestionsArray[$subQ]['student_options'] = json_decode($allAnswerList[$i]['student_options']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['student_options'] = [];
                                                    }
                                                    if (
                                                        $params['platform'] == 'web' && $allAnswerList[$i]['question_type_id'] == 40 ||
                                                        $allAnswerList[$i]['question_type_id'] == 41
                                                    ) {
                                                        $tempSubQuestionsArray[$subQ]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                                        if ($allAnswerList[$i]['student_answer'] == "") {
                                                            $tempSubQuestionsArray[$subQ]['given_answer'] = array((object)array("isSelected" => ''));
                                                        }
                                                    }
                                                } else {
                                                    if (count($tempSubQuestionsArray[$subQ]['options']) > 0) {
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                                        $tempSubQuestionsArray[$subQ]['student_options'] = json_decode($allAnswerList[$i]['options']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = array((object)array("isSelected" => ''));
                                                        $tempSubQuestionsArray[$subQ]['student_options'] = [];
                                                    }
                                                }
                                                if (isset($params['student_id'])) {
                                                    $tempSubQuestionsArray[$subQ]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                                    $tempSubQuestionsArray[$subQ]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                                    $tempSubQuestionsArray[$subQ]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                                    $tempSubQuestionsArray[$subQ]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                                    $tempSubQuestionsArray[$subQ]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                                    $tempSubQuestionsArray[$subQ]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                                    if ($params['platform'] == 'web') {
                                                        $tempSubQuestionsArray[$subQ]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                                        $tempSubQuestionsArray[$subQ]['is_correct'] = $allAnswerList[$i]['is_correct'];
                                                        $tempSubQuestionsArray[$subQ]['no_of_attempt'] = $allAnswerList[$i]['no_of_attempt'];
                                                        $tempSubQuestionsArray[$subQ]['time_taken'] = $allAnswerList[$i]['time_taken'];
                                                        $tempSubQuestionsArray[$subQ]['markedAsReview'] = $allAnswerList[$i]['markedAsReview'];
                                                    }
                                                    if ($params['platform'] == 'ios') {
                                                        $tempSubQuestionsArray[$subQ]['optionsCopy'] = $allAnswerList[$i]['optionsCopy'] != '' ? json_decode($allAnswerList[$i]['optionsCopy']) : [];
                                                    }
                                                }
                                                if ($allAnswerList[$i]['question_type_id'] == 5 || $allAnswerList[$i]['question_type_id'] == 7) {
                                                    $tempSubQuestionsArray[$subQ]['heading_option'] = json_decode($allAnswerList[$i]['heading_option']);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['heading_option'] = [];
                                                }
                                                $tempSubQuestionsArray[$subQ]['multiple_response'] = $allAnswerList[$i]['multiple_response'];
                                                $tempSubQuestionsArray[$subQ]['audo_grade'] = $allAnswerList[$i]['audo_grade'];
                                                $tempSubQuestionsArray[$subQ]['points'] = $allAnswerList[$i]['points'];
                                                $tempSubQuestionsArray[$subQ]['exact_match'] = $allAnswerList[$i]['exact_match'];
                                                if ($allAnswerList[$i]['hint'] != []) {
                                                    $tempSubQuestionsArray[$subQ]['hint'] = json_decode($allAnswerList[$i]['hint']);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['hint'] = [];
                                                }
                                                $tempSubQuestionsArray[$subQ]['explanation'] = $allAnswerList[$i]['explanation'];
                                                $tempSubQuestionsArray[$subQ]['word_limit'] = $allAnswerList[$i]['word_limit'];
                                                $tempSubQuestionsArray[$subQ]['scoring_instruction'] = $allAnswerList[$i]['scoring_instruction'];
                                                $tempSubQuestionsArray[$subQ]['source'] = $allAnswerList[$i]['source'];
                                                $tempSubQuestionsArray[$subQ]['target'] = $allAnswerList[$i]['target'];
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['content_id'] = $allAnswerList[$i]['content_id'];
                                                $tempSubQuestionsArray[$subQ]['editor_context'] = $allAnswerList[$i]['editor_context'];
                                                $tempSubQuestionsArray[$subQ]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                                $tempSubQuestionsArray[$subQ]['question_no'] = $allAnswerList[$i]['question_no'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['question_id'] = $allAnswerList[$i]['question_id'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['question_type_id'] = $allAnswerList[$i]['sub_question_type_id'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['question_no'] = $allAnswerList[$i]['question_no'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['editor_type'] = $allAnswerList[$i]['editor_type'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['question'] = $allAnswerList[$i]['question'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['options'] = json_decode($allAnswerList[$i]['options']);
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['level'] = $allAnswerList[$i]['level'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['passage_id'] = $allAnswerList[$i]['passage_id'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['passage_title'] = $allAnswerList[$i]['passage_title'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['passage'] = isset($allAnswerList[$i]['passage']) && $allAnswerList[$i]['passage'] != '' ? json_decode($allAnswerList[$i]['passage']) : '';
                                                if (isset($allAnswerList[$i]['student_content_status']) && $allAnswerList[$i]['student_content_status'] > 1) {
                                                    if ($allAnswerList[$i]['student_answer'] != "") {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['given_answer'] = json_decode($allAnswerList[$i]['student_answer']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                                    }
                                                    if ($allAnswerList[$i]['student_options'] != "") {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_options'] = json_decode($allAnswerList[$i]['student_options']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_options'] = [];
                                                    }
                                                } else {
                                                    if (count($tempSubQuestionsArray[$subQ]['subQuestions'][$count]['options']) > 0) {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['given_answer'] = json_decode($allAnswerList[$i]['options']);
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_options'] = json_decode($allAnswerList[$i]['options']);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['given_answer'] = array((object)array("isSelected" => ''));
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_options'] = [];
                                                    }
                                                }
                                                if (isset($params['student_id'])) {
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                                    if ($params['platform'] == 'web') {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['is_correct'] = $allAnswerList[$i]['is_correct'];
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['no_of_attempt'] = $allAnswerList[$i]['no_of_attempt'];
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['time_taken'] = $allAnswerList[$i]['time_taken'];
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['markedAsReview'] = $allAnswerList[$i]['markedAsReview'];
                                                    }
                                                    if ($params['platform'] == 'ios') {
                                                        $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['optionsCopy'] = $allAnswerList[$i]['optionsCopy'] != '' ? json_decode($allAnswerList[$i]['optionsCopy']) : [];
                                                    }
                                                }
                                                if ($allAnswerList[$i]['sub_question_type_id'] == 5 || $allAnswerList[$i]['sub_question_type_id'] == 7) {
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['heading_option'] = json_decode($allAnswerList[$i]['heading_option']);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['heading_option'] = [];
                                                }
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['multiple_response'] = $allAnswerList[$i]['multiple_response'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['audo_grade'] = $allAnswerList[$i]['audo_grade'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['points'] = $allAnswerList[$i]['points'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['exact_match'] = $allAnswerList[$i]['exact_match'];
                                                if ($allAnswerList[$i]['hint'] != []) {
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['hint'] = json_decode($allAnswerList[$i]['hint']);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['hint'] = [];
                                                }
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['explanation'] = $allAnswerList[$i]['explanation'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['word_limit'] = $allAnswerList[$i]['word_limit'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['scoring_instruction'] = $allAnswerList[$i]['scoring_instruction'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['source'] = $allAnswerList[$i]['source'];
                                                $tempSubQuestionsArray[$subQ]['subQuestions'][$count]['target'] = $allAnswerList[$i]['target'];
                                                $count++;
                                            }
                                            array_push($tempSubQuestionsArray1, $tempSubQuestionsArray[0]);
                                        }
                                    }
                                    if (count($tempSubQuestionsArray) > 0) {
                                        array_push($questionList[$k]['questions'], $tempSubQuestionsArray1[0]);
                                    }
                                }
                            }
                        }
                        $temp = [];
                        //removing passage type questions
                        foreach ($questionList[0]['questions'] as $key => $value) {
                            if ($value['question_type_id'] == 24) {
                                array_push($temp, $value);
                                unset($questionList[0]['questions'][$key]);
                            }
                        }
                        $tempList = [];
                        //re-arranging passage questions
                        foreach ($temp as $key => $value) {
                            $data = false;
                            foreach ($tempList as $key1 => $value1) {
                                if ($value1['question_no'] == $value['question_no']) {
                                    $data = true;
                                }
                            }
                            if (!$data) {
                                $tempList[$key] = $value;
                            } else {
                                foreach ($tempList as $key1 => $value1) {
                                    if ($value1['question_no'] == $value['question_no']) {
                                        array_push($tempList[$key1]['subQuestions'], $value['subQuestions'][0]);
                                    }
                                }
                            }
                        }
                        foreach ($tempList as $key => $value) {
                            array_push($questionList[0]['questions'], $tempList[$key]);
                        }
                        usort($questionList[0]['questions'], function ($a, $b) {
                            return $a['question_no'] <=> $b['question_no'];
                        });
                    }
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = $contentList;
                    if (count($allAnswerList) > 0) {
                        $this->jsonarr['ResponseObject']['questions'] = array_values($questionList[0]['questions']);
                    } else {
                        $this->jsonarr['ResponseObject']['questions'] = [];
                    }
                } else {
                    $studentSuggestions = array();
                    if (isset($params['student_id'])) {
                        $studentStatus = $this->content_model->studentStatus($params);
                        $studentSuggestions = $this->content_model->studentSuggestions($params);
                        if (isset($params['student_id']) && $params['student_id'] > 0 && $studentStatus['status'] > 1 && $studentStatus['status'] < 3) {
                            $allAnswerList = $this->content_model->studentAnswerList($params);
                        } elseif (isset($params['student_id']) && $params['student_id'] > 0 && $studentStatus['status'] > 3) {
                            $allAnswerList = $this->content_model->studentCompletedAnswerList($params);
                        } elseif (isset($params['student_id']) && $params['student_id'] > 0 && $studentStatus['status'] == 1) {
                            $allAnswerList = $this->content_model->noAnswerList($params);
                        }
                    } else {
                        $allAnswerList = $this->content_model->answerList($params);
                    }
                    foreach ($allAnswerList as $key => $value) {
                        if (count($studentSuggestions) > 0) {
                            foreach ($studentSuggestions as $key1 => $value1) {
                                if ($value['answer_id'] == $value1['answer_id']) {
                                    $allAnswerList[$key]['student_feedback'] = $value1['student_feedback'];
                                } else {
                                    if (!isset($allAnswerList[$key]['student_feedback'])) {
                                        $allAnswerList[$key]['student_feedback'] = "";
                                    }
                                }
                            }
                        } else {
                            $allAnswerList[$key]['student_feedback'] = "";
                        }
                    }
                    usort($allAnswerList, function ($a, $b) {
                        if ($a['display_order'] === $b['display_order']) {
                            return $a['answer_id'] <=> $b['answer_id'];
                        }
                        return $a['display_order'] <=> $b['display_order'];
                    });
                    $allAnswerList = $this->group_by('heading', $allAnswerList);
                    $answerList = [];
                    $subQ = 0;
                    $tempSubQuestionsArray = [];
                    for ($i = 0; $i < count($allAnswerList); $i++) {
                        $dataAvailable = false;
                        for ($j = 0; $j < count($answerList); $j++) {
                            if ($answerList[$j]['heading'] == $allAnswerList[$i]['heading']) {
                                $dataAvailable = true;
                            }
                        }
                        $c = count($answerList);
                        if (!$dataAvailable) {
                            if (count($answerList) >= 1) {
                                if ($answerList[$c - 1]['heading'] == $allAnswerList[$i]['heading']) {
                                    if ($answerList[$c - 1]['section'][0]['sub_questions'][0]['question_no'] == $allAnswerList[$i]['question_no']) {
                                        $tempSubQuestionsArray[$subQ]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                        $tempSubQuestionsArray[$subQ]['question_no'] = $allAnswerList[$i]['question_no'];
                                        $tempSubQuestionsArray[$subQ]['question'] = $allAnswerList[$i]['question'];
                                        $tempSubQuestionsArray[$subQ]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                        $tempSubQuestionsArray[$subQ]['page_no'] = $allAnswerList[$i]['page_no'];
                                        $tempSubQuestionsArray[$subQ]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                        $tempSubQuestionsArray[$subQ]['options'] = $allAnswerList[$i]['options'];
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['answer']);
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['array']);
                                            }
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['array']);
                                        }
                                        $tempSubQuestionsArray[$subQ]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                        if ($allAnswerList[$i]['question_type_id'] == 40) {
                                            if ($params['role_id'] == 5) {
                                                $tempSubQuestionsArray[$subQ]['answer'] = '';
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                            }
                                            if ($params['platform'] != 'ios') {
                                                $tempSubQuestionsArray[$subQ]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                            }
                                        } else {
                                          //  $tempSubQuestionsArray[$subQ]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                            $tempSubQuestionsArray[$subQ]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                        }
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'web') {
                                                $tempSubQuestionsArray[$subQ]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['answer'] = $allAnswerList[$i]['answer'];
                                            }
                                        }
                                        if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                if ($params['platform'] == 'web') {
                                                    $tempSubQuestionsArray[$subQ]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                                    if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                        $tempSubQuestionsArray[$subQ]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                                    }
                                                    if ($allAnswerList[$i]['student_answer'] != '') {
                                                        //   $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['given_answer'] = [];
                                                    }
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                                }
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                            }
                                            $tempSubQuestionsArray[$subQ]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                        } else {
                                            if ($params['platform'] == 'web') {
                                                $tempSubQuestionsArray[$subQ]['answer_attended'] = 0;
                                                $tempSubQuestionsArray[$subQ]['answer_status'] = 0;
                                                if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                    $tempSubQuestionsArray[$subQ]['given_answer'] = [];
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['given_answer'] = '';
                                                }
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['given_answer'] = '';
                                            }
                                            $tempSubQuestionsArray[$subQ]['answerdata'] = '';
                                        }
                                        if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                            $tempSubQuestionsArray[$subQ]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                            if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                $tempSubQuestionsArray[$subQ]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                            }
                                            $tempSubQuestionsArray[$subQ]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                            $tempSubQuestionsArray[$subQ]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                            if ($allAnswerList[$i]['question_type_id'] == 40) {
                                                $tempSubQuestionsArray[$subQ]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                            }
                                        }
                                        if ($params['platform'] != 'ios') {
                                            $tempSubQuestionsArray[$subQ]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                        }
                                        $tempSubQuestionsArray[$subQ]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                        $tempSubQuestionsArray[$subQ]['points'] = $allAnswerList[$i]['points'];
                                        $tempSubQuestionsArray[$subQ]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                        $tempSubQuestionsArray[$subQ]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                        $tempSubQuestionsArray[$subQ]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                        $tempSubQuestionsArray[$subQ]['match_case'] = $allAnswerList[$i]['match_case'];
                                        $tempSubQuestionsArray[$subQ]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                        if (isset($params['student_id'])) {
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                if ($params['platform'] == 'ios') {
                                                    //  $tempSubQuestionsArray[$subQ]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                                    if ($allAnswerList[$i]['student_answer'] != '') {
                                                        $tempSubQuestionsArray[$subQ]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                    } else {
                                                        $tempSubQuestionsArray[$subQ]['mob_options'] = [];
                                                    }
                                                }
                                            }
                                            $tempSubQuestionsArray[$subQ]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                            $tempSubQuestionsArray[$subQ]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                            $tempSubQuestionsArray[$subQ]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                            $tempSubQuestionsArray[$subQ]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                            if ($params['platform'] == 'web') {
                                                $tempSubQuestionsArray[$subQ]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                                if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                                    $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                                    $tempSubQuestionsArray[$subQ]['workarea'] = json_decode($workarea);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['workarea'] = [];
                                                }
                                                if (is_null($tempSubQuestionsArray[$subQ]['workarea'])) {
                                                    $tempSubQuestionsArray[$subQ]['workarea'] = [];
                                                }
                                            }
                                        }
                                        array_push($answerList[$c - 1]['section'][0]['sub_questions'], $tempSubQuestionsArray[0]);
                                    } else {
                                        $answerList[$c]['heading'] = $allAnswerList[$i]['heading'];
                                        $answerList[$c]['section'][0]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['question'] = $allAnswerList[$i]['question'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['page_no'] = $allAnswerList[$i]['page_no'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['options'] = $allAnswerList[$i]['options'];
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['answer']);
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                            }
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                        if ($allAnswerList[$i]['question_type_id'] == 40) {
                                            if ($params['role_id'] == 5) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = '';
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                            }
                                            if ($params['platform'] != 'ios') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                            }
                                        } else {
                                           // $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                        }
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'web') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'];
                                            }
                                        }
                                        if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                if ($params['platform'] == 'web') {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                                    if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                                    }
                                                    if ($allAnswerList[$i]['student_answer'] != '') {
                                                        //  $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                        $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                    } else {
                                                        $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                                    }
                                                } else {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                                }
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                            }
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                        } else {
                                            if ($params['platform'] == 'web') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = 0;
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = 0;
                                                if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                                } else {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                                }
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                            }
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = '';
                                        }
                                        if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                            if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                            }
                                            $answerList[$c]['section'][0]['sub_questions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                            $answerList[$c]['section'][0]['sub_questions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                            if ($allAnswerList[$i]['question_type_id'] == 40) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                            }
                                        }
                                        if ($params['platform'] != 'ios') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['points'] = $allAnswerList[$i]['points'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['match_case'] = $allAnswerList[$i]['match_case'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                        if (isset($params['student_id'])) {
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                if ($params['platform'] == 'ios') {
                                                    //   $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                                    if ($allAnswerList[$i]['student_answer'] != '') {
                                                        $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                    } else {
                                                        $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = [];
                                                    }
                                                }
                                            }
                                            $answerList[$c]['section'][0]['sub_questions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                            $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                            $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                            $answerList[$c]['section'][0]['sub_questions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                            if ($params['platform'] == 'web') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                                if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                                    $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = json_decode($workarea);
                                                } else {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                                }
                                                if (is_null($answerList[$c]['section'][0]['sub_questions'][0]['workarea'])) {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $answerList[$c]['heading'] = $allAnswerList[$i]['heading'];
                                    $answerList[$c]['section'][0]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['question'] = $allAnswerList[$i]['question'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['page_no'] = $allAnswerList[$i]['page_no'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['options'] = $allAnswerList[$i]['options'];
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'ios') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['answer']);
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                        }
                                    } else {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                    if ($allAnswerList[$i]['question_type_id'] == 40) {
                                        if ($params['role_id'] == 5) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = '';
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = json_decode($allAnswerList[$i]['answer']);
                                        }

                                        if ($params['platform'] != 'ios') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                        }
                                    } else {
                                      //  $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(string: base64_encode($allAnswerList[$i]['answer']));
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                    }
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'web') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'];
                                        }
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'web') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                                if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                                }
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    // $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                                }
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                            }
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                    } else {
                                        if ($params['platform'] == 'web') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = 0;
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = 0;
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                            }
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = '';
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                        if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                        if ($allAnswerList[$i]['question_type_id'] == 40) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                        }
                                    }
                                    if ($params['platform'] != 'ios') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['points'] = $allAnswerList[$i]['points'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['match_case'] = $allAnswerList[$i]['match_case'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                    if (isset($params['student_id'])) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                //   $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = [];
                                                }
                                            }
                                        }
                                        $answerList[$c]['section'][0]['sub_questions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                        $answerList[$c]['section'][0]['sub_questions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                        if ($params['platform'] == 'web') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                            if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                                $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                                $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = json_decode($workarea);
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                            }
                                            if (is_null($answerList[$c]['section'][0]['sub_questions'][0]['workarea'])) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                            }
                                        }
                                    }
                                }
                            } else {
                                $answerList[$c]['heading'] = $allAnswerList[$i]['heading'];
                                $answerList[$c]['section'][0]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['question'] = $allAnswerList[$i]['question'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['page_no'] = $allAnswerList[$i]['page_no'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['options'] = $allAnswerList[$i]['options'];
                                if ($allAnswerList[$i]['question_type_id'] == 54) {
                                    if ($params['platform'] == 'ios') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['answer']);
                                    } else {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                    }
                                } else {
                                    $answerList[$c]['section'][0]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                }
                                $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                if ($allAnswerList[$i]['question_type_id'] == 40) {
                                    if ($params['platform'] != 'ios') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : '';
                                } else {
                                   // $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                }
                                if ($allAnswerList[$i]['question_type_id'] == 54) {
                                    if ($params['platform'] == 'web') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                    } else {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'];
                                    }
                                }
                                if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'web') {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                            if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                            }
                                            if ($allAnswerList[$i]['student_answer'] != '') {
                                                // $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                            }
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                        }
                                    } else {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                } else {
                                    if ($params['platform'] == 'web') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = 0;
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = 0;
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = [];
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                        }
                                    } else {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['given_answer'] = '';
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answerdata'] = '';
                                }
                                if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                    if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                    if ($allAnswerList[$i]['question_type_id'] == 40) {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                    }
                                }
                                if ($params['platform'] != 'ios') {
                                    $answerList[$c]['section'][0]['sub_questions'][0]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                }
                                $answerList[$c]['section'][0]['sub_questions'][0]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['points'] = $allAnswerList[$i]['points'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['match_case'] = $allAnswerList[$i]['match_case'];
                                $answerList[$c]['section'][0]['sub_questions'][0]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                if (isset($params['student_id'])) {
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'ios') {
                                            //  $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                            if ($allAnswerList[$i]['student_answer'] != '') {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                            } else {
                                                $answerList[$c]['section'][0]['sub_questions'][0]['mob_options'] = [];
                                            }
                                        }
                                    }
                                    $answerList[$c]['section'][0]['sub_questions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                    $answerList[$c]['section'][0]['sub_questions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                    if ($params['platform'] == 'web') {
                                        $answerList[$c]['section'][0]['sub_questions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                        if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                            $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                            $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = json_decode($workarea);
                                        } else {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                        }
                                        if (is_null($answerList[$c]['section'][0]['sub_questions'][0]['workarea'])) {
                                            $answerList[$c]['section'][0]['sub_questions'][0]['workarea'] = [];
                                        }
                                    }
                                }
                            }
                        } else {
                            $subQuestion = 0;
                            $noSubQuestion = 0;
                            $tempSubQuestionsArray = [];
                            if ($answerList[$c - 1]['heading'] == $allAnswerList[$i]['heading']) {
                                //$l => count of each heading questions
                                $l = count($answerList[$c - 1]['section']) - 1;
                                if (isset($answerList[$c - 1]['section'][$l]['sub_questions'])) {
                                    //$m => count of each subquestions in a heading
                                    $m = count($answerList[$c - 1]['section'][$l]['sub_questions']) - 1;
                                }
                                if ($answerList[$c - 1]['section'][$l]['sub_questions'][$m]['question_no'] == $allAnswerList[$i]['question_no']) {
                                    $subQuestion = 1;
                                    $tempSubQuestionsArray[$subQ]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                    $tempSubQuestionsArray[$subQ]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $tempSubQuestionsArray[$subQ]['question'] = $allAnswerList[$i]['question'];
                                    $tempSubQuestionsArray[$subQ]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                    $tempSubQuestionsArray[$subQ]['page_no'] = $allAnswerList[$i]['page_no'];
                                    $tempSubQuestionsArray[$subQ]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                    $tempSubQuestionsArray[$subQ]['options'] = $allAnswerList[$i]['options'];
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'ios') {
                                            $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['answer']);
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['array']);
                                        }
                                    } else {
                                        $tempSubQuestionsArray[$subQ]['array'] = json_decode($allAnswerList[$i]['array']);
                                    }
                                    $tempSubQuestionsArray[$subQ]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                    if ($allAnswerList[$i]['question_type_id'] == 40) {
                                        $tempSubQuestionsArray[$subQ]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : '';
                                        if ($params['platform'] != 'ios') {
                                            $tempSubQuestionsArray[$subQ]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                        }
                                    } else {
                                      //  $tempSubQuestionsArray[$subQ]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                        $tempSubQuestionsArray[$subQ]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                    }
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['answer'] = $allAnswerList[$i]['answer'];
                                        }
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'web') {
                                                $tempSubQuestionsArray[$subQ]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                                if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                    $tempSubQuestionsArray[$subQ]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                                }
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    //  $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                    $tempSubQuestionsArray[$subQ]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['given_answer'] = [];
                                                }
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                            }
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                        }
                                        $tempSubQuestionsArray[$subQ]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                    } else {
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['answer_attended'] = 0;
                                            $tempSubQuestionsArray[$subQ]['answer_status'] = 0;
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                $tempSubQuestionsArray[$subQ]['given_answer'] = [];
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['given_answer'] = '';
                                            }
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['given_answer'] = '';
                                        }
                                        $tempSubQuestionsArray[$subQ]['answerdata'] = '';
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        $tempSubQuestionsArray[$subQ]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                        if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                            $tempSubQuestionsArray[$subQ]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                        }
                                        $tempSubQuestionsArray[$subQ]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                        $tempSubQuestionsArray[$subQ]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                        if ($allAnswerList[$i]['question_type_id'] == 40) {
                                            $tempSubQuestionsArray[$subQ]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                        }
                                    }
                                    if ($params['platform'] != 'ios') {
                                        $tempSubQuestionsArray[$subQ]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                    }
                                    $tempSubQuestionsArray[$subQ]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                    $tempSubQuestionsArray[$subQ]['points'] = $allAnswerList[$i]['points'];
                                    $tempSubQuestionsArray[$subQ]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                    $tempSubQuestionsArray[$subQ]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                    $tempSubQuestionsArray[$subQ]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                    $tempSubQuestionsArray[$subQ]['match_case'] = $allAnswerList[$i]['match_case'];
                                    $tempSubQuestionsArray[$subQ]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                    if (isset($params['student_id'])) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                //   $tempSubQuestionsArray[$subQ]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    $tempSubQuestionsArray[$subQ]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['mob_options'] = [];
                                                }
                                            }
                                        }
                                        $tempSubQuestionsArray[$subQ]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                        $tempSubQuestionsArray[$subQ]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                        $tempSubQuestionsArray[$subQ]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                        $tempSubQuestionsArray[$subQ]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                            if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                                $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                                $tempSubQuestionsArray[$subQ]['workarea']  = json_decode($workarea);
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['workarea'] = [];
                                            }
                                            if (is_null($tempSubQuestionsArray[$subQ]['workarea'])) {
                                                $tempSubQuestionsArray[$subQ]['workarea'] = [];
                                            }
                                        }
                                    }
                                } else {
                                    $noSubQuestion = 1;
                                    $tempSubQuestionsArray[$subQ]['has_sub_question'] = $allAnswerList[$i]['has_sub_question'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_id'] = $allAnswerList[$i]['answer_id'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['question_no'] = $allAnswerList[$i]['question_no'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['question'] = $allAnswerList[$i]['question'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['question_type_id'] = $allAnswerList[$i]['question_type_id'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['page_no'] = $allAnswerList[$i]['page_no'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['sub_question_no'] = $allAnswerList[$i]['sub_question_no'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['options'] = $allAnswerList[$i]['options'];
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'ios') {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['answer']);
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                        }
                                    } else {
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['array'] = json_decode($allAnswerList[$i]['array']);
                                    }
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['mob_options'] != '' ? json_decode($allAnswerList[$i]['mob_options']) : [];
                                    if ($allAnswerList[$i]['question_type_id'] == 40) {
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : '';

                                        if ($params['platform'] != 'ios') {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['editor_answer'] = $allAnswerList[$i]['editor_answer'];
                                        }
                                    } else {
                                      //  $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? $allAnswerList[$i]['answer'] : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer'] = $params['role_id'] != 5 ? json_decode($allAnswerList[$i]['answer']) : base64_encode(base64_encode($allAnswerList[$i]['answer']));
                                    }
                                    if ($allAnswerList[$i]['question_type_id'] == 54) {
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'] != '' ? json_decode($allAnswerList[$i]['answer']) : [];
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer'] = $allAnswerList[$i]['answer'];
                                        }
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'web') {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                                if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                                }
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    //  $allAnswerList[$i]['student_answer'] = stripslashes($allAnswerList[$i]['student_answer']);
                                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = [];
                                                }
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                            }
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = $allAnswerList[$i]['student_answer'];
                                        }
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answerdata'] = $allAnswerList[$i]['student_answer'];
                                    } else {
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_attended'] = 0;
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_status'] = 0;
                                            if ($allAnswerList[$i]['question_type_id'] == 54) {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = [];
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = '';
                                            }
                                        } else {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['given_answer'] = '';
                                        }
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answerdata'] = '';
                                    }
                                    if (isset($studentStatus['status']) && $studentStatus['status'] > 1) {
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_attended'] = $allAnswerList[$i]['answer_attended'];
                                        if ($redoStatus['redo_test'] == 1 || $redoStatus['redo_test'] == 2) {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_status'] = $allAnswerList[$i]['answer_status'];
                                        }
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['jiixdata'] = $allAnswerList[$i]['jiixdata'];
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['roughdata'] = $allAnswerList[$i]['roughdata'];
                                        if ($allAnswerList[$i]['question_type_id'] == 40) {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['student_editor_answer'] = $allAnswerList[$i]['student_editor_answer'];
                                        }
                                    }
                                    if ($params['platform'] != 'ios') {
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['answer_explanation'] = $allAnswerList[$i]['answer_explanation'];
                                    }
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['auto_grade'] = $allAnswerList[$i]['auto_grade'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['points'] = $allAnswerList[$i]['points'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['difficulty'] = $allAnswerList[$i]['difficulty'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['allow_exact_match'] = $allAnswerList[$i]['allow_exact_match'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['allow_any_text'] = $allAnswerList[$i]['allow_any_text'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['match_case'] = $allAnswerList[$i]['match_case'];
                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['minimum_line'] = $allAnswerList[$i]['minimum_line'];
                                    if (isset($params['student_id'])) {
                                        if ($allAnswerList[$i]['question_type_id'] == 54) {
                                            if ($params['platform'] == 'ios') {
                                                //  $tempSubQuestionsArray[$subQ]['sub_questions'][0]['mob_options'] = $allAnswerList[$i]['student_answer'] != '' ? json_decode($allAnswerList[$i]['student_answer']) : [];
                                                if ($allAnswerList[$i]['student_answer'] != '') {
                                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['mob_options'] = (json_decode($allAnswerList[$i]['student_answer'], true) == NULL) ? json_decode(stripslashes($allAnswerList[$i]['student_answer'])) : json_decode($allAnswerList[$i]['student_answer'], true);
                                                } else {
                                                    $tempSubQuestionsArray[$subQ]['sub_questions'][0]['mob_options'] = [];
                                                }
                                            }
                                        }
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['student_feedback'] = $allAnswerList[$i]['student_feedback'];
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['rough_image_url'] = $allAnswerList[$i]['rough_image_url'];
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['rough_image_thumb_url'] = $allAnswerList[$i]['rough_image_thumb_url'];
                                        $tempSubQuestionsArray[$subQ]['sub_questions'][0]['student_answer_image'] = $allAnswerList[$i]['student_answer_image'];
                                        if ($params['platform'] == 'web') {
                                            $tempSubQuestionsArray[$subQ]['sub_questions'][0]['student_roughdata'] = $allAnswerList[$i]['student_roughdata'];
                                            if (isset($allAnswerList[$i]['workarea']) && $allAnswerList[$i]['workarea'] != '') {
                                                $workarea = $this->common_model->annotation($allAnswerList[$i]['workarea']);
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['workarea']  = json_decode($workarea);
                                            } else {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['workarea'] = [];
                                            }
                                            if (is_null($tempSubQuestionsArray[$subQ]['sub_questions'][0]['workarea'])) {
                                                $tempSubQuestionsArray[$subQ]['sub_questions'][0]['workarea'] = [];
                                            }
                                        }
                                    }
                                }
                                if (count($tempSubQuestionsArray) > 0) {
                                    if ($subQuestion > 0) {
                                        $check = array_search($allAnswerList[$i]['answer_id'], $answerList[$c - 1]['section'][$l]['sub_questions'][$m]);
                                        if (!$check) {
                                            array_push($answerList[$c - 1]['section'][$l]['sub_questions'], $tempSubQuestionsArray[0]);
                                        }
                                    } elseif ($noSubQuestion > 0) {
                                        $check = array_search($allAnswerList[$i]['answer_id'], $answerList[$c - 1]['section'][$l]['sub_questions'][$m]);
                                        if (!$check) {
                                            array_push($answerList[$c - 1]['section'], $tempSubQuestionsArray[0]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = $contentList;
                    $this->jsonarr['ResponseObject']['answers'] = $answerList;
                    if ($params['platform'] == 'web') {
                        //$this->jsonarr['ResponseObject']['processing_time'] = $this->benchmark->elapsed_time('code_start', 'code_end');
                    }
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject']['message'] = "This is a draft content. Can't access";
                //                $this->jsonarr['ResponseObject']['content_type'] = $getStudentClass[0]['content_type'];
            }
        }
        $this->common_model->createLog($params, 'v1/content/contentDetail', $this->jsonarr, 'contentDetail');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function detail_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/detail', 'only request', 'detail');
            $contentList = $this->content_model->contentIdList($params);
            if ($contentList > 0) {
                if ($this->config->item('filePathBase64') == true) {
                    $base64FilePath = base64_encode(base64_encode(base64_encode(base64_encode($contentList['file_path']))));
                }
                if ($this->config->item('filePathBase64') == true) {
                    if ($contentList['file_path'] != '' && $contentList['file_path'] != '[]') {
                        $contentList['file_path'] = $base64FilePath;
                    } else {
                        $contentList['file_path'] = [];
                    }
                } else {
                    $contentList['file_path'] = json_decode($contentList['file_path']);
                }
                if ($this->config->item('filePathBase64') == true) {
                    $base64FilePath = base64_encode(base64_encode(base64_encode(base64_encode($contentList['answerkey_path']))));
                }
                if ($this->config->item('filePathBase64') == true) {
                    if ($contentList['answerkey_path'] != '' && $contentList['answerkey_path'] != '[]') {
                        $contentList['answerkey_path'] = $base64FilePath;
                    } else {
                        $contentList['answerkey_path'] = [];
                    }
                } else {
                    $contentList['answerkey_path'] = json_decode($contentList['answerkey_path']);
                }
                //$contentList['file_path'] = $this->config->item('filePathBase64') == true ? $base64FilePath : $contentList['file_path'] != '' ? json_decode($contentList['file_path']) : [];
                //            if ($contentList['file_path'] != '') {
                //                $contentList['file_path'] = $base64FilePath;
                //            } else if ($contentList['file_path'] == '') {
                //                $contentList['file_path'] = [];
                //            }
                if ($contentList['annotation'] != '') {
                    $contentList['annotation'] = json_decode($contentList['annotation']);
                }
                $contentList['links'] = explode(',', $contentList['links']);
                $contentList['tags'] = explode(',', $contentList['tags']);
                $contentList['links'] = array_filter($contentList['links']);
                $contentList['tags'] = array_filter($contentList['tags']);
                $data = 0;
                if (isset($contentList['content_format']) && $contentList['content_format'] == 1) {
                    $checkQuestionExists = $this->content_model->getQuestionExists($params);
                    $data = 0;
                    if (count($checkQuestionExists) == 0) {
                        $data = 1;
                    }
                } elseif (isset($contentList['content_format']) && $contentList['content_format'] == 3) {
                    $checkQuestionExists1 = $this->content_model->getQuestionExists1($params);
                    $data = 0;
                    if (count($checkQuestionExists1) == 0) {
                        $data = 1;
                    }
                }
                $contentList['without_question'] = $data;
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $contentList;
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = [];
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/detail', $this->jsonarr, 'detail');
        return $this->printjson($this->jsonarr);
    }

    public function sortMaster_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/sortMaster', 'only request', 'sortMaster');
            $sortMaster = $this->content_model->sortMaster($params);
            $i = 0;
            $data = [];
            foreach ($sortMaster as $value) {
                $data[$i] = $value;
                if ($data[$i]['annotation'] != '' && $data[$i]['annotation'] != '[]') {
                    $annotation = $this->common_model->annotation($data[$i]['annotation']);
                    $data[$i]['annotation'] = json_decode($annotation);
                }
                if ($data[$i]['questionAnnotation'] != '' && $data[$i]['questionAnnotation'] != '[]') {
                    $questionAnnotation = $this->common_model->annotation($data[$i]['questionAnnotation']);
                    $data[$i]['questionAnnotation'] = json_decode($questionAnnotation);
                } else {
                    $data[$i]['questionAnnotation'] = [];
                }
                if($data[$i]['file_path'] != '') {
                    $pdfContent = 0;
                    $file_path = json_decode($data[$i]['file_path'], true);
                    if (isset($file_path[0]['original_image_url']) && $file_path[0]['original_image_url'] != '') {
                        $pdfContent = 1;
                    }
                    $data[$i]['is_pdf_content'] = $pdfContent;
                } else {
                    $data[$i]['is_pdf_content'] = 0;
                }

                $links = explode(',', $value['links']);
                $tags = explode(',', $value['tags']);
                $links = array_filter($links);
                $tags = array_filter($tags);
                $data[$i]['links'] = $links;
                $data[$i]['tags'] = $tags;
                $i++;
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $data;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/sortMaster', $this->jsonarr, 'sortMaster');
        return $this->printjson($this->jsonarr);
    }

    public function contentCreatorList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        // $this->common_model->checkPermission($this->controller,$params,$headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "School Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/contentCreatorList', 'only request', 'contentCreatorList');
            $list = $this->content_model->contentCreatorList($params);
            $i = 0;
            $data = array();
            foreach ($list as $value) {
                $data[$i] = $value;
                if ($data[$i]['content_user_id'] == $params['user_id']) {
                    unset($data[$i]);
                }
                $i++;
            }
            foreach ($data as $key => $value) {
                if ($value['name'] == "" || $value['name'] == " ") {
                    unset($data[$key]);
                }
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = array_values($data);
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/contentCreatorList', $this->jsonarr, 'contentCreatorList');
        return $this->printjson($this->jsonarr);
    }

    public function deleteContent_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/deleteContent', 'only request', 'deleteContent');
            $contentDelete = false;
            foreach ($params['class_content_id'] as $key => $value) {
                $classContentDetails = $this->content_model->classContentDetails($value);
                $classContentDetails[0]['status'] = 2;
                $data = array(
                    'status' => 2,
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $condition = array('id' => $value);
                $contentDelete = $this->common_model->update('class_content', $data, $condition);
                $insert_log = $this->common_model->insert('class_content_log', $classContentDetails[0]);

                $condition = array('class_content_id' => $value);
                $deleteStudentContent = $this->common_model->delete('student_content', $condition);
                $work = array(
                    'status' => 0,
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $condition = array('content_id' => $classContentDetails[0]['content_id'], 'class_id' => $classContentDetails[0]['class_id']);
                $studentworkupdated = $this->common_model->update('student_work', $work, $condition);
            }
            if ($contentDelete) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Content Deleted Successfully";
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ErrorObject'] = "Failed To Delete";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/deleteContent', $this->jsonarr, 'deleteContent');
        return $this->printjson($this->jsonarr);
    }

    public function contentRepositoryDelete_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/contentRepositoryDelete', 'only request', 'contentRepositoryDelete');
            $checkContentAssign = $this->content_model->checkContentAssign($params);
            if (count($checkContentAssign) > 0) {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ResponseObject'] = "Content already assgined";
            } else {
                $data = array(
                    'status' => 4,
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $condition = array('content_id' => $params['content_id']);
                $contentDelete = $this->common_model->update('content', $data, $condition);
                if ($contentDelete) {
                    $this->jsonarr["IsSuccess"] = true;
                    $this->jsonarr["ResponseObject"] = "Content Deleted Successfully";
                }
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/contentRepositoryDelete', $this->jsonarr, 'contentRepositoryDelete');
        return $this->printjson($this->jsonarr);
    }

    public function deleteAssignedContent_post()
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
            $this->jsonarr["ErrorObject"] = "Role_Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } elseif (count($this->jsonarr) == 0) {
            $this->common_model->createLog($params, 'v1/content/deleteAssignedContent', 'only request', 'deleteAssignedContent');
            $data = array('status' => 2);
            $contentData = array('status' => 4);
            $condition = array('content_id' => $params['content_id']);
            $contentDelete = $this->common_model->update('class_content', $data, $condition);
            $deleteStudentContent = $this->common_model->delete('student_content', $condition);
            $work = array(
                'status' => 0
            );
            $studentworkupdated = $this->common_model->update('student_work', $work, $condition);
            $deleteContent = $this->common_model->update('content', $contentData, $condition);
            if ($contentDelete) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Content Deleted Successfully";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/deleteAssignedContent', $this->jsonarr, 'deleteAssignedContent');
        return $this->printjson($this->jsonarr);
    }

    public function cloneContent_post()
    {
        //in request {"user_id":"","school_id":"","content_id":""}
        //school_id -> to which school have to copy
        //content_id -> which content have to copy
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->common_model->createLog($params, 'v1/content/cloneContent', 'only request', 'cloneContent');
        $getContent = $this->content_model->getContentForCopy($params);
        $copyContent = $this->common_model->insert('content', $getContent);
        $getAnswers = $this->content_model->getContentAnswers($params, $copyContent);
        if (count($getAnswers) > 0) {
            $this->common_model->bulkInsert('answers', $getAnswers);
        }
        if ($copyContent > 0) {
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "Content Cloned";
        }
        $this->common_model->createLog($params, 'v1/content/cloneContent', $this->jsonarr, 'cloneContent');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function questionDelete_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } elseif ($params['answer_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Answer Id Should not be Empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/questionDelete', 'only request', 'questionDelete');
            foreach ($params['answer_id'] as $key => $value) {
                $condition = array('answer_id' => $value);
                $data = array('status' => 0);
                $deleteData = $this->common_model->update('answers', $data, $condition);
            }
            if ($deleteData) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Question Deleted Successfully";
            }
        }
        $this->common_model->createLog($params, 'v1/content/questionDelete', $this->jsonarr, 'questionDelete');
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function scoreReleaseCheck_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id Should not be Empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id Should not be Empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/scoreReleaseCheck', 'only request', 'scoreReleaseCheck');
            $autoGrade = $this->content_model->checkAutoGrade($params);
            if ($autoGrade) {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Score Release Not Possible";
            } else {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Score Can be Released";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/scoreReleaseCheck', $this->jsonarr, 'scoreReleaseCheck');
        return $this->printjson($this->jsonarr);
    }

    public function answerkeyRequestList_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "User Id Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Role Id Should not be Empty";
        } elseif ($params['school_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "School Id Should not be Empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/answerkeyRequestList', 'only request', 'answerkeyRequestList');
            $answerkeyRequestList = $this->content_model->answerkeyRequestList($params);
            $response['data'] = $answerkeyRequestList;
            $pending = [];
            $rejected = [];
            $accepted = [];
            $statusList = $this->content_model->answerkeyStatus($params);
            foreach ($statusList as $key => $value) {
                if ($value['status'] == 1) {
                    array_push($pending, $value['status']);
                }
                if ($value['status'] == 2) {
                    array_push($rejected, $value['status']);
                }
                if ($value['status'] == 3) {
                    array_push($accepted, $value['status']);
                }
            }
            $response['pending_count'] = count($pending);
            $response['rejected_count'] = count($rejected);
            $response['accepted_count'] = count($accepted);

            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = $response;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/answerkeyRequestList', $this->jsonarr, 'answerkeyRequestList');
        return $this->printjson($this->jsonarr);
    }

    public function updateAnswerKey_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform Should not be Empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "User Id Should not be Empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Role Id Should not be Empty";
        } elseif ($params['student_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Student Id Should not be Empty";
        } elseif ($params['class_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Class Id Should not be Empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Content Id Should not be Empty";
        } elseif ($params['answer_request'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObejct"] = "Answer Request Status Should not be Empty";
        } else {
            $this->common_model->createLog($params, 'v1/content/updateAnswerKey', 'only request', 'updateAnswerKey');
            $data1 = array(
                'status' => $params['answer_request']
            );
            $condition1 = array(
                'student_id' => $params['student_id'],
                'content_id' => $params['content_id'],
                'class_id' => $params['class_id']
            );
            $updateAnswerkey1 = $this->common_model->update('student_answerkey_request', $data1, $condition1);
            $data2 = array(
                'answer_request' => $params['answer_request']
            );
            $condition2 = array(
                'student_id' => $params['student_id'],
                'content_id' => $params['content_id'],
                'class_id' => $params['class_id']
            );
            $updateAnswerkey2 = $this->common_model->update('student_content', $data2, $condition2);
            if ($updateAnswerkey1 && $updateAnswerkey2) {
                $this->jsonarr["IsSuccess"] = True;
                $this->jsonarr["ResponseObject"] = "AnswerKey Request Update Successful!";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "AnswerKey Request Update Failed!";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->common_model->createLog($params, 'v1/content/updateAnswerKey', $this->jsonarr, 'updateAnswerKey');
        return $this->printjson($this->jsonarr);
    }

    public function emailNotification($params, $id, $action, $records)
    {
        $actionData = array(
            'user_id' => $params['user_id'],
            'role_id' => $params['role_id'],
            'content_id' => $id,
            'school_id' => $params['school_id'],
            'action' => 'Content ' . $action,
            'message_content' => $records,
            'request_data' => json_encode($params)
        );
        $insertAction = $this->common_model->insert('teacher_action_notification', $actionData);
        /*if ($insertAction > 0) {
            $getAdmin = $this->common_model->getAdmin($params['school_id']);
            if (count($getAdmin) > 0) {
                if ($getAdmin[0]['name'] == 'teacher_activity_email') {
                    $emailId = $getAdmin[0]['value'];
                }
                if ($getAdmin[1]['name'] == 'teacher_notify_email_admin') {
                    $cc = $getAdmin[1]['value'];
                }
                $messageTemplates = $this->common_model->smsEmailTemplate('action_notification', 'email');
                $subject = $messageTemplates['subject'];
                $subject = str_replace('%TYPE%', 'Content', $subject);
                $subject = str_replace('%ACTION%', $action, $subject);
                $emailMsg = $messageTemplates['template'];
                $emailMsg = str_replace('%USER%', 'Admin', $emailMsg);
                $emailMsg = str_replace('%ACTION%', $action, $emailMsg);
                $emailMsg = str_replace('%TYPE%', 'content', $emailMsg);
                $emailMsg = str_replace('%RECORDS%', $records, $emailMsg);
//            if ($this->config->item('user_send_email') == true) {
                $this->common_model->sendEmail_cc($subject, $emailId, $emailMsg, '', $cc);
            }
//            }
        }*/
    }

    public function uploadCurriculum_post()
    {
        $this->benchmark->mark('code_start');
        $file = "../uploads/excel/sample_curriculum.xlsx";
        $this->load->library('PHPExcel');
        if (file_exists($file)) {
            $objPHPExcel = PHPExcel_IOFactory::load($file);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $rowCheck = $sheet->rangeToArray('A1:H1', NULL, TRUE, FALSE);
            if (
                $rowCheck[0][0] != "Sno" || $rowCheck[0][1] != "class_code" || $rowCheck[0][2] != "content_name"
                || $rowCheck[0][3] != "institution_name" || $rowCheck[0][4] != "start_date" || $rowCheck[0][5] != "end_date"
                || $rowCheck[0][6] != "start_time" || $rowCheck[0][7] != "end_time"
            ) {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Please Upload Correct format of excel";
            } else {
                $i = 0;
                $array = [];
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray('A' . $row . ':' . 'H' . $row, NULL, TRUE, FALSE);
                    if (
                        $rowData[0][1] != "" && $rowData[0][2] != "" && $rowData[0][3] != ""
                        && $rowData[0][4] != "" && $rowData[0][5] != "" && $rowData[0][6] != ""
                    ) {
                        $getClassId = $this->content_model->getClassId($rowData[0][1]);
                        $getContentId = $this->content_model->getContentName($rowData[0][2]);
                        $checkExists = $this->content_model->getCheckExists($getClassId['class_id'], $getContentId['content_id']);
                        if (count($checkExists) == 0) {
                            $array[$i] = array(
                                'class_id' => $getClassId['class_id'],
                                'content_id' => $getContentId['content_id'],
                                'school_id' => $getClassId['school_id'],
                                'start_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][4])),
                                'end_date' => date("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][5])),
                                'start_time' => date("H:i:s", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][6])),
                                'end_time' => date("H:i:s", PHPExcel_Shared_Date::ExcelToPHP($rowData[0][7])),
                                'created_by' => $getClassId['created_by'],
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $i++;
                        }
                    }
                }
                if (count($array) > 0) {
                    $this->common_model->bulkInsert('class_content', $array);
                }
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = $array;
            }
        } else {
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = "File does'nt exists";
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function group_by($key, $data)
    {
        $result = array();
        $finalData = array();
        foreach ($data as $val) {
            if (array_key_exists($key, $val)) {
                $result[$val[$key]][] = $val;
            } else {
                $result[""][] = $val;
            }
        }
        foreach ($result as $value) {
            for ($i = 0; $i < count($value); $i++) {
                array_push($finalData, $value[$i]);
            }
        }
        return $finalData;
    }

    public function addPassage_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents("php://input"), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['title'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Title should not be empty";
        } elseif ($params['passage'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Passage should not be empty";
        } elseif ($params['status'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Status should not be empty";
        } else {
            $passage = array(
                'title' => $params['title'],
                'passage' => json_encode($params['passage']),
                'status' => $params['status'],
                'created_by' => $params['user_id'],
                'created_date' => date('Y-m-d H:i:s')
            );
            $addPassage = $this->common_model->insert('passage', $passage);
            if ($addPassage > 0) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Passage Added Successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Passage Add Failed";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function editPassage_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['title'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Title should not be empty";
        } elseif ($params['passage'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Passage should not be empty";
        } elseif ($params['status'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Status should not be empty";
        } elseif ($params['passage_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Passage Id should not be empty";
        } else {
            $condition = array(
                "passage_id" => $params['passage_id']
            );
            $passageData = array(
                'title' => $params['title'],
                'passage' => json_encode($params['passage']),
                'status' => $params['status'],
                'modified_by' => $params['user_id'],
                'modified_date' => date('Y-m-d H:i:s')
            );
            $updatePassage = $this->common_model->update('passage', $passageData, $condition);
            if ($updatePassage > 0) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Passage updated successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Passage update failed";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function deletePassage_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['passage_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Passage Id should not be empty";
        } else {
            $condition = array("passage_id" => $params['passage_id']);
            $deletePassage = $this->common_model->delete('passage', $condition);
            if ($deletePassage) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Passage Deleted Successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Passage Delete Failed";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

    public function listPassage_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $listPassage = $this->content_model->listPassage($params);
            foreach ($listPassage as $key => $value) {
                if (isset($listPassage[$key]['passage'])) {
                    $listPassage[$key]['passage'] = json_decode($value['passage']);
                }
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $listPassage;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function editContentType_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Platform should not be empty";
        } elseif ($params['role_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } elseif ($params['content_id'] == '') {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "Content Id should not be empty";
        } else {
            $condition = array(
                "content_id" => $params['content_id']
            );
            $contentType = array(
                'content_type' => $params['content_type'],
                'modified_by' => $params['user_id'],
                'modified_date' => date('Y-m-d H:i:s')
            );
            $update = $this->common_model->update('content', $contentType, $condition);
            if ($update > 0) {
                $this->jsonarr["IsSuccess"] = true;
                $this->jsonarr["ResponseObject"] = "Content updated successfully";
            } else {
                $this->jsonarr["IsSuccess"] = false;
                $this->jsonarr["ErrorObject"] = "Content update failed";
            }
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        $this->printjson($this->jsonarr);
    }

        public function questionTopic_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $condition = "";
            if(isset($params['question_id']) && $params['question_id'] > 0){
                $condition = "WHERE question_id = {$params['question_id']}";
            }
            $questionTopic = $this->content_model->questionTopic($condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $questionTopic;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function questionSubTopic_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $condition = "";
            if (isset($params['question_topic_id']) && $params['question_topic_id'] > 0) {
                $condition = "WHERE question_topic_id = {$params['question_topic_id']}";
            }
            $questionSubTopic = $this->content_model->questionSubTopic($condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $questionSubTopic;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }
    public function addSkills($skill,$params){
        foreach($skill as $key => $value){
            $value = trim($value);
            $condition = "WHERE skill = '{$value}'";
            $checkSkillExists = $this->content_model->checkSkillExists($condition);
            if(count($checkSkillExists) == 0){
               $skill['skill'] = $value;
               $skill['status'] = 1;
               $skill['created_by'] = $params['user_id'];
               $skill['created_date'] = date('Y-m-d H:i:s');
               $this->common_model->insert('question_skill',$skill);
            }
        }
    }

    public function questionSkill_post()
    {
        $this->benchmark->mark('code_start');
        $params = json_decode(file_get_contents('php://input'), true);
        $headers = $this->input->request_headers();
        // $this->common_model->checkPermission($this->controller, $params, $headers);
        if ($params['platform'] != "web" && $params['platform'] != "ios") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["IsSuccess"] = false;
            $this->jsonarr["ErrorObject"] = "User Id should not be empty";
        } else {
            $condition = "";
            if(isset($params['skill']) && $params['skill'] != ''){
                $condition = "WHERE skill LIKE '%{$params['skill']}%'";
            }
            $questionSkill = $this->content_model->questionSkill($condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $questionSkill;
        }
        $this->benchmark->mark('code_end');
        $this->jsonarr["processing_time"] = $this->benchmark->elapsed_time('code_start', 'code_end');
        return $this->printjson($this->jsonarr);
    }

    public function addScratchQuestion_post() {
        $text = json_decode(file_get_contents('php://input'), true);   
        $count = 1;
        $answer= array();
        $contentExists = $this->content_model->checkTextQuestions($text['content_id']);
        if ($contentExists > 0) {
            $count = $contentExists['question_no'] + $count;
        } 
        for($i = 0; $i < count($text['question']); $i++) {
            if(isset($text['question'][$i]['passage']) && $text['question'][$i]['passage'] != "") {
                $passage = array(
                    'title' => "",
                    'passage' => json_encode("<p>".$text['question'][$i]['passage']."</p>"),
                    'status' => 1,
                    'created_by' => 49,
                    'created_date' => date('Y-m-d H:i:s')
                );
                $passageId = $this->common_model->insert('passage', $passage);    
            } else {
                $passageId = 0;
            }
            
            //get subject id with name
            if(isset($text['question'][$i]['subject']) && $text['question'][$i]['subject'] != "") {
                $getSubjectId = $this->subject_model->checkSubject($text['question'][$i]['subject'],2);
            } else {
                $getSubjectId[0]['subject_id'] = 0;
            }
            $answer[$i]['subject_id'] = $getSubjectId[0]['subject_id'];

            //get topic id with topic name
            if(isset($text['question'][$i]['topic']) && $text['question'][$i]['topic'] != "") {
                $topic = array(
                    'topic' => $text['question'][$i]['topic'],
                    'class_id' => 0);
                $getTopicId = $this->classes_model->checkTopicExists($topic,'');
            } else {
                $getTopicId[0]['topic_id'] = 0;
            }
            $answer[$i]['question_topic_id'] = $getTopicId[0]['topic_id'];

            //get subtopic id with subtopic name
            if(isset($text['question'][$i]['subtopic']) && $text['question'][$i]['subtopic'] != "") {
                $getSubTopicId = $this->classes_model->checkSubTopicExists($text['question'][$i]['subtopic']);
                $answer[$i]['question_sub_topic_id'] = $getSubTopicId[0]['sub_topic_id'];
            } else {
                $answer[$i]['question_sub_topic_id'] = 0;
            }
            $answer[$i]['question_standard'] = isset($text['question'][$i]['standards']) ? $text['question'][$i]['standards'] : null;
            $answer[$i]['predicted_solving_time'] = isset($text['question'][$i]['predicted_solving_time']) && $text['question'][$i]['predicted_solving_time'] != "" ? $text['question'][$i]['predicted_solving_time'] : null;
            $answer[$i]['content_id'] = $text['content_id'];
            $answer[$i]['question_type_id'] = isset($text['question'][$i]['passage']) ? 24 : 1;
            $answer[$i]['passage_id'] =$passageId;
            $answer[$i]['sub_question_type_id'] = 1;
            // if (isset($params['questions'][0]['editor_context'])) {
            //     $text[$i]['editor_context'] = $params['questions'][0]['editor_context'];
            // }
            // if (isset($params['questions'][0]['subQuestions'][$i]['editor_type'])) {
            //     $text[$i]['editor_type'] = $params['questions'][0]['subQuestions'][$i]['editor_type'];
            // }
            $answer[$i]['question_no'] = $count;
            $answer[$i]['sub_question_no'] = 1;
            $answer[$i]['has_sub_question'] = 1;
            $answer[$i]['question'] = $text['question'][$i]['question'];
            $answer[$i]['editor_answer'] =  '';
            $j=0;
            foreach($text['question'][$i]['choices'] as $key => $value) {
                $choiceArray[$j]['options'] = !empty($value) ? $value : "";
                $choiceArray[$j]['isSelected'] = "";
                $j++;
            }
            $answer[$i]['options'] = json_encode($choiceArray);
            $k=0;
            foreach($text['question'][$i]['choices'] as $key1 => $value1) {
                $answerArray[$k]['correctAnswer'] = $key1 == $text['question'][$i]['answer'] ? $text['question'][$i]['choices'][$key1] : "";
                $answerArray[$k]['correctActive'] = $key1 == $text['question'][$i]['answer'] ? 1 : 0;
                $k++;
            }
            $answer[$i]['answer'] = json_encode($answerArray);
            $answer[$i]['level'] = isset($text['question'][0]['difficulty']) && $text['question'][0]['difficulty'] = "Easy" ? 1 : (isset($text['question'][0]['difficulty']) && $text['question'][0]['difficulty'] = 'Medium' ? 2 : 3);
            $answer[$i]['heading_option'] = '';
            $answer[$i]['multiple_response'] = 0;
            $answer[$i]['audo_grade'] = 0;
            $answer[$i]['points'] = 1;
            $answer[$i]['exact_match'] = 0;
            $hint = array("hint"=>"");
            $answer[$i]['hint'] = json_encode($hint);
            $answer[$i]['explanation'] = $text['question'][$i]['explanation'];
            $answer[$i]['word_limit'] = 0;
            $answer[$i]['scoring_instruction'] = "";
            $answer[$i]['source'] = "";
            $answer[$i]['target'] = "";
            $answer[$i]['created_by'] = 49;
            $answer[$i]['created_date'] = date('Y-m-d H:i:s');
            $count++;
        }
        $this->common_model->bulkInsert('text_questions', $answer);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = "Questions added";
        return $this->printjson($this->jsonarr);
    }
}
