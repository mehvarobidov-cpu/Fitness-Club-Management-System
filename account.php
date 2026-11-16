<?php require 'config.php'; need(['client']); chk(); $u = user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? ''; $id = (int)($_POST['id'] ?? 0);
  if ($a === 'freeze') flash(freeze($pdo, $id, $u['id']));
  elseif ($a === 'unfreeze') { $m = find($pdo, $id); flash($m && $m['user_id'] == $u['id'] ? unfreeze($pdo, $id) : 'Хато'); }
  elseif ($a === 'cancel') flash(cancel_booking($pdo, $u['id'], (int)$_POST['cid']));
  header('Location: account.php'); exit;
}
$ms = $pdo->prepare('SELECT m.*,t.name tname FROM memberships m JOIN membership_types t ON t.id=m.type_id WHERE m.user_id=? ORDER BY m.status="active" DESC,m.end_date DESC'); $ms->execute([$u['id']]); $ms = $ms->fetchAll();
$bk = $pdo->prepare("SELECT b.status,c.* FROM class_bookings b JOIN classes c ON c.id=b.class_id WHERE b.user_id=? AND b.status<>'cancelled' AND c.starts_at>? AND c.cancelled=0 ORDER BY c.starts_at"); $bk->execute([$u['id'], date('Y-m-d H:i:s')]); $bk = $bk->fetchAll();
$vs = $pdo->prepare('SELECT v.visited_at,c.title FROM visits v LEFT JOIN classes c ON c.id=v.class_id WHERE v.user_id=? ORDER BY v.id DESC LIMIT 15'); $vs->execute([$u['id']]); $vs = $vs->fetchAll();
$msg = flash(); $T = csrf(); head('Утоқи ман'); ?>
<h1 class="reveal">Салом, <?= h($u['name']) ?> 👋</h1>
<?php if ($msg): ?><div class="flash"><?= h($msg) ?></div><?php endif ?>
<h2 class="reveal">Абонементҳои ман</h2>
<?php if (!$ms): ?><p class="muted">Абонемент надоред. Аз маъмур хариред.</p><?php endif ?>
<div class="grid2"><?php foreach ($ms as $m): $pct = $m['visits_total'] ? round($m['visits_left'] / $m['visits_total'] * 100) : 100; $fl = FREEZE_MAX - $m['freeze_days_used']; ?>
<div class="card reveal"><h3><?= h($m['tname']) ?> <?= mbadge($m['status']) ?></h3>
<p class="price"><?= $m['visits_left'] === null ? '∞' : $m['visits_left'] ?> <small>ташриф боқӣ<?= $m['visits_total'] ? ' аз ' . $m['visits_total'] : '' ?></small></p>
<div class="bar"><i style="width:<?= $pct ?>%"></i></div>
<p class="muted"><?= h($m['start_date']) ?> → <b><?= h($m['end_date']) ?></b><?= $m['status'] === 'frozen' ? '<br>Боздошта шудааст аз ' . h($m['frozen_at']) : '' ?></p>
<form method="post"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="id" value="<?= $m['id'] ?>">
<?php if ($m['status'] === 'active' && $fl > 0): ?><button class="btn sm ghost" name="a" value="freeze">❄ Боздоштан (боқӣ: <?= $fl ?> рӯз)</button>
<?php elseif ($m['status'] === 'frozen'): ?><button class="btn sm" name="a" value="unfreeze">▶ Идома додан</button><?php endif ?></form></div><?php endforeach ?></div>
<h2 class="reveal">Сабтҳои ман</h2><div class="card reveal"><div class="tbl"><table><tr><th>Машғулият</th><th>Вақт</th><th>Ҳолат</th><th></th></tr>
<?php foreach ($bk as $b): ?><tr><td><?= h($b['title']) ?></td><td><?= h(date('d.m H:i', strtotime($b['starts_at']))) ?></td><td><span class="badge s-<?= $b['status'] ?>"><?= $b['status'] === 'booked' ? 'Сабт шудааст' : 'Дар навбат' ?></span></td>
<td><form method="post"><input type="hidden" name="t" value="<?= $T ?>"><input type="hidden" name="cid" value="<?= $b['id'] ?>"><button class="btn sm danger" name="a" value="cancel">Бекор</button></form></td></tr><?php endforeach ?>
<?php if (!$bk): ?><tr><td colspan="4" class="muted">Сабт нест. <a href="schedule.php">Ба ҷадвал равед</a></td></tr><?php endif ?></table></div></div>
<h2 class="reveal">Таърихи ташрифҳо</h2><div class="card reveal"><ul class="tl"><?php foreach ($vs as $v): ?><li><b><?= h(date('d.m.Y H:i', strtotime($v['visited_at']))) ?></b> <span class="muted"><?= h($v['title'] ?? 'Машқи озод') ?></span></li><?php endforeach ?><?php if (!$vs) echo '<li class="muted">Ташриф нест</li>' ?></ul></div>
<?php foot();
