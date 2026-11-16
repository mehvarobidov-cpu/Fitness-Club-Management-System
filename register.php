<?php require 'config.php'; chk(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $n = trim($_POST['name']); $l = trim($_POST['login']); $p = trim($_POST['phone']); $pw = $_POST['password'];
  if (!$n || !preg_match('/^[A-Za-z0-9_]{3,30}$/', $l) || strlen($pw) < 6) $err = 'Логин: 3–30 ҳарфи лотинӣ/рақам; парол: на кам аз 6 аломат.';
  else try {
    $pdo->prepare("INSERT INTO users(full_name,login,password_hash,phone,role) VALUES(?,?,?,?,'client')")->execute([$n, $l, password_hash($pw, PASSWORD_DEFAULT), $p]);
    $_SESSION['u'] = ['id'=>$pdo->lastInsertId(), 'name'=>$n, 'role'=>'client']; header('Location: account.php'); exit;
  } catch (Exception $e) { $err = 'Ин логин банд аст'; }
}
head('Сабти ном'); ?>
<section class="card narrow reveal"><h1>Сабти ном</h1><?php if ($err): ?><p class="err"><?= h($err) ?></p><?php endif ?>
<form method="post" class="form"><input type="hidden" name="t" value="<?= csrf() ?>">
<label>Ному насаб<input name="name" required></label><label>Телефон<input name="phone"></label>
<label>Логин<input name="login" required></label><label>Парол<input name="password" type="password" required></label>
<button class="btn">Сабт шудан</button></form></section>
<?php foot();
