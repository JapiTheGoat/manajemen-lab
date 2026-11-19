<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Login</title><link rel="stylesheet" href="public/css/style.css"></head>
<body style="display:flex;justify-content:center;align-items:center;height:100vh;background:var(--light);">
<div class="card" style="width:400px;">
<h2 style="color:var(--primary);text-align:center;">Login Sistem Lab</h2>
<?php $ok = flash('ok'); if($ok): ?><p style="color:green;text-align:center;background:#D1FAE5;padding:8px;border-radius:6px;"><?= htmlspecialchars($ok) ?></p><?php endif; ?>
<?php if(!empty($error)): ?><p style="color:red;text-align:center;background:#FEE2E2;padding:8px;border-radius:6px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="POST" style="display:flex;flex-direction:column;gap:15px;margin-top:20px;">
<input type="email" name="email" placeholder="Email" required style="padding:12px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<input type="password" name="password" placeholder="Password" required style="padding:12px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<button class="btn-primary">Masuk</button>
</form>
<p style="text-align:center;margin-top:15px;font-size:14px;">Belum punya akun? <a href="?url=register" style="color:var(--secondary);font-weight:600;">Daftar</a></p>
<div style="margin-top:20px;padding:12px;background:var(--light);border-radius:8px;font-size:12px;color:#666;">
<strong>Demo akun:</strong><br>
Asisten: budi@lab.com<br>Dosen: dosen@lab.com<br>Mahasiswa: siti@lab.com<br>
Password: <code>password</code>
</div>
</div></body></html>