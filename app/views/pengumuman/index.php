<?php partial('header', ['title' => 'Pengumuman']); ?>
<h2>Pengumuman</h2>
<?php if(is_role('asisten','dosen')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Buat Pengumuman</h3>
<form method="POST" style="margin-top:10px;">
<input type="hidden" name="add" value="1">
<input name="judul" placeholder="Judul" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;">
<textarea name="konten" placeholder="Isi pengumuman..." required style="width:100%;padding:10px;height:100px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></textarea>
<button class="btn-primary">Kirim</button>
</form>
</div>
<?php endif; ?>
<?php if(empty($list)): ?>
<div class="card"><p style="color:#888;text-align:center;padding:20px;">Belum ada pengumuman</p></div>
<?php endif; ?>
<?php foreach($list as $p): ?>
<div class="card" style="margin-bottom:15px;position:relative;">
<h3 style="border:none;padding-bottom:0;margin-bottom:8px;"><?= htmlspecialchars($p['judul']) ?></h3>
<p style="margin:8px 0;font-size:14px;line-height:1.6;"><?= nl2br(htmlspecialchars($p['konten'])) ?></p>
<small style="color:#888;">Oleh <?= htmlspecialchars($p['nama']) ?> • <?= $p['created_at'] ?></small>
<?php if(is_role('asisten','dosen')): ?>
<a href="?url=pengumuman&delete=<?= $p['id'] ?>" style="color:#EF4444;font-size:12px;position:absolute;top:20px;right:20px;" onclick="return confirm('Hapus pengumuman?')">Hapus</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php partial('footer'); ?>