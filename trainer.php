<?php require 'config.php'; need(['trainer']); $u = user(); $w = (int)($_GET['w'] ?? 0);
$t = $pdo->prepare('SELECT id,specialty FROM trainers WHERE user_id=?'); $t->execute([$u['id']]); $tr = $t->fetch(); if (!$tr) die('Профили мураббӣ ёфт нашуд');
$mon = week_start($w); $cs = $pdo->prepare('SELECT * FROM classes WHERE trainer_id=? AND cancelled=0 AND starts_at>=? AND starts_at<? ORDER BY starts_at');
$cs->execute([$tr['id'], date('Y-m-d', $mon), date('Y-m-d', strtotime('+7 days', $mon))]);
$bs = $pdo->prepare("SELECT b.status,x.full_name,x.phone FROM class_bookings b JOIN users x ON x.id=b.user_id WHERE b.class_id=? AND b.status<>'cancelled' ORDER BY b.status,b.created_at,b.id");
head('Мураббӣ'); ?>
<h1 class="reveal">Ҷадвали ман · <?= h($u['name']) ?></h1>
<div class="wk"><a class="btn ghost sm" href="?w=<?= $w - 1 ?>">← Пеш</a><b><?= date('d.m', $mon) ?> – <?= date('d.m.Y', strtotime('+6 days', $mon)) ?></b><a class="btn ghost sm" href="?w=<?= $w + 1 ?>">Оянда →</a></div>
<?php foreach ($cs->fetchAll() as $c): $bs->execute([$c['id']]); $r = $bs->fetchAll(); ?>
<div class="card reveal"><h3><?= h($c['title']) ?> <small><?= DAYS[date('N', strtotime($c['starts_at'])) - 1] ?>, <?= date('d.m H:i', strtotime($c['starts_at'])) ?> · <?= h($c['room']) ?></small></h3>
<p class="muted">Ҷойҳо: <?= count(array_filter($r, fn($x) => $x['status'] === 'booked')) ?>/<?= $c['capacity'] ?></p>
<div class="tbl"><table><?php foreach ($r as $x): ?><tr><td><?= h($x['full_name']) ?></td><td><?= h($x['phone']) ?></td><td><span class="badge s-<?= $x['status'] ?>"><?= $x['status'] === 'booked' ? 'Сабт шудааст' : 'Дар навбат' ?></span></td></tr><?php endforeach ?>
<?php if (!$r): ?><tr><td class="muted">Ҳанӯз касе сабт нашудааст</td></tr><?php endif ?></table></div></div>
<?php endforeach ?><?php foot();
