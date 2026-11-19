<?php partial('header', ['title' => 'Dashboard']); ?>
<h2>Dashboard</h2>
<div class="grid-3">
<div class="card">
<h3>Status Lab</h3>
<p style="font-size:20px;font-weight:bold;color:<?= $status['is_occupied']?'var(--tertiary)':'#10B981' ?>;margin:8px 0;"><?= $status['is_occupied']?'SEDANG DIPAKAI':'KOSONG' ?></p>
<p style="font-size:13px;margin:4px 0;">Aktivitas: <strong><?= htmlspecialchars($status['aktivitas']) ?></strong></p>
<p style="font-size:13px;margin:4px 0;">Dosen: <?= htmlspecialchars($status['dosen_mengajar']) ?></p>
<p style="font-size:13px;margin:4px 0;">Kunci: <?= htmlspecialchars($status['pemegang_kunci']) ?></p>
</div>
<div class="card">
<h3>Statistik</h3>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
<div><small style="color:#888;">Mahasiswa</small><p style="font-size:24px;font-weight:bold;color:var(--primary);"><?= $stats['mahasiswa'] ?></p></div>
<div><small style="color:#888;">Tugas Aktif</small><p style="font-size:24px;font-weight:bold;color:var(--tertiary);"><?= $stats['tugas_aktif'] ?></p></div>
<div><small style="color:#888;">Stok Menipis</small><p style="font-size:24px;font-weight:bold;color:#EF4444;"><?= $stats['stok_menipis'] ?></p></div>
<div><small style="color:#888;">Reservasi Pending</small><p style="font-size:24px;font-weight:bold;color:#F59E0B;"><?= $stats['reservasi_pending'] ?></p></div>
</div>
</div>
<div class="card">
<h3>Jumlah Siswa</h3>
<p style="font-size:36px;font-weight:bold;color:var(--primary);text-align:center;margin:15px 0;"><?= $settings['jumlah_siswa'] ?></p>
<p style="color:#666;font-size:12px;text-align:center;">Kapasitas maksimal lab</p>
</div>
</div>
<div class="card" style="margin-top:20px;">
<h3>Menu Cepat</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:10px;">
<a href="?url=absensi" class="btn-primary" style="text-align:center;text-decoration:none;">Absensi & Tugas</a>
<a href="?url=reservasi" class="btn-outline" style="text-align:center;text-decoration:none;">Reservasi</a>
<a href="?url=lostfound" class="btn-outline" style="text-align:center;text-decoration:none;">Lost & Found</a>
<a href="?url=chat" class="btn-outline" style="text-align:center;text-decoration:none;">Chat</a>
</div>
</div>
<?php if ($show_popup && !empty($announcements)): ?>
<div class="popup-overlay" id="popupPengumuman" style="display:flex;">
<div class="popup-content card">
<h3>📢 Pengumuman Terbaru</h3>
<?php foreach ($announcements as $a): ?>
<div style="border-bottom:1px solid #eee;padding:10px 0;">
<strong style="color:var(--primary);"><?= htmlspecialchars($a['judul']) ?></strong>
<p style="font-size:13px;color:#555;margin-top:4px;"><?= htmlspecialchars($a['konten']) ?></p>
</div>
<?php endforeach; ?>
<button class="btn-primary" style="margin-top:15px;width:100%;" onclick="document.getElementById('popupPengumuman').style.display='none'">Tutup</button>
</div></div>
<?php endif; ?>
<?php partial('footer'); ?>