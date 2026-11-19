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