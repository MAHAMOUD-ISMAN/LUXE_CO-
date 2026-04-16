<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'layout.php';
if(isLoggedIn()) redirect('index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $email=trim($_POST['email']??'');
    $pw   =trim($_POST['password']??'');
    $stmt =db()->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]); $user=$stmt->fetch();
    $ok=$user&&(password_verify($pw,$user['password'])||$pw===$user['password']);

    if($ok&&isset($user['email_verified'])&&!$user['email_verified']){
        // Compte non vérifié → OTP
        $_SESSION['pending_email']=$user['email'];
        redirect('verify.php');
    } elseif($ok){
        $_SESSION['user_id']   =$user['id'];
        $_SESSION['user_name'] =$user['name'];
        $_SESSION['role']      =$user['role'];
        $_SESSION['user_email']=$user['email'];
        redirect($user['role']==='admin'?'admin.php':'index.php');
    } else {
        $error='Email ou mot de passe incorrect.';
    }
}
pageHeader('Connexion');
?>
<div class="page-wrap">
<div class="auth-page">
  <div class="auth-card fade-up">
    <div class="auth-brand">
      <div class="logo">LUXE<em>.CO</em></div>
      <h2>Content de vous revoir</h2>
      <p>Connectez-vous à votre espace privilège</p>
    </div>
    <div class="auth-body">

      <div class="auth-demo">
        💡 Comptes démo ·
        <span>alex@demo.com</span> / demo123 ·
        <span>admin@demo.com</span> / admin123
      </div>

      <?php if($error): ?>
      <div class="alert alert-error">⚠ <?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" placeholder="votre@email.com" required
                 value="<?= e($_POST['email']??'') ?>" autocomplete="email">
        </div>
        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;padding:13px;font-size:14px">
          Se connecter →
        </button>
      </form>

    </div>
    <div class="auth-footer">
      Pas encore membre ? <a href="register.php">Créer un compte</a>
    </div>
  </div>
</div>
</div>
<?php pageFooter(); ?>
