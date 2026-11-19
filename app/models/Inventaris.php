<?php
class Inventaris {
    public static function getAll() { return DB::connect()->query("SELECT * FROM inventaris ORDER BY nama")->fetchAll(); }
    public static function getRequests() {
        return DB::connect()->query("SELECT r.*,i.nama as barang,u.nama as pemohon FROM restock_requests r JOIN inventaris i ON r.inventaris_id=i.id JOIN users u ON r.requested_by=u.id ORDER BY r.created_at DESC")->fetchAll();
    }
}