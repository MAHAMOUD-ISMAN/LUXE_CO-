<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
header('Content-Type: application/json');
if(($_POST['csrf']??'')!==($_SESSION['csrf']??'')){ echo json_encode(['success'=>false,'error'=>'Session expirée.']); exit; }
if(!isLoggedIn()){ echo json_encode(['success'=>false,'error'=>'Non connecté.']); exit; }
$action=$_POST['action']??''; $db=db(); $uid=currentUserId();
try { $db->exec("CREATE TABLE IF NOT EXISTS payments(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,user_id INT NOT NULL,stripe_pi_id VARCHAR(200) DEFAULT NULL,amount DECIMAL(10,2) NOT NULL,currency VARCHAR(3) DEFAULT 'eur',status ENUM('pending','succeeded','failed','refunded') DEFAULT 'pending',card_brand VARCHAR(20) DEFAULT NULL,card_last4 VARCHAR(4) DEFAULT NULL,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(PDOException $e){}
if($action==='create_intent'){
    $stmt=$db->prepare("SELECT COALESCE(SUM(c.qty*p.price),0) AS subtotal FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?");
    $stmt->execute([$uid]); $subtotal=(float)$stmt->fetchColumn();
    if($subtotal<=0){ echo json_encode(['success'=>false,'error'=>'Panier vide.']); exit; }
    $shipping=$subtotal>=500?0:20; $total=$subtotal+$shipping; $cents=(int)round($total*100);
    $response=stripePost('payment_intents',['amount'=>$cents,'currency'=>CURRENCY,'metadata[user_id]'=>$uid,'automatic_payment_methods[enabled]'=>'true']);
    if(isset($response['error'])){ echo json_encode(['success'=>false,'error'=>'Stripe: '.($response['error']['message']??'Erreur')]); exit; }
    $_SESSION['pending_pi_id']=$response['id']; $_SESSION['pending_amount']=$total;
    echo json_encode(['success'=>true,'client_secret'=>$response['client_secret']]); exit;
}
if($action==='confirm'){
    $piId=trim($_POST['payment_intent_id']??'');
    if(!$piId||$piId!==($_SESSION['pending_pi_id']??'')){ echo json_encode(['success'=>false,'error'=>'PI invalide.']); exit; }
    $pi=stripeGet('payment_intents/'.$piId);
    if(isset($pi['error'])){ echo json_encode(['success'=>false,'error'=>'Stripe: '.($pi['error']['message']??'')]); exit; }
    if($pi['status']!=='succeeded'){ echo json_encode(['success'=>false,'error'=>'Statut: '.$pi['status']]); exit; }
    $cardBrand=null; $cardLast4=null;
    if(!empty($pi['payment_method'])){ $pm=stripeGet('payment_methods/'.$pi['payment_method']); $cardBrand=$pm['card']['brand']??null; $cardLast4=$pm['card']['last4']??null; }
    $stmt=$db->prepare("SELECT c.qty,p.price,p.id AS product_id FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?");
    $stmt->execute([$uid]); $cartItems=$stmt->fetchAll();
    if(empty($cartItems)){ echo json_encode(['success'=>false,'error'=>'Panier vide.']); exit; }
    $subtotal=array_sum(array_map(fn($i)=>(float)$i['price']*(int)$i['qty'],$cartItems));
    $total=$subtotal+($subtotal>=500?0:20);
    try {
        $db->beginTransaction();
        $db->prepare("INSERT INTO orders(user_id,total,status)VALUES(?,?,'confirmed')")->execute([$uid,$total]);
        $orderId=(int)$db->lastInsertId(); if(!$orderId) throw new RuntimeException("lastInsertId=0");
        $db->prepare("INSERT INTO payments(order_id,user_id,stripe_pi_id,amount,currency,status,card_brand,card_last4)VALUES(?,?,?,?,?,'succeeded',?,?)")->execute([$orderId,$uid,$piId,$total,CURRENCY,$cardBrand,$cardLast4]);
        $st=$db->prepare("UPDATE products SET stock=GREATEST(0,stock-?) WHERE id=?");
        foreach($cartItems as $i) $st->execute([(int)$i['qty'],(int)$i['product_id']]);
        $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$uid]);
        $db->commit();
    } catch(Throwable $e){ try{$db->rollBack();}catch(Throwable $x){} echo json_encode(['success'=>false,'error'=>'DB: '.$e->getMessage()]); exit; }
    unset($_SESSION['pending_pi_id'],$_SESSION['pending_amount']);
    echo json_encode(['success'=>true,'order_id'=>$orderId]); exit;
}
echo json_encode(['success'=>false,'error'=>'Action inconnue.']);
