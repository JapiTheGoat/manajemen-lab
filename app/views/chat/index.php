<?php partial('header', ['title' => 'Chat']); ?>
<h2>Chat Pribadi</h2>
<div class="card" style="display:grid;grid-template-columns:220px 1fr;height:600px;padding:0;overflow:hidden;">
<div style="border-right:1px solid #eee;overflow-y:auto;padding:15px;background:#FAFBFC;">
<h3 style="font-size:14px;margin-bottom:10px;border:none;padding:0;">Kontak</h3>
<div id="user-list"><p style="color:#888;font-size:12px;">Memuat...</p></div>
</div>
<div style="display:flex;flex-direction:column;overflow:hidden;">
<div style="padding:15px;border-bottom:1px solid #eee;background:#fff;">
<strong id="chat-with" style="color:var(--primary);">Pilih kontak untuk mulai chat</strong>
</div>
<div id="chat-box" style="flex:1;overflow-y:auto;padding:15px;background:#F8FAFC;"></div>
<div style="padding:15px;border-top:1px solid #eee;display:flex;gap:10px;background:#fff;">
<input id="chat-input" placeholder="Ketik pesan..." style="flex:1;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;">
<button class="btn-primary" onclick="sendMsg()">Kirim</button>
</div>
</div>
</div>
<script>window.MY_ID = <?= $_SESSION['user_id'] ?>;</script>
<?php partial('footer'); ?>