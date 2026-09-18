<?php
require 'config.php';

$kode       = strtoupper(trim($_GET['kode'] ?? ''));
$id         = (int)($_GET['id'] ?? 0);
$konfirmasi = $_GET['konfirmasi'] ?? '';

// ===== CARI PESANAN =====
$order = null;
if ($kode || $id) {
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode=? OR id=? LIMIT 1");
    $stmt->bind_param('si', $kode, $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

if (!$order) {
    $_SESSION['error_pesanan'] = '❌ Pesanan tidak ditemukan.';
    redirect('pesanan_saya.php');
}

// ===== CEK APAKAH BISA DIBATALKAN =====
$bisa_batal = ($order['status_pesanan'] === 'baru');

if (!$bisa_batal) {
    $_SESSION['error_pesanan'] = "❌ Pesanan <b>{$order['kode']}</b> tidak bisa dibatalkan karena sudah <b>" . strtoupper($order['status_pesanan']) . "</b>.";
    redirect('pesanan_saya.php');
}

// ===== HALAMAN KONFIRMASI =====
if ($konfirmasi !== 'yes') {
    include 'header.php';
    ?>

    <div class="section-head">
        <div class="eyebrow">BATAL PESANAN</div>
        <h1>⚠️ Konfirmasi Pembatalan</h1>
    </div>

    <div class="alert alert-warning" style="border-left:5px solid #F4A261;">
        <div>
            <b>Apakah kamu yakin ingin membatalkan pesanan ini?</b>
            <p style="margin-top:8px; font-size:0.9rem;">
                Pesanan yang sudah dibatalkan <b>tidak bisa dikembalikan</b>.
                Kamu harus pesan ulang jika berubah pikiran.
            </p>
        </div>
    </div>

    <div class="info-card">
        <h3>📋 Detail Pesanan</h3>
        <p><b>Kode:</b> <span style="font-family:monospace; color:var(--primary); font-weight:700;"><?= e($order['kode']) ?></span></p>
        <p><b>Menu:</b> <?= e($order['menu_nama']) ?> × <?= $order['jumlah'] ?></p>
        <p><b>Pilihan Sambal:</b> <?= e($order['jenis_sambal'] ?? 'Sambal Geprek') ?></p>
        <p><b>Total:</b> <span style="color:var(--primary); font-weight:800;"><?= rupiah($order['total']) ?></span></p>
        <p><b>Metode Bayar:</b> <?= e($order['metode_bayar']) ?></p>
        <p><b>Status Bayar:</b>
            <span class="badge badge-<?= $order['status_bayar'] ?>">
                <?= strtoupper($order['status_bayar']) ?>
            </span>
        </p>
    </div>

    <?php if ($order['status_bayar'] === 'lunas' && $order['metode_bayar'] !== 'COD'): ?>
        <div class="alert alert-warning">
            ⚠️ <b>Pembayaran kamu sudah LUNAS.</b><br>
            Setelah dibatalkan, silakan hubungi admin via WhatsApp untuk proses <b>refund</b>:
            <br><br>
            <a href="https://wa.me/6285398790908?text=Halo%20Admin%2C%20saya%20ingin%20refund%20pesanan%20<?= urlencode($order['kode']) ?>" 
               target="_blank" 
               class="btn btn-block" 
               style="background:#25D366; color:#fff;">
                💬 Hubungi Admin untuk Refund
            </a>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:20px;">
        <a href="batalkan_pesanan.php?id=<?= $order['id'] ?>&konfirmasi=yes" 
           class="btn btn-lg" 
           style="flex:1; min-width:180px; background:#E63946; color:#fff;">
            ❌ Ya, Batalkan Pesanan
        </a>
        <a href="pesanan_saya.php" 
           class="btn btn-lg" 
           style="flex:1; min-width:180px; background:#EAE5E0; color:#333;">
            ← Tidak, Kembali
        </a>
    </div>

    <?php
    include 'footer.php';
    exit;
}

// ===== PROSES PEMBATALAN =====
$stmt = $conn->prepare("UPDATE pesanan SET status_pesanan='batal', status_bayar=IF(status_bayar='lunas','lunas','gagal') WHERE id=?");
$stmt->bind_param('i', $order['id']);
$stmt->execute();

// ===== BUAT NOTIFIKASI UNTUK ADMIN =====
$cek_notif = $conn->query("SHOW TABLES LIKE 'notifikasi'");
if ($cek_notif && $cek_notif->num_rows > 0) {
    $judul = "❌ Pesanan Dibatalkan: {$order['kode']}";
    $pesan = "{$order['nama']} membatalkan pesanan";
    $link  = "pesanan.php";
    $tipe  = 'customer_batal';
    
    $stmt_notif = $conn->prepare("INSERT INTO notifikasi (tipe, judul, pesan, link, pesanan_id, kode_pesanan) VALUES (?,?,?,?,?,?)");
    $stmt_notif->bind_param('ssssis', $tipe, $judul, $pesan, $link, $order['id'], $order['kode']);
    $stmt_notif->execute();
}

// ===== UPDATE COOKIE STATUS (untuk toast) =====
setcookie('kios_last_seen_' . $order['kode'], 'batal', time() + (365 * 24 * 3600), '/');

// ===== REDIRECT =====
$_SESSION['sukses_pesanan'] = "✅ Pesanan <b>{$order['kode']}</b> berhasil dibatalkan.";
redirect('pesanan_saya.php');