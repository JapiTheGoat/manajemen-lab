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