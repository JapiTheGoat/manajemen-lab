<?php
if (!isset($title)) $title = 'Sistem Lab';
$db = DB::connect();
$unread = $db->query("SELECT COUNT(*) FROM pengumuman")->fetchColumn();
$rem_count = 0;
if (is_role('mahasiswa')) {
    $rs = $db->prepare("SELECT COUNT(*) FROM reminders WHERE user_id=? AND is_read=0");
    $rs->execute([$_SESSION['user_id']]);
    $rem_count = $rs->fetchColumn();
}
$cur = $_GET['url'] ?? 'dashboard';
$flash_ok = flash('ok'); $flash_err = flash('err');
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title><?= $title ?></title>
<link rel="stylesheet" href="public/css/style.css"></head><body>
<div class="sidebar"><h2>Sistem Lab</h2>
<?php foreach ([
    'dashboard'=>'Dashboard','inventaris'=>'Inventaris','lab'=>'Status Lab',
    'absensi'=>'Absensi & Tugas','pengumuman'=>'Pengumuman','reservasi'=>'Reservasi',
    'lostfound'=>'Lost & Found','chat'=>'Chat'
] as $s=>$l): ?>
<a href="?url=<?= $s ?>" class="<?= $cur===$s?'active':'' ?>"><?= $l ?></a>
<?php endforeach; ?>
<a href="?url=logout" style="margin-top:auto;color:#FCA5A5;">Logout</a>
</div>
<div class="main-content"><div class="topbar">
<div class="header-left"><a href="?url=absensi" class="btn-header-absen">📋 Buka Absensi</a></div>
<div class="header-right">
<div class="search-bar"><input placeholder="Cari..."></div>
<div class="profile-section">
<div class="bell-icon" onclick="location.href='?url=pengumuman'" title="Pengumuman">🔔 <?php if($unread): ?><span class="badge"><?= $unread ?></span><?php endif; ?></div>
<?php if(is_role('mahasiswa') && $rem_count > 0): ?>
<div class="bell-icon" onclick="location.href='?url=absensi'" title="Reminder tugas" style="color:#F59E0B;">⏰ <span class="badge" style="background:#F59E0B;"><?= $rem_count ?></span></div>
<?php endif; ?>
<div class="avatar"><?= substr($_SESSION['nama']??'?',0,1) ?></div>
<div style="font-size:12px;font-weight:600;color:var(--primary);"><?= htmlspecialchars($_SESSION['nama']??'') ?><br><span style="color:var(--tertiary);"><?= ucfirst($_SESSION['role']??'') ?></span></div>
</div></div></div>
<div class="content-area">
<?php if($flash_ok): ?><div class="flash flash-ok">✅ <?= htmlspecialchars($flash_ok) ?></div><?php endif; ?>
<?php if($flash_err): ?><div class="flash flash-err">⚠️ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>