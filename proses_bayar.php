<?php
require 'config.php';

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? '';
$id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) redirect('pemesanan.php');

// ===== UPLOAD BUKTI PEMBAYARAN =====
if ($aksi === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['bukti']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $_SESSION['error'] = 'Format file harus JPG/PNG/WEBP.';
            redirect("pembayaran.php?id=$id");
        }
        
        $folder = 'bukti/';
        if (!is_dir($folder)) mkdir($folder, 0755, true);
        
        $nama_file = 'bukti_' . $id . '_' . time() . '.' . $ext;
        
        if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $folder . $nama_file)) {
            $_SESSION['error'] = 'Gagal upload file. Coba lagi.';
            redirect("pembayaran.php?id=$id");
        }

        // Update database
        $stmt = $conn->prepare("UPDATE pesanan SET bukti_bayar=?, status_bayar='menunggu' WHERE id=?");
        $stmt->bind_param('si', $nama_file, $id);
        $stmt->execute();

        // ===== BUAT NOTIFIKASI UNTUK ADMIN =====
        $cek_notif = $conn->query("SHOW TABLES LIKE 'notifikasi'");
        if ($cek_notif && $cek_notif->num_rows > 0) {
            $stmt_info = $conn->prepare("SELECT kode, nama, total FROM pesanan WHERE id=?");
            $stmt_info->bind_param('i', $id);
            $stmt_info->execute();
            $info = $stmt_info->get_result()->fetch_assoc();
            
            if ($info) {
                $judul = "💳 Bukti Bayar: {$info['kode']}";
                $pesan = "{$info['nama']} upload bukti transfer — " . rupiah($info['total']);
                $link  = "verifikasi.php";
                $tipe  = 'bukti_bayar';
                
                $stmt_notif = $conn->prepare("INSERT INTO notifikasi (tipe, judul, pesan, link, pesanan_id, kode_pesanan) VALUES (?,?,?,?,?,?)");
                $stmt_notif->bind_param('ssssis', $tipe, $judul, $pesan, $link, $id, $info['kode']);
                $stmt_notif->execute();
            }
        }

        $_SESSION['sukses'] = "✅ Bukti pembayaran berhasil dikirim! Menunggu verifikasi admin.";
        redirect("cek_pesanan.php?id=$id");
    } else {
        $_SESSION['error'] = 'Pilih file bukti terlebih dahulu.';
        redirect("pembayaran.php?id=$id");
    }
}

// ===== SIMULASI PEMBAYARAN SUKSES (untuk metode Midtrans/DANA) =====
if ($aksi === 'sukses') {
    $stmt = $conn->prepare("UPDATE pesanan SET status_bayar='lunas', status_pesanan='diproses' WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    
    $_SESSION['sukses'] = "🎉 Pembayaran berhasil! Pesanan sedang diproses.";
    redirect("cek_pesanan.php?id=$id");
}

// ===== DEFAULT REDIRECT =====
redirect('pemesanan.php');