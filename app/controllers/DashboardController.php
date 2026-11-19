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