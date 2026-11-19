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