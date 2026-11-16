<?php
session_start(); date_default_timezone_set('Asia/Dushanbe');
try {
  $pdo = new PDO('mysql:host=localhost;dbname=fitness_club;charset=utf8mb4', 'root', '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $e) { die('Хато: пойгоҳи додаҳо fitness_club пайваст нашуд.'); }
const FREEZE_MAX = 14;
const DAYS = ['Душанбе','Сешанбе','Чоршанбе','Панҷшанбе','Ҷумъа','Шанбе','Якшанбе'];
const MS = ['active'=>'Фаъол','frozen'=>'Боздошташуда','expired'=>'Мӯҳлат гузашт','finished'=>'Ташрифҳо тамом'];
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['u'] ?? null; }
function need($roles = null) {
  $u = user(); if (!$u) { header('Location: login.php'); exit; }
  if ($roles && !in_array($u['role'], $roles)) { http_response_code(403); die('Дастрасӣ мамнӯъ аст'); }
}
function csrf() { return $_SESSION['t'] ?? ($_SESSION['t'] = bin2hex(random_bytes(16))); }
function chk() { if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals(csrf(), $_POST['t'] ?? '')) die('CSRF'); }
function money($v) { return number_format((float)$v, 0, '.', ' ') . ' сомонӣ'; }
function flash($m = null) { if ($m !== null) { $_SESSION['m'] = $m; return; } $x = $_SESSION['m'] ?? ''; unset($_SESSION['m']); return $x; }
function mbadge($s) { return '<span class="badge s-' . h($s) . '">' . h(MS[$s] ?? $s) . '</span>'; }

// ---- Мантиқи абонемент ----
function find($pdo, $id) { $s = $pdo->prepare('SELECT * FROM memberships WHERE id=?'); $s->execute([$id]); return $s->fetch(); }
function days_between($a, $b) { return (int)((strtotime($b) - strtotime($a)) / 86400); }
function unfreeze($pdo, $id) {
  $m = find($pdo, $id); if (!$m || $m['status'] !== 'frozen') return 'Абонемент боздошта нест';
  $d = max(0, min(days_between($m['frozen_at'], date('Y-m-d')), FREEZE_MAX - $m['freeze_days_used']));
  $pdo->prepare("UPDATE memberships SET status='active',frozen_at=NULL,freeze_days_used=freeze_days_used+?,end_date=DATE_ADD(end_date,INTERVAL ? DAY) WHERE id=?")->execute([$d, $d, $id]);
  return "Абонемент идома дорад. Мӯҳлат $d рӯз дароз шуд.";
}
function freeze($pdo, $id, $uid = null) {
  $m = find($pdo, $id); if (!$m || ($uid && $m['user_id'] != $uid)) return 'Абонемент ёфт нашуд';
  if ($m['status'] !== 'active') return 'Танҳо абонементи фаъолро боздоштан мумкин аст';
  $left = FREEZE_MAX - $m['freeze_days_used']; if ($left <= 0) return 'Лимити боздошт (14 рӯз) тамом шудааст';
  $pdo->prepare("UPDATE memberships SET status='frozen',frozen_at=? WHERE id=?")->execute([date('Y-m-d'), $id]);
  return "Абонемент боздошта шуд (ҳадди аксар $left рӯз, баъд худкор идома меёбад).";
}
function sync($pdo) { // нигоҳдории худкор
  foreach ($pdo->query("SELECT id,frozen_at,freeze_days_used FROM memberships WHERE status='frozen'")->fetchAll() as $m)
    if (days_between($m['frozen_at'], date('Y-m-d')) >= FREEZE_MAX - $m['freeze_days_used']) unfreeze($pdo, $m['id']);
  $pdo->prepare("UPDATE memberships SET status='expired' WHERE status='active' AND end_date<?")->execute([date('Y-m-d')]);
}
sync($pdo);
function valid_membership($pdo, $uid, $date) {
  $s = $pdo->prepare("SELECT * FROM memberships WHERE user_id=? AND status='active' AND start_date<=? AND end_date>=? AND (visits_left IS NULL OR visits_left>0) ORDER BY end_date LIMIT 1");
  $s->execute([$uid, $date, $date]); return $s->fetch();
}
function checkin($pdo, $uid, $mid, $cid, $by) {
  $pdo->beginTransaction();
  $s = $pdo->prepare('SELECT * FROM memberships WHERE id=? AND user_id=? FOR UPDATE'); $s->execute([$mid, $uid]); $m = $s->fetch(); $t = date('Y-m-d');
  if (!$m || $m['status'] !== 'active' || $m['start_date'] > $t || $m['end_date'] < $t) { $pdo->rollBack(); return 'Абонемент фаъол нест (боздошта, гузашта ё ҳанӯз оғоз нашудааст)'; }
  if ($m['visits_left'] !== null && $m['visits_left'] <= 0) { $pdo->rollBack(); return 'Ташрифҳо тамом шудаанд'; }
  $pdo->prepare('INSERT INTO visits(user_id,membership_id,class_id,checked_by) VALUES(?,?,?,?)')->execute([$uid, $mid, $cid ?: null, $by]);
  if ($m['visits_left'] !== null) $pdo->prepare("UPDATE memberships SET visits_left=visits_left-1,status=IF(visits_left=0,'finished',status) WHERE id=?")->execute([$mid]);
  $pdo->commit(); return 'Ташриф қайд шуд ✔' . ($m['visits_left'] !== null ? ' Бақия: ' . ($m['visits_left'] - 1) : '');
}
// ---- Сабт ва рӯйхати интизорӣ ----
function book_class($pdo, $uid, $cid) {
  $pdo->beginTransaction();
  $s = $pdo->prepare('SELECT * FROM classes WHERE id=? AND cancelled=0 FOR UPDATE'); $s->execute([$cid]); $c = $s->fetch();
  if (!$c || strtotime($c['starts_at']) < time()) { $pdo->rollBack(); return 'Машғулот дастнорас аст'; }
  $e = $pdo->prepare("SELECT id FROM class_bookings WHERE class_id=? AND user_id=? AND status<>'cancelled'"); $e->execute([$cid, $uid]);
  if ($e->fetch()) { $pdo->rollBack(); return 'Шумо аллакай сабт шудаед'; }
  if (!valid_membership($pdo, $uid, substr($c['starts_at'], 0, 10))) { $pdo->rollBack(); return 'Барои сабт абонементи фаъол (бо ташрифи боқимонда) лозим аст'; }
  $n = $pdo->prepare("SELECT COUNT(*) FROM class_bookings WHERE class_id=? AND status='booked'"); $n->execute([$cid]);
  $st = $n->fetchColumn() < $c['capacity'] ? 'booked' : 'waitlist';
  $pdo->prepare('INSERT INTO class_bookings(class_id,user_id,status) VALUES(?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),created_at=NOW()')->execute([$cid, $uid, $st]);
  $pdo->commit();
  return $st === 'booked' ? 'Шумо ба машғулият сабт шудед ✔' : 'Гурӯҳ пур аст — шумо ба рӯйхати интизорӣ дохил шудед ⏳';
}
function cancel_booking($pdo, $uid, $cid) {
  $pdo->beginTransaction();
  $s = $pdo->prepare('SELECT * FROM classes WHERE id=? FOR UPDATE'); $s->execute([$cid]); $c = $s->fetch();
  $b = $pdo->prepare("SELECT * FROM class_bookings WHERE class_id=? AND user_id=? AND status<>'cancelled'"); $b->execute([$cid, $uid]); $b = $b->fetch();
  if (!$c || !$b || strtotime($c['starts_at']) < time()) { $pdo->rollBack(); return 'Бекор кардан имконнопазир аст'; }
  $pdo->prepare("UPDATE class_bookings SET status='cancelled' WHERE id=?")->execute([$b['id']]);
  $extra = '';
  if ($b['status'] === 'booked') { // ҷойи холӣ → аввалин дар навбат
    $w = $pdo->prepare("SELECT id,user_id FROM class_bookings WHERE class_id=? AND status='waitlist' ORDER BY created_at,id"); $w->execute([$cid]);
    foreach ($w->fetchAll() as $x) if (valid_membership($pdo, $x['user_id'], substr($c['starts_at'], 0, 10))) {
      $pdo->prepare("UPDATE class_bookings SET status='booked' WHERE id=?")->execute([$x['id']]); $extra = ' Ҷой ба шахси навбатӣ дода шуд.'; break;
    }
  }
  $pdo->commit(); return 'Сабт бекор шуд.' . $extra;
}
function week_start($w) { return strtotime("monday this week $w weeks"); }

function head($t) { $u = user(); ?>
<!doctype html><html lang="tg"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($t) ?> — ФитПулс</title><link rel="stylesheet" href="assets/style.css"></head><body>
<div class="bg"><i></i><i></i><i></i></div>
<header class="nav"><a class="logo" href="index.php">💪 Фит<b>Пулс</b></a><nav>
<a href="index.php">Асосӣ</a><a href="schedule.php">Ҷадвал</a>
<?php if ($u): $p = ['client'=>'account.php','trainer'=>'trainer.php','admin'=>'admin.php'][$u['role']]; ?>
<a href="<?= $p ?>"><?= $u['role'] === 'client' ? 'Утоқи ман' : ($u['role'] === 'admin' ? 'Маъмур' : 'Мураббӣ') ?></a><a href="logout.php">Баромад</a>
<?php else: ?><a href="login.php">Вуруд</a><a href="register.php">Сабти ном</a><?php endif ?></nav></header><main>
<?php }
function foot() { ?></main><footer>© <?= date('Y') ?> ФитПулс · Фитнес-клуб</footer><script src="assets/app.js"></script></body></html><?php }
