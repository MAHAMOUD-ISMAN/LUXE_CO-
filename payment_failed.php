<?php
require_once 'layout.php';
pageHeader('Paiement échoué');
?>
<div class="page-wrap">
<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 20px">
  <div style="text-align:center;max-width:400px">
    <div style="font-size:56px;margin-bottom:16px">❌</div>
    <h1 style="font-family:var(--display);font-size:28px;font-weight:800;color:var(--white);margin-bottom:10px">Paiement échoué</h1>
    <p style="color:var(--text-2);margin-bottom:24px">Votre paiement n'a pas pu être traité. Vérifiez vos informations et réessayez.</p>
    <a href="checkout.php" class="btn btn-primary">Réessayer</a>
  </div>
</div>
</div>
<?php pageFooter(); ?>
