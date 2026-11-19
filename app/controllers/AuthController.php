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