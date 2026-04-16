<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'layout.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mailer.php';
if(isLoggedIn()) redirect('index.php');

// Création automatique des colonnes OTP si absentes
(function(){
    $db=db();
    $cols=$db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('email_verified',$cols)) $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER role");
    if(!in_array('verify_token',$cols))   $db->exec("ALTER TABLE users ADD COLUMN verify_token VARCHAR(64) DEFAULT NULL AFTER email_verified");
    if(!in_array('verify_expires',$cols)) $db->exec("ALTER TABLE users ADD COLUMN verify_expires DATETIME DEFAULT NULL AFTER verify_token");
    $db->exec("UPDATE users SET email_verified=1 WHERE email_verified=0 AND email LIKE '%@demo.com'");
})();

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $name   =trim($_POST['name']    ??'');
    $email  =trim($_POST['email']   ??'');
    $pw     =$_POST['password']     ??'';
    $confirm=$_POST['confirm']      ??'';

    if(!$name||!$email||!$pw)
        $error='Tous les champs sont obligatoires.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))
        $error='Format email invalide.';
    elseif(strlen($pw)<6)
        $error='Mot de passe trop court (6 caractères minimum).';
    elseif($pw!==$confirm)
        $error='Les mots de passe ne correspondent pas.';
    else{
        $db=db();
        $check=$db->prepare("SELECT id,email_verified FROM users WHERE email=?");
        $check->execute([$email]); $existing=$check->fetch();

        if($existing&&$existing['email_verified']){
            $error='Cet email est déjà utilisé.';
        } else {
            $code   =str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
            $token  =hash('sha256',$code.$email);
            $expires=time()+(15*60);

            if($existing){
                $db->prepare("UPDATE users SET verify_token=?,verify_expires=FROM_UNIXTIME(?) WHERE email=?")
                   ->execute([$token,$expires,$email]);
            } else {
                $hash=password_hash($pw,PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO users(name,email,password,role,email_verified,verify_token,verify_expires) VALUES(?,?,?,'user',0,?,FROM_UNIXTIME(?))")
                   ->execute([$name,$email,$hash,$token,$expires]);
            }

            $sent=sendMail($email,'Votre code de vérification LUXE.CO',
                "Bonjour $name,\n\n"
               ."Votre code de vérification est :\n\n"
               ."    $code\n\n"
               ."Ce code est valable 15 minutes.\n\n"
               ."Si vous n'avez pas demandé ce code, ignorez cet email.\n\n"
               ."— L'équipe LUXE.CO");

            if(!$sent){
                $error='Impossible d\'envoyer l\'email. Vérifiez la configuration SMTP dans mailer.php.';
            } else {
                $_SESSION['pending_email']=$email;
                redirect('verify.php');
            }
        }
    }
}
pageHeader('Inscription');
?>
<div class="page-wrap">
<div class="auth-page">
  <div class="auth-card fade-up">
    <div class="auth-brand">
      <div class="logo">LUXE<em>.CO</em></div>
      <h2>Créez votre compte</h2>
      <p>Rejoignez notre communauté privilège</p>
    </div>
    <div class="auth-body">

      <?php if($error): ?>
      <div class="alert alert-error">⚠ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">

        <div class="form-group">
          <label class="form-label">Nom complet</label>
          <input type="text" name="name" placeholder="Prénom Nom" required value="<?= e($_POST['name']??'') ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="email-input" placeholder="votre@email.com" required value="<?= e($_POST['email']??'') ?>">
          <div id="email-status" style="font-size:11px;margin-top:5px;min-height:16px;font-weight:500"></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="password" placeholder="••••••••" required minlength="6">
          </div>
          <div class="form-group">
            <label class="form-label">Confirmer</label>
            <input type="password" name="confirm" placeholder="••••••••" required>
          </div>
        </div>

        <div style="background:rgba(139,92,246,.07);border:1px solid rgba(139,92,246,.18);border-radius:8px;padding:10px 14px;font-size:12px;color:rgba(167,139,250,.85);margin-bottom:16px;line-height:1.6">
          ● Un <strong style="color:#a78bfa">code à 6 chiffres</strong> sera envoyé à votre email pour confirmer qu'il est réel.
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="padding:13px;font-size:14px">
          Continuer →
        </button>
      </form>

    </div>
    <div class="auth-footer">
      Déjà membre ? <a href="login.php">Se connecter</a>
    </div>
  </div>
</div>
</div>

<script>
(function(){
  const input=document.getElementById('email-input');
  const status=document.getElementById('email-status');
  let timer=null;
  function check(email){
    if(!email||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){status.textContent='';return;}
    status.innerHTML='<span style="color:var(--text-3)">⏳ Vérification...</span>';
    fetch('api/check_email.php?email='+encodeURIComponent(email))
      .then(r=>r.json()).then(d=>{
        if(email!==input.value.trim())return;
        if(!d.available){status.innerHTML='<span style="color:#f87171">✗ Email déjà utilisé</span>';input.style.borderColor='rgba(244,63,94,.5)';}
        else{status.innerHTML='<span style="color:#34d399">✓ Email disponible</span>';input.style.borderColor='rgba(16,185,129,.5)';}
      }).catch(()=>{status.textContent='';});
  }
  input?.addEventListener('input',()=>{input.style.borderColor='';status.textContent='';clearTimeout(timer);timer=setTimeout(()=>check(input.value.trim()),500);});
  input?.addEventListener('blur',()=>check(input.value.trim()));
})();
</script>
<?php pageFooter(); ?>
