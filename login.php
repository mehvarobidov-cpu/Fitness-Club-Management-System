<?php require 'config.php'; chk(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $s = $pdo->prepare('SELECT * FROM users WHERE login=? AND active=1'); $s->execute([trim($_POST['login'])]); $r = $s->fetch(); $ok = false; $pw = $_POST['password'];
  if ($r) {
    if ($r['password_hash'] === 'NEW' && $pw === '123456') { // аввалин вуруди корбарони намунавӣ
      $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pw, PASSWORD_DEFAULT), $r['id']]); $ok = true;
    } else $ok = password_verify($pw, $r['password_hash']);
  }
  if ($ok) {
    session_regenerate_id(true); $_SESSION['u'] = ['id'=>$r['id'], 'name'=>$r['full_name'], 'role'=>$r['role']];
    header('Location: ' . ['client'=>'account.php','trainer'=>'trainer.php','admin'=>'admin.php'][$r['role']]); exit;
  }
  $err = 'Логин ё парол нодуруст аст';
}
head('Вуруд'); ?>
<section class="card narrow reveal"><h1>Вуруд</h1>
<?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif ?>
<form method="post" class="form"><input type="hidden" name="t" value="<?= csrf() ?>">
<label>Логин<input name="login" required autofocus></label><label>Парол<input name="password" type="password" required></label>
<button class="btn">Ворид шудан</button></form>
<p class="muted">Ҳисоб надоред? <a href="register.php">Сабти ном</a></p></section>
<?php foot();
