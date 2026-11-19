<?php
class AbsensiController {
    public function index() {
        $db = DB::connect(); $role = $_SESSION['role']; $uid = $_SESSION['user_id'];

        // Read reminders (AJAX)
        if (isset($_GET['read_reminders'])) {
            if ($role==='mahasiswa') $db->prepare("UPDATE reminders SET is_read=1 WHERE user_id=?")->execute([$uid]);
            exit('ok');
        }
        // Send reminder single
        if (isset($_GET['send_reminder']) && in_array($role,['asisten','dosen'])) {
            list($tid,$sid) = explode('-', $_GET['send_reminder']);
            $c = $db->prepare("SELECT status FROM tugas_status WHERE tugas_id=? AND user_id=?");
            $c->execute([(int)$tid,(int)$sid]);
            if ($c->fetchColumn()==='selesai') { flash('err','Mahasiswa sudah selesai'); }
            else {
                $e = $db->prepare("SELECT id FROM reminders WHERE tugas_id=? AND user_id=? AND is_read=0");
                $e->execute([(int)$tid,(int)$sid]);
                if ($e->fetchColumn()) { flash('err','Reminder sudah dikirim'); }
                else {
                    $db->prepare("INSERT INTO reminders (tugas_id,user_id,created_by) VALUES (?,?,?)")->execute([(int)$tid,(int)$sid,$uid]);
                    flash('ok','Reminder terkirim');
                }
            }
            redirect('absensi');
        }
        // Send reminder all
        if (isset($_GET['send_reminder_all']) && in_array($role,['asisten','dosen'])) {
            $tid = (int)$_GET['send_reminder_all']; $n=0;
            foreach ($db->query("SELECT id FROM users WHERE role='mahasiswa'")->fetchAll() as $m) {
                $c = $db->prepare("SELECT status FROM tugas_status WHERE tugas_id=? AND user_id=?");
                $c->execute([$tid,$m['id']]);
                if ($c->fetchColumn()!=='selesai') {
                    $e = $db->prepare("SELECT id FROM reminders WHERE tugas_id=? AND user_id=? AND is_read=0");
                    $e->execute([$tid,$m['id']]);
                    if (!$e->fetchColumn()) {
                        $db->prepare("INSERT INTO reminders (tugas_id,user_id,created_by) VALUES (?,?,?)")->execute([$tid,$m['id'],$uid]);
                        $n++;
                    }
                }
            }
            flash('ok',"Reminder terkirim ke $n mahasiswa"); redirect('absensi');
        }

        // Absensi CRUD
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_absensi']) && in_array($role,['asisten','dosen'])) {
            $c = $db->prepare("SELECT id FROM absensi WHERE user_id=? AND tanggal=?");
            $c->execute([$_POST['user_id'], $_POST['tanggal']]);
            $exist = $c->fetchColumn();
            if ($exist) {
                $db->prepare("UPDATE absensi SET status=?, keterangan=? WHERE id=?")->execute([$_POST['status'],trim($_POST['keterangan']),$exist]);
                flash('ok','Absensi diperbarui');
            } else {
                $db->prepare("INSERT INTO absensi (user_id,tanggal,status,keterangan) VALUES (?,?,?,?)")->execute([$_POST['user_id'],$_POST['tanggal'],$_POST['status'],trim($_POST['keterangan'])]);
                flash('ok','Absensi ditambahkan');
            }
            redirect('absensi');
        }
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_absensi']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("UPDATE absensi SET status=?, keterangan=? WHERE id=?")->execute([$_POST['status'],trim($_POST['keterangan']),$_POST['absensi_id']]);
            flash('ok','Absensi disimpan'); redirect('absensi');
        }
        if (isset($_GET['delete_absen']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("DELETE FROM absensi WHERE id=?")->execute([$_GET['delete_absen']]);
            flash('ok','Absensi dihapus'); redirect('absensi');
        }

        // Tugas CRUD
        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_tugas']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("INSERT INTO tugas (judul,tanggal_diberikan,deadline,reminder) VALUES (?,?,?,?)")
               ->execute([trim($_POST['judul']), date('Y-m-d'), $_POST['deadline'], $_POST['reminder']]);
            flash('ok','Tugas ditambahkan'); redirect('absensi');
        }
        if (isset($_GET['delete_tugas']) && in_array($role,['asisten','dosen'])) {
            $db->prepare("DELETE FROM tugas_status WHERE tugas_id=?")->execute([$_GET['delete_tugas']]);
            $db->prepare("DELETE FROM reminders WHERE tugas_id=?")->execute([$_GET['delete_tugas']]);
            $db->prepare("DELETE FROM tugas WHERE id=?")->execute([$_GET['delete_tugas']]);
            flash('ok','Tugas dihapus'); redirect('absensi');
        }
        if (isset($_GET['toggle_tugas_siswa'])) {
            list($tid,$sid) = explode('-', $_GET['toggle_tugas_siswa']);
            $tid=(int)$tid; $sid=(int)$sid;
            if (is_role('mahasiswa') && $sid !== $uid) redirect('absensi');
            $c = $db->prepare("SELECT id,status FROM tugas_status WHERE tugas_id=? AND user_id=?");
            $c->execute([$tid,$sid]); $row = $c->fetch();
            if ($row) {
                $new = $row['status']==='selesai'?'belum':'selesai';
                $db->prepare("UPDATE tugas_status SET status=? WHERE id=?")->execute([$new,$row['id']]);
            } else {
                $db->prepare("INSERT INTO tugas_status (tugas_id,user_id,status) VALUES (?,?,'selesai')")->execute([$tid,$sid]);
            }
            redirect('absensi');
        }

        // Data
        $mahasiswa = $db->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama")->fetchAll();
        $absensi = is_role('mahasiswa')
            ? $db->query("SELECT a.*,u.nama FROM absensi a JOIN users u ON a.user_id=u.id WHERE a.user_id=$uid ORDER BY a.tanggal DESC")->fetchAll()
            : $db->query("SELECT a.*,u.nama FROM absensi a JOIN users u ON a.user_id=u.id ORDER BY a.tanggal DESC")->fetchAll();
        $tugas = $db->query("SELECT * FROM tugas ORDER BY deadline ASC")->fetchAll();
        $tugas_status = [];
        foreach ($db->query("SELECT * FROM tugas_status")->fetchAll() as $t) $tugas_status[$t['user_id']][$t['tugas_id']] = $t['status'];
        view('absensi/index', compact('mahasiswa','absensi','tugas','tugas_status'));
    }
}