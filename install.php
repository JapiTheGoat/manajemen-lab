<?php
// install.php — Final v3: Auth + Reservasi + Reminder + All Features
set_time_limit(120);
$base = __DIR__;
$log = []; $errors = [];
$N = "\n";
$isCli = php_sapi_name() === 'cli';
if (!$isCli) echo "<pre style='font-family:monospace;padding:20px;line-height:1.5;'>";

// ============================================================
// 1. FOLDER
// ============================================================
foreach (['config','database','scripts','app/controllers','app/models',
    'app/views/partials','app/views/auth','app/views/dashboard','app/views/inventaris',
    'app/views/lab','app/views/absensi','app/views/pengumuman','app/views/reservasi',
    'app/views/lost-found','app/views/chat','public/css','public/js','public/uploads'
] as $f) { if (!is_dir("$base/$f")) mkdir("$base/$f", 0755, true); }

$files = [];

// ---------- config/database.php ----------
$files['config/database.php'] = <<<'EOF'
<?php
class DB {
    private static $pdo = null;
    public static function connect() {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . BASE_PATH . '/database/lab.db');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$pdo;
    }
}
EOF;

// ---------- config/helpers.php ----------
$files['config/helpers.php'] = <<<'EOF'
<?php
function view($n, $d = []) { extract($d); require BASE_PATH . '/app/views/' . $n . '.php'; }
function partial($n, $d = []) { extract($d); require BASE_PATH . '/app/views/partials/' . $n . '.php'; }
function redirect($u) { header('Location: ?url=' . $u); exit; }
function is_role(...$r) { return in_array($_SESSION['role'] ?? '', $r); }
function flash($k, $v = null) {
    if ($v !== null) { $_SESSION['flash'][$k] = $v; return; }
    $x = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $x;
}
EOF;

// ---------- index.php ----------
$files['index.php'] = <<<'EOF'
<?php
session_start();
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/helpers.php';

$url = $_GET['url'] ?? 'dashboard';

if ($url === 'login')    { require BASE_PATH . '/app/controllers/AuthController.php'; (new AuthController)->login(); exit; }
if ($url === 'register') { require BASE_PATH . '/app/controllers/AuthController.php'; (new AuthController)->register(); exit; }
if ($url === 'logout')   { session_destroy(); redirect('login'); }
if (!isset($_SESSION['user_id'])) redirect('login');

$routes = [
    'dashboard' => 'DashboardController', 'inventaris' => 'InventarisController',
    'lab' => 'LabController', 'absensi' => 'AbsensiController',
    'pengumuman' => 'PengumumanController', 'reservasi' => 'ReservasiController',
    'lostfound' => 'LostFoundController', 'chat' => 'ChatController',
];
if (isset($routes[$url])) {
    require BASE_PATH . '/app/controllers/' . $routes[$url] . '.php';
    (new $routes[$url])->index();
} else { redirect('dashboard'); }
EOF;

// ---------- AuthController ----------
$files['app/controllers/AuthController.php'] = <<<'EOF'
<?php
class AuthController {
    public function login() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $s = DB::connect()->prepare("SELECT * FROM users WHERE email=?");
            $s->execute([trim($_POST['email'])]);
            $u = $s->fetch();
            if ($u && password_verify($_POST['password'], $u['password'])) {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['nama'] = $u['nama'];
                $_SESSION['role'] = $u['role'];
                $_SESSION['show_popup'] = true;
                redirect('dashboard');
            }
            $error = "Email atau password salah!";
        }
        view('auth/login', compact('error'));
    }
    public function register() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama']); $email = trim($_POST['email']); $pass = $_POST['password'];
            if (strlen($pass) < 6) { $error = "Password minimal 6 karakter"; }
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = "Format email tidak valid"; }
            else {
                try {
                    DB::connect()->prepare("INSERT INTO users (nama,email,password,role) VALUES (?,?,?,'mahasiswa')")
                        ->execute([$nama, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                    flash('ok','Registrasi berhasil, silakan login');
                    redirect('login');
                } catch (Exception $e) { $error = "Email sudah terdaftar!"; }
            }
        }
        view('auth/register', compact('error'));
    }
}
EOF;

// ---------- DashboardController ----------
$files['app/controllers/DashboardController.php'] = <<<'EOF'
<?php
class DashboardController {
    public function index() {
        $db = DB::connect();
        $status = $db->query("SELECT * FROM status_lab WHERE id=1")->fetch();
        $settings = $db->query("SELECT * FROM lab_settings WHERE id=1")->fetch();
        $stats = [
            'mahasiswa' => $db->query("SELECT COUNT(*) FROM users WHERE role='mahasiswa'")->fetchColumn(),
            'tugas_aktif' => $db->query("SELECT COUNT(*) FROM tugas WHERE deadline >= date('now')")->fetchColumn(),
            'stok_menipis' => $db->query("SELECT COUNT(*) FROM inventaris WHERE stok <= min_stok")->fetchColumn(),
            'reservasi_pending' => $db->query("SELECT COUNT(*) FROM reservasi WHERE status='pending'")->fetchColumn(),
        ];
        $show_popup = $_SESSION['show_popup'] ?? false;
        if ($show_popup) $_SESSION['show_popup'] = false;
        $announcements = $db->query("SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 3")->fetchAll();
        view('dashboard/index', compact('status','settings','stats','announcements','show_popup'));
    }
}
EOF;

// ---------- InventarisController ----------
$files['app/controllers/InventarisController.php'] = <<<'EOF'
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
EOF;

// ---------- LabController ----------
$files['app/controllers/LabController.php'] = <<<'EOF'
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
EOF;

// ---------- AbsensiController ----------
$files['app/controllers/AbsensiController.php'] = <<<'EOF'
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
EOF;

// ---------- PengumumanController ----------
$files['app/controllers/PengumumanController.php'] = <<<'EOF'
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
EOF;

// ---------- ReservasiController (FIXED) ----------
$files['app/controllers/ReservasiController.php'] = <<<'EOF'
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
EOF;

// ---------- LostFoundController ----------
$files['app/controllers/LostFoundController.php'] = <<<'EOF'
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
EOF;

// ---------- ChatController ----------
$files['app/controllers/ChatController.php'] = <<<'EOF'
<?php
class ChatController {
    public function index() {
        $db = DB::connect(); $uid = $_SESSION['user_id'];
        if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='send') {
            $msg = trim($_POST['msg'] ?? ''); $to = (int)($_POST['receiver'] ?? 0);
            if ($msg !== '' && $to>0 && $to!==$uid) {
                $db->prepare("INSERT INTO chat (sender_id,receiver_id,pesan) VALUES (?,?,?)")->execute([$uid,$to,$msg]);
            }
            exit('ok');
        }
        if (($_GET['action'] ?? '')==='fetch') {
            $target = (int)($_GET['target'] ?? 0);
            header('Content-Type: application/json');
            if ($target<=0) { echo json_encode([]); exit; }
            $s = $db->prepare("SELECT * FROM chat WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY waktu ASC");
            $s->execute([$uid,$target,$target,$uid]);
            echo json_encode($s->fetchAll()); exit;
        }
        if (($_GET['action'] ?? '')==='users') {
            $s = $db->prepare("SELECT id,nama,role FROM users WHERE id!=? ORDER BY role,nama");
            $s->execute([$uid]);
            header('Content-Type: application/json');
            echo json_encode($s->fetchAll()); exit;
        }
        view('chat/index');
    }
}
EOF;

// ---------- Model Inventaris ----------
$files['app/models/Inventaris.php'] = <<<'EOF'
<?php
class Inventaris {
    public static function getAll() { return DB::connect()->query("SELECT * FROM inventaris ORDER BY nama")->fetchAll(); }
    public static function getRequests() {
        return DB::connect()->query("SELECT r.*,i.nama as barang,u.nama as pemohon FROM restock_requests r JOIN inventaris i ON r.inventaris_id=i.id JOIN users u ON r.requested_by=u.id ORDER BY r.created_at DESC")->fetchAll();
    }
}
EOF;

// ---------- PARTIALS/header.php ----------
$files['app/views/partials/header.php'] = <<<'EOF'
<?php
if (!isset($title)) $title = 'Sistem Lab';
$db = DB::connect();
$unread = $db->query("SELECT COUNT(*) FROM pengumuman")->fetchColumn();
$rem_count = 0;
if (is_role('mahasiswa')) {
    $rs = $db->prepare("SELECT COUNT(*) FROM reminders WHERE user_id=? AND is_read=0");
    $rs->execute([$_SESSION['user_id']]);
    $rem_count = $rs->fetchColumn();
}
$cur = $_GET['url'] ?? 'dashboard';
$flash_ok = flash('ok'); $flash_err = flash('err');
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title><?= $title ?></title>
<link rel="stylesheet" href="public/css/style.css"></head><body>
<div class="sidebar"><h2>Sistem Lab</h2>
<?php foreach ([
    'dashboard'=>'Dashboard','inventaris'=>'Inventaris','lab'=>'Status Lab',
    'absensi'=>'Absensi & Tugas','pengumuman'=>'Pengumuman','reservasi'=>'Reservasi',
    'lostfound'=>'Lost & Found','chat'=>'Chat'
] as $s=>$l): ?>
<a href="?url=<?= $s ?>" class="<?= $cur===$s?'active':'' ?>"><?= $l ?></a>
<?php endforeach; ?>
<a href="?url=logout" style="margin-top:auto;color:#FCA5A5;">Logout</a>
</div>
<div class="main-content"><div class="topbar">
<div class="header-left"><a href="?url=absensi" class="btn-header-absen">📋 Buka Absensi</a></div>
<div class="header-right">
<div class="search-bar"><input placeholder="Cari..."></div>
<div class="profile-section">
<div class="bell-icon" onclick="location.href='?url=pengumuman'" title="Pengumuman">🔔 <?php if($unread): ?><span class="badge"><?= $unread ?></span><?php endif; ?></div>
<?php if(is_role('mahasiswa') && $rem_count > 0): ?>
<div class="bell-icon" onclick="location.href='?url=absensi'" title="Reminder tugas" style="color:#F59E0B;">⏰ <span class="badge" style="background:#F59E0B;"><?= $rem_count ?></span></div>
<?php endif; ?>
<div class="avatar"><?= substr($_SESSION['nama']??'?',0,1) ?></div>
<div style="font-size:12px;font-weight:600;color:var(--primary);"><?= htmlspecialchars($_SESSION['nama']??'') ?><br><span style="color:var(--tertiary);"><?= ucfirst($_SESSION['role']??'') ?></span></div>
</div></div></div>
<div class="content-area">
<?php if($flash_ok): ?><div class="flash flash-ok">✅ <?= htmlspecialchars($flash_ok) ?></div><?php endif; ?>
<?php if($flash_err): ?><div class="flash flash-err">⚠️ <?= htmlspecialchars($flash_err) ?></div><?php endif; ?>
EOF;

// ---------- PARTIALS/footer.php (with reminder popup) ----------
$files['app/views/partials/footer.php'] = <<<'EOF'
<?php
if (is_role('mahasiswa')):
    $db = DB::connect();
    $r = $db->prepare("SELECT r.id, t.judul, t.deadline FROM reminders r JOIN tugas t ON r.tugas_id=t.id WHERE r.user_id=? AND r.is_read=0 ORDER BY r.created_at DESC");
    $r->execute([$_SESSION['user_id']]);
    $rem_list = $r->fetchAll();
    if (!empty($rem_list)):
?>
<div class="popup-overlay" id="reminderPopup" style="display:flex;z-index:1100;">
<div class="popup-content card" style="text-align:left;">
<h3 style="border:none;padding:0;">🔔 Pengingat Tugas</h3>
<p style="font-size:13px;color:#666;margin:10px 0 15px;">Anda memiliki <strong><?= count($rem_list) ?></strong> tugas yang belum diselesaikan:</p>
<?php foreach ($rem_list as $r): ?>
<div style="padding:10px 12px;border-left:4px solid #F59E0B;background:#FEF3C7;border-radius:6px;margin-bottom:8px;">
<strong style="color:var(--primary);font-size:14px;"><?= htmlspecialchars($r['judul']) ?></strong>
<div style="font-size:12px;color:#666;margin-top:4px;">📅 Deadline: <strong><?= $r['deadline'] ?></strong></div>
</div>
<?php endforeach; ?>
<button class="btn-primary" style="width:100%;margin-top:10px;" onclick="dismissReminders()">Mengerti</button>
</div>
</div>
<?php endif; endif; ?>
</div></div><script src="public/js/script.js"></script></body></html>
EOF;

// ---------- AUTH/LOGIN ----------
$files['app/views/auth/login.php'] = <<<'EOF'
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
EOF;

// ---------- AUTH/REGISTER ----------
$files['app/views/auth/register.php'] = <<<'EOF'
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
EOF;

// ---------- DASHBOARD ----------
$files['app/views/dashboard/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Dashboard']); ?>
<h2>Dashboard</h2>
<div class="grid-3">
<div class="card">
<h3>Status Lab</h3>
<p style="font-size:20px;font-weight:bold;color:<?= $status['is_occupied']?'var(--tertiary)':'#10B981' ?>;margin:8px 0;"><?= $status['is_occupied']?'SEDANG DIPAKAI':'KOSONG' ?></p>
<p style="font-size:13px;margin:4px 0;">Aktivitas: <strong><?= htmlspecialchars($status['aktivitas']) ?></strong></p>
<p style="font-size:13px;margin:4px 0;">Dosen: <?= htmlspecialchars($status['dosen_mengajar']) ?></p>
<p style="font-size:13px;margin:4px 0;">Kunci: <?= htmlspecialchars($status['pemegang_kunci']) ?></p>
</div>
<div class="card">
<h3>Statistik</h3>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
<div><small style="color:#888;">Mahasiswa</small><p style="font-size:24px;font-weight:bold;color:var(--primary);"><?= $stats['mahasiswa'] ?></p></div>
<div><small style="color:#888;">Tugas Aktif</small><p style="font-size:24px;font-weight:bold;color:var(--tertiary);"><?= $stats['tugas_aktif'] ?></p></div>
<div><small style="color:#888;">Stok Menipis</small><p style="font-size:24px;font-weight:bold;color:#EF4444;"><?= $stats['stok_menipis'] ?></p></div>
<div><small style="color:#888;">Reservasi Pending</small><p style="font-size:24px;font-weight:bold;color:#F59E0B;"><?= $stats['reservasi_pending'] ?></p></div>
</div>
</div>
<div class="card">
<h3>Jumlah Siswa</h3>
<p style="font-size:36px;font-weight:bold;color:var(--primary);text-align:center;margin:15px 0;"><?= $settings['jumlah_siswa'] ?></p>
<p style="color:#666;font-size:12px;text-align:center;">Kapasitas maksimal lab</p>
</div>
</div>
<div class="card" style="margin-top:20px;">
<h3>Menu Cepat</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:10px;">
<a href="?url=absensi" class="btn-primary" style="text-align:center;text-decoration:none;">Absensi & Tugas</a>
<a href="?url=reservasi" class="btn-outline" style="text-align:center;text-decoration:none;">Reservasi</a>
<a href="?url=lostfound" class="btn-outline" style="text-align:center;text-decoration:none;">Lost & Found</a>
<a href="?url=chat" class="btn-outline" style="text-align:center;text-decoration:none;">Chat</a>
</div>
</div>
<?php if ($show_popup && !empty($announcements)): ?>
<div class="popup-overlay" id="popupPengumuman" style="display:flex;">
<div class="popup-content card">
<h3>📢 Pengumuman Terbaru</h3>
<?php foreach ($announcements as $a): ?>
<div style="border-bottom:1px solid #eee;padding:10px 0;">
<strong style="color:var(--primary);"><?= htmlspecialchars($a['judul']) ?></strong>
<p style="font-size:13px;color:#555;margin-top:4px;"><?= htmlspecialchars($a['konten']) ?></p>
</div>
<?php endforeach; ?>
<button class="btn-primary" style="margin-top:15px;width:100%;" onclick="document.getElementById('popupPengumuman').style.display='none'">Tutup</button>
</div></div>
<?php endif; ?>
<?php partial('footer'); ?>
EOF;

// ---------- INVENTARIS ----------
$files['app/views/inventaris/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Inventaris']); ?>
<h2>Inventaris & Restock</h2>
<?php if (is_role('dosen')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Tambah Barang Baru</h3>
<form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
<input type="hidden" name="add_barang" value="1">
<input name="nama" placeholder="Nama Barang" required style="padding:10px;border:1px solid #ccc;border-radius:8px;">
<input type="number" name="stok" placeholder="Stok" required min="0" style="padding:10px;border:1px solid #ccc;border-radius:8px;width:100px;">
<input type="number" name="min_stok" placeholder="Min Stok" required min="0" style="padding:10px;border:1px solid #ccc;border-radius:8px;width:100px;">
<button class="btn-primary">Tambah</button>
</form></div>
<?php endif; ?>
<div class="grid-3">
<?php foreach($items as $i): $p=min(100,($i['stok']/max(1,$i['min_stok']*3))*100); ?>
<div class="card">
<h3><?= htmlspecialchars($i['nama']) ?></h3>
<p>Sisa: <strong><?= $i['stok'] ?></strong> <small style="color:#888;">(min: <?= $i['min_stok'] ?>)</small></p>
<?php if($i['stok']<=$i['min_stok']): ?><p style="color:#EF4444;font-size:12px;margin-top:4px;">⚠️ Stok Menipis</p><?php endif; ?>
<div class="progress-container"><div class="progress-bar" style="width:<?= $p ?>%;background:<?= $p<30?'#EF4444':'var(--tertiary)' ?>;"></div></div>
<?php if(is_role('dosen')): ?>
<form method="POST" style="margin-top:15px;display:flex;gap:5px;flex-wrap:wrap;">
<input type="hidden" name="edit_barang" value="1">
<input type="hidden" name="id" value="<?= $i['id'] ?>">
<input name="nama" value="<?= htmlspecialchars($i['nama']) ?>" required style="flex:1;min-width:100px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<input type="number" name="stok" value="<?= $i['stok'] ?>" required min="0" style="width:60px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<input type="number" name="min_stok" value="<?= $i['min_stok'] ?>" required min="0" style="width:60px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-outline" style="padding:5px 10px;font-size:11px;">Edit</button>
<a href="?url=inventaris&delete_barang=<?= $i['id'] ?>" style="color:#EF4444;font-size:12px;align-self:center;" onclick="return confirm('Hapus?')">Hapus</a>
</form>
<?php elseif(is_role('asisten')): ?>
<form method="POST" style="margin-top:15px;display:flex;gap:5px;">
<input type="hidden" name="usul_restock" value="1">
<input type="hidden" name="inventaris_id" value="<?= $i['id'] ?>">
<input type="number" name="jumlah" placeholder="Jumlah" required min="1" style="width:80px;padding:5px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-outline" style="padding:5px 10px;font-size:12px;">Usul Restock</button>
</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<div class="card" style="margin-top:30px;">
<h3>Riwayat Pengajuan Restock</h3>
<?php if(empty($requests)): ?><p style="color:#888;text-align:center;padding:15px;">Belum ada pengajuan</p><?php else: ?>
<table style="width:100%;border-collapse:collapse;margin-top:10px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th>Barang</th><th>Jumlah</th><th>Pemohon</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($requests as $r): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;"><?= htmlspecialchars($r['barang']) ?></td>
<td>+<?= $r['jumlah'] ?></td>
<td><?= htmlspecialchars($r['pemohon']) ?></td>
<td><span class="badge-status badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
<td><?php if($r['status']==='pending' && is_role('dosen')): ?>
<a href="?url=inventaris&approve_restock=<?= $r['id'] ?>" style="color:green;margin-right:10px;font-weight:600;">ACC</a>
<a href="?url=inventaris&reject_restock=<?= $r['id'] ?>" style="color:red;">Tolak</a>
<?php endif; ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>
EOF;

// ---------- LAB ----------
$files['app/views/lab/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Status Lab']); ?>
<h2>Status & Pengaturan Lab</h2>
<div class="grid-3">
<div class="card">
<h3>Status Lab</h3>
<form method="POST">
<input type="hidden" name="update_status" value="1">
<label>Status</label>
<select name="is_occupied" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;">
<option value="1" <?= $status['is_occupied']?'selected':'' ?>>Dipakai</option>
<option value="0" <?= !$status['is_occupied']?'selected':'' ?>>Kosong</option>
</select>
<label>Aktivitas</label>
<input name="aktivitas" value="<?= htmlspecialchars($status['aktivitas']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<label>Dosen Mengajar</label>
<input name="dosen_mengajar" value="<?= htmlspecialchars($status['dosen_mengajar']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<label>Pemegang Kunci</label>
<input name="pemegang_kunci" value="<?= htmlspecialchars($status['pemegang_kunci']) ?>" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('asisten','dosen')?'readonly':'' ?>>
<?php if(is_role('asisten','dosen')): ?><button class="btn-primary" style="width:100%;">Simpan Status</button><?php endif; ?>
</form>
</div>
<div class="card">
<h3>Jumlah Siswa</h3>
<form method="POST">
<input type="hidden" name="update_jumlah" value="1">
<input type="number" name="jumlah_siswa" value="<?= $settings['jumlah_siswa'] ?>" min="1" style="width:100%;padding:8px;margin-bottom:10px;border:1px solid #ccc;border-radius:5px;" <?= !is_role('dosen')?'readonly':'' ?>>
<?php if(is_role('dosen')): ?><button class="btn-primary" style="width:100%;">Update</button><?php endif; ?>
</form>
<p style="font-size:12px;color:#666;margin-top:10px;">Hanya dosen yang dapat mengubah.</p>
</div></div>
<?php partial('footer'); ?>
EOF;

// ---------- ABSENSI ----------
$files['app/views/absensi/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Absensi & Tugas']); ?>
<h2>Absensi & Tugas</h2>
<div class="grid-absensi">
<div class="card">
<h3>Data Kehadiran</h3>
<?php if(is_role('asisten','dosen')): ?>
<form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;background:var(--light);padding:12px;border-radius:8px;margin-top:10px;">
<input type="hidden" name="add_absensi" value="1">
<select name="user_id" required style="flex:1;min-width:120px;padding:8px;border:1px solid #ccc;border-radius:5px;">
<option value="">-- Pilih Mahasiswa --</option>
<?php foreach($mahasiswa as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></option><?php endforeach; ?>
</select>
<input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>" style="padding:8px;border:1px solid #ccc;border-radius:5px;">
<select name="status" required style="padding:8px;border:1px solid #ccc;border-radius:5px;">
<option value="Hadir">Hadir</option><option value="Tidak Hadir">Tidak Hadir</option>
</select>
<input name="keterangan" placeholder="Keterangan/alasan" style="flex:1;min-width:100px;padding:8px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-primary" style="padding:8px 14px;font-size:12px;">Tambah</button>
</form>
<?php endif; ?>
<?php if(empty($absensi)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada data absensi</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;margin-top:15px;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th>Nama</th><th>Tanggal</th><th>Status</th><th>Ket</th><?php if(is_role('asisten','dosen')): ?><th>Aksi</th><?php endif; ?></tr>
<?php foreach($absensi as $a): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;font-weight:600;"><?= htmlspecialchars($a['nama']) ?></td>
<td><?= $a['tanggal'] ?></td>
<td><?php if(is_role('asisten','dosen')): ?>
<form method="POST" style="display:flex;gap:5px;align-items:center;">
<input type="hidden" name="edit_absensi" value="1">
<input type="hidden" name="absensi_id" value="<?= $a['id'] ?>">
<select name="status" style="padding:4px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
<option value="Hadir" <?= $a['status']==='Hadir'?'selected':'' ?>>Hadir</option>
<option value="Tidak Hadir" <?= $a['status']==='Tidak Hadir'?'selected':'' ?>>Tidak Hadir</option>
</select>
<input name="keterangan" value="<?= htmlspecialchars($a['keterangan']) ?>" placeholder="Alasan" style="width:90px;padding:4px;border:1px solid #ccc;border-radius:4px;font-size:12px;">
<button class="btn-primary" style="padding:4px 8px;font-size:11px;">Simpan</button>
</form>
<?php else: ?>
<span class="badge-status <?= $a['status']==='Hadir'?'badge-approved':'badge-rejected' ?>"><?= $a['status'] ?></span>
<?php endif; ?></td>
<td style="font-size:12px;color:#666;"><?= htmlspecialchars($a['keterangan']) ?></td>
<?php if(is_role('asisten','dosen')): ?>
<td><a href="?url=absensi&delete_absen=<?= $a['id'] ?>" style="color:#EF4444;font-size:12px;" onclick="return confirm('Hapus?')">Hapus</a></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div style="display:flex;flex-direction:column;gap:20px;">
<?php if(is_role('asisten','dosen')): ?>
<div class="card">
<h3>➕ Tambah Tugas</h3>
<form method="POST" style="margin-top:10px;">
<input type="hidden" name="add_tugas" value="1">
<input name="judul" placeholder="Judul Tugas" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<label style="font-size:12px;">Deadline</label>
<input type="date" name="deadline" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<label style="font-size:12px;">Reminder</label>
<input type="datetime-local" name="reminder" required style="width:100%;padding:8px;margin-bottom:8px;border:1px solid #ccc;border-radius:5px;">
<button class="btn-primary" style="width:100%;">Tambah Tugas</button>
</form>
</div>
<?php endif; ?>

<div class="card">
<h3>✅ Checklist Tugas</h3>
<?php if(empty($tugas)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada tugas</p>
<?php elseif(is_role('mahasiswa')): ?>
<?php foreach($tugas as $t): $s = $tugas_status[$_SESSION['user_id']][$t['id']] ?? 'belum'; ?>
<div style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:1px solid #eee;border-radius:8px;margin-bottom:8px;">
<a href="?url=absensi&toggle_tugas_siswa=<?= $t['id'] ?>-<?= $_SESSION['user_id'] ?>" style="text-decoration:none;font-size:20px;"><?= $s==='selesai'?'✅':'⬜' ?></a>
<div style="flex:1;">
<strong style="font-size:13px;<?= $s==='selesai'?'text-decoration:line-through;color:#888;':'' ?>"><?= htmlspecialchars($t['judul']) ?></strong>
<div style="font-size:11px;color:#666;margin-top:4px;">📅 <?= $t['deadline'] ?> • ⏰ <?= $t['reminder'] ?></div>
<div style="font-size:11px;margin-top:4px;color:<?= $s==='selesai'?'green':'#EF4444' ?>;font-weight:600;"><?= $s==='selesai'?'Tugas selesai':'Tugas belum selesai' ?></div>
</div>
</div>
<?php endforeach; ?>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:12px;margin-top:10px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);">
<th style="padding:6px;">Mahasiswa</th>
<?php foreach($tugas as $t): ?><th title="<?= htmlspecialchars($t['judul']) ?>" style="text-align:center;"><?= mb_substr(htmlspecialchars($t['judul']),0,8) ?>.</th><?php endforeach; ?>
</tr>
<?php foreach($mahasiswa as $m): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:8px;font-weight:600;"><?= htmlspecialchars($m['nama']) ?></td>
<?php foreach($tugas as $t): $s=$tugas_status[$m['id']][$t['id']]??'belum'; ?>
<td style="text-align:center;">
<a href="?url=absensi&toggle_tugas_siswa=<?= $t['id'] ?>-<?= $m['id'] ?>" style="text-decoration:none;font-size:16px;"><?= $s==='selesai'?'✅':'⬜' ?></a>
<div style="font-size:9px;color:<?= $s==='selesai'?'green':'#EF4444' ?>;"><?= $s==='selesai'?'selesai':'belum' ?></div>
<?php if($s!=='selesai'): ?>
<a href="?url=absensi&send_reminder=<?= $t['id'] ?>-<?= $m['id'] ?>" style="font-size:12px;text-decoration:none;display:block;margin-top:2px;" onclick="return confirm('Kirim reminder ke <?= htmlspecialchars($m['nama']) ?>?')" title="Kirim reminder">🔔</a>
<?php endif; ?>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
<div style="margin-top:15px;padding-top:15px;border-top:1px solid #eee;font-size:12px;">
<strong style="color:var(--primary);">Daftar Tugas:</strong><br>
<?php foreach($tugas as $t): ?>
<div style="padding:6px 0;border-bottom:1px solid #f5f5f5;">
<strong><?= htmlspecialchars($t['judul']) ?></strong> — deadline <?= $t['deadline'] ?>
<div style="float:right;display:flex;gap:10px;">
<a href="?url=absensi&send_reminder_all=<?= $t['id'] ?>" style="color:#F59E0B;font-weight:600;text-decoration:none;" onclick="return confirm('Reminder massal ke mahasiswa yang belum selesai?')">🔔 Reminder</a>
<a href="?url=absensi&delete_tugas=<?= $t['id'] ?>" style="color:#EF4444;text-decoration:none;" onclick="return confirm('Hapus tugas?')">[Hapus]</a>
</div>
<div style="clear:both;"></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
<?php partial('footer'); ?>
EOF;

// ---------- PENGUMUMAN ----------
$files['app/views/pengumuman/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Pengumuman']); ?>
<h2>Pengumuman</h2>
<?php if(is_role('asisten','dosen')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Buat Pengumuman</h3>
<form method="POST" style="margin-top:10px;">
<input type="hidden" name="add" value="1">
<input name="judul" placeholder="Judul" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;">
<textarea name="konten" placeholder="Isi pengumuman..." required style="width:100%;padding:10px;height:100px;margin-bottom:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></textarea>
<button class="btn-primary">Kirim</button>
</form>
</div>
<?php endif; ?>
<?php if(empty($list)): ?>
<div class="card"><p style="color:#888;text-align:center;padding:20px;">Belum ada pengumuman</p></div>
<?php endif; ?>
<?php foreach($list as $p): ?>
<div class="card" style="margin-bottom:15px;position:relative;">
<h3 style="border:none;padding-bottom:0;margin-bottom:8px;"><?= htmlspecialchars($p['judul']) ?></h3>
<p style="margin:8px 0;font-size:14px;line-height:1.6;"><?= nl2br(htmlspecialchars($p['konten'])) ?></p>
<small style="color:#888;">Oleh <?= htmlspecialchars($p['nama']) ?> • <?= $p['created_at'] ?></small>
<?php if(is_role('asisten','dosen')): ?>
<a href="?url=pengumuman&delete=<?= $p['id'] ?>" style="color:#EF4444;font-size:12px;position:absolute;top:20px;right:20px;" onclick="return confirm('Hapus pengumuman?')">Hapus</a>
<?php endif; ?>
</div>
<?php endforeach; ?>
<?php partial('footer'); ?>
EOF;

// ---------- RESERVASI (FIXED) ----------
$files['app/views/reservasi/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Reservasi']); ?>
<h2>Reservasi Lab</h2>
<?php if(is_role('mahasiswa')): ?>
<div class="card" style="margin-bottom:20px;">
<h3>Ajukan Reservasi</h3>
<form method="POST" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
<input type="hidden" name="ajukan" value="1">
<div style="grid-column:1/-1;"><label>Nama Lab</label>
<input name="lab" placeholder="Contoh: Lab Komputer 1" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div><label>Tanggal</label>
<input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div style="display:flex;gap:10px;">
<div style="flex:1;"><label>Mulai</label>
<input type="time" name="mulai" required value="08:00" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
<div style="flex:1;"><label>Selesai</label>
<input type="time" name="selesai" required value="10:00" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;"></div>
</div>
<div style="grid-column:1/-1;"><label>Keterangan</label>
<textarea name="keterangan" placeholder="Keperluan reservasi..." style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px;font-family:inherit;height:60px;"></textarea></div>
<div style="grid-column:1/-1;"><button class="btn-primary" style="width:100%;">Ajukan Reservasi</button></div>
</form>
</div>
<?php endif; ?>
<div class="card">
<h3>Daftar Reservasi<?= is_role('mahasiswa') ? ' Saya' : ' Semua Mahasiswa' ?></h3>
<?php if(empty($list)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada reservasi</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);">
<?php if(!is_role('mahasiswa')): ?><th style="padding:8px;">Pemohon</th><?php endif; ?>
<th>Lab</th><th>Tanggal</th><th>Waktu</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($list as $r): ?>
<tr style="border-bottom:1px solid #eee;">
<?php if(!is_role('mahasiswa')): ?><td style="padding:10px;font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td><?php endif; ?>
<td><?= htmlspecialchars($r['lab']) ?></td>
<td><?= $r['tanggal'] ?></td>
<td><?= $r['waktu_mulai'] ?> - <?= $r['waktu_selesai'] ?></td>
<td style="font-size:12px;color:#666;"><?= htmlspecialchars($r['keterangan']) ?></td>
<td><span class="badge-status badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
<td>
<?php if($r['status']==='pending' && is_role('asisten','dosen')): ?>
<a href="?url=reservasi&approve=<?= $r['id'] ?>" style="color:green;font-weight:600;text-decoration:none;">ACC</a> | 
<a href="?url=reservasi&reject=<?= $r['id'] ?>" style="color:red;text-decoration:none;">Tolak</a>
<?php elseif($r['status']==='pending' && is_role('mahasiswa')): ?>
<a href="?url=reservasi&cancel=<?= $r['id'] ?>" style="color:#EF4444;font-size:12px;text-decoration:none;" onclick="return confirm('Batalkan reservasi ini?')">Batalkan</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>
EOF;

// ---------- LOST & FOUND ----------
$files['app/views/lost-found/index.php'] = <<<'EOF'
<?php partial('header', ['title' => 'Lost & Found']); ?>
<h2>Lost & Found</h2>
<div class="card" style="margin-bottom:20px;">
<h3>Laporkan Barang</h3>
<form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
<input type="hidden" name="lapor" value="1">
<input name="nama" placeholder="Nama Barang" required style="padding:10px;flex:1;min-width:150px;border:1px solid #ccc;border-radius:8px;">
<input name="deskripsi" placeholder="Deskripsi/lokasi terakhir" style="padding:10px;flex:2;min-width:200px;border:1px solid #ccc;border-radius:8px;">
<button class="btn-primary">Lapor</button>
</form>
</div>
<div class="card">
<h3>Daftar Barang</h3>
<?php if(empty($list)): ?>
<p style="color:#888;text-align:center;padding:20px;">Belum ada laporan</p>
<?php else: ?>
<table style="width:100%;border-collapse:collapse;font-size:13px;">
<tr style="border-bottom:2px solid var(--light);text-align:left;color:var(--primary);"><th style="padding:8px;">Barang</th><th>Deskripsi</th><th>Pelapor</th><th>Status</th><th>Aksi</th></tr>
<?php foreach($list as $l): ?>
<tr style="border-bottom:1px solid #eee;">
<td style="padding:10px;font-weight:600;"><?= htmlspecialchars($l['nama_barang']) ?></td>
<td><?= htmlspecialchars($l['deskripsi']) ?></td>
<td><?= htmlspecialchars($l['nama']) ?></td>
<td><span class="badge-status badge-<?= $l['status']==='hilang'?'rejected':($l['status']==='ditemukan'?'pending':'approved') ?>"><?= $l['status'] ?></span></td>
<td>
<?php if($l['user_id']==$_SESSION['user_id'] && $l['status']==='hilang'): ?>
<a href="?url=lostfound&delete_own=<?= $l['id'] ?>" style="color:#EF4444;font-size:12px;" onclick="return confirm('Hapus laporan?')">Hapus</a>
<?php endif; ?>
<?php if(is_role('asisten','dosen')): ?>
<a href="?url=lostfound&status=ditemukan&id=<?= $l['id'] ?>" style="color:#F59E0B;font-size:12px;margin-left:6px;">Ditemukan</a>
<a href="?url=lostfound&status=diklaim&id=<?= $l['id'] ?>" style="color:green;font-size:12px;margin-left:6px;">Diklaim</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php partial('footer'); ?>
EOF;

// ---------- CHAT ----------
$files['app/views/chat/index.php'] = <<<'EOF'
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
EOF;

// ---------- CSS ----------
$files['public/css/style.css'] = <<<'EOF'
:root{--primary:#0B3D91;--secondary:#2563EB;--tertiary:#0EA5E9;--light:#E0F2FE;--dark:#0F172A;--white:#FFF}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{display:flex;background:var(--light);color:var(--dark);height:100vh;overflow:hidden}
.sidebar{width:240px;background:var(--primary);padding:20px;display:flex;flex-direction:column;gap:2px}
.sidebar h2{color:#fff;margin-bottom:25px;font-size:20px}
.sidebar a{color:var(--light);text-decoration:none;padding:11px 14px;border-radius:8px;font-size:14px;transition:0.2s}
.sidebar a:hover,.sidebar a.active{background:var(--secondary);color:#fff}
.main-content{flex:1;display:flex;flex-direction:column;height:100vh}
.topbar{height:68px;background:#fff;display:flex;justify-content:space-between;align-items:center;padding:0 25px;box-shadow:0 2px 10px rgba(0,0,0,0.05);z-index:10}
.btn-header-absen{background:var(--secondary);color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px}
.btn-header-absen:hover{background:var(--primary)}
.header-right{display:flex;align-items:center;gap:18px}
.search-bar input{padding:9px 15px;border-radius:20px;border:none;width:220px;background:var(--light);outline:none;font-size:13px}
.profile-section{display:flex;align-items:center;gap:12px}
.bell-icon{font-size:19px;cursor:pointer;position:relative}
.badge{position:absolute;top:-6px;right:-6px;background:#EF4444;color:#fff;font-size:10px;padding:2px 5px;border-radius:50%}
.avatar{width:38px;height:38px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold}
.content-area{padding:25px;overflow-y:auto;flex:1}
h2{color:var(--primary);margin-bottom:20px;font-size:24px}
h3{color:var(--primary);margin-bottom:14px;font-size:16px;border-bottom:2px solid var(--light);padding-bottom:10px}
.grid-3{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}
.grid-absensi{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start}
@media(max-width:900px){.grid-absensi{grid-template-columns:1fr}}
.card{background:#fff;padding:22px;border-radius:14px;box-shadow:0 4px 15px rgba(0,0,0,0.03)}
.btn-primary{background:var(--secondary);color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;transition:0.2s}
.btn-primary:hover{background:var(--primary)}
.btn-outline{background:transparent;color:var(--secondary);border:2px solid var(--secondary);padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px}
.btn-outline:hover{background:var(--light)}
.progress-container{background:var(--light);border-radius:10px;height:8px;margin-top:10px;overflow:hidden}
.progress-bar{height:100%;border-radius:10px;transition:0.5s}
label{font-size:12px;font-weight:600;color:var(--primary);display:block;margin-bottom:4px;margin-top:6px}
input,select,textarea{font-family:inherit;font-size:13px}
.badge-status{display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;text-transform:capitalize}
.badge-pending{background:#FEF3C7;color:#92400E}
.badge-approved{background:#D1FAE5;color:#065F46}
.badge-rejected{background:#FEE2E2;color:#991B1B}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:13px;font-weight:600}
.flash-ok{background:#D1FAE5;color:#065F46}
.flash-err{background:#FEE2E2;color:#991B1B}
.popup-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;z-index:999}
.popup-content{width:450px;max-height:80vh;overflow-y:auto;text-align:left}
.chat-user{padding:10px 12px;cursor:pointer;border-radius:6px;margin-bottom:4px;font-size:13px}
.chat-user:hover{background:var(--light)}
.chat-user.active{background:var(--light);border-left:3px solid var(--secondary)}
EOF;

// ---------- JS ----------
$files['public/js/script.js'] = <<<'EOF'
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
EOF;

// ---------- scripts/setup.php ----------
$files['scripts/setup.php'] = <<<'EOF'
<?php
define('BASE_PATH', dirname(__DIR__));
$dbPath = BASE_PATH . '/database/lab.db';
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("DROP TABLE IF EXISTS users; DROP TABLE IF EXISTS inventaris;
        DROP TABLE IF EXISTS restock_requests; DROP TABLE IF EXISTS status_lab;
        DROP TABLE IF EXISTS lab_settings; DROP TABLE IF EXISTS absensi;
        DROP TABLE IF EXISTS tugas; DROP TABLE IF EXISTS tugas_status;
        DROP TABLE IF EXISTS chat; DROP TABLE IF EXISTS pengumuman;
        DROP TABLE IF EXISTS reservasi; DROP TABLE IF EXISTS lost_found;
        DROP TABLE IF EXISTS reminders;");
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $q = [
        "CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT, email TEXT UNIQUE, password TEXT, role TEXT)",
        "INSERT INTO users (nama,email,password,role) VALUES ('Budi Asisten','budi@lab.com','$hash','asisten'),('Dosen Pengampu','dosen@lab.com','$hash','dosen'),('Siti Mahasiswa','siti@lab.com','$hash','mahasiswa'),('Andi Pratama','andi@lab.com','$hash','mahasiswa'),('Rina Wulandari','rina@lab.com','$hash','mahasiswa')",
        "CREATE TABLE inventaris (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT, stok INTEGER, min_stok INTEGER)",
        "INSERT INTO inventaris (nama,stok,min_stok) VALUES ('Galon Air',3,5),('Makanan Ringan',10,5),('Sachet Kopi',50,10)",
        "CREATE TABLE restock_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, inventaris_id INTEGER, jumlah INTEGER, status TEXT DEFAULT 'pending', requested_by INTEGER, approved_by INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE status_lab (id INTEGER PRIMARY KEY, is_occupied INTEGER DEFAULT 0, aktivitas TEXT, dosen_mengajar TEXT, pemegang_kunci TEXT)",
        "INSERT INTO status_lab VALUES (1,1,'Praktikum Basis Data','Dosen Pengampu','Budi Asisten')",
        "CREATE TABLE lab_settings (id INTEGER PRIMARY KEY, jumlah_siswa INTEGER DEFAULT 30)",
        "INSERT INTO lab_settings VALUES (1,30)",
        "CREATE TABLE absensi (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, tanggal TEXT, status TEXT, keterangan TEXT)",
        "INSERT INTO absensi (user_id,tanggal,status,keterangan) VALUES (3,date('now'),'Hadir',''),(4,date('now'),'Tidak Hadir','Sakit'),(5,date('now'),'Hadir','')",
        "CREATE TABLE tugas (id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, tanggal_diberikan TEXT, deadline TEXT, reminder TEXT)",
        "INSERT INTO tugas (judul,tanggal_diberikan,deadline,reminder) VALUES ('Laporan Praktikum 1',date('now'),date('now','+7 day'),datetime('now','+6 day')),('Kuis Basis Data',date('now'),date('now','+3 day'),datetime('now','+2 day'))",
        "CREATE TABLE tugas_status (id INTEGER PRIMARY KEY AUTOINCREMENT, tugas_id INTEGER, user_id INTEGER, status TEXT DEFAULT 'belum', UNIQUE(tugas_id,user_id))",
        "CREATE TABLE reminders (id INTEGER PRIMARY KEY AUTOINCREMENT, tugas_id INTEGER, user_id INTEGER, created_by INTEGER, is_read INTEGER DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE chat (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_id INTEGER, receiver_id INTEGER, pesan TEXT, waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE pengumuman (id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, konten TEXT, created_by INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "INSERT INTO pengumuman (judul,konten,created_by) VALUES ('Selamat Datang','Selamat menggunakan sistem lab. Silakan eksplorasi semua fitur.',2),('Persiapan UTS','Lab akan ditutup sementara untuk persiapan UTS minggu depan.',2)",
        "CREATE TABLE reservasi (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, lab TEXT, tanggal TEXT, waktu_mulai TEXT, waktu_selesai TEXT, status TEXT DEFAULT 'pending', keterangan TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE lost_found (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, nama_barang TEXT, deskripsi TEXT, status TEXT DEFAULT 'hilang', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"
    ];
    foreach ($q as $sql) $db->exec($sql);
    echo "<h1>✅ Database siap! <a href='../index.php'>Login</a></h1>";
    echo "<p>Akun demo: budi@lab.com / dosen@lab.com / siti@lab.com — password: <code>password</code></p>";
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
EOF;

// ============================================================
// 3. TULIS FILE
// ============================================================
foreach ($files as $path => $content) {
    file_put_contents("$base/$path", $content) !== false ? $log[] = "📄 $path" : $errors[] = "Gagal: $path";
}

// ============================================================
// 4. DATABASE + SEED
// ============================================================
try {
    $dbPath = "$base/database/lab.db";
    if (file_exists($dbPath)) unlink($dbPath);
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $q = [
        "CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT, email TEXT UNIQUE, password TEXT, role TEXT)",
        "INSERT INTO users (nama,email,password,role) VALUES
            ('Budi Asisten','budi@lab.com','$hash','asisten'),
            ('Dosen Pengampu','dosen@lab.com','$hash','dosen'),
            ('Siti Mahasiswa','siti@lab.com','$hash','mahasiswa'),
            ('Andi Pratama','andi@lab.com','$hash','mahasiswa'),
            ('Rina Wulandari','rina@lab.com','$hash','mahasiswa')",
        "CREATE TABLE inventaris (id INTEGER PRIMARY KEY AUTOINCREMENT, nama TEXT, stok INTEGER, min_stok INTEGER)",
        "INSERT INTO inventaris (nama,stok,min_stok) VALUES ('Galon Air',3,5),('Makanan Ringan',10,5),('Sachet Kopi',50,10)",
        "CREATE TABLE restock_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, inventaris_id INTEGER, jumlah INTEGER, status TEXT DEFAULT 'pending', requested_by INTEGER, approved_by INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE status_lab (id INTEGER PRIMARY KEY, is_occupied INTEGER DEFAULT 0, aktivitas TEXT, dosen_mengajar TEXT, pemegang_kunci TEXT)",
        "INSERT INTO status_lab VALUES (1,1,'Praktikum Basis Data','Dosen Pengampu','Budi Asisten')",
        "CREATE TABLE lab_settings (id INTEGER PRIMARY KEY, jumlah_siswa INTEGER DEFAULT 30)",
        "INSERT INTO lab_settings VALUES (1,30)",
        "CREATE TABLE absensi (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, tanggal TEXT, status TEXT, keterangan TEXT)",
        "INSERT INTO absensi (user_id,tanggal,status,keterangan) VALUES (3,date('now'),'Hadir',''),(4,date('now'),'Tidak Hadir','Sakit'),(5,date('now'),'Hadir','')",
        "CREATE TABLE tugas (id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, tanggal_diberikan TEXT, deadline TEXT, reminder TEXT)",
        "INSERT INTO tugas (judul,tanggal_diberikan,deadline,reminder) VALUES ('Laporan Praktikum 1',date('now'),date('now','+7 day'),datetime('now','+6 day')),('Kuis Basis Data',date('now'),date('now','+3 day'),datetime('now','+2 day'))",
        "CREATE TABLE tugas_status (id INTEGER PRIMARY KEY AUTOINCREMENT, tugas_id INTEGER, user_id INTEGER, status TEXT DEFAULT 'belum', UNIQUE(tugas_id,user_id))",
        "CREATE TABLE reminders (id INTEGER PRIMARY KEY AUTOINCREMENT, tugas_id INTEGER, user_id INTEGER, created_by INTEGER, is_read INTEGER DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE chat (id INTEGER PRIMARY KEY AUTOINCREMENT, sender_id INTEGER, receiver_id INTEGER, pesan TEXT, waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE pengumuman (id INTEGER PRIMARY KEY AUTOINCREMENT, judul TEXT, konten TEXT, created_by INTEGER, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "INSERT INTO pengumuman (judul,konten,created_by) VALUES ('Selamat Datang','Selamat menggunakan sistem lab. Silakan eksplorasi semua fitur.',2),('Persiapan UTS','Lab akan ditutup sementara untuk persiapan UTS minggu depan.',2)",
        "CREATE TABLE reservasi (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, lab TEXT, tanggal TEXT, waktu_mulai TEXT, waktu_selesai TEXT, status TEXT DEFAULT 'pending', keterangan TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)",
        "CREATE TABLE lost_found (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, nama_barang TEXT, deskripsi TEXT, status TEXT DEFAULT 'hilang', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"
    ];
    foreach ($q as $sql) $db->exec($sql);
    $log[] = "🗄️  Database + seed (5 users, 3 inventaris, 2 tugas, 2 pengumuman, 3 absensi)";
} catch (PDOException $e) { $errors[] = "DB: " . $e->getMessage(); }

// ============================================================
// 5. OUTPUT
// ============================================================
echo "════════════════════════════════════════$N";
echo "  ✅ INSTALASI SELESAI (v3 - All Features)$N";
echo "════════════════════════════════════════$N$N";
echo implode($N, $log) . $N;
if ($errors) { echo "{$N}⚠️  ERRORS:$N"; foreach ($errors as $e) echo "  - $e$N"; }
echo "{$N}🔗 <a href='index.php'>Buka Aplikasi →</a>$N$N";
echo "📋 Akun Demo (password: password):$N";
echo "   • budi@lab.com   → Asisten$N";
echo "   • dosen@lab.com  → Dosen$N";
echo "   • siti@lab.com   → Mahasiswa$N";
echo "   • andi@lab.com   → Mahasiswa$N";
echo "   • rina@lab.com   → Mahasiswa$N$N";
echo "🗑️  HAPUS install.php setelah berhasil!$N";
echo "🔄 Reset DB kapan saja: scripts/setup.php$N";
if (!$isCli) echo "</pre>";