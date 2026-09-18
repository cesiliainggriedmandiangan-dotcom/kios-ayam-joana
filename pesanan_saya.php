<?php
include 'header.php';

// ===== HAPUS RIWAYAT KALAU DIMINTA =====
if (isset($_GET['hapus_semua'])) {
    setcookie('kios_pesanan', '', time() - 3600, '/');
    header('Location: pesanan_saya.php');
    exit;
}

// ===== NOTIFIKASI =====
if (isset($_SESSION['sukses_pesanan'])) {
    echo '<div class="alert alert-success">' . $_SESSION['sukses_pesanan'] . '</div>';
    unset($_SESSION['sukses_pesanan']);
}
if (isset($_SESSION['error_pesanan'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error_pesanan'] . '</div>';
    unset($_SESSION['error_pesanan']);
}

// ===== AMBIL KODE PESANAN DARI COOKIE =====
$kode_cookie = $_COOKIE['kios_pesanan'] ?? '';
$kode_list = array_filter(explode(',', $kode_cookie));

// ===== CARI BERDASARKAN NO. HP JUGA =====
$hp_cari = trim($_GET['hp'] ?? '');
$orders = [];

if ($hp_cari) {
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE hp=? ORDER BY waktu DESC LIMIT 20");
    $stmt->bind_param('s', $hp_cari);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} elseif (!empty($kode_list)) {
    $placeholders = implode(',', array_fill(0, count($kode_list), '?'));
    $types = str_repeat('s', count($kode_list));
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode IN ($placeholders) ORDER BY waktu DESC");
    $stmt->bind_param($types, ...$kode_list);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="section-head">
    <div class="eyebrow">RIWAYAT PESANAN</div>
    <h1>📋 Pesanan Saya</h1>
    <p>Semua pesanan yang pernah kamu buat</p>
</div>

<!-- Search by HP -->
<div class="form-card" style="margin-bottom:24px;">
    <h3 style="margin-bottom:12px;">🔍 Cari dengan No. HP</h3>
    <form method="GET">
        <div class="form-group">
            <label>No. HP yang dipakai saat pesan</label>
            <input type="tel" name="hp" 
                   placeholder="Contoh: 081234567890" 
                   value="<?= e($hp_cari) ?>"
                   pattern="[0-9]{10,15}"
                   required>
        </div>
        <button class="btn btn-primary btn-block">🔍 Cari Pesanan</button>
    </form>
</div>

<?php if ($hp_cari && empty($orders)): ?>
    <div class="alert alert-error">
        ❌ Tidak ada pesanan dengan No. HP <b><?= e($hp_cari) ?></b>.
    </div>
<?php endif; ?>

<?php if (empty($orders) && !$hp_cari): ?>
    <div class="info-card" style="text-align:center; padding:60px 20px;">
        <div style="font-size:4rem; margin-bottom:16px; opacity:0.4;">📭</div>
        <h3 style="color:#6B6B70;">Belum Ada Pesanan</h3>
        <p style="margin-bottom:20px;">Yuk pesan ayam geprek pertamamu!</p>
        <a href="pemesanan.php" class="btn btn-primary btn-lg">🛒 Pesan Sekarang</a>
    </div>
<?php endif; ?>

<?php if (!empty($orders)): ?>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
        <p style="margin:0; color:#6B6B70;">
            <b><?= count($orders) ?></b> pesanan ditemukan
        </p>
        <?php if (!empty($kode_list)): ?>
            <a href="pesanan_saya.php?hapus_semua=1" 
               onclick="return confirm('Hapus riwayat dari perangkat ini?')"
               style="color:#888; font-size:0.85rem;">🗑️ Hapus Riwayat</a>
        <?php endif; ?>
    </div>

    <?php foreach ($orders as $o): 
        $bisa_batal = ($o['status_pesanan'] === 'baru');
        $bisa_bayar = ($o['metode_bayar'] !== 'COD' 
                       && $o['status_bayar'] !== 'lunas' 
                       && $o['status_bayar'] !== 'gagal');
        $bisa_ganti_metode = (
            $o['status_pesanan'] === 'baru' && 
            in_array($o['status_bayar'], ['belum', 'menunggu'])
        );
    ?>
        <div class="info-card" style="border-left:4px solid <?= 
            $o['status_pesanan']==='selesai' ? 'var(--success)' : 
            ($o['status_pesanan']==='batal' ? '#888' : 
            ($o['status_pesanan']==='dikirim' ? '#457B9D' : 'var(--primary)')) ?>;">
            
            <!-- Header: Kode + Status -->
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
                <div>
                    <b style="font-family:monospace; color:var(--primary); font-size:1.05rem;">
                        <?= e($o['kode']) ?>
                    </b>
                    <br>
                    <small style="color:#888;"><?= date('d M Y, H:i', strtotime($o['waktu'])) ?> WIB</small>
                </div>
                <span class="badge badge-<?= $o['status_pesanan'] ?>">
                    <?= strtoupper($o['status_pesanan']) ?>
                </span>
            </div>

            <hr style="border:none; border-top:1px solid var(--border); margin:10px 0;">

            <!-- Detail Pesanan -->
            <p style="margin:4px 0;"><b>Menu:</b> <?= e($o['menu_nama']) ?> × <?= $o['jumlah'] ?></p>
            <p style="margin:4px 0;"><b>Sambal:</b> <?= e($o['jenis_sambal'] ?? 'Sambal Geprek') ?></p>
            <p style="margin:4px 0;">
                <b>Total:</b> 
                <span style="color:var(--primary); font-weight:800; font-size:1.1rem;">
                    <?= rupiah($o['total']) ?>
                </span>
            </p>
            <p style="margin:4px 0;">
                <b>Bayar:</b> <?= e($o['metode_bayar']) ?> — 
                <span class="badge badge-<?= $o['status_bayar'] ?>">
                    <?= strtoupper($o['status_bayar']) ?>
                </span>
            </p>

            <!-- ===== TOMBOL AKSI ===== -->
            <div style="display:flex; gap:8px; margin-top:14px; flex-wrap:wrap;">
                
                <!-- Lihat Detail -->
                <a href="cek_pesanan.php?kode=<?= urlencode($o['kode']) ?>" 
                   class="btn btn-primary btn-sm">
                    🔍 Lihat Detail
                </a>
                
                <!-- Lanjut Bayar -->
                <?php if ($bisa_bayar): ?>
                    <a href="pembayaran.php?id=<?= $o['id'] ?>" 
                       class="btn btn-accent btn-sm">
                        💳 Lanjut Bayar
                    </a>
                <?php endif; ?>

                <!-- Ganti Metode -->
                <?php if ($bisa_ganti_metode): ?>
                    <a href="ganti_metode.php?id=<?= $o['id'] ?>" 
                       class="btn btn-sm" 
                       style="background:#FFF6E5; color:#8A5A00; border:1.5px solid #FFB703;">
                        🔄 Ganti Metode
                    </a>
                <?php endif; ?>

                <!-- Batalkan Pesanan -->
                <?php if ($bisa_batal): ?>
                    <a href="batalkan_pesanan.php?id=<?= $o['id'] ?>" 
                       class="btn btn-sm" 
                       style="background:#E63946; color:#fff;">
                        ❌ Batalkan
                    </a>
                <?php endif; ?>
            </div>

            <!-- Info Tambahan -->
            <?php if ($o['status_pesanan'] === 'batal'): ?>
                <div style="margin-top:12px; padding:10px 14px; background:#F5F1ED; border-radius:8px; font-size:0.82rem; color:#888;">
                    ℹ️ Pesanan ini sudah <b>dibatalkan</b>. Pesan ulang kapan saja!
                </div>
            <?php elseif ($o['status_pesanan'] === 'selesai'): ?>
                <div style="margin-top:12px; padding:10px 14px; background:#E8F6F1; border-radius:8px; font-size:0.82rem; color:#1B6E5E;">
                    ✅ Pesanan selesai! Terima kasih sudah memesan 🍗
                    <br>
                    <a href="testimoni.php?kode=<?= urlencode($o['kode']) ?>#formTestimoni" 
                       style="color:#1B6E5E; font-weight:700; text-decoration:underline;">
                        ⭐ Beri testimoni
                    </a>
                </div>
            <?php elseif ($o['status_pesanan'] === 'dikirim'): ?>
                <div style="margin-top:12px; padding:10px 14px; background:#DCEAF7; border-radius:8px; font-size:0.82rem; color:#1E4E6E;">
                    🚚 Pesanan sedang dalam perjalanan. Kurir akan menghubungi kamu!
                </div>
            <?php elseif ($o['status_pesanan'] === 'diproses'): ?>
                <div style="margin-top:12px; padding:10px 14px; background:#FFF6E5; border-radius:8px; font-size:0.82rem; color:#8A5A00;">
                    ⚙️ Pesanan sedang diproses di dapur. Mohon tunggu ya!
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Info Bantuan -->
<?php if (!empty($orders) || $hp_cari): ?>
    <div class="info-card" style="margin-top:24px; background:linear-gradient(135deg, #FFF8F0, #FFEEE0); border:1.5px dashed var(--accent);">
        <h3>💡 Butuh Bantuan?</h3>
        <p style="font-size:0.9rem; margin-bottom:12px;">
            Ada kendala dengan pesananmu? Hubungi admin via WhatsApp:
        </p>
        <a href="https://wa.me/6285398790908" 
           target="_blank" 
           class="btn btn-block" 
           style="background:#25D366; color:#fff;">
            💬 Chat Admin
        </a>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>