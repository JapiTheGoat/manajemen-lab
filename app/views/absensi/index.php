<?php partial('header', ['title' => 'Absensi & Tugas']); ?>
<h2>Absensi & Tugas</h2>
<div class="grid-absensi">
<div class="card">
<h3>Data Kehadiran</h3>
<?php if(is_role('asisten','dosen')): ?>
<form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;background:var(--light);padding:12px;border-radius:8px;margin-top:10px;">
<input type="hidden" name="add_absensi" value="1">
<select name="user_id" required style="flex:1;min-width:120px;padding:8px;border:1px solid #ccc;border-radius:5px;">
<option value="">-- Pilih Mahasiswa --</option>
<?php foreach($mahasiswa as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option><?php endforeach; ?>
</select>
<input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" style="padding:8px;border:1px solid #ccc;border-radius:5px;">
<select name="status" required style="padding:8px;border:1px solid #ccc;border-radius:5px;">
<option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option>
</select>
<input name="keterangan" placeholder="Keterangan/alasan" style="flex:1;min-width:100px;padding:8px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-primary" style="padding:8px 14px;font-size:12px;">Tambah</button>
</form>
<?php endif; ?>
<?php if(empty($absensi)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada data absensi</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;margin-top:15px;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th>Nama</th><th>Tanggal</th><th>Status</th><th>Ket</th><?php if(is_role('asisten','dosen')): ?><th>Aksi</th><?php endif; ?></tr>
<?php foreach($absensi as $a): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;font-weight:600;"><?= htmlspecialchars($a['nama']) ?></td>
<td><?= $a['tanggal'] ?></td>
<td><?php if(is_role('asisten','dosen')): ?>
<form method="POST" style="display:flex;gap:5px;align-items:center;">
<input type="hidden" name="edit_absensi" value="1">
<input type="hidden" name="absensi_id" value="<?= $a['id'] ?>">
<select name="status" style="padding:4px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
<option value="Hadir" <?= $a['status']==='Hadir'?'selected':'' ?>>Hadir</option>
<option value="Tidak Hadir" <?= $a['status']==='Tidak Hadir'?'selected':'' ?>>Tidak Hadir</option>
</select>
<input name="keterangan" value="<?= htmlspecialchars($a['keterangan']) ?>" placeholder="Alasan" style="width:90px;padding:4px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
<button class="btn-primary" style="padding:4px 8px;font-size:11px;">Simpan</button>
</form>
<?php else: ?>
<span class="badge-status <?= $a['status']==='Hadir'?'badge-approved':'badge-rejected' ?>"><?= $a['status'] ?></span>
<?php endif; ?></td>
<td style="font-size:12px;color:#666;"><?= htmlspecialchars($a['keterangan']) ?></td>
<?php if(is_role('asisten','dosen')): ?>
<td><a href="?url=absensi&delete_absen=<?= $a['id'] ?>" style="color:#EF4444;font-size:12px;" onclick="return confirm('Hapus?')">Hapus</a></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div style="display:flex;flex-direction:column;gap:20px;">
<?php if(is_role('asisten','dosen')): ?>
<div class="card">
<h3>➕ Tambah Tugas</h3>
<form method="POST" style="margin-top:10px;">
<input type="hidden" name="add_tugas" value="1">
<input name="judul" placeholder="Judul Tugas" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<label style="font-size:12px;">Deadline</label>
<input type="date" name="deadline" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<label style="font-size:12px;">Reminder</label>
<input type="datetime-local" name="reminder" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-primary" style="width:100%;">Tambah Tugas</button>
</form>
</div>
<?php endif; ?>

<div class="card">
<h3>✅ Checklist Tugas</h3>
<?php if(empty($tugas)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada tugas</p>
<?php elseif(is_role('mahasiswa')): ?>
<?php foreach($tugas as $t): $s = $tugas_status[$_SESSION['user_id']][$t['id']] ?? 'belum'; ?>
<div style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #eee;border-radius:8px;margin-bottom:8px;">
<a href="?url=absensi&toggle_tugas_siswa=<?= $t['id'] ?>-<?= $_SESSION['user_id'] ?>" style="text-decoration:none;font-size:20px;"><?= $s==='selesai'?'✅':'⬜' ?></a>
<div style="flex:1;">
<strong style="font-size:13px;<?= $s==='selesai'?'text-decoration:line-through;color:#888;':'' ?>"><?= htmlspecialchars($t['judul']) ?></strong>
<div style="font-size:11px;color:#666;margin-top:4px;">📅 <?= $t['deadline'] ?> • ⏰ <?= $t['reminder'] ?></div>
<div style="font-size:11px;margin-top:4px;color:<?= $s==='selesai'?'green':'#EF4444' ?>;font-weight:600;"><?= $s==='selesai'?'Tugas selesai':'Tugas belum selesai' ?></div>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:10px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);">
<th style="padding:6px;">Mahasiswa</th>
<?php foreach($tugas as $t): ?><th title="<?= htmlspecialchars($t['judul']) ?>" style="text-align:center;"><?= mb_substr(htmlspecialchars($t['judul']),0,8) ?>.</th><?php endforeach; ?>
</tr>
<?php foreach($mahasiswa as $m): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:8px;font-weight:600;"><?= htmlspecialchars($m['nama']) ?></td>
<?php foreach($tugas as $t): $s=$tugas_status[$m['id']][$t['id']]??'belum'; ?>
<td style="text-align:center;">
<a href="?url=absensi&toggle_tugas_siswa=<?= $t['id'] ?>-<?= $m['id'] ?>" style="text-decoration:none;font-size:16px;"><?= $s==='selesai'?'✅':'⬜' ?></a>
<div style="font-size:9px;color:<?= $s==='selesai'?'green':'#EF4444' ?>;"><?= $s==='selesai'?'selesai':'belum' ?></div>
<?php if($s!=='selesai'): ?>
<a href="?url=absensi&send_reminder=<?= $t['id'] ?>-<?= $m['id'] ?>" style="font-size:12px;text-decoration:none;display:block;margin-top:2px;" onclick="return confirm('Kirim reminder ke <?= htmlspecialchars($m['nama']) ?>?')" title="Kirim reminder">🔔</a>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
<div style="margin-top:15px;padding-top:15px;border-top:1px solid #eee;font-size:12px;">
<strong style="color:var(--primary);">Daftar Tugas:</strong><br>
<?php foreach($tugas as $t): ?>
<div style="padding:6px 0;border-bottom:1px solid #f5f5f5;">
<strong><?= htmlspecialchars($t['judul']) ?></strong> — deadline <?= $t['deadline'] ?>
<div style="float:right;display:flex;gap:10px;">
<a href="?url=absensi&send_reminder_all=<?= $t['id'] ?>" style="color:#F59E0B;font-weight:600;text-decoration:none;" onclick="return confirm('Reminder massal ke mahasiswa yang belum selesai?')">🔔 Reminder</a>
<a href="?url=absensi&delete_tugas=<?= $t['id'] ?>" style="color:#EF4444;text-decoration:none;" onclick="return confirm('Hapus tugas?')">[Hapus]</a>
</div>
<div style="clear:both;"></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
<?php partial('footer'); ?>