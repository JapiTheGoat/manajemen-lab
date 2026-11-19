<?php
require_once BASE_PATH . '/app/models/Inventaris.php';
class InventarisController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role']; $uid = $_SESSION['user_id'];
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_barang']) && $role==='dosen') {
            $db->prepare("INSERT INTO inventaris (nama,stok,min_stok) VALUES (?,?,?)")->execute([trim($_POST['nama']),(int)$_POST['stok'],(int)$_POST['min_stok']]);
            flash('ok','Barang ditambahkan'); redirect('inventaris');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_barang']) && $role==='dosen') {
            $db->prepare("UPDATE inventaris SET nama=?, stok=?, min_stok=? WHERE id=?")->execute([trim($_POST['nama']),(int)$_POST['stok'],(int)$_POST['min_stok'],$_POST['id']]);
            flash('ok','Barang diperbarui'); redirect('inventaris');
        }
        if (isset($_GET['delete_barang']) && $role==='dosen') {
            $db->prepare("DELETE FROM restock_requests WHERE inventaris_id=?")->execute([$_GET['delete_barang']]);
            $db->prepare("DELETE FROM inventaris WHERE id=?")->execute([$_GET['delete_barang']]);
            flash('ok','Barang dihapus'); redirect('inventaris');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['usul_restock']) && $role==='asisten') {
            $db->prepare("INSERT INTO restock_requests (inventaris_id,jumlah,requested_by) VALUES (?,?,?)")->execute([$_POST['inventaris_id'],(int)$_POST['jumlah'],$uid]);
            flash('ok','Usulan restock dikirim'); redirect('inventaris');
        }
        if (isset($_GET['approve_restock']) && $role==='dosen') {
            $r = $db->query("SELECT * FROM restock_requests WHERE id=".(int)$_GET['approve_restock'])->fetch();
            if ($r && $r['status']==='pending') {
                $db->prepare("UPDATE restock_requests SET status='approved', approved_by=? WHERE id=?")->execute([$uid, $r['id']]);
                $db->prepare("UPDATE inventaris SET stok=stok+? WHERE id=?")->execute([$r['jumlah'], $r['inventaris_id']]);
                flash('ok','Restock di-ACC');
            }
            redirect('inventaris');
        }
        if (isset($_GET['reject_restock']) && $role==='dosen') {
            $db->prepare("UPDATE restock_requests SET status='rejected', approved_by=? WHERE id=?")->execute([$uid, $_GET['reject_restock']]);
            flash('ok','Restock ditolak'); redirect('inventaris');
        }
        $items = Inventaris::getAll(); $requests = Inventaris::getRequests();
        view('inventaris/index', compact('items','requests'));
    }
}