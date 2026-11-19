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