<?php

ini_set('memory_limit', '-1');
ini_set('max_execution_time', 0);
ini_set("display_errors", "1");
error_reporting(E_ALL);

class Cron extends CI_Controller
{

    private $jsonarr = array();

    public function checkVersion()
    {
        phpinfo();
    }

    private function printjson($jsonarr)
    {
        echo json_encode($jsonarr);
    }

    public function autoCorrection()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->autoCorrection();
        $this->printjson($this->jsonarr);
    }

    public function filePath_post()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->cron_model->fileConversion();
    }

    public function notifyParentsSendEmail()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->notifyParents();
        $this->printjson($this->jsonarr);
    }

    public function contentOverDueEmail_updated()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->contentOverDueEmail();
        $this->printjson($this->jsonarr);
    }

    public function emailNotification()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->emailNotification();
        $this->printjson($this->jsonarr);
    }

    public function backUp()
    {
        $this->load->dbutil();

        $prefs = array(
            'format' => 'zip',
            'filename' => 'edquill_live.sql'
        );

        $backup = &$this->dbutil->backup($prefs);

        $db_name = 'edquill-' . date("Y-m-d-H-i-s") . '.zip';
        $path = dirname(FCPATH) . '/api/application/dbBackup/';
        $save = $path . $db_name;

            $this->load->helper('file');
            write_file($save, $backup);

            $this->load->helper('download');
            force_download($db_name, $backup);
    }

    public function adminMailInsert() {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->adminEmailInsert();
        $this->printjson($this->jsonarr);
    }

    public function adminMailNotification() {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->adminEmailNotify();
        $this->printjson($this->jsonarr);
    }

    public function edquillRegistrationMail(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->edquillRegistrationMail();
        $this->printjson($this->jsonarr);
    }
    public function studentPlatformWiseAnswerReport(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->studentPlatformWiseAnswerReport();
        $this->printjson($this->jsonarr);
    }

    public function uploadAnswerExcelMail(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->uploadAnswerExcelMail();
        $this->printjson($this->jsonarr);
    }
    public function belowScoreReport(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->belowScoreReport();
        $this->printjson($this->jsonarr);
    }

    public function deleteLogFiles() {
        $generateDate = date('Y-m-d',strtotime('-15 days',strtotime(date('Y-m-d'))));
        $dateFormat = date('d-M',strtotime($generateDate));
        $output=null;
        $retval=null;
//        file_get_contents('../');
//        $readDir = opendir('/var/www/uthkal.com/public_html/rista/');
//        if($readDir) {
//            print_r("as");
//        }
        //$cmd = 'sudo find -depth -path ' . "'" . $dateFormat. "'" . ' -delete';
        $cmd = './test.sh';
        shell_exec('chmod +');
        $contents = file_get_contents('../api/test.sh');print_R($contents);
        file_put_contents('../api/test.sh',str_replace('%CODE%','ls',$contents));
//        print_r($contents);
        $output = shell_exec($contents);
        print_R($output);
    }
    public function futureClassShift(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->futureClassShift();
        $this->printjson($this->jsonarr);
    }
    public function studentUpgrade(){
        $this->load->database();
        $this->load->model('cron_model');
        $params = json_decode(file_get_contents('php://input'), true);
        $this->jsonarr = $this->cron_model->studentUpgrade($params);
        $this->printjson($this->jsonarr);
    }

    public function dayWiseReport() {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->dayWiseReport();
        $this->printjson($this->jsonarr);
    }
    public function studentAddLimitNotification(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->studentAddLimitNotification();
        $this->printjson($this->jsonarr);
    }

    //save the student existing Annotation to file
    public function saveStudentAnnotation() {
        $this->load->database();
        $this->load->model('cron_model');
        $data = $this->cron_model->getAllAnnotations();
        // print_r($data);exit;
        for ($a =0; $a <count($data); $a++) {
            if(!is_null($data[$a]['annotation'])){
                $folder = "../uploads/studentAnnotation/";
                $fileName = "student-annotation".$data[$a]['id'].$data[$a]['class_id'].$data[$a]['content_id'].'.json';
                $create = $folder.$fileName;
                file_put_contents($create,$data[$a]['annotation']);
                $path = "uploads/studentAnnotation/".$fileName; 
            } else {
                $path = NULL;
            }
        
            if(!is_null($data[$a]['teacher_annotation'])){
                $teacherFolder = "../uploads/studentTeacherAnnotation/";
                $teacherName = "teacher-annotation".$data[$a]['id'].$data[$a]['class_id'].$data[$a]['content_id'].'.json';
                $create1 = $teacherFolder.$teacherName;
                file_put_contents($create1,$data[$a]['teacher_annotation']);
                $teacherPath = "uploads/studentTeacherAnnotation/".$teacherName;
            } else {
                $teacherPath = NULL;
            }
            
            if(!is_null($data[$a]['answer_sheet_annotation'])){
                $answerFolder = "../uploads/answerAnnotation/";
                $answerName = "answer-annotation".$data[$a]['id'].$data[$a]['class_id'].$data[$a]['content_id'].'.json';
                $create2 = $answerFolder.$answerName;
                file_put_contents($create2,$data[$a]['answer_sheet_annotation']);
                $answerPath = "uploads/answerAnnotation/".$answerName;
            } else {
                $answerPath = NULL;
            }

            $data1 = array('annotation' => $path,'teacher_annotation' => $teacherPath,'answer_sheet_annotation' => $answerPath);
            $updateCondition = array('content_id' => $data[$a]['content_id'],
                'student_id' => $data[$a]['student_id'],
                'class_id' => $data[$a]['class_id']);
            $update = $this->common_model->update('student_content',$data1,$updateCondition);
        }
        if ($update) {
            print_r("Files saved successfully");
        }

    }
    
    public function updateContentAnnotation(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->updateContentAnnotation();
        $this->printjson($this->jsonarr);
    }

    public function studentWorkareaAnnotation(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->studentWorkareaAnnotation();
        $this->printjson($this->jsonarr);
    }

    public function teacherClassAnnotation(){
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->teacherClassAnnotation();
        $this->printjson($this->jsonarr);
    }

    public function updateContentLinks()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->updateContentLinks();
        $this->printjson($this->jsonarr);
    }

    public function checkSpace()
    {
        $this->load->model('common_model');
        $prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
        $emailId = "usharaj99@gmail.com,sridhar.sivichandran@gmail.com,hemaramesh93@gmail.com";
        $last_line = system('df -h /home');
        $str = trim(preg_replace('/[\t\n\r\s]+/', ' ', $last_line));
        $storageDetails = explode(' ', $str);
        $totalSize = $storageDetails[1];
        $storageUsed = $storageDetails[2];
        $storageAvailable = $storageDetails[3];
        $usedPercentage = $storageDetails[4];
        $subject = 'Storage Details';
        $emailMsg = "Dear User, <br> Following are the Storage Details.<br>
                     <strong>Total Size: </strong> $totalSize <br>
                     <strong>Storage Used: </strong> $storageUsed <br>
                     <strong>Storage Available: </strong> $storageAvailable <br>
                     <strong>Percentage Used: </strong> $usedPercentage <br><br>
                     <strong>Thanks and Regards,</strong><br> Team EdQuill International";
        $sendMail = $this->common_model->sendEmail($subject, $emailId, $emailMsg, '', '');
        if ($sendMail) {
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ResponseObject"] = "Mail Send Successfully";
        } else {
            $this->jsonarr["IsSuccess"] = true;
            $this->jsonarr["ErrorObject"] = "Mail Not Send";
        }
        $this->printjson($this->jsonarr);
    }

    public function removeInactiveStudents()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->removeInactiveStudents();
        $this->printjson($this->jsonarr);
    }
    public function deleteCompletedClass()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->deleteCompletedClass();
        $this->printjson($this->jsonarr);
    }

    public function inboxCron()
    {
        $this->load->database();
        $this->load->model('cron_model');
        $this->jsonarr = $this->cron_model->inboxCron();
        $this->printjson($this->jsonarr);
    }
}
