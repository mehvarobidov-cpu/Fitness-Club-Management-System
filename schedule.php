<?php require 'config.php'; chk(); $u = user(); $w = (int)($_GET['w'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  need(['client']); $cid = (int)$_POST['cid'];
  flash(($_POST['a'] ?? '') === 'cancel' ? cancel_booking($pdo, $u['id'], $cid) : book_class($pdo, $u['id'], $cid));
  header("Location: schedule.php?w=$w"); exit;
}
$mon = week_start($w); $from = date('Y-m-d', $mon); $to = date('Y-m-d', strtotime('+7 days', $mon));
$s = $pdo->prepare("SELECT c.*,u.full_name tn,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='booked') bk,(SELECT COUNT(*) FROM class_bookings b WHERE b.class_id=c.id AND b.status='waitlist') wl FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id WHERE c.cancelled=0 AND c.starts_at>=? AND c.starts_at<? ORDER BY c.starts_at");
$s->execute([$from, $to]); $by = [];
foreach ($s->fetchAll() as $c) $by[substr($c['starts_at'], 0, 10)][] = $c;
$mine = [];
if ($u && $u['role'] === 'client') { $m = $pdo->prepare("SELECT class_id,status FROM class_bookings WHERE user_id=? AND status<>'cancelled'"); $m->execute([$u['id']]); $mine = $m->fetchAll(PDO::FETCH_KEY_PAIR); }
$msg = flash(); head('Ҷадвал'); ?>
<h1 class="reveal">Ҷадвали машғулиятҳо</h1>
<?php if ($msg): ?><div class="flash"><?= h($msg) ?></div><?php endif ?>
<div class="wk"><a class="btn ghost sm" href="?w=<?= $w - 1 ?>">← Ҳафтаи пеш</a><b><?= date('d.m', $mon) ?> – <?= date('d.m.Y', strtotime('+6 days', $mon)) ?></b><a class="btn ghost sm" href="?w=<?= $w + 1 ?>">Ҳафтаи оянда →</a></div>
<?php for ($i = 0; $i < 7; $i++): $d = date('Y-m-d', strtotime("+$i day", $mon)); if (empty($by[$d])) continue; ?>
<h3 class="day reveal"><?= DAYS[$i] ?> <small><?= date('d.m', strtotime($d)) ?></small></h3>
<?php foreach ($by[$d] as $c): $full = $c['bk'] >= $c['capacity']; $past = strtotime($c['starts_at']) < time(); $st = $mine[$c['id']] ?? null; ?>
<div class="card cls reveal"><div class="t"><?= date('H:i', strtotime($c['starts_at'])) ?></div>
<div class="i"><b><?= h($c['title']) ?></b><br><span class="muted"><?= h($c['tn']) ?> · <?= h($c['room']) ?> · <?= $c['duration_min'] ?> дақ.</span></div>
<div class="cap <?= $full ? 'full-t' : '' ?>">Ҷойҳо: <b><?= $c['bk'] ?>/<?= $c['capacity'] ?></b><?= $c['wl'] ? ' · дар навбат: ' . $c['wl'] : '' ?></div>
<?php if (!$u): ?><a class="btn sm" href="login.php">Ворид шавед</a>
<?php elseif ($u['role'] === 'client' && !$past): ?>
<form method="post" action="?w=<?= $w ?>"><input type="hidden" name="t" value="<?= csrf() ?>"><input type="hidden" name="cid" value="<?= $c['id'] ?>">
<?php if ($st): ?><span class="badge s-<?= $st ?>"><?= $st === 'booked' ? 'Сабт шудаед' : 'Дар навбат' ?></span> <button class="btn sm danger" name="a" value="cancel">Бекор</button>
<?php else: ?><button class="btn sm" name="a" value="book"><?= $full ? 'Ба навбат ⏳' : 'Сабт шудан' ?></button><?php endif ?></form><?php endif ?></div>
<?php endforeach; endfor; if (!$by): ?><p class="muted">Дар ин ҳафта машғулият нест.</p><?php endif ?>
<?php foot();
