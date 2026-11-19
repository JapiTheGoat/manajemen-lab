<?php partial('header', ['title' => 'Lost & Found']); ?>
<h2>Lost & Found</h2>
<div class="card" style="margin-bottom:20px;">
<h3>Laporkan Barang</h3>
<form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
<input type="hidden" name="lapor" value="1">
<input name="nama" placeholder="Nama Barang" required style="padding:10px;flex:1;min-width:150px;border:1px solid #ccc;border-radius:8px;">
<input name="deskripsi" placeholder="Deskripsi/lokasi terakhir" style="padding:10px;flex:2;min-width:200px;border:1px solid #ccc;border-radius:8px;">
<button class="btn-primary">Lapor</button>
</form>
</div>
<div class="card">
<h3>Daftar Barang</h3>
<?php if(empty($list)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada laporan</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th style="padding:8px;">Barang</th><th>Deskripsi</th><th>Pelapor</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($list as $l): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;font-weight:600;"><?= htmlspecialchars($l['nama_barang']) ?></td>
<td><?= htmlspecialchars($l['deskripsi']) ?></td>
<td><?= htmlspecialchars($l['nama']) ?></td>
<td><span class="badge-status badge-<?= $l['status']==='hilang'?'rejected':($l['status']==='ditemukan'?'pending':'approved') ?>"><?= $l['status'] ?></span></td>
<td>
<?php if($l['user_id']==$_SESSION['user_id'] && $l['status']==='hilang'): ?>
<a href="?url=lostfound&delete_own=<?= $l['id'] ?>" style="color:#EF4444;font-size:12px;" onclick="return confirm('Hapus laporan?')">Hapus</a>
<?php endif; ?>
<?php if(is_role('asisten','dosen')): ?>
<a href="?url=lostfound&status=ditemukan&id=<?= $l['id'] ?>" style="color:#F59E0B;font-size:12px;margin-left:6px;">Ditemukan</a>
<a href="?url=lostfound&status=diklaim&id=<?= $l['id'] ?>" style="color:green;font-size:12px;margin-left:6px;">Diklaim</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>