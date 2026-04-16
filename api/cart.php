<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
if(($_POST['csrf']??'')!==($_SESSION['csrf']??'')) { echo json_encode(['success'=>false,'error'=>'CSRF']); exit; }
if(!isLoggedIn()) { header('Location: ../login.php'); exit; }
$action=$_POST['action']??''; $db=db(); $uid=currentUserId();
if($action==='add'){
    $pid=(int)($_POST['product_id']??0); $qty=(int)($_POST['qty']??1);
    $stmt=$db->prepare("SELECT stock FROM products WHERE id=?"); $stmt->execute([$pid]); $p=$stmt->fetch();
    if(!$p||$p['stock']<1) { if(!empty($_POST['redirect'])) redirect('../'.ltrim($_POST['redirect'],'/')); redirect('../cart.php'); }
    $db->prepare("INSERT INTO cart(user_id,product_id,qty) VALUES(?,?,?) ON DUPLICATE KEY UPDATE qty=LEAST(qty+?,?)")->execute([$uid,$pid,$qty,$qty,$p['stock']]);
    if(!empty($_POST['redirect'])) redirect('../'.ltrim($_POST['redirect'],'/'));
    redirect('../cart.php');
}
if($action==='update'){
    $pid=(int)($_POST['product_id']??0); $qty=(int)($_POST['qty']??1);
    if($qty<1) $db->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$uid,$pid]);
    else $db->prepare("UPDATE cart SET qty=? WHERE user_id=? AND product_id=?")->execute([$qty,$uid,$pid]);
    header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
}
if($action==='remove'){
    $pid=(int)($_POST['product_id']??0);
    $db->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")->execute([$uid,$pid]);
    header('Content-Type: application/json'); echo json_encode(['success'=>true]); exit;
}
redirect('../cart.php');
