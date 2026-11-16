<?php require 'config.php'; need(['admin']); chk(); $u = user(); $tab = $_GET['tab'] ?? 'mem'; $T = csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? ''; $id = (int)($_POST['id'] ?? 0); $msg = 'Иҷро шуд ✔';
  try {
    if ($a === 'sell') {
      $ty = $pdo->prepare('SELECT * FROM membership_types WHERE id=?'); $ty->execute([(int)$_POST['type']]); $ty = $ty->fetch(); $st = $_POST['start'] ?: date('Y-m-d');
      if ($ty && (int)$_POST['client']) $pdo->prepare('INSERT INTO memberships(user_id,type_id,visits_total,visits_left,start_date,end_date,price_paid,sold_by) VALUES(?,?,?,?,?,DATE_ADD(?,INTERVAL ? DAY),?,?)')
        ->execute([(int)$_POST['client'], $ty['id'], $ty['visits'], $ty['visits'], $st, $st, $ty['duration_days'], $ty['price'], $u['id']]);
      $msg = 'Абонемент фурӯхта шуд ✔';
    } elseif ($a === 'freeze') $msg = freeze($pdo, $id);
    elseif ($a === 'unfreeze') $msg = unfreeze($pdo, $id);
    elseif ($a === 'checkin') { $m = find($pdo, (int)$_POST['mid']); $msg = $m ? checkin($pdo, $m['user_id'], $m['id'], (int)$_POST['cid'], $u['id']) : 'Абонемент ёфт нашуд'; }
    elseif ($a === 'addclass') $pdo->prepare('INSERT INTO classes(title,trainer_id,starts_at,duration_min,capacity,room) VALUES(?,?,?,?,?,?)')
      ->execute([trim($_POST['title']), (int)$_POST['trainer'], str_replace('T', ' ', $_POST['starts']) . ':00', max(15, (int)$_POST['dur']), max(1, (int)$_POST['cap']), trim($_POST['room']) ?: 'Толори 1']);
    elseif ($a === 'cancelclass') $pdo->prepare('UPDATE classes SET cancelled=1 WHERE id=?')->execute([$id]);
    elseif ($a === 'addtype') $pdo->prepare('INSERT INTO membership_types(name,visits,duration_days,price) VALUES(?,?,?,?)')
      ->execute([trim($_POST['name']), $_POST['visits'] === '' ? null : (int)$_POST['visits'], (int)$_POST['days'], (float)$_POST['price']]);
    elseif ($a === 'toggletype') $pdo->prepare('UPDATE membership_types SET active=1-active WHERE id=?')->execute([$id]);
    elseif ($a === 'adduser') {
      $pdo->beginTransaction(); $role = $_POST['role'] === 'trainer' ? 'trainer' : 'client';
      $pdo->prepare('INSERT INTO users(full_name,login,password_hash,phone,role) VALUES(?,?,?,?,?)')->execute([trim($_POST['name']), trim($_POST['login']), password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT), trim($_POST['phone']), $role]);
      if ($role === 'trainer') $pdo->prepare('INSERT INTO trainers(user_id,specialty) VALUES(?,?)')->execute([$pdo->lastInsertId(), trim($_POST['spec'])]);
      $pdo->commit();
    }
  } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $msg = 'Хато: маълумот нодуруст ё логин банд аст'; }
  flash($msg); header("Location: admin.php?tab=$tab"); exit;
}
$msg = flash(); $today = date('Y-m-d');
$stat = [
  'Абонементи фаъол' => $pdo->query("SELECT COUNT(*) FROM memberships WHERE status='active'")->fetchColumn(),
  'Ташрифи имрӯз' => $pdo->query("SELECT COUNT(*) FROM visits WHERE DATE(visited_at)=CURDATE()")->fetchColumn(),
  'Машғулоти оянда' => $pdo->query("SELECT COUNT(*) FROM classes WHERE cancelled=0 AND starts_at>NOW()")->fetchColumn(),
  'Дар рӯйхати интизорӣ' => $pdo->query("SELECT COUNT(*) FROM class_bookings WHERE status='waitlist'")->fetchColumn()];
$clients = $pdo->query("SELECT id,full_name,phone FROM users WHERE role='client' AND active=1 ORDER BY full_name")->fetchAll();
$types = $pdo->query('SELECT * FROM membership_types ORDER BY active DESC,price')->fetchAll();
$tabs = ['mem'=>'Абонементҳо','visit'=>'Ташриф','cls'=>'Ҷадвал','types'=>'Намудҳо','ppl'=>'Одамон'];
head('Маъмур'); ?>
<h1 class="reveal">Панели маъмур</h1>
<div class="grid5"><?php foreach ($stat as $k => $v): ?><div class="stat card tilt reveal"><b data-count="<?= (int)$v ?>">0</b><span><?= h($k) ?></span></div><?php endforeach ?></div>
<?php if ($msg): ?><div class="flash"><?= h($msg) ?></div><?php endif ?>
<div class="tabs"><?php foreach ($tabs as $k => $v): ?><a class="<?= $tab === $k ? 'on' : '' ?>" href="?tab=<?= $k ?>"><?= $v ?></a><?php endforeach ?></div>

<?php if ($tab === 'mem'): $ms = $pdo->query('SELECT m.*,u.full_name,t.name tname FROM memberships m JOIN users u ON u.id=m.user_id JOIN membership_types t ON t.id=m.type_id ORDER BY m.id DESC LIMIT 60')->fetchAll(); ?>
<section class="card reveal"><h2>Фурӯши абонемент</h2><form method="post" class="form two"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="a" value="sell">
<label>Мизоҷ<select name="client" required><?php foreach ($clients as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['full_name']) ?> · <?= h($c['phone']) ?></option><?php endforeach ?></select></label>
<label>Намуд<select name="type"><?php foreach ($types as $t): if (!$t['active']) continue; ?><option value="<?= $t['id'] ?>"><?= h($t['name']) ?> — <?= money($t['price']) ?></option><?php endforeach ?></select></label>
<label>Санаи оғоз<input type="date" name="start" value="<?= $today ?>"></label><button class="btn" style="align-self:end">Фурӯхтан</button></form></section>
<section class="card reveal"><h2>Абонементҳо</h2><div class="tbl"><table><tr><th>Мизоҷ</th><th>Намуд</th><th>Боқӣ</th><th>Мӯҳлат</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($ms as $m): ?><tr><td><?= h($m['full_name']) ?></td><td><?= h($m['tname']) ?></td><td><?= $m['visits_left'] ?? '∞' ?></td><td><?= h($m['end_date']) ?></td><td><?= mbadge($m['status']) ?></td>
<td><form method="post"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="id" value="<?= $m['id'] ?>">
<?php if ($m['status'] === 'active'): ?><button class="btn sm ghost" name="a" value="freeze">❄ Боздоштан</button><?php elseif ($m['status'] === 'frozen'): ?><button class="btn sm" name="a" value="unfreeze">▶ Идома</button><?php endif ?></form></td></tr><?php endforeach ?></table></div></section>

<?php elseif ($tab === 'visit'):
  $am = $pdo->prepare("SELECT m.id,m.visits_left,u.full_name,t.name tname FROM memberships m JOIN users u ON u.id=m.user_id JOIN membership_types t ON t.id=m.type_id WHERE m.status='active' AND m.start_date<=? AND m.end_date>=? AND (m.visits_left IS NULL OR m.visits_left>0) ORDER BY u.full_name"); $am->execute([$today, $today]);
  $tc = $pdo->prepare('SELECT id,title,starts_at FROM classes WHERE cancelled=0 AND DATE(starts_at)=? ORDER BY starts_at'); $tc->execute([$today]);
  $rv = $pdo->query('SELECT v.visited_at,u.full_name,c.title FROM visits v JOIN users u ON u.id=v.user_id LEFT JOIN classes c ON c.id=v.class_id ORDER BY v.id DESC LIMIT 15')->fetchAll(); ?>
<section class="card reveal"><h2>Қайди ташриф дар қабулгоҳ</h2><form method="post" class="form two"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="a" value="checkin">
<label>Мизоҷ ва абонемент<select name="mid" required><?php foreach ($am as $m): ?><option value="<?= $m['id'] ?>"><?= h($m['full_name']) ?> — <?= h($m['tname']) ?> (боқӣ: <?= $m['visits_left'] ?? '∞' ?>)</option><?php endforeach ?></select></label>
<label>Машғулият (ихтиёрӣ)<select name="cid"><option value="0">Машқи озод</option><?php foreach ($tc as $c): ?><option value="<?= $c['id'] ?>"><?= date('H:i', strtotime($c['starts_at'])) ?> <?= h($c['title']) ?></option><?php endforeach ?></select></label>
<button class="btn full">✔ Қайд кардан (1 ташриф кам мешавад)</button></form></section>
<section class="card reveal"><h2>Ташрифҳои охирин</h2><ul class="tl"><?php foreach ($rv as $v): ?><li><b><?= h($v['full_name']) ?></b> <small><?= h($v['visited_at']) ?></small> <span class="muted"><?= h($v['title'] ?? 'Машқи озод') ?></span></li><?php endforeach ?></ul></section>

<?php elseif ($tab === 'cls'):
  $trs = $pdo->query('SELECT t.id,u.full_name FROM trainers t JOIN users u ON u.id=t.user_id WHERE u.active=1')->fetchAll();
  $cl = $pdo->query("SELECT c.*,u.full_name tn,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') bk,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='waitlist') wl FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id WHERE c.cancelled=0 AND c.starts_at>NOW() ORDER BY c.starts_at LIMIT 50")->fetchAll(); ?>
<section class="card reveal"><h2>Машғулияти нав</h2><form method="post" class="form two"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="a" value="addclass">
<label>Ном<input name="title" required></label><label>Мураббӣ<select name="trainer"><?php foreach ($trs as $t): ?><option value="<?= $t['id'] ?>"><?= h($t['full_name']) ?></option><?php endforeach ?></select></label>
<label>Оғоз<input type="datetime-local" name="starts" required></label><label>Давомнокӣ (дақ.)<input type="number" name="dur" value="60"></label>
<label>Шумораи ҷойҳо<input type="number" name="cap" min="1" value="10"></label><label>Толор<input name="room" placeholder="Толори 1"></label><button class="btn full">Илова кардан</button></form></section>
<section class="card reveal"><h2>Машғулиятҳои оянда</h2><div class="tbl"><table><tr><th>Вақт</th><th>Ном</th><th>Мураббӣ</th><th>Ҷой</th><th>Навбат</th><th></th></tr>
<?php foreach ($cl as $c): ?><tr><td><?= h(date('d.m H:i', strtotime($c['starts_at']))) ?></td><td><?= h($c['title']) ?></td><td><?= h($c['tn']) ?></td><td><?= $c['bk'] ?>/<?= $c['capacity'] ?></td><td><?= $c['wl'] ?></td>
<td><form method="post" onsubmit="return confirm('Машғулият бекор шавад?')"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn sm danger" name="a" value="cancelclass">Бекор</button></form></td></tr><?php endforeach ?></table></div></section>

<?php elseif ($tab === 'types'): ?>
<section class="card reveal"><h2>Намуди нави абонемент</h2><form method="post" class="form two"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="a" value="addtype">
<label>Ном<input name="name" required></label><label>Шумораи ташриф (холӣ = беҳад)<input type="number" name="visits" min="1"></label>
<label>Мӯҳлат (рӯз)<input type="number" name="days" min="1" required></label><label>Нарх<input type="number" name="price" step="0.01" min="0" required></label><button class="btn full">Илова кардан</button></form></section>
<section class="card reveal"><div class="tbl"><table><tr><th>Ном</th><th>Ташриф</th><th>Рӯз</th><th>Нарх</th><th></th></tr>
<?php foreach ($types as $t): ?><tr><td><?= h($t['name']) ?></td><td><?= $t['visits'] ?? '∞' ?></td><td><?= $t['duration_days'] ?></td><td><?= money($t['price']) ?></td>
<td><form method="post"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn sm ghost" name="a" value="toggletype"><?= $t['active'] ? 'Хомӯш' : 'Фаъол' ?></button></form></td></tr><?php endforeach ?></table></div></section>

<?php else: ?>
<section class="card reveal"><h2>Илова кардани мизоҷ / мураббӣ</h2><form method="post" class="form two"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="a" value="adduser">
<label>Ному насаб<input name="name" required></label><label>Телефон<input name="phone"></label><label>Логин<input name="login" required></label><label>Парол (пешфарз 123456)<input name="password"></label>
<label>Нақш<select name="role"><option value="client">Мизоҷ</option><option value="trainer">Мураббӣ</option></select></label><label>Ихтисос (барои мураббӣ)<input name="spec"></label><button class="btn full">Илова кардан</button></form></section>
<?php endif ?>
<?php foot();
