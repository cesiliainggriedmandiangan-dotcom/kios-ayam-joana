<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('pemesanan.php');

$nama    = trim($_POST['nama'] ?? '');
$hp      = trim($_POST['hp'] ?? '');
$alamat  = trim($_POST['alamat'] ?? '');
$menu_id = (int)($_POST['menu_id'] ?? 0);
$jumlah  = (int)($_POST['jumlah'] ?? 0);
$catatan = trim($_POST['catatan'] ?? '');
$metode  = $_POST['metode_bayar'] ?? 'COD';

// ===== VALIDASI METODE PEMBAYARAN =====
$allowed_metode = ['COD', 'Transfer', 'QRIS', 'DANA', 'Midtrans'];
if (!in_array($metode, $allowed_metode)) {
    $metode = 'COD';
}

// ===== VALIDASI JENIS SAMBAL =====
$jenis_sambal = trim($_POST['jenis_sambal'] ?? 'Sambal Geprek');
$allowed_sambal = ['Sambal Geprek', 'Saus Sambal'];
if (!in_array($jenis_sambal, $allowed_sambal)) {
    $jenis_sambal = 'Sambal Geprek';
}

// ===== VALIDASI DATA WAJIB =====
if (!$nama || !$hp || !$alamat || !$menu_id || $jumlah < 1) {
    $_SESSION['error'] = 'Data tidak lengkap!';
    redirect('pemesanan.php');
}

// ===== AMBIL MENU DARI DATABASE =====
$stmt = $conn->prepare("SELECT nama, harga FROM menu WHERE id=? AND status='aktif'");
$stmt->bind_param('i', $menu_id);
$stmt->execute();
$menu = $stmt->get_result()->fetch_assoc();

if (!$menu) {
    $_SESSION['error'] = 'Menu tidak ditemukan!';
    redirect('pemesanan.php');
}

$total = $menu['harga'] * $jumlah;
$kode  = kodePesanan();

// ===== STATUS BAYAR AWAL =====
$status_bayar = ($metode === 'COD') ? 'belum' : 'menunggu';

// ===== INSERT KE DATABASE =====
$stmt = $conn->prepare("INSERT INTO pesanan 
    (kode, nama, hp, alamat, menu_id, menu_nama, harga, jumlah, jenis_sambal, catatan, total, metode_bayar, status_bayar)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

if (!$stmt) {
    die('Query error: ' . $conn->error);
}

$stmt->bind_param('ssssisississs',
    $kode, $nama, $hp, $alamat, $menu_id, $menu['nama'],
    $menu['harga'], $jumlah, $jenis_sambal, $catatan, $total, $metode, $status_bayar
);
$stmt->execute();
$id = $stmt->insert_id;

// ===== BUAT NOTIFIKASI UNTUK ADMIN =====
$cek_notif = $conn->query("SHOW TABLES LIKE 'notifikasi'");
if ($cek_notif && $cek_notif->num_rows > 0) {
    $judul = "🛒 Pesanan Baru: $kode";
    $pesan = "$nama memesan " . $menu['nama'] . " × $jumlah — " . rupiah($total);
    $link  = "pesanan.php?filter=baru";
    $tipe  = 'pesanan_baru';

    $stmt_notif = $conn->prepare("INSERT INTO notifikasi (tipe, judul, pesan, link, pesanan_id, kode_pesanan) VALUES (?,?,?,?,?,?)");
    $stmt_notif->bind_param('ssssis', $tipe, $judul, $pesan, $link, $id, $kode);
    $stmt_notif->execute();
}

// ===== SIMPAN KODE PESANAN KE COOKIE =====
$kode_cookie = $_COOKIE['kios_pesanan'] ?? '';
$kode_list = array_filter(explode(',', $kode_cookie));

array_unshift($kode_list, $kode);
$kode_list = array_slice(array_unique($kode_list), 0, 20);

setcookie('kios_pesanan', implode(',', $kode_list), time() + (365 * 24 * 3600), '/');

// ===== SIMPAN STATUS AWAL KE COOKIE (untuk toast tracking) =====
setcookie('kios_last_seen_' . $kode, 'baru', time() + (365 * 24 * 3600), '/');

// ===== REDIRECT =====
if ($metode === 'COD') {
    $_SESSION['sukses'] = "✅ Pesanan <b>$kode</b> berhasil dibuat! (COD — bayar di tempat)";
    $_SESSION['kode_pesanan'] = $kode;
    redirect('pemesanan.php');
} else {
    redirect("pembayaran.php?id=$id");
}