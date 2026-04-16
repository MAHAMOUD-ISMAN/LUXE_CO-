<?php
require_once 'layout.php';
if(!isLoggedIn()) redirect('login.php');
$db=db(); $uid=currentUserId();

// Fetch user info
$uStmt=$db->prepare("SELECT * FROM users WHERE id=?"); $uStmt->execute([$uid]); $userInfo=$uStmt->fetch();

$stmt=$db->prepare("SELECT c.qty,p.* FROM cart c JOIN products p ON p.id=c.product_id WHERE c.user_id=?");
$stmt->execute([$uid]); $items=$stmt->fetchAll();
if(empty($items)) redirect('cart.php');
$subtotal=array_sum(array_map(fn($i)=>$i['price']*$i['qty'],$items));
$shipping=$subtotal>=500?0:20; $total=$subtotal+$shipping;

pageHeader('Paiement');
?>
<div class="page-wrap">

<!-- Checkout header -->
<div style="background:rgba(139,92,246,.04);border-bottom:1px solid var(--border);padding:18px 40px">
  <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:16px">
    <a href="index.php" style="font-family:var(--display);font-size:18px;font-weight:800;color:var(--white);letter-spacing:-.03em;text-decoration:none">LUXE<em style="font-style:normal;background:linear-gradient(135deg,#8b5cf6,#06b6d4);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent">.CO</em></a>
    <div style="display:flex;align-items:center;gap:8px;margin-left:auto">
      <?php foreach([['1','Panier','done'],['2','Livraison','active'],['3','Paiement','pending']] as [$n,$l,$s]): ?>
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--display);font-size:10px;font-weight:700;
          <?= $s==='done'?'background:linear-gradient(135deg,#7c3aed,#0ea5e9);color:#fff;':($s==='active'?'background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.5);color:#a78bfa;':'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#475569;') ?>">
          <?= $s==='done'?'✓':$n ?>
        </div>
        <span style="font-size:11px;font-weight:600;color:<?= $s==='active'?'#a78bfa':($s==='done'?'#94a3b8':'#475569') ?>"><?= $l ?></span>
      </div>
      <?php if($n<3): ?><div style="width:24px;height:1px;background:<?= $s==='done'?'rgba(139,92,246,.5)':'var(--border)' ?>"></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="container checkout-wrap">
  <div class="checkout-grid">

    <!-- LEFT: Forms -->
    <div>

      <!-- Test mode -->
      <div class="test-mode-bar">
        <div class="test-mode-icon">🧪</div>
        <div>
          <div class="test-mode-label">Mode Test Stripe — Aucun vrai débit</div>
          <div class="test-mode-sub">Utilisez les cartes de test ci-dessous</div>
        </div>
        <div style="margin-left:auto;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.2);border-radius:20px;padding:3px 10px;font-size:9px;color:var(--violet-l);font-weight:700">STRIPE TEST</div>
      </div>
      <div class="test-cards-grid" style="margin-bottom:22px">
        <?php foreach([
          ['Visa','4242 4242 4242 4242','Succès','success'],
          ['Mastercard','5555 5555 5555 4444','Succès','success'],
          ['Refus','4000 0000 0000 0002','Refusée','fail'],
          ['3D Secure','4000 0025 0000 3155','Auth 3D','fail']
        ] as [$b,$n,$r,$t]): ?>
        <div class="test-card-item <?= $t ?>">
          <div class="test-card-brand"><?= $b ?></div>
          <div class="test-card-num" onclick="copyCard(this)" style="cursor:pointer;letter-spacing:.05em" title="Cliquer pour copier"><?= $n ?></div>
          <div class="test-card-result"><?= $r ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Informations de contact -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon">👤</div>
          <div class="form-card-title">Informations de contact</div>
        </div>
        <div class="form-card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Prénom</label>
              <input type="text" id="first-name" placeholder="Jean" value="<?= e(explode(' ',$userInfo['name']??'')[0]) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Nom</label>
              <input type="text" id="last-name" placeholder="Dupont" value="<?= e(implode(' ',array_slice(explode(' ',$userInfo['name']??''),1))) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="checkout-email" placeholder="votre@email.com"
                   value="<?= e($userInfo['email']??$_SESSION['user_email']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Téléphone</label>
            <input type="tel" id="phone" placeholder="+33 6 00 00 00 00">
          </div>
        </div>
      </div>

      <!-- Adresse de livraison -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon">📦</div>
          <div class="form-card-title">Adresse de livraison</div>
        </div>
        <div class="form-card-body">
          <div class="form-group">
            <label class="form-label">Adresse</label>
            <input type="text" id="address" placeholder="123 Rue de la Paix">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Ville</label>
              <input type="text" id="city" placeholder="Paris">
            </div>
            <div class="form-group">
              <label class="form-label">Code postal</label>
              <input type="text" id="zip" placeholder="75001">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Pays</label>
            <select id="country">
              <option value="FR">France</option>
              <option value="DJ">Djibouti</option>
              <option value="BE">Belgique</option>
              <option value="CH">Suisse</option>
              <option value="CA">Canada</option>
              <option value="MA">Maroc</option>
              <option value="SN">Sénégal</option>
              <option value="CI">Côte d'Ivoire</option>
            </select>
          </div>

          <!-- Options de livraison -->
          <div style="margin-top:8px">
            <label class="form-label">Mode de livraison</label>
            <div style="display:flex;flex-direction:column;gap:8px">
              <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.3);border-radius:8px;cursor:pointer">
                <input type="radio" name="shipping_method" value="express" checked style="width:auto;accent-color:#8b5cf6">
                <div style="flex:1">
                  <div style="font-family:var(--display);font-size:13px;font-weight:600;color:var(--white)">🚀 Express 24-48h</div>
                  <div style="font-size:11px;color:var(--text-2);margin-top:2px">Livraison garantie en 24 à 48 heures</div>
                </div>
                <div style="font-family:var(--display);font-weight:700;color:<?= $shipping==0?'#34d399':'var(--white)' ?>">
                  <?= $shipping==0?'Offerte ✓':number_format($shipping,2,',',' ').' €' ?>
                </div>
              </label>
              <label style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;cursor:pointer">
                <input type="radio" name="shipping_method" value="standard" style="width:auto;accent-color:#8b5cf6">
                <div style="flex:1">
                  <div style="font-family:var(--display);font-size:13px;font-weight:600;color:var(--white)">📫 Standard 3-5j</div>
                  <div style="font-size:11px;color:var(--text-2);margin-top:2px">Livraison postale en 3 à 5 jours ouvrés</div>
                </div>
                <div style="font-family:var(--display);font-weight:700;color:var(--white)">Gratuite</div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Paiement -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-icon">💳</div>
          <div class="form-card-title">Paiement sécurisé</div>
        </div>
        <div class="form-card-body">
          <div id="card-error" style="display:none;margin-bottom:14px;padding:10px 14px;background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.2);border-radius:8px;font-size:12px;color:#f87171"></div>
          <div class="form-group">
            <label class="form-label">Numéro de carte</label>
            <div id="card-number" class="stripe-wrap"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Expiration</label><div id="card-expiry" class="stripe-wrap"></div></div>
            <div class="form-group"><label class="form-label">CVC</label><div id="card-cvc" class="stripe-wrap"></div></div>
          </div>
          <button id="pay-btn" class="pay-button">
            🔒 Payer <?= number_format($total,2,',',' ') ?> €
          </button>
          <div class="ssl-strip">
            <span>🔒 SSL 256-bit</span>
            <span>✦ Stripe Certified</span>
            <span>🛡 3D Secure</span>
          </div>
        </div>
      </div>

    </div><!-- /LEFT -->

    <!-- RIGHT: Summary -->
    <div class="order-summary fade-up d2">
      <div class="summary-header">
        <span class="summary-title">Récapitulatif</span>
        <span class="summary-count"><?= count($items) ?> article<?= count($items)>1?'s':'' ?></span>
      </div>
      <div class="summary-items">
        <?php foreach($items as $item): ?>
        <div class="summary-item">
          <div class="summary-item-img"><?= productImage($item) ?></div>
          <div style="flex:1">
            <div class="summary-item-name"><?= e($item['name']) ?></div>
            <div class="summary-item-qty">×<?= (int)$item['qty'] ?></div>
          </div>
          <div class="summary-item-price"><?= number_format($item['price']*$item['qty'],2,',',' ') ?> €</div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="summary-totals">
        <div class="summary-row">
          <span class="summary-row-key">Sous-total</span>
          <span><?= number_format($subtotal,2,',',' ') ?> €</span>
        </div>
        <div class="summary-row">
          <span class="summary-row-key">Livraison</span>
          <span class="<?= $shipping==0?'summary-row-free':'' ?>">
            <?= $shipping==0?'Offerte ✓':number_format($shipping,2,',',' ').' €' ?>
          </span>
        </div>
        <?php if($shipping>0): ?>
        <div style="font-size:11px;color:var(--text-3);padding:4px 0">
          Plus que <?= number_format(500-$subtotal,2,',',' ') ?> € pour la livraison offerte
        </div>
        <?php endif; ?>
        <div class="summary-total-row">
          <span class="summary-total-label">Total TTC</span>
          <span class="summary-total-amount"><?= number_format($total,2,',',' ') ?> €</span>
        </div>
      </div>
      <!-- Garanties -->
      <div style="padding:14px 20px;border-top:1px solid var(--border)">
        <?php foreach([['🔒','Paiement 100% sécurisé'],['🔄','Retours gratuits 30j'],['📦','Livraison suivie'],['✦','Produits authentiques']] as [$ic,$lb]): ?>
        <div style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-2);padding:4px 0">
          <span style="font-size:13px"><?=$ic?></span><?=$lb?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe = Stripe('<?= STRIPE_PK ?>');
const elements = stripe.elements({
  appearance:{
    theme:'night',
    variables:{
      colorPrimary:'#8b5cf6',
      colorBackground:'#161b26',
      colorText:'#f1f5f9',
      colorDanger:'#f87171',
      fontFamily:'Inter,sans-serif',
      borderRadius:'8px',
      spacingUnit:'4px'
    }
  }
});
const cardNumber = elements.create('cardNumber'); cardNumber.mount('#card-number');
const cardExpiry = elements.create('cardExpiry'); cardExpiry.mount('#card-expiry');
const cardCvc    = elements.create('cardCvc');    cardCvc.mount('#card-cvc');

[cardNumber,cardExpiry,cardCvc].forEach(el=>{
  el.on('change',e=>{
    const err=document.getElementById('card-error');
    if(e.error){ err.style.display='block'; err.textContent=e.error.message; }
    else err.style.display='none';
  });
});

document.getElementById('pay-btn').addEventListener('click', async()=>{
  const btn=document.getElementById('pay-btn');
  const errBox=document.getElementById('card-error');

  // Validation des champs obligatoires
  const email=document.getElementById('checkout-email')?.value.trim();
  const address=document.getElementById('address')?.value.trim();
  const city=document.getElementById('city')?.value.trim();
  const zip=document.getElementById('zip')?.value.trim();
  if(!email||!address||!city||!zip){
    errBox.style.display='block';
    errBox.textContent='⚠ Veuillez remplir tous les champs de livraison.';
    return;
  }

  btn.disabled=true; btn.textContent='⏳ Traitement en cours...';

  // Créer le Payment Intent
  const r1=await fetch('api/payment.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=create_intent&csrf=<?= csrf() ?>`
  });
  const d1=await r1.json();
  if(!d1.success){
    btn.disabled=false; btn.textContent='🔒 Payer <?= number_format($total,2,',',' ') ?> €';
    errBox.style.display='block'; errBox.textContent=d1.error; return;
  }

  // Confirmer le paiement
  const{error,paymentIntent}=await stripe.confirmCardPayment(d1.client_secret,{
    payment_method:{card:cardNumber,billing_details:{name:document.getElementById('first-name')?.value+' '+document.getElementById('last-name')?.value,email:email,address:{line1:address,city:city,postal_code:zip,country:document.getElementById('country')?.value||'FR'}}}
  });
  if(error){
    btn.disabled=false; btn.textContent='🔒 Réessayer';
    errBox.style.display='block'; errBox.textContent=error.message; return;
  }

  // Confirmer côté serveur
  const r2=await fetch('api/payment.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`action=confirm&payment_intent_id=${paymentIntent.id}&csrf=<?= csrf() ?>`
  });
  const d2=await r2.json();
  if(d2.success) window.location.href='payment_success.php?order_id='+d2.order_id;
  else{
    btn.disabled=false; btn.textContent='🔒 Réessayer';
    errBox.style.display='block'; errBox.textContent=d2.error||'Erreur serveur.';
  }
});

function copyCard(el){
  navigator.clipboard.writeText(el.textContent.trim()).then(()=>{
    const orig=el.style.color; el.style.color='#34d399';
    setTimeout(()=>el.style.color=orig,1500);
  });
}
</script>
<?php pageFooter(); ?>
