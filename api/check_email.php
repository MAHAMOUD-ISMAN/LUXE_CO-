<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
header('Content-Type: application/json');
$email=trim($_GET['email']??'');
if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)){ echo json_encode(['available'=>false]); exit; }
$stmt=db()->prepare("SELECT id FROM users WHERE email=? AND email_verified=1");
$stmt->execute([$email]);
echo json_encode(['available'=>$stmt->fetch()===false]);
