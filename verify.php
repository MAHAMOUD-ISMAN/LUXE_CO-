<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'layout.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mailer.php';
if(isLoggedIn()) redirect('index.php');

$pendingEmail=$_SESSION['pending_email']??'';
if(!$pendingEmail) redirect('register.php');

$error=''; $success='';

// Renvoi du code
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['resend'])){
    verifyCsrf();
    $db=db();
    $stmt=$db->prepare("SELECT name FROM users WHERE email=? AND email_verified=0");
    $stmt->execute([$pendingEmail]); $user=$stmt->fetch();
    if($user){
        $code   =str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
        $token  =hash('sha256',$code.$pendingEmail);
        $expires=time()+(15*60);
        $db->prepare("UPDATE users SET verify_token=?,verify_expires=FROM_UNIXTIME(?) WHERE email=?")
           ->execute([$token,$expires,$pendingEmail]);
        $sent=sendMail($pendingEmail,'Nouveau code LUXE.CO',
            "Bonjour {$user['name']},\n\nVotre nouveau code de vérification :\n\n    $code\n\nValable 15 minutes.\n\n— LUXE.CO");
        $success=$sent?'Nouveau code envoyé. Vérifiez votre boîte email (et vos spams).':'Erreur d\'envoi. Vérifiez la config SMTP.';
    }
}

// Vérification du code
elseif($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $code=trim(preg_replace('/\D/','',$_POST['code']??''));
    if(strlen($code)!==6){
        $error='Le code doit contenir exactement 6 chiffres.';
    } else {
        $db=db();
        $token=hash('sha256',$code.$pendingEmail);
        $stmt=$db->prepare("SELECT *,UNIX_TIMESTAMP(verify_expires) AS expires_ts FROM users WHERE email=? AND email_verified=0 AND verify_token=?");
        $stmt->execute([$pendingEmail,$token]); $user=$stmt->fetch();

        if(!$user){
            // Distinguer "code incorrect" de "pas de compte"
            $exists=$db->prepare("SELECT id FROM users WHERE email=? AND email_verified=0");
            $exists->execute([$pendingEmail]);
            if($exists->fetch()) $error='Code incorrect. Vérifiez les 6 chiffres reçus dans votre email.';
            else redirect('register.php');
        } elseif((int)$user['expires_ts']<time()){
            $error='Code expiré. Cliquez sur "Renvoyer un code" pour en obtenir un nouveau.';
        } else {
            // ✅ Succès — activer le compte
            $db->prepare("UPDATE users SET email_verified=1,verify_token=NULL,verify_expires=NULL WHERE id=?")
               ->execute([$user['id']]);
            unset($_SESSION['pending_email']);
            $_SESSION['user_id']   =(int)$user['id'];
            $_SESSION['user_name'] =$user['name'];
            $_SESSION['role']      =$user['role'];
            $_SESSION['user_email']=$user['email'];
            redirect('index.php');
        }
    }
}

// Masquer l'email : mahamoud@gmail.com → ma*****@gmail.com
$parts  =explode('@',$pendingEmail);
$masked =mb_substr($parts[0],0,2).str_repeat('*',max(3,mb_strlen($parts[0])-2));
$maskedEmail=$masked.'@'.($parts[1]??'');

pageHeader('Vérification Email');
?>
<div class="page-wrap">
<div class="auth-page">
  <div class="auth-card fade-up" style="max-width:460px">
    <div class="auth-brand">
      <div style="font-size:36px;margin-bottom:8px;filter:drop-shadow(0 0 12px rgba(139,92,246,.4))">📧</div>
      <div class="logo">LUXE<em>.CO</em></div>
      <h2>Vérifiez votre email</h2>
      <p>Code envoyé à <strong style="color:#a78bfa"><?= e($maskedEmail) ?></strong></p>
    </div>
    <div class="auth-body">

      <?php if($error): ?>
      <div class="alert alert-error">⚠ <?= e($error) ?></div>
      <?php endif; ?>
      <?php if($success): ?>
      <div class="alert alert-success">✓ <?= e($success) ?></div>
      <?php endif; ?>

      <p style="font-size:12px;color:var(--text-3);text-align:center;margin-bottom:22px;line-height:1.9">
        Entrez le <strong style="color:var(--text-2)">code à 6 chiffres</strong> reçu dans votre email.<br>
        Vérifiez aussi vos <strong style="color:var(--text-2)">spams</strong> si vous ne le trouvez pas.
      </p>

      <form method="POST" id="otp-form">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input type="hidden" name="code" id="code-hidden">

        <!-- 6 cases OTP -->
        <div style="display:flex;gap:10px;justify-content:center;margin-bottom:24px">
          <?php for($i=0;$i<6;$i++): ?>
          <input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"
                 class="otp-d"
                 style="width:52px;height:62px;text-align:center;
                        font-family:var(--display);font-size:28px;font-weight:700;
                        color:var(--white);background:rgba(255,255,255,.05);
                        border:1.5px solid var(--border-2);
                        border-radius:10px;padding:0;outline:none;
                        transition:all .2s"
                 autocomplete="off">
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-full" id="otp-btn" disabled
                style="padding:13px;font-size:14px">
          ✦ Confirmer mon email
        </button>
      </form>

      <div style="margin-top:12px">
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <input type="hidden" name="resend" value="1">
          <button type="submit" class="btn btn-ghost btn-full" style="font-size:12px">
            📨 Renvoyer un nouveau code
          </button>
        </form>
      </div>

    </div>
    <div class="auth-footer">
      <a href="register.php">← Retour à l'inscription</a>
    </div>
  </div>
</div>
</div>

<style>
.otp-d:focus  { border-color:#8b5cf6!important; background:rgba(139,92,246,.08)!important; box-shadow:0 0 0 3px rgba(139,92,246,.15)!important; }
.otp-d.filled { border-color:rgba(139,92,246,.5)!important; background:rgba(139,92,246,.06)!important; }
</style>

<script>
(function(){
  const D=Array.from(document.querySelectorAll('.otp-d'));
  const H=document.getElementById('code-hidden');
  const B=document.getElementById('otp-btn');

  function sync(){
    const code=D.map(d=>d.value).join('');
    H.value=code;
    B.disabled=!/^\d{6}$/.test(code);
  }

  D.forEach((d,i)=>{
    // Saisie
    d.addEventListener('input',()=>{
      d.value=d.value.replace(/\D/g,'').slice(-1);
      d.classList.toggle('filled',!!d.value);
      if(d.value&&i<5) D[i+1].focus();
      sync();
    });
    // Retour arrière
    d.addEventListener('keydown',e=>{
      if(e.key==='Backspace'){
        e.preventDefault();
        if(d.value){ d.value=''; d.classList.remove('filled'); sync(); }
        else if(i>0){ D[i-1].value=''; D[i-1].classList.remove('filled'); D[i-1].focus(); sync(); }
      }
      if(e.key==='ArrowLeft'&&i>0)  D[i-1].focus();
      if(e.key==='ArrowRight'&&i<5) D[i+1].focus();
    });
    // Coller un code complet
    d.addEventListener('paste',e=>{
      e.preventDefault();
      const p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
      p.split('').forEach((ch,j)=>{if(D[j]){D[j].value=ch;D[j].classList.add('filled');}});
      D[Math.min(p.length,5)].focus();
      sync();
    });
  });

  // Focus automatique sur le premier champ
  D[0].focus();
})();
</script>
<?php pageFooter(); ?>
