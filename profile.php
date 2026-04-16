<?php
require_once 'layout.php';
require_once 'recommend.php';
if(!isLoggedIn()) redirect('login.php');
$db=db(); $uid=currentUserId();
$user=$db->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$uid]); $user=$user->fetch();
$orders=$db->prepare("SELECT o.*,COALESCE(p.amount,o.total) AS paid FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE o.user_id=? ORDER BY o.created_at DESC"); $orders->execute([$uid]); $orders=$orders->fetchAll();
$inters=$db->prepare("SELECT i.*,p.* FROM interactions i JOIN products p ON p.id=i.product_id WHERE i.user_id=? ORDER BY i.score DESC LIMIT 6"); $inters->execute([$uid]); $inters=$inters->fetchAll();
$recs=getRecommendations($uid,4);
pageHeader('Mon Compte','profile');
?>
<div class="page-wrap">
<div class="profile-header">
  <div class="container">
    <div style="display:flex;align-items:center;gap:20px">
      <div class="profile-avatar"><?= mb_strtoupper(mb_substr($user['name'],0,1)) ?></div>
      <div>
        <div style="font-family:var(--display);font-size:24px;font-weight:800;color:var(--white);letter-spacing:-.02em"><?= e($user['name']) ?></div>
        <div style="color:var(--text-3);font-size:13px;margin-top:3px"><?= e($user['email']) ?></div>
        <div style="margin-top:6px"><span class="tag"><?= $user['role']==='admin'?'Admin':'Membre' ?></span></div>
      </div>
    </div>
  </div>
</div>
<div class="container" style="padding-top:32px;padding-bottom:60px">
  <div class="profile-grid">
    <!-- Commandes -->
    <div>
      <div style="font-family:var(--display);font-size:16px;font-weight:700;color:var(--white);margin-bottom:16px">Mes Commandes (<?= count($orders) ?>)</div>
      <?php if(empty($orders)): ?>
      <div style="color:var(--text-3);font-size:13px;padding:20px 0">Aucune commande pour l'instant.</div>
      <?php else: ?>
      <div style="background:var(--card);border:1px solid var(--border-2);border-radius:var(--r-md);overflow:hidden">
        <table class="order-table">
          <thead><tr><th>N°</th><th>Date</th><th>Montant</th><th>Statut</th></tr></thead>
          <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
              <td style="font-family:var(--display);font-weight:600;color:var(--white)">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></td>
              <td style="color:var(--text-2)"><?= date('d/m/Y',strtotime($o['created_at'])) ?></td>
              <td style="font-family:var(--display);font-weight:700;color:var(--white)"><?= number_format($o['paid'],2,',',' ') ?> €</td>
              <td><span class="tag tag-green" style="font-size:9px"><?= e($o['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
    <!-- Interactions + Recos -->
    <div>
      <div style="font-family:var(--display);font-size:16px;font-weight:700;color:var(--white);margin-bottom:16px">Produits Consultés</div>
      <div class="inter-grid" style="margin-bottom:24px">
        <?php foreach($inters as $i): ?>
        <div class="inter-card" onclick="location.href='product.php?id=<?= $i['product_id'] ?>'" style="cursor:pointer">
          <div style="height:70px;border-radius:6px;overflow:hidden;margin-bottom:7px"><?= productImage($i) ?></div>
          <div style="font-size:11px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($i['name']) ?></div>
          <div style="font-size:10px;color:var(--text-3)">Score: <?= (int)$i['score'] ?>/5</div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="font-family:var(--display);font-size:16px;font-weight:700;color:var(--white);margin-bottom:12px">🤖 Recommandé pour vous</div>
      <div class="reco-mini-grid">
        <?php foreach($recs as $r): ?>
        <div class="reco-mini-card" onclick="location.href='product.php?id=<?= $r['id'] ?>'">
          <div style="width:44px;height:44px;border-radius:6px;overflow:hidden;flex-shrink:0"><?= productImage($r) ?></div>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text)"><?= e(mb_substr($r['name'],0,20)) ?>...</div>
            <div style="font-family:var(--display);font-size:13px;font-weight:700;color:var(--white)"><?= number_format($r['price'],0,',',' ') ?> €</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
</div>
<?php pageFooter(); ?>
