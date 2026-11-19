<?php
class PengumumanController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role'];
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("INSERT INTO pengumuman (judul,konten,created_by) VALUES (?,?,?)")
               ->execute([trim($_POST['judul']),trim($_POST['konten']),$_SESSION['user_id']]);
            flash('ok','Pengumuman dibuat'); redirect('pengumuman');
        }
        if (isset($_GET['delete']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("DELETE FROM pengumuman WHERE id=?")->execute([$_GET['delete']]);
            flash('ok','Pengumuman dihapus'); redirect('pengumuman');
        }
        $list = $db->query("SELECT p.*,u.nama FROM pengumuman p JOIN users u ON p.created_by=u.id ORDER BY p.created_at DESC")->fetchAll();
        view('pengumuman/index', compact('list'));
    }
}