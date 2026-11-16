<?php require 'config.php';
$types = $pdo->query('SELECT * FROM membership_types WHERE active=1 ORDER BY price')->fetchAll();
$next = $pdo->prepare('SELECT c.*,u.full_name tn FROM classes c JOIN trainers t ON t.id=c.trainer_id JOIN users u ON u.id=t.user_id WHERE c.cancelled=0 AND c.starts_at>? ORDER BY c.starts_at LIMIT 4');
$next->execute([date('Y-m-d H:i:s')]); head('Асосӣ'); ?>
<section class="hero"><span class="pill reveal">Фитнес-клуб · машғулиятҳои гурӯҳӣ</span>
<h1 class="reveal">Қувват. <span class="grad">Энергия.</span> Натиҷа.</h1>
<p class="reveal">Абонементро интихоб кунед, ба машғулиятҳои гурӯҳӣ онлайн сабт шавед ва ташрифҳоро дар утоқи шахсӣ пайгирӣ кунед.</p>
<a class="btn reveal" href="register.php">Оғоз кардан →</a> <a class="btn ghost reveal" href="schedule.php">Ҷадвали ҳафта</a></section>
<h2 class="reveal">Абонементҳо</h2>
<div class="grid3"><?php foreach ($types as $t): ?><div class="card tilt reveal"><h3><?= h($t['name']) ?></h3><div class="price"><?= money($t['price']) ?></div>
<p class="muted"><?= $t['visits'] === null ? 'Ташрифҳои беҳад' : $t['visits'] . ' ташриф' ?> · <?= $t['duration_days'] ?> рӯз</p></div><?php endforeach ?></div>
<h2 class="reveal">Машғулиятҳои наздик</h2>
<?php foreach ($next as $c): ?><div class="card cls reveal"><div class="t"><?= date('H:i', strtotime($c['starts_at'])) ?></div><div class="i"><b><?= h($c['title']) ?></b><br><span class="muted"><?= DAYS[date('N', strtotime($c['starts_at'])) - 1] ?>, <?= date('d.m', strtotime($c['starts_at'])) ?> · <?= h($c['tn']) ?></span></div></div><?php endforeach ?>
<?php foot();
