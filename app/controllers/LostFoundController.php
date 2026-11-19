<?php
class LostFoundController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role']; $uid = $_SESSION['user_id'];
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['lapor'])) {
            $db->prepare("INSERT INTO lost_found (user_id,nama_barang,deskripsi) VALUES (?,?,?)")
               ->execute([$uid,trim($_POST['nama']),trim($_POST['deskripsi'])]);
            flash('ok','Laporan dibuat'); redirect('lostfound');
        }
        if (isset($_GET['delete_own'])) {
            $db->prepare("DELETE FROM lost_found WHERE id=? AND user_id=? AND status='hilang'")->execute([(int)$_GET['delete_own'],$uid]);
            flash('ok','Laporan dihapus'); redirect('lostfound');
        }
        if (isset($_GET['status'], $_GET['id']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("UPDATE lost_found SET status=? WHERE id=?")->execute([$_GET['status'],(int)$_GET['id']]);
            flash('ok','Status diperbarui'); redirect('lostfound');
        }
        $list = $db->query("SELECT l.*,u.nama FROM lost_found l JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC")->fetchAll();
        view('lost-found/index', compact('list'));
    }
}