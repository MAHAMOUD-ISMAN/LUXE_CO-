<?php
require_once 'layout.php';
if(!isLoggedIn()) redirect('login.php');
$db=db(); $uid=currentUserId(); $orderId=(int)($_GET['order_id']??0);
$stmt=$db->prepare("SELECT o.*,p.card_brand,p.card_last4,p.amount AS paid_amount FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.id=? AND o.user_id=?");
$stmt->execute([$orderId,$uid]); $order=$stmt->fetch();
if(!$order) redirect('index.php');
$brandIcon=['visa'=>'💙','mastercard'=>'🔴','amex'=>'💚'][$order['card_brand']??'']??'💳';
pageHeader('Commande Confirmée');
?>
<div class="page-wrap">
<div class="success-wrap">
  <div style="position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center">
    <?php for($i=1;$i<=3;$i++): ?>
    <div style="position:absolute;width:<?=$i*200?>px;height:<?=$i*200?>px;border-radius:50%;border:1px solid rgba(16,185,129,<?=.05/$i?>);animation:ringExp <?=2+$i*.5?>s ease-out <?=$i*.2?>s both"></div>
    <?php endfor; ?>
  </div>
  <div class="success-card">
    <div class="success-top">
      <div class="success-icon">
        <svg class="success-icon-svg" viewBox="0 0 44 44"><polyline class="success-checkmark" points="8,22 18,32 36,14"/></svg>
        <div class="success-icon-ring"></div>
      </div>
      <div class="success-eyebrow">Paiement Confirmé</div>
      <h1 class="success-h1">Merci pour votre commande !</h1>
      <p class="success-sub">Commande <strong style="color:var(--white)">#<?= str_pad($orderId,5,'0',STR_PAD_LEFT) ?></strong> validée. Livraison sous 48h.</p>
    </div>
    <div class="success-details">
      <?php foreach([['🧾','Commande','#'.str_pad($orderId,5,'0',STR_PAD_LEFT)],['📅','Date',date('d/m/Y à H:i',strtotime($order['created_at']))],['💰','Montant',number_format($order['paid_amount'],2,',',' ').' €','price'],[$brandIcon,'Carte',$order['card_brand']?ucfirst($order['card_brand']).' ····'.$order['card_last4']:'—'],['📦','Statut','En préparation']] as $row): [$icon,$key,$val]=$row; $cls=$row[3]??''; ?>
      <div class="detail-row">
        <div class="detail-key"><span class="detail-key-icon"><?= $icon ?></span><?= $key ?></div>
        <div class="detail-val <?= $cls ?>"><?= e($val) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="success-actions">
      <a href="catalog.php" class="btn btn-primary btn-full">Continuer mes achats →</a>
      <a href="profile.php" class="btn btn-ghost btn-full">Voir mes commandes</a>
    </div>
  </div>
</div>
</div>
<div class="confetti-container" id="confetti"></div>
<script>
(function(){const c=document.getElementById('confetti');if(!c)return;const colors=['#8b5cf6','#a78bfa','#06b6d4','#10b981','#f59e0b','#f43f5e'];for(let i=0;i<90;i++){const el=document.createElement('div');const col=colors[i%colors.length];const size=5+Math.random()*8;Object.assign(el.style,{position:'absolute',width:size+'px',height:size+'px',background:col,left:Math.random()*100+'%',top:'-20px',opacity:'.9',borderRadius:Math.random()>.5?'50%':'2px',animation:`confetti ${1.6+Math.random()*2.4}s ${Math.random()*2}s ease-in forwards`,transform:'rotate('+Math.random()*360+'deg)'});c.appendChild(el);}setTimeout(()=>c.remove(),6000);})();
</script>
<?php pageFooter(); ?>
