<?php
require 'config.php';

// ===== AMBIL PARAMETER =====
$kode = strtoupper(trim($_GET['kode'] ?? ''));
$id   = (int)($_GET['id'] ?? 0);

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

// ===== CEK APAKAH BISA GANTI METODE =====
// Bisa ganti kalau:
// - Status pesanan masih 'baru'
// - Status bayar 'belum' atau 'menunggu' (bukan lunas/gagal)
$bisa_ganti = (
    $order['status_pesanan'] === 'baru' &&
    in_array($order['status_bayar'], ['belum', 'menunggu'])
);

if (!$bisa_ganti) {
    $_SESSION['error_pesanan'] = "❌ Pesanan <b>{$order['kode']}</b> tidak bisa ganti metode karena sudah <b>" . strtoupper($order['status_pesanan']) . "</b> atau pembayaran sudah <b>" . strtoupper($order['status_bayar']) . "</b>.";
    redirect('pesanan_saya.php');
}

// ===== PROSES GANTI METODE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode_baru = $_POST['metode_bayar'] ?? '';

    $allowed = ['COD', 'Transfer', 'QRIS', 'DANA'];
    if (!in_array($metode_baru, $allowed)) {
        $_SESSION['error_pesanan'] = '❌ Metode pembayaran tidak valid.';
        redirect("ganti_metode.php?id={$order['id']}");
    }

    // Kalau metode sama, tidak perlu update
    if ($metode_baru === $order['metode_bayar']) {
        $_SESSION['error_pesanan'] = '⚠️ Metode baru sama dengan metode sebelumnya.';
        redirect("ganti_metode.php?id={$order['id']}");
    }

    // Update metode + reset status bayar
    // COD → 'belum', lainnya → 'menunggu'
    $status_bayar_baru = ($metode_baru === 'COD') ? 'belum' : 'menunggu';

    $stmt = $conn->prepare("UPDATE pesanan SET metode_bayar=?, status_bayar=? WHERE id=?");
    $stmt->bind_param('ssi', $metode_baru, $status_bayar_baru, $order['id']);
    $stmt->execute();

    $_SESSION['sukses_pesanan'] = "✅ Metode pembayaran berhasil diubah menjadi <b>$metode_baru</b>.";

    // Kalau metode baru COD → redirect ke pesanan_saya
    // Kalau lainnya → redirect ke pembayaran
    if ($metode_baru === 'COD') {
        redirect('pesanan_saya.php');
    } else {
        redirect("pembayaran.php?id={$order['id']}");
    }
}

// ===== TAMPILKAN FORM =====
include 'header.php';

$metode_options = [
    'COD'      => ['icon' => '💵', 'label' => 'COD (Bayar di Tempat)', 'desc' => 'Bayar tunai saat pesanan diantar'],
    'Transfer' => ['icon' => '🏦', 'label' => 'Transfer Bank BRI', 'desc' => 'Transfer ke 5179 0102 8678 532'],
    'QRIS'     => ['icon' => '📱', 'label' => 'QRIS (E-Wallet / M-Banking)', 'desc' => 'Scan QR pakai GoPay/OVO/DANA/dll'],
    'DANA'     => ['icon' => '📲', 'label' => 'DANA', 'desc' => 'Kirim ke 0853-9879-0908'],
];
?>

<div class="section-head">
    <div class="eyebrow">GANTI METODE</div>
    <h1>🔄 Ganti Metode Pembayaran</h1>
    <p>Pilih metode baru untuk pesanan ini</p>
</div>

<?php if (isset($_SESSION['error_pesanan'])): ?>
    <div class="alert alert-error"><?= $_SESSION['error_pesanan'] ?></div>
    <?php unset($_SESSION['error_pesanan']); ?>
<?php endif; ?>

<!-- Info Pesanan -->
<div class="info-card">
    <h3>📋 Info Pesanan</h3>
    <p><b>Kode:</b> <span style="font-family:monospace; color:var(--primary); font-weight:700;"><?= e($order['kode']) ?></span></p>
    <p><b>Menu:</b> <?= e($order['menu_nama']) ?> × <?= $order['jumlah'] ?></p>
    <p><b>Total:</b> <span style="color:var(--primary); font-weight:800;"><?= rupiah($order['total']) ?></span></p>
    <hr style="border:none; border-top:1px solid var(--border); margin:12px 0;">
    <p>
        <b>Metode Saat Ini:</b> 
        <span class="badge badge-<?= $order['status_bayar'] ?>">
            <?= e($order['metode_bayar']) ?>
        </span>
    </p>
</div>

<!-- Form Ganti Metode -->
<div class="form-card">
    <h3 style="margin-bottom:6px;">Pilih Metode Baru</h3>
    <p style="color:#888; font-size:0.9rem; margin-bottom:20px;">
        Status pembayaran akan di-reset menjadi <b>"Menunggu"</b> (untuk Transfer/QRIS/DANA) atau <b>"Belum"</b> (untuk COD).
    </p>

    <form method="POST">
        <div class="pay-options" style="margin-bottom:20px;">
            <?php foreach ($metode_options as $kode_metode => $opt): 
                $is_current = ($order['metode_bayar'] === $kode_metode);
            ?>
                <label class="pay-opt" style="<?= $is_current ? 'opacity:0.5; cursor:not-allowed;' : '' ?>">
                    <input type="radio" 
                           name="metode_bayar" 
                           value="<?= $kode_metode ?>" 
                           <?= $is_current ? 'disabled' : '' ?>
                           <?= ($order['metode_bayar'] !== $kode_metode) ? '' : '' ?>
                           required>
                    <span>
                        <?= $opt['icon'] ?> <b><?= $opt['label'] ?></b>
                        <?php if ($is_current): ?>
                            <span class="badge badge-diproses" style="font-size:0.65rem; margin-left:6px;">SAAT INI</span>
                        <?php endif; ?>
                        <br>
                        <small style="color:#888; font-weight:400;"><?= $opt['desc'] ?></small>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary btn-lg" style="flex:1; min-width:180px;">
                💾 Simpan Perubahan
            </button>
            <a href="pesanan_saya.php" class="btn btn-lg" 
               style="flex:1; min-width:180px; background:#EAE5E0; color:#333;">
                ← Batal
            </a>
        </div>
    </form>
</div>

<div class="info-card" style="background:#FFF6E5; border-left:4px solid #FFB703; margin-top:20px;">
    <p style="margin:0; font-size:0.9rem; color:#8A5A00;">
        ⚠️ <b>Perhatian:</b> Kalau kamu sudah upload bukti transfer sebelumnya, bukti itu akan <b>tetap tersimpan</b> tapi <b>tidak akan diverifikasi</b> karena metode sudah berubah.
        Upload bukti baru setelah ganti metode ya!
    </p>
</div>

<?php include 'footer.php'; ?>