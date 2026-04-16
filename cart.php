<?php
require_once 'layout.php';
$db=db(); $uid=currentUserId();
$items=$db->prepare("SELECT c.qty,p.* FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?");
$items->execute([$uid]); $items=$items->fetchAll();
$subtotal=array_sum(array_map(fn($i)=>$i['price']*$i['qty'],$items));
$shipping=$subtotal>=500?0:20; $total=$subtotal+$shipping;
pageHeader('Panier');
?>
<div class="page-wrap">
<div class="container cart-wrap">
  <h1 class="cart-title">Mon Panier</h1>
  <?php if(empty($items)): ?>
  <div class="empty-state"><div class="empty-icon">🛍️</div><div class="empty-title">Votre panier est vide</div><a href="catalog.php" class="btn btn-primary">Explorer le catalogue</a></div>
  <?php else: ?>
  <div class="cart-grid">
    <div>
      <?php foreach($items as $item): ?>
      <div class="cart-item">
        <div class="cart-thumb"><?= productImage($item) ?></div>
        <div style="flex:1">
          <div style="font-size:9px;color:var(--text-3);letter-spacing:.1em;text-transform:uppercase;margin-bottom:3px;font-weight:600"><?= e($item['category']) ?></div>
          <div style="font-family:var(--display);font-weight:600;margin-bottom:6px;color:var(--white)"><?= e($item['name']) ?></div>
          <div style="font-family:var(--display);font-size:17px;font-weight:700;color:var(--white)"><?= number_format($item['price'],0,',',' ') ?> €</div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px">
          <div class="qty-control">
            <button class="qty-btn" onclick="updateCart(<?= (int)$item['id'] ?>,<?= max(1,$item['qty']-1) ?>)">−</button>
            <span style="width:32px;text-align:center;font-size:13px;font-weight:600"><?= (int)$item['qty'] ?></span>
            <button class="qty-btn" onclick="updateCart(<?= (int)$item['id'] ?>,<?= $item['qty']+1 ?>)">+</button>
          </div>
          <button onclick="removeFromCart(<?= (int)$item['id'] ?>)" class="btn btn-danger btn-sm">🗑</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cart-summary">
      <div style="font-family:var(--display);font-size:16px;font-weight:700;color:var(--white);margin-bottom:16px">Récapitulatif</div>
      <div class="cart-row"><span style="color:var(--text-2)">Sous-total</span><span><?= number_format($subtotal,2,',',' ') ?> €</span></div>
      <div class="cart-row"><span style="color:var(--text-2)">Livraison</span><span class="<?= $shipping==0?'stock-ok':'' ?>"><?= $shipping==0?'Offerte':number_format($shipping,2,',',' ').' €' ?></span></div>
      <?php if($shipping>0): ?>
      <div style="font-size:11px;color:var(--text-3);margin-bottom:8px">Plus que <?= number_format(500-$subtotal,2,',',' ') ?> € pour la livraison offerte</div>
      <?php endif; ?>
      <div class="cart-total">
        <span style="font-family:var(--display);font-weight:700;font-size:15px;color:var(--white)">Total</span>
        <span style="font-family:var(--display);font-weight:800;font-size:22px;color:var(--white)"><?= number_format($total,2,',',' ') ?> €</span>
      </div>
      <?php if(isLoggedIn()): ?>
      <a href="checkout.php" class="btn btn-primary btn-full" style="margin-top:16px;padding:14px;font-size:14px">Passer commande →</a>
      <?php else: ?>
      <a href="login.php" class="btn btn-primary btn-full" style="margin-top:16px">Connexion pour commander</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>
<?php pageFooter(); ?>
