<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$cek = $conn->query("SHOW TABLES LIKE 'notifikasi'");
if (!$cek || $cek->num_rows === 0) {
    echo json_encode(['success' => true, 'unread' => 0, 'notifs' => [], 'latest_id' => 0]);
    exit;
}

$action = $_GET['action'] ?? 'get';

// ===== AMBIL DAFTAR NOTIFIKASI =====
if ($action === 'get') {
    $notifs = [];
    $latest_id = 0;
    
    $q = $conn->query("SELECT * FROM notifikasi ORDER BY waktu DESC LIMIT 10");
    
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $notifs[] = [
                'id'        => (int)$r['id'],
                'tipe'      => $r['tipe'],
                'judul'     => $r['judul'],
                'pesan'     => $r['pesan'],
                'link'      => $r['link'],
                'waktu'     => $r['waktu'],
                'waktu_ago' => timeAgo($r['waktu']),
                'dibaca'    => (bool)$r['dibaca']
            ];
        }
    }

    $unread = 0;
    $q = $conn->query("SELECT COUNT(*) AS c FROM notifikasi WHERE dibaca=0");
    if ($q) {
        $unread = (int)($q->fetch_assoc()['c'] ?? 0);
    }

    // Ambil ID terbaru (untuk deteksi notif baru)
    $q = $conn->query("SELECT MAX(id) AS max_id FROM notifikasi");
    if ($q) {
        $row = $q->fetch_assoc();
        $latest_id = (int)($row['max_id'] ?? 0);
    }

    echo json_encode([
        'success'   => true,
        'unread'    => $unread,
        'notifs'    => $notifs,
        'latest_id' => $latest_id
    ]);
    exit;
}

// ===== TANDAI DIBACA =====
if ($action === 'baca') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE notifikasi SET dibaca=1 WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
    echo json_encode(['success' => true]);
    exit;
}

// ===== TANDAI SEMUA DIBACA =====
if ($action === 'baca_semua') {
    $conn->query("UPDATE notifikasi SET dibaca=1 WHERE dibaca=0");
    echo json_encode(['success' => true]);
    exit;
}

// ===== HELPER: Waktu Relatif =====
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return 'Baru saja';
    
    $diff = time() - $timestamp;
    if ($diff < 0) return 'Baru saja';
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', $timestamp);
}