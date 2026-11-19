function showAnnouncement(){var p=document.getElementById('popupPengumuman');if(p)p.style.display='flex';}
window.dismissReminders = function() {
    var p = document.getElementById('reminderPopup');
    if (p) p.style.display = 'none';
    fetch('?url=absensi&read_reminders=1').catch(function(){});
};
(function(){
    var userList = document.getElementById('user-list');
    if (!userList) return;
    var currentTarget = null, pollTimer = null;
    function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
    function loadUsers(){
        fetch('?url=chat&action=users').then(r=>r.json()).then(users=>{
            if (!users.length) { userList.innerHTML = '<p style="color:#888;font-size:12px;">Belum ada user lain</p>'; return; }
            userList.innerHTML = users.map(u => '<div class="chat-user" data-id="'+u.id+'" data-nama="'+esc(u.nama)+'"><strong>'+esc(u.nama)+'</strong><br><small style="color:#888;font-size:11px;">'+esc(u.role)+'</small></div>').join('');
            userList.querySelectorAll('.chat-user').forEach(el=>{ el.onclick = ()=>selectUser(parseInt(el.dataset.id), el.dataset.nama, el); });
        });
    }
    function selectUser(id, nama, el){
        currentTarget = id;
        document.getElementById('chat-with').textContent = 'Chat dengan ' + nama;
        userList.querySelectorAll('.chat-user').forEach(x=>x.classList.remove('active'));
        el.classList.add('active');
        loadMessages();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(loadMessages, 3000);
    }
    function loadMessages(){
        if (!currentTarget) return;
        fetch('?url=chat&action=fetch&target='+currentTarget).then(r=>r.json()).then(msgs=>{
            var box = document.getElementById('chat-box');
            if (!msgs.length) { box.innerHTML = '<p style="text-align:center;color:#888;margin-top:20px;font-size:12px;">Belum ada pesan</p>'; return; }
            box.innerHTML = msgs.map(m=>{
                var me = m.sender_id == window.MY_ID;
                return '<div style="margin:8px 0;text-align:'+(me?'right':'left')+';">'+
                    '<span style="display:inline-block;padding:8px 12px;border-radius:10px;max-width:70%;font-size:13px;'+
                    (me?'background:var(--tertiary);color:#fff;':'background:#fff;border:1px solid #eee;')+'">'+esc(m.pesan)+'</span>'+
                    '<div style="font-size:10px;color:#999;margin-top:2px;">'+esc(m.waktu)+'</div></div>';
            }).join('');
            box.scrollTop = box.scrollHeight;
        });
    }
    window.sendMsg = function(){
        if (!currentTarget) { alert('Pilih kontak dulu'); return; }
        var input = document.getElementById('chat-input');
        var v = input.value.trim();
        if (!v) return;
        var f = new FormData();
        f.append('action','send'); f.append('receiver', currentTarget); f.append('msg', v);
        fetch('?url=chat',{method:'POST',body:f}).then(()=>{ input.value=''; loadMessages(); });
    };
    loadUsers();
})();