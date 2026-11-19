<?php
class ReservasiController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role']; $uid = $_SESSION['user_id'];

        // Mahasiswa: ajukan
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajukan']) && $role==='mahasiswa') {
            $lab = trim($_POST['lab']); $tgl = $_POST['tanggal'];
            $mulai = $_POST['mulai']; $selesai = $_POST['selesai'];
            if (!$lab || !$tgl || !$mulai || !$selesai) { flash('err','Semua field wajib diisi'); redirect('reservasi'); }
            if ($tgl < date('Y-m-d')) { flash('err','Tanggal tidak boleh lewat'); redirect('reservasi'); }
            if ($mulai >= $selesai) { flash('err','Waktu selesai harus setelah mulai'); redirect('reservasi'); }
            // Cek bentrok dengan reservasi approved
            $c = $db->prepare("SELECT COUNT(*) FROM reservasi WHERE lab=? AND tanggal=? AND status='approved' AND NOT (waktu_selesai<=? OR waktu_mulai>=?)");
            $c->execute([$lab,$tgl,$mulai,$selesai]);
            if ($c->fetchColumn() > 0) { flash('err','Jadwal bentrok dengan reservasi lain'); redirect('reservasi'); }
            $db->prepare("INSERT INTO reservasi (user_id,lab,tanggal,waktu_mulai,waktu_selesai,keterangan) VALUES (?,?,?,?,?,?)")
               ->execute([$uid,$lab,$tgl,$mulai,$selesai,trim($_POST['keterangan'])]);
            flash('ok','Reservasi diajukan, menunggu persetujuan'); redirect('reservasi');
        }

        // Mahasiswa: batalkan
        if (isset($_GET['cancel']) && $role==='mahasiswa') {
            $db->prepare("DELETE FROM reservasi WHERE id=? AND user_id=? AND status='pending'")
               ->execute([(int)$_GET['cancel'], $uid]);
            flash('ok','Reservasi dibatalkan'); redirect('reservasi');
        }

        // Asisten/Dosen: ACC
        if (isset($_GET['approve']) && in_array($role,['asisten','dosen'])) {
            $id = (int)$_GET['approve'];
            $r = $db->query("SELECT * FROM reservasi WHERE id=$id AND status='pending'")->fetch();
            if ($r) {
                $c = $db->prepare("SELECT COUNT(*) FROM reservasi WHERE lab=? AND tanggal=? AND status='approved' AND id!=? AND NOT (waktu_selesai<=? OR waktu_mulai>=?)");
                $c->execute([$r['lab'],$r['tanggal'],$id,$r['waktu_mulai'],$r['waktu_selesai']]);
                if ($c->fetchColumn()>0) { flash('err','Bentrok dengan reservasi lain'); }
                else {
                    $db->prepare("UPDATE reservasi SET status='approved' WHERE id=?")->execute([$id]);
                    flash('ok','Reservasi di-ACC');
                }
            }
            redirect('reservasi');
        }
        // Asisten/Dosen: Tolak
        if (isset($_GET['reject']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("UPDATE reservasi SET status='rejected' WHERE id=? AND status='pending'")->execute([(int)$_GET['reject']]);
            flash('ok','Reservasi ditolak'); redirect('reservasi');
        }

        $list = $role==='mahasiswa'
            ? $db->query("SELECT * FROM reservasi WHERE user_id=$uid ORDER BY tanggal DESC, waktu_mulai DESC")->fetchAll()
            : $db->query("SELECT r.*,u.nama FROM reservasi r JOIN users u ON r.user_id=u.id ORDER BY r.status='pending' DESC, r.tanggal DESC, r.waktu_mulai DESC")->fetchAll();
        view('reservasi/index', compact('list'));
    }
}