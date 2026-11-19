<?php partial('header', ['title' => 'Status Lab']); ?>
<h2>Status & Pengaturan Lab</h2>
<div class="grid-3">
<div class="card">
<h3>Status Lab</h3>
<form method="POST">
<input type="hidden" name="update_status" value="1">
<label>Status</label>
<select name="is_occupied" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;">
<option value="1" <?= $status['is_occupied']?'selected':'' ?>>Dipakai</option>
<option value="0" <?= !$status['is_occupied']?'selected':'' ?>>Kosong</option>
</select>
<label>Aktivitas</label>
<input name="aktivitas" value="<?= htmlspecialchars($status['aktivitas']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<label>Dosen Mengajar</label>
<input name="dosen_mengajar" value="<?= htmlspecialchars($status['dosen_mengajar']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<label>Pemegang Kunci</label>
<input name="pemegang_kunci" value="<?= htmlspecialchars($status['pemegang_kunci']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<?php if(is_role('asisten','dosen')): ?><button class="btn-primary" style="width:100%;">Simpan Status</button><?php endif; ?>
</form>
</div>
<div class="card">
<h3>Jumlah Siswa</h3>
<form method="POST">
<input type="hidden" name="update_jumlah" value="1">
<input type="number" name="jumlah_siswa" value="<?= $settings['jumlah_siswa'] ?>" min="1" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('dosen')?'readonly':'' ?>>
<?php if(is_role('dosen')): ?><button class="btn-primary" style="width:100%;">Update</button><?php endif; ?>
</form>
<p style="font-size:12px;color:#666;margin-top:10px;">Hanya dosen yang dapat mengubah.</p>
</div></div>
<?php partial('footer'); ?>