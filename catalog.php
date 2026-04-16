<?php
require_once 'layout.php';
$db=$db??db();
$cats=$db->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$products=$db->query("SELECT * FROM products ORDER BY id")->fetchAll();
pageHeader('Catalogue','catalog');
?>
<div class="page-wrap">
<div class="catalog-header">
  <div class="container">
    <div class="section-label">Catalogue Complet</div>
    <h1 style="font-family:var(--display);font-size:42px;font-weight:800;letter-spacing:-.03em;margin-bottom:20px;color:var(--white)">Tous nos Produits</h1>
    <div class="filter-row" style="margin-bottom:14px">
      <input id="search" placeholder="Rechercher..." style="max-width:260px">
      <select id="sort" style="max-width:200px">
        <option value="default">Trier...</option>
        <option value="price-asc">Prix croissant</option>
        <option value="price-desc">Prix décroissant</option>
        <option value="rating">Meilleures notes</option>
      </select>
    </div>
    <div class="filter-cats">
      <button class="filter-cat active" data-cat="Tout">Tout</button>
      <?php foreach($cats as $c): ?>
      <button class="filter-cat" data-cat="<?= e($c) ?>"><?= e($c) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<div class="container" style="padding-top:24px;padding-bottom:60px">
  <div style="font-size:12px;color:var(--text-3);margin-bottom:18px;font-weight:500">
    <span id="products-count"><?= count($products) ?></span> produit(s)
  </div>
  <div class="products-grid">
    <?php foreach($products as $p): ?>
    <div class="product-card"
         data-category="<?= e($p['category']) ?>"
         data-name="<?= e($p['name']) ?>"
         data-price="<?= $p['price'] ?>"
         data-rating="<?= $p['rating'] ?>"
         onclick="location.href='product.php?id=<?= (int)$p['id'] ?>'">
      <div class="card-img"><?= productImage($p) ?><div class="card-tag-corner"><?= e($p['tag']) ?></div></div>
      <div class="card-body">
        <div class="card-cat"><?= e($p['category']) ?></div>
        <div class="card-name"><?= e($p['name']) ?></div>
        <div class="card-stars"><span class="stars"><?= stars((float)$p['rating']) ?></span><span class="stars-count">(<?= (int)$p['reviews'] ?>)</span></div>
        <div class="card-footer">
          <span class="card-price"><?= number_format($p['price'],0,',',' ') ?> €</span>
          <button class="btn btn-outline btn-sm" onclick="event.stopPropagation();addToCart(<?= (int)$p['id'] ?>)">+ Panier</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</div>
<?php pageFooter(); ?>
