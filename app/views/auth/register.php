<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Daftar</title><link rel="stylesheet" href="public/css/style.css"></head>
<body style="display:flex;justify-content:center;align-items:center;height:100vh;background:var(--light);">
<div class="card" style="width:400px;">
<h2 style="color:var(--primary);text-align:center;">Daftar Akun</h2>
<p style="text-align:center;font-size:12px;color:#666;margin-top:8px;">Registrasi hanya untuk mahasiswa</p>
<?php if(!empty($error)): ?><p style="color:red;text-align:center;background:#FEE2E2;padding:8px;border-radius:6px;margin-top:15px;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<form method="POST" style="display:flex;flex-direction:column;gap:15px;margin-top:20px;">
<input type="text" name="nama" placeholder="Nama Lengkap" required style="padding:12px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<input type="email" name="email" placeholder="Email" required style="padding:12px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<input type="password" name="password" placeholder="Password (min 6 karakter)" required minlength="6" style="padding:12px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<button class="btn-primary">Daftar</button>
</form>
<p style="text-align:center;margin-top:15px;font-size:14px;">Sudah punya akun? <a href="?url=login" style="color:var(--secondary);font-weight:600;">Login</a></p>
</div></body></html>