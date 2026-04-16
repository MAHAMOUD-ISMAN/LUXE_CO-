<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

function pageHeader(string $title, string $active=''): void {
    $cart = cartCount();
    $user = $_SESSION['user_name'] ?? '';
    $role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — LUXE.CO</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<!-- Announce Bar -->
<div class="announce-bar">
  <span>🚀 Nouveau</span>
  Livraison express 24h — Collection Printemps 2026 disponible
</div>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">LUXE<em>.CO</em></a>
  <div class="navbar-nav">
    <a href="index.php"    class="nav-pill <?= $active==='home'?'active':'' ?>">Accueil</a>
    <a href="catalog.php"  class="nav-pill <?= $active==='catalog'?'active':'' ?>">Catalogue</a>
    <?php if(isAdmin()): ?>
    <a href="admin.php"    class="nav-pill <?= $active==='admin'?'active':'' ?>">Admin</a>
    <?php endif; ?>
    <?php if(isLoggedIn()): ?>
    <a href="profile.php"  class="nav-pill <?= $active==='profile'?'active':'' ?>">Mon Compte</a>
    <?php endif; ?>
  </div>
  <div class="navbar-right">
    <a href="cart.php" class="cart-icon" title="Panier">
      🛍️<?php if($cart>0): ?><div class="cart-badge"><?= $cart ?></div><?php endif; ?>
    </a>
    <?php if(isLoggedIn()): ?>
      <span style="font-size:13px;color:var(--text-2);font-weight:500"><?= e(explode(' ',$user)[0]) ?></span>
      <a href="logout.php" class="btn-nav" style="font-size:12px;padding:6px 14px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-2)">Déconnexion</a>
    <?php else: ?>
      <a href="login.php"    class="btn-nav">Connexion</a>
      <a href="register.php" class="btn-nav btn-nav-primary">S'inscrire</a>
    <?php endif; ?>
  </div>
</nav>
<?php
}

function pageFooter(): void {
?>
<div id="toast" class="toast"></div>
<footer>
  <div class="foot-logo">LUXE<em>.CO</em></div>
  <div class="foot-copy">© 2026 LUXE.CO — Tous droits réservés · Paiement sécurisé Stripe</div>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}
