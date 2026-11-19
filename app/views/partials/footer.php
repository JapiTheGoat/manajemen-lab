<?php
if (is_role('mahasiswa')):
    $db = DB::connect();
    $r = $db->prepare("SELECT r.id, t.judul, t.deadline FROM reminders r JOIN tugas t ON r.tugas_id=t.id WHERE r.user_id=? AND r.is_read=0 ORDER BY r.created_at DESC");
    $r->execute([$_SESSION['user_id']]);
    $rem_list = $r->fetchAll();
    if (!empty($rem_list)):
?>
<div class="popup-overlay" id="reminderPopup" style="display:flex;z-index:1100;">
<div class="popup-content card" style="text-align:left;">
<h3 style="border:none;padding:0;">🔔 Pengingat Tugas</h3>
<p style="font-size:13px;color:#666;margin:10px 0 15px;">Anda memiliki <strong><?= count($rem_list) ?></strong> tugas yang belum diselesaikan:</p>
<?php foreach ($rem_list as $r): ?>
<div style="padding:10px 12px;border-left:4px solid #F59E0B;background:#FEF3C7;border-radius:6px;margin-bottom:8px;">
<strong style="color:var(--primary);font-size:14px;"><?= htmlspecialchars($r['judul']) ?></strong>
<div style="font-size:12px;color:#666;margin-top:4px;">📅 Deadline: <strong><?= $r['deadline'] ?></strong></div>
</div>
<?php endforeach; ?>
<button class="btn-primary" style="width:100%;margin-top:10px;" onclick="dismissReminders()">Mengerti</button>
</div>
</div>
<?php endif; endif; ?>
</div></div><script src="public/js/script.js"></script></body></html>