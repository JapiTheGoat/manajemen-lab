<?php
function view($n, $d = []) { extract($d); require BASE_PATH . '/app/views/' . $n . '.php'; }
function partial($n, $d = []) { extract($d); require BASE_PATH . '/app/views/partials/' . $n . '.php'; }
function redirect($u) { header('Location: ?url=' . $u); exit; }
function is_role(...$r) { return in_array($_SESSION['role'] ?? '', $r); }
function flash($k, $v = null) {
    if ($v !== null) { $_SESSION['flash'][$k] = $v; return; }
    $x = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $x;
}