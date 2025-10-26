<?php
date_default_timezone_set("Asia/Calcutta");
header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");
include('../httpful.phar');
$start_time =Date("Y-m-d H:i:s");
$start_time."\n";
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
    $http = "https://";
} else {
    $http = "http://";
}

// prescription completed remainder to patient.

$url=  $http.$_SERVER['HTTP_HOST'].'/';
$prop = parse_ini_file('../properties.ini', true, INI_SCANNER_RAW);
$hostName = $prop['remote_address'];
if($_SERVER['REMOTE_ADDR'] == $hostName) {
    $uri_login = $url.'rista/api/index.php/v1/cron/adminMailNotification';
    $result_login = \Httpful\Request::get($uri_login)
        ->sendsJson()
        ->addHeader("Content-Type", "application/json")
        ->send();
  } else {
        echo "Authentication Failed";
        $result_login = false;
  }

print_r($result_login);
?>
