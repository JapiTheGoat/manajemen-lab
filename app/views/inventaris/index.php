<?php partial('header', ['title' => 'Inventaris']); ?>
<h2>Inventaris & Restock</h2>
<?php if (is_role('dosen')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Tambah Barang Baru</h3>
<form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
<input type="hidden" name="add_barang" value="1">
<input name="nama" placeholder="Nama Barang" required style="padding:10px;border:1px solid #ccc;border-radius:8px;">
<input type="number" name="stok" placeholder="Stok" required min="0" style="padding:10px;border:1px solid #ccc;border-radius:8px;width:100px;">
<input type="number" name="min_stok" placeholder="Min Stok" required min="0" style="padding:10px;border:1px solid #ccc;border-radius:8px;width:100px;">
<button class="btn-primary">Tambah</button>
</form></div>
<?php endif; ?>
<div class="grid-3">
<?php foreach($items as $i): $p=min(100,($i['stok']/max(1,$i['min_stok']*3))*100); ?>
<div class="card">
<h3><?= htmlspecialchars($i['nama']) ?></h3>
<p>Sisa: <strong><?= $i['stok'] ?></strong> <small style="color:#888;">(min: <?= $i['min_stok'] ?>)</small></p>
<?php if($i['stok']<=$i['min_stok']): ?><p style="color:#EF4444;font-size:12px;margin-top:4px;">⚠️ Stok Menipis</p><?php endif; ?>
<div class="progress-container"><div class="progress-bar" style="width:<?= $p ?>%;background:<?= $p<30?'#EF4444':'var(--tertiary)' ?>;"></div></div>
<?php if(is_role('dosen')): ?>
<form method="POST" style="margin-top:15px;display:flex;gap:5px;flex-wrap:wrap;">
<input type="hidden" name="edit_barang" value="1">
<input type="hidden" name="id" value="<?= $i['id'] ?>">
<input name="nama" value="<?= htmlspecialchars($i['nama']) ?>" required style="flex:1;min-width:100px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<input type="number" name="stok" value="<?= $i['stok'] ?>" required min="0" style="width:60px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<input type="number" name="min_stok" value="<?= $i['min_stok'] ?>" required min="0" style="width:60px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-outline" style="padding:5px 10px;font-size:11px;">Edit</button>
<a href="?url=inventaris&delete_barang=<?= $i['id'] ?>" style="color:#EF4444;font-size:12px;align-self:center;" onclick="return confirm('Hapus?')">Hapus</a>
</form>
<?php elseif(is_role('asisten')): ?>
<form method="POST" style="margin-top:15px;display:flex;gap:5px;">
<input type="hidden" name="usul_restock" value="1">
<input type="hidden" name="inventaris_id" value="<?= $i['id'] ?>">
<input type="number" name="jumlah" placeholder="Jumlah" required min="1" style="width:80px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-outline" style="padding:5px 10px;font-size:12px;">Usul Restock</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<div class="card" style="margin-top:30px;">
<h3>Riwayat Pengajuan Restock</h3>
<?php if(empty($requests)): ?><p style="color:#888;text-align:center;padding:15px;">Belum ada pengajuan</p><?php else: ?>
<table style="width:100%;border-collapse:collapse;margin-top:10px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th>Barang</th><th>Jumlah</th><th>Pemohon</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($requests as $r): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;"><?= htmlspecialchars($r['barang']) ?></td>
<td>+<?= $r['jumlah'] ?></td>
<td><?= htmlspecialchars($r['pemohon']) ?></td>
<td><span class="badge-status badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
<td><?php if($r['status']==='pending' && is_role('dosen')): ?>
<a href="?url=inventaris&approve_restock=<?= $r['id'] ?>" style="color:green;margin-right:10px;font-weight:600;">ACC</a>
<a href="?url=inventaris&reject_restock=<?= $r['id'] ?>" style="color:red;">Tolak</a>
<?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>