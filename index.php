<?php
require_once 'layout.php';
require_once 'recommend.php';
$recs = isLoggedIn()
    ? getRecommendations(currentUserId(), 6)
    : db()->query("SELECT * FROM products ORDER BY (rating*reviews) DESC LIMIT 6")->fetchAll();
$recoLabel = isLoggedIn() ? "Recommandé pour vous" : "Nos Coups de Cœur";
$showAI    = isLoggedIn();
pageHeader('Accueil', 'home');
?>
<div class="page-wrap">

<!-- Hero -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-inner">
    <div class="fade-up">
      <div class="hero-label">
        <div class="hero-label-dot"></div>
        ✦ Collection Printemps 2026
      </div>
      <h1>Le Luxe.<br><em class="hero-grad">Redéfini.</em></h1>
      <p class="hero-desc">
        Une curation exceptionnelle pour ceux qui refusent le compromis.
        Des pièces <strong>sélectionnées avec soin</strong>, livrées en 48h.
      </p>
      <div class="hero-btns">
        <a href="catalog.php" class="btn btn-primary btn-xl">
          Explorer le Catalogue
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
        <a href="#recs" class="btn btn-outline btn-xl">Recommandations IA</a>
      </div>
      <ul class="hero-checks">
        <li><div class="check-icon"></div> Produits authentiques garantis</li>
        <li><div class="check-icon"></div> Livraison express 24-48h</li>
        <li><div class="check-icon"></div> Recommandations IA personnalisées</li>
        <li><div class="check-icon"></div> Retours gratuits sous 30 jours</li>
        <li><div class="check-icon"></div> Paiement 100% sécurisé Stripe</li>
      </ul>
    </div>
    <div class="hero-card-wrap fade-up d3">
      <?php $feat = db()->query("SELECT * FROM products ORDER BY (rating*reviews) DESC LIMIT 1")->fetch(); ?>
      <?php if($feat): ?>
      <div class="hero-card">
        <div class="hero-card-img">
          <?= productImage($feat) ?>
          <div class="hero-card-badge">✦ BESTSELLER</div>
        </div>
        <div class="hero-card-body">
          <div class="hero-card-cat"><?= e($feat['category']) ?> · Édition Limitée</div>
          <div class="hero-card-name"><?= e($feat['name']) ?></div>
          <div class="hero-card-price"><?= number_format($feat['price'],0,',',' ') ?> €</div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-scroll"><div class="hero-scroll-line"></div>Défiler</div>
</section>

<!-- Stats -->
<div class="stats-bar">
  <div class="stat-item"><div class="stat-num">20k+</div><div class="stat-label">Clients</div></div>
  <div class="stat-item"><div class="stat-num">500+</div><div class="stat-label">Marques</div></div>
  <div class="stat-item"><div class="stat-num">48h</div><div class="stat-label">Livraison</div></div>
  <div class="stat-item"><div class="stat-num">4.9★</div><div class="stat-label">Satisfaction</div></div>
</div>

<!-- Recommendations -->
<div id="recs" class="section">
  <div class="container">
    <div class="section-header">
      <div>
        <div class="section-label">✦ <?= $showAI ? 'Sélection pour '.e(explode(' ',$_SESSION['user_name'])[0]) : 'Tendances' ?></div>
        <h2 class="section-title"><?= e($recoLabel) ?></h2>
      </div>
      <?php if($showAI): ?>
      <div class="ai-box">
        <strong>🤖 IA Active</strong>
        <span>Analyse de contenu · Similarité cosinus · Profil utilisateur</span>
      </div>
      <?php endif; ?>
    </div>
    <div class="products-grid">
      <?php foreach($recs as $p): ?>
      <div class="product-card"
           onclick="location.href='product.php?id=<?= (int)$p['id'] ?>'">
        <?php if($showAI): ?><div class="card-reco-badge"><span class="tag tag-purple">✦ Reco IA</span></div><?php endif; ?>
        <div class="card-img">
          <?= productImage($p) ?>
          <div class="card-tag-corner"><?= e($p['tag']) ?></div>
        </div>
        <div class="card-body">
          <div class="card-cat"><?= e($p['category']) ?></div>
          <div class="card-name"><?= e($p['name']) ?></div>
          <div class="card-stars">
            <span class="stars"><?= stars((float)$p['rating']) ?></span>
            <span class="stars-count">(<?= (int)$p['reviews'] ?>)</span>
          </div>
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
</div>
<?php pageFooter(); ?>
