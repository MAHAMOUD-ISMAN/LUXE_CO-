<?php
require_once 'layout.php';
require_once 'recommend.php';
$id=(int)($_GET['id']??0); $db=db();
$stmt=$db->prepare("SELECT * FROM products WHERE id=?"); $stmt->execute([$id]); $p=$stmt->fetch();
if(!$p) redirect('catalog.php');
if(isLoggedIn()) trackInteraction(currentUserId(),$id,1);
$stmt=$db->prepare("SELECT * FROM products WHERE category=? AND id!=? LIMIT 3"); $stmt->execute([$p['category'],$id]); $similar=$stmt->fetchAll();
pageHeader($p['name'],'catalog');
?>
<div class="page-wrap">
<div class="container" style="padding-top:44px;padding-bottom:64px">
  <button onclick="history.back()" class="back-link">← Retour</button>
  <div class="product-detail-grid">
    <div style="border-radius:16px;overflow:hidden;height:480px;border:1px solid var(--border-2);background:#0a0c12;position:relative">
      <?= productImage($p,'width:100%;height:100%;object-fit:cover') ?>
      <div style="position:absolute;bottom:0;left:0;right:0;height:100px;background:linear-gradient(to top,rgba(11,14,20,.8),transparent);pointer-events:none"></div>
    </div>
    <div>
      <div style="display:flex;gap:8px;margin-bottom:14px">
        <span class="tag"><?= e($p['category']) ?></span>
        <span class="tag tag-cyan"><?= e($p['tag']) ?></span>
      </div>
      <h1 style="font-family:var(--display);font-size:32px;font-weight:800;letter-spacing:-.03em;margin-bottom:12px;color:var(--white)"><?= e($p['name']) ?></h1>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
        <span style="color:var(--amber);font-size:16px"><?= stars((float)$p['rating']) ?></span>
        <span style="font-size:12px;color:var(--text-3)"><?= $p['rating'] ?> · <?= (int)$p['reviews'] ?> avis</span>
      </div>
      <div class="product-price"><?= number_format($p['price'],0,',',' ') ?> €</div>
      <div style="background:rgba(255,255,255,.04);border-radius:8px;padding:10px 16px;font-size:13px;color:var(--text-2);margin-bottom:22px;border:1px solid var(--border)">
        📦 Stock : <span class="<?= $p['stock']<5?'stock-low':'stock-ok' ?>"><?= (int)$p['stock'] ?> unités</span>
      </div>
      <form action="api/cart.php" method="POST" style="display:flex;gap:12px;align-items:center;margin-bottom:24px">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="redirect" value="product.php?id=<?= $p['id'] ?>">
        <div class="qty-control">
          <button type="button" class="qty-btn" id="qty-dec">−</button>
          <span class="qty-val" id="qty-val" data-max="<?= $p['stock'] ?>">1</span>
          <button type="button" class="qty-btn" id="qty-inc">+</button>
          <input type="hidden" name="qty" id="qty-input" value="1">
        </div>
        <button type="submit" class="btn btn-primary">Ajouter au Panier</button>
      </form>
      <div style="border-top:1px solid var(--border);padding-top:18px">
        <div class="product-tabs">
          <div class="product-tab active" data-tab="desc">Description</div>
          <div class="product-tab" data-tab="details">Détails</div>
          <div class="product-tab" data-tab="livraison">Livraison</div>
        </div>
        <div id="tab-desc" class="tab-content" style="font-size:13px;color:var(--text-2);line-height:1.9"><?= e($p['description']?:$p['name'].' — une pièce d\'exception.') ?></div>
        <div id="tab-details" class="tab-content" style="display:none;font-size:13px;color:var(--text-2);line-height:2">
          <div>🏷 Réf : LXC-<?= str_pad($p['id'],4,'0',STR_PAD_LEFT) ?></div>
          <div>📦 Catégorie : <?= e($p['category']) ?></div>
          <div>✅ Stock : <?= (int)$p['stock'] ?> unités</div>
          <div>⭐ Note : <?= $p['rating'] ?>/5</div>
        </div>
        <div id="tab-livraison" class="tab-content" style="display:none;font-size:13px;color:var(--text-2);line-height:1.9">Livraison express 48h offerte dès 500€. Retours gratuits sous 30 jours.</div>
      </div>
    </div>
  </div>
  <?php if($similar): ?>
  <div style="margin-top:56px">
    <h3 style="font-family:var(--display);font-size:22px;font-weight:700;letter-spacing:-.02em;margin-bottom:20px;color:var(--white)">Vous aimerez aussi</h3>
    <div class="products-grid" style="grid-template-columns:repeat(3,1fr)">
      <?php foreach($similar as $s): ?>
      <div class="product-card" onclick="location.href='product.php?id=<?= (int)$s['id'] ?>'">
        <div class="card-img"><?= productImage($s) ?><div class="card-tag-corner"><?= e($s['tag']) ?></div></div>
        <div class="card-body">
          <div class="card-cat"><?= e($s['category']) ?></div>
          <div class="card-name"><?= e($s['name']) ?></div>
          <div class="card-stars"><span class="stars"><?= stars((float)$s['rating']) ?></span></div>
          <div class="card-footer">
            <span class="card-price"><?= number_format($s['price'],0,',',' ') ?> €</span>
            <button class="btn btn-outline btn-sm" onclick="event.stopPropagation();addToCart(<?= (int)$s['id'] ?>)">+ Panier</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
</div>
<?php pageFooter(); ?>
