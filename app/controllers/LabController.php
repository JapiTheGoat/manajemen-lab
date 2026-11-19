<?php
class LabController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role'];
        if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($role,['asisten','dosen'])) {
            if (isset($_POST['update_status'])) {
                $db->prepare("UPDATE status_lab SET is_occupied=?, aktivitas=?, dosen_mengajar=?, pemegang_kunci=? WHERE id=1")
                   ->execute([(int)$_POST['is_occupied'], $_POST['aktivitas'], $_POST['dosen_mengajar'], $_POST['pemegang_kunci']]);
                flash('ok','Status lab diperbarui');
            }
            if (isset($_POST['update_jumlah']) && $role==='dosen') {
                $db->prepare("UPDATE lab_settings SET jumlah_siswa=? WHERE id=1")->execute([(int)$_POST['jumlah_siswa']]);
                flash('ok','Jumlah siswa diperbarui');
            }
            redirect('lab');
        }
        $status = $db->query("SELECT * FROM status_lab WHERE id=1")->fetch();
        $settings = $db->query("SELECT * FROM lab_settings WHERE id=1")->fetch();
        view('lab/index', compact('status','settings'));
    }
}