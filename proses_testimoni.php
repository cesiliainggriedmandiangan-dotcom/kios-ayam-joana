<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('testimoni.php');
}

// ===== AMBIL DATA =====
$nama         = trim($_POST['nama'] ?? '');
$hp           = trim($_POST['hp'] ?? '');
$rating       = (int)($_POST['rating'] ?? 5);
$pesan        = trim($_POST['pesan'] ?? '');
$kode_pesanan = strtoupper(trim($_POST['kode_pesanan'] ?? ''));

// ===== VALIDASI =====
if (!$nama || !$pesan) {
    $_SESSION['error_testimoni'] = '❌ Nama dan testimoni wajib diisi!';
    redirect('testimoni.php#formTestimoni');
}

if ($rating < 1 || $rating > 5) {
    $rating = 5;
}

if (strlen($pesan) < 10) {
    $_SESSION['error_testimoni'] = '❌ Testimoni minimal 10 karakter!';
    redirect('testimoni.php#formTestimoni');
}

if (strlen($pesan) > 500) {
    $pesan = substr($pesan, 0, 500);
}

// ===== CEK APAKAH SUDAH PERNAH ISI TESTIMONI DENGAN KODE INI =====
if ($kode_pesanan) {
    $stmt = $conn->prepare("SELECT id FROM testimoni WHERE kode_pesanan=? LIMIT 1");
    $stmt->bind_param('s', $kode_pesanan);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $_SESSION['error_testimoni'] = '❌ Kamu sudah pernah memberi testimoni untuk pesanan ini.';
        redirect('testimoni.php');
    }
}

// ===== AMBIL NAMA MENU (kalau ada kode pesanan) =====
$menu_nama = null;
if ($kode_pesanan) {
    $stmt = $conn->prepare("SELECT menu_nama FROM pesanan WHERE kode=? LIMIT 1");
    $stmt->bind_param('s', $kode_pesanan);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if ($order) {
        $menu_nama = $order['menu_nama'];
    }
}

// ===== SIMPAN KE DATABASE =====
$status = 'approved'; // langsung tampil, admin bisa hide kalau perlu
$stmt = $conn->prepare("INSERT INTO testimoni 
    (nama, hp, rating, pesan, kode_pesanan, menu_nama, status) 
    VALUES (?,?,?,?,?,?,?)");

$stmt->bind_param('ssissss',
    $nama, $hp, $rating, $pesan, $kode_pesanan, $menu_nama, $status
);

if ($stmt->execute()) {
    $_SESSION['sukses_testimoni'] = "🎉 Terima kasih <b>" . e($nama) . "</b>! Testimoni kamu sudah dipublikasikan.";
} else {
    $_SESSION['error_testimoni'] = '❌ Gagal menyimpan testimoni. Coba lagi.';
}

redirect('testimoni.php');