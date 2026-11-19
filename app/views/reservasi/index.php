<?php partial('header', ['title' => 'Reservasi']); ?>
<h2>Reservasi Lab</h2>
<?php if(is_role('mahasiswa')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Ajukan Reservasi</h3>
<form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
<input type="hidden" name="ajukan" value="1">
<div style="grid-column:1/-1;"><label>Nama Lab</label>
<input name="lab" placeholder="Contoh: Lab Komputer 1" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div><label>Tanggal</label>
<input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div style="display:flex;gap:10px;">
<div style="flex:1;"><label>Mulai</label>
<input type="time" name="mulai" required value="08:00" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div style="flex:1;"><label>Selesai</label>
<input type="time" name="selesai" required value="10:00" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
</div>
<div style="grid-column:1/-1;"><label>Keterangan</label>
<textarea name="keterangan" placeholder="Keperluan reservasi..." style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;height:60px;"></textarea></div>
<div style="grid-column:1/-1;"><button class="btn-primary" style="width:100%;">Ajukan Reservasi</button></div>
</form>
</div>
<?php endif; ?>
<div class="card">
<h3>Daftar Reservasi<?= is_role('mahasiswa') ? ' Saya' : ' Semua Mahasiswa' ?></h3>
<?php if(empty($list)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada reservasi</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);">
<?php if(!is_role('mahasiswa')): ?><th style="padding:8px;">Pemohon</th><?php endif; ?>
<th>Lab</th><th>Tanggal</th><th>Waktu</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($list as $r): ?>
<tr style="border-bottom:1px solid #eee;">
<?php if(!is_role('mahasiswa')): ?><td style="padding:10px;font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td><?php endif; ?>
<td><?= htmlspecialchars($r['lab']) ?></td>
<td><?= $r['tanggal'] ?></td>
<td><?= $r['waktu_mulai'] ?> - <?= $r['waktu_selesai'] ?></td>
<td style="font-size:12px;color:#666;"><?= htmlspecialchars($r['keterangan']) ?></td>
<td><span class="badge-status badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
<td>
<?php if($r['status']==='pending' && is_role('asisten','dosen')): ?>
<a href="?url=reservasi&approve=<?= $r['id'] ?>" style="color:green;font-weight:600;text-decoration:none;">ACC</a> | 
<a href="?url=reservasi&reject=<?= $r['id'] ?>" style="color:red;text-decoration:none;">Tolak</a>
<?php elseif($r['status']==='pending' && is_role('mahasiswa')): ?>
<a href="?url=reservasi&cancel=<?= $r['id'] ?>" style="color:#EF4444;font-size:12px;text-decoration:none;" onclick="return confirm('Batalkan reservasi ini?')">Batalkan</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>