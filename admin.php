<?php
require_once 'layout.php';
require_once 'recommend.php';
if(!isAdmin()) redirect('login.php');
$db=db(); $msg=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf(); $action=$_POST['form_action']??'';
    if($action==='add_product'){
        $name=trim($_POST['name']??''); $price=(float)($_POST['price']??0); $cat=trim($_POST['category']??'');
        $tag=$_POST['tag']??'Nouveau'; $stock=(int)($_POST['stock']??10); $color=$_POST['color']??'#1a1a2e'; $desc=trim($_POST['description']??'');
        if(!$name||!$price||!$cat){ $msg=['type'=>'err','text'=>'❌ Nom, prix et catégorie requis.']; }
        else {
            $imagePath=null;
            if(!empty($_FILES['product_image']['name'])){ $imagePath=uploadProductImage($_FILES['product_image']); if(!$imagePath) $msg=['type'=>'err','text'=>'❌ Image invalide (JPG/PNG/WEBP, max 5Mo).']; }
            if(!$msg){ $db->prepare("INSERT INTO products(name,description,price,category,tag,stock,image_path,color,rating,reviews)VALUES(?,?,?,?,?,?,?,?,4.5,0)")->execute([$name,$desc,$price,$cat,$tag,$stock,$imagePath,$color]); $msg=['type'=>'ok','text'=>'✅ Produit "'.$name.'" ajouté.']; }
        }
    }
    if($action==='delete_product'){ $pid=(int)($_POST['product_id']??0); $r=$db->prepare("SELECT image_path FROM products WHERE id=?"); $r->execute([$pid]); $row=$r->fetch(); if($row&&$row['image_path']&&!str_starts_with($row['image_path'],'http')){ $f=__DIR__.'/uploads/'.$row['image_path']; if(file_exists($f))unlink($f); } $db->prepare("DELETE FROM products WHERE id=?")->execute([$pid]); $msg=['type'=>'ok','text'=>'🗑 Produit supprimé.']; }
    if($action==='update_stock'){ $db->prepare("UPDATE products SET stock=? WHERE id=?")->execute([(int)($_POST['stock']??0),(int)($_POST['product_id']??0)]); $msg=['type'=>'ok','text'=>'✅ Stock mis à jour.']; }
}
$products=$db->query("SELECT * FROM products ORDER BY id")->fetchAll();
$users=$db->query("SELECT * FROM users WHERE role='user' ORDER BY id")->fetchAll();
$cats=array_unique(array_column($products,'category'));
$colors=['#0b0e14','#1a1a2e','#2e1a0e','#1a0e2e','#1e1e1e','#0e1a2e','#1a2e1a','#0a0c18'];
pageHeader('Administration','admin');
?>
<div class="page-wrap">
<div style="background:linear-gradient(135deg,rgba(139,92,246,.08),transparent);padding:32px 40px 24px;border-bottom:1px solid var(--border)">
  <div class="container">
    <div class="section-label">Administration</div>
    <h1 style="font-family:var(--display);font-size:36px;font-weight:800;letter-spacing:-.03em;color:var(--white)">Panneau Admin</h1>
  </div>
</div>
<div class="container" style="padding-top:24px;padding-bottom:60px">
  <div class="admin-tabs">
    <button class="admin-tab active" data-tab="dashboard">📊 Dashboard</button>
    <button class="admin-tab" data-tab="products">📦 Produits</button>
    <button class="admin-tab" data-tab="add">➕ Ajouter</button>
    <button class="admin-tab" data-tab="users">👥 Utilisateurs</button>
    <button class="admin-tab" data-tab="reco">🤖 IA Reco</button>
  </div>
  <?php if($msg): ?><div class="alert alert-<?= $msg['type']==='ok'?'success':'error' ?>"><?= $msg['text'] ?></div><?php endif; ?>

  <!-- DASHBOARD -->
  <div id="panel-dashboard" class="admin-panel">
    <?php $lowStock=count(array_filter($products,fn($p)=>$p['stock']<5)); $totalInter=(int)$db->query("SELECT COUNT(*) FROM interactions")->fetchColumn(); $withImg=count(array_filter($products,fn($p)=>!empty($p['image_path']))); ?>
    <div class="admin-stats">
      <?php foreach([['📦','Produits',count($products),'var(--violet-l)'],['👥','Utilisateurs',count($users),'#34d399'],['🖼️','Avec image',$withImg,'var(--cyan-l)'],['🤖','Interactions',$totalInter,'#a78bfa'],['⚠️','Stock Critique',$lowStock,'#f87171']] as [$i,$l,$v,$c]): ?>
      <div class="stat-card"><div class="stat-icon"><?=$i?></div><div class="stat-value" style="color:<?=$c?>"><?=$v?></div><div class="stat-name"><?=$l?></div></div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:24px">
      <div class="section-label" style="margin-bottom:14px">Aperçu Catalogue</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px">
        <?php foreach(array_slice($products,0,10) as $p): ?>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden">
          <div style="height:100px;overflow:hidden"><?= productImage($p) ?></div>
          <div style="padding:8px 10px"><div style="font-size:10px;color:var(--text-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:500"><?= e($p['name']) ?></div><div style="font-family:var(--display);font-size:12px;color:var(--white);font-weight:700"><?= number_format($p['price'],0,',',' ') ?>€</div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- PRODUITS -->
  <div id="panel-products" class="admin-panel" style="display:none">
    <div style="overflow-x:auto"><table class="data-table">
      <thead><tr><th>Image</th><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Note</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($products as $p): ?>
        <tr>
          <td><div style="width:56px;height:56px;border-radius:8px;overflow:hidden;border:1px solid var(--border)"><?= productImage($p) ?></div></td>
          <td><div style="font-weight:600;color:var(--white);font-size:13px"><?= e($p['name']) ?></div><span class="tag" style="margin-top:3px;display:inline-block"><?= e($p['tag']) ?></span></td>
          <td style="color:var(--text-3);font-size:12px"><?= e($p['category']) ?></td>
          <td style="font-family:var(--display);font-size:15px;font-weight:700;color:var(--white)"><?= number_format($p['price'],0,',',' ') ?>€</td>
          <td><form method="POST" style="display:flex;gap:6px;align-items:center">
            <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="form_action" value="update_stock"><input type="hidden" name="product_id" value="<?=$p['id']?>">
            <input type="number" name="stock" value="<?=$p['stock']?>" min="0" style="width:55px;padding:4px 8px;font-size:11px;color:<?=$p['stock']<5?'#f87171':'#34d399'?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="padding:4px 8px;font-size:10px">✓</button>
          </form></td>
          <td><span style="color:var(--amber)"><?= stars((float)$p['rating']) ?></span></td>
          <td><form method="POST" onsubmit="return confirm('Supprimer ?')"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="form_action" value="delete_product"><input type="hidden" name="product_id" value="<?=$p['id']?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <!-- AJOUTER -->
  <div id="panel-add" class="admin-panel" style="display:none;max-width:600px">
    <h3 style="font-family:var(--display);font-size:22px;font-weight:800;color:var(--white);margin-bottom:20px">Ajouter un Produit</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="form_action" value="add_product">
      <div class="form-group"><label class="form-label">Nom *</label><input type="text" name="name" id="name-input" required placeholder="Ex: Sac Cuir Milano"></div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Prix (€) *</label><input type="number" name="price" id="price-input" required step="0.01" min="0"></div>
        <div class="form-group"><label class="form-label">Stock</label><input type="number" name="stock" value="10" min="0"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Catégorie *</label><input type="text" name="category" list="cats-dl" required><datalist id="cats-dl"><?php foreach($cats as $c): ?><option value="<?=e($c)?>"><?php endforeach; ?></datalist></div>
        <div class="form-group"><label class="form-label">Badge</label><select name="tag"><?php foreach(['Nouveau','Bestseller','Limité','Exclusif','Premium','Luxe','Prestige'] as $t): ?><option><?=$t?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea name="description" rows="3" placeholder="Décrivez le produit..."></textarea></div>
      <div class="form-group">
        <label class="form-label">📸 Image</label>
        <div class="upload-zone" id="drop-zone" onclick="document.getElementById('product_image').click()">
          <div id="img-preview-wrap" style="display:none;margin-bottom:10px"><img id="img-preview-img" src="" style="max-height:180px;max-width:100%;border-radius:8px;object-fit:cover;border:1px solid var(--border)"><div id="img-preview-name" style="font-size:11px;color:var(--text-3);margin-top:5px"></div></div>
          <div id="drop-placeholder"><div style="font-size:32px;margin-bottom:8px">📷</div><div style="font-size:13px;color:var(--text-2)">Cliquer ou glisser une image</div><div style="font-size:11px;color:var(--text-3);margin-top:4px">JPG, PNG, WEBP — max 5 Mo</div></div>
          <input type="file" name="product_image" id="product_image" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="padding:13px 32px;font-size:14px">➕ Ajouter le produit</button>
    </form>
  </div>

  <!-- USERS -->
  <div id="panel-users" class="admin-panel" style="display:none">
    <?php foreach($users as $u): $cs=$db->prepare("SELECT COUNT(*) FROM interactions WHERE user_id=?"); $cs->execute([$u['id']]); $ic=(int)$cs->fetchColumn(); ?>
    <div style="background:var(--card);border:1px solid var(--border-2);border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:16px;margin-bottom:10px">
      <div style="width:44px;height:44px;background:linear-gradient(135deg,rgba(139,92,246,.2),rgba(6,182,212,.15));border:1px solid rgba(139,92,246,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--display);font-size:18px;font-weight:800;color:var(--violet-l);flex-shrink:0"><?= mb_strtoupper(mb_substr($u['name'],0,1)) ?></div>
      <div style="flex:1"><div style="font-weight:600;font-size:13px;color:var(--white)"><?= e($u['name']) ?></div><div style="font-size:11px;color:var(--text-3)"><?= e($u['email']) ?></div></div>
      <div style="text-align:center"><div style="font-family:var(--display);font-size:20px;font-weight:800;color:var(--white)"><?= $ic ?></div><div style="font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;font-weight:600">interactions</div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- RECO -->
  <div id="panel-reco" class="admin-panel" style="display:none">
    <h3 style="font-family:var(--display);font-size:20px;font-weight:800;color:var(--white);margin-bottom:6px">Moteur IA — Content-Based Filtering</h3>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px">
      <div style="background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.18);border-radius:10px;padding:12px 14px;text-align:center">
        <div style="font-size:20px;margin-bottom:5px">🏷</div>
        <div style="font-family:var(--display);font-size:11px;font-weight:700;color:var(--violet-l)">Catégorie</div>
        <div style="font-size:10px;color:var(--text-3);margin-top:2px">One-hot encoding</div>
      </div>
      <div style="background:rgba(6,182,212,.08);border:1px solid rgba(6,182,212,.18);border-radius:10px;padding:12px 14px;text-align:center">
        <div style="font-size:20px;margin-bottom:5px">💰</div>
        <div style="font-family:var(--display);font-size:11px;font-weight:700;color:var(--cyan-l)">Prix</div>
        <div style="font-size:10px;color:var(--text-3);margin-top:2px">3 tranches</div>
      </div>
      <div style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.18);border-radius:10px;padding:12px 14px;text-align:center">
        <div style="font-size:20px;margin-bottom:5px">⭐</div>
        <div style="font-family:var(--display);font-size:11px;font-weight:700;color:#34d399">Note / Pop.</div>
        <div style="font-size:10px;color:var(--text-3);margin-top:2px">Normalisée</div>
      </div>
      <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.18);border-radius:10px;padding:12px 14px;text-align:center">
        <div style="font-size:20px;margin-bottom:5px">🎯</div>
        <div style="font-family:var(--display);font-size:11px;font-weight:700;color:#fbbf24">Cosinus</div>
        <div style="font-size:10px;color:var(--text-3);margin-top:2px">Similarité</div>
      </div>
    </div>
    <p style="color:var(--text-3);font-size:12px;margin-bottom:20px;line-height:1.7">
      Profil utilisateur = somme pondérée des vecteurs produits consultés (score 1→5) · 
      Vecteur produit = features one-hot (catégorie, tag) + tranches de prix + note + popularité log-scale · 
      Score final = similarité cosinus + bonus note (×0.1)
    </p>
    <div style="display:grid;gap:8px">
      <?php foreach($users as $u): $recs=getRecommendations((int)$u['id'],5); ?>
      <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:14px">
        <div style="width:36px;height:36px;background:rgba(139,92,246,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--violet-l);font-weight:700;flex-shrink:0"><?= mb_strtoupper(mb_substr($u['name'],0,1)) ?></div>
        <div style="flex:1;font-size:13px;font-weight:500;color:var(--text)"><?= e($u['name']) ?></div>
        <div style="display:flex;gap:6px">
          <?php foreach($recs as $r): ?>
          <a href="product.php?id=<?=$r['id']?>" title="<?=e($r['name'])?>" style="width:44px;height:44px;border-radius:8px;overflow:hidden;border:1px solid var(--border);display:block"><?= productImage($r) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>
</div>
<script>
document.querySelectorAll('.admin-tab').forEach(t=>t.addEventListener('click',()=>{
  document.querySelectorAll('.admin-tab').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.admin-panel').forEach(x=>x.style.display='none');
  t.classList.add('active');
  const p=document.getElementById('panel-'+t.dataset.tab);
  if(p) p.style.display='block';
}));
const fi=document.getElementById('product_image');
fi?.addEventListener('change',function(){
  const f=this.files[0]; if(!f) return;
  const url=URL.createObjectURL(f);
  document.getElementById('img-preview-img').src=url;
  document.getElementById('img-preview-name').textContent=f.name+' ('+(f.size/1024).toFixed(0)+' Ko)';
  document.getElementById('img-preview-wrap').style.display='block';
  document.getElementById('drop-placeholder').style.display='none';
});
const dz=document.getElementById('drop-zone');
dz?.addEventListener('dragover',e=>{e.preventDefault();dz.style.borderColor='var(--violet)';});
dz?.addEventListener('dragleave',()=>dz.style.borderColor='');
dz?.addEventListener('drop',e=>{e.preventDefault();const f=e.dataTransfer.files[0];if(f&&f.type.startsWith('image/')){const dt=new DataTransfer();dt.items.add(f);fi.files=dt.files;fi.dispatchEvent(new Event('change'));}});
document.getElementById('name-input')?.addEventListener('input',e=>document.getElementById('prev-name')&&(document.getElementById('prev-name').textContent=e.target.value||'Nom'));
</script>
<?php pageFooter(); ?>
