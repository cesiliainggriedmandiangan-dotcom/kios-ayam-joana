<?php
// =====================================================
// BLOK PROSES — HARUS DI ATAS include 'header_admin.php'
// =====================================================
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin'])) {
    redirect('login.php');
}

$id = (int)($_GET['id'] ?? 0);

// ===== PROSES POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_status';

    // ===== ACTION 1: UPDATE STATUS =====
    if ($action === 'update_status') {
        $status_bayar   = $_POST['status_bayar'] ?? '';
        $status_pesanan = $_POST['status_pesanan'] ?? '';

        $allowed_bayar = ['belum', 'menunggu', 'lunas', 'gagal'];
        $allowed_pesanan = ['baru', 'diproses', 'dikirim', 'selesai', 'batal'];

        if (!in_array($status_bayar, $allowed_bayar)) $status_bayar = 'belum';
        if (!in_array($status_pesanan, $allowed_pesanan)) $status_pesanan = 'baru';

        $stmt = $conn->prepare("UPDATE pesanan SET status_bayar=?, status_pesanan=? WHERE id=?");
        $stmt->bind_param('ssi', $status_bayar, $status_pesanan, $id);
        $stmt->execute();

        $_SESSION['flash'] = '✅ Status pesanan berhasil diperbarui.';
        redirect("detail.php?id=$id");
    }

    // ===== ACTION 2: GANTI METODE BAYAR =====
    if ($action === 'ganti_metode') {
        $metode_baru = $_POST['metode_bayar'] ?? '';

        $allowed_metode = ['COD', 'Transfer', 'QRIS', 'DANA'];
        if (!in_array($metode_baru, $allowed_metode)) {
            $_SESSION['flash'] = '❌ Metode tidak valid.';
            redirect("detail.php?id=$id");
        }

        // Ambil metode lama
        $stmt = $conn->prepare("SELECT metode_bayar FROM pesanan WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();

        if ($old && $old['metode_bayar'] === $metode_baru) {
            $_SESSION['flash'] = '⚠️ Metode baru sama dengan sebelumnya.';
            redirect("detail.php?id=$id");
        }

        // Reset status bayar
        $status_bayar_baru = ($metode_baru === 'COD') ? 'belum' : 'menunggu';

        $stmt = $conn->prepare("UPDATE pesanan SET metode_bayar=?, status_bayar=? WHERE id=?");
        $stmt->bind_param('ssi', $metode_baru, $status_bayar_baru, $id);
        $stmt->execute();

        $_SESSION['flash'] = "✅ Metode pembayaran berhasil diubah menjadi <b>$metode_baru</b>.";
        redirect("detail.php?id=$id");
    }
}

// ===== AKSI HAPUS BUKTI BAYAR =====
if (isset($_GET['hapus_bukti'])) {
    $stmt = $conn->prepare("SELECT bukti_bayar FROM pesanan WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && $row['bukti_bayar']) {
        $file = __DIR__ . '/../bukti/' . $row['bukti_bayar'];
        if (file_exists($file)) unlink($file);
    }

    $stmt = $conn->prepare("UPDATE pesanan SET bukti_bayar=NULL WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $_SESSION['flash'] = '🗑️ Bukti bayar berhasil dihapus.';
    redirect("detail.php?id=$id");
}

// ===== AMBIL DATA PESANAN =====
$stmt = $conn->prepare("SELECT * FROM pesanan WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();

if (!$p) {
    $_SESSION['flash'] = '❌ Pesanan tidak ditemukan.';
    redirect('pesanan.php');
}

// =====================================================
// BARU INCLUDE HEADER
// =====================================================
include 'header_admin.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h1>📋 Detail Pesanan</h1>
    <div style="display:flex; gap:8px;">
        <a href="pesanan.php" class="btn-xs btn-xs-ghost">← Kembali</a>
        <a href="verifikasi.php" class="btn-xs btn-xs-info">💳 Verifikasi</a>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= $_SESSION['flash'] ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- ===== INFO PESANAN ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>📦 Info Pesanan</h2>
        <span class="badge badge-<?= $p['status_pesanan'] ?>">
            <?= strtoupper($p['status_pesanan']) ?>
        </span>
    </div>
    <div class="panel-body">
        <p><b>Kode:</b> <span class="kode"><?= e($p['kode']) ?></span></p>
        <p><b>Waktu:</b> <?= date('d F Y, H:i', strtotime($p['waktu'])) ?> WIB</p>
        <p><b>Nama:</b> <?= e($p['nama']) ?></p>
        <p>
            <b>No. HP:</b> 
            <a href="tel:<?= e($p['hp']) ?>"><?= e($p['hp']) ?></a>
            |
            <a href="https://wa.me/62<?= ltrim(e($p['hp']), '0') ?>?text=Halo%20<?= urlencode($p['nama']) ?>%2C%20pesanan%20Anda%20<?= urlencode($p['kode']) ?>%20sedang%20kami%20proses." 
               target="_blank" 
               style="color:#25D366; font-weight:700;">
                💬 WA
            </a>
        </p>
        <p><b>Alamat:</b> <?= nl2br(e($p['alamat'])) ?></p>
    </div>
</div>

<!-- ===== DETAIL MENU ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>🍗 Detail Menu</h2>
    </div>
    <div class="panel-body">
        <p><b>Menu:</b> <?= e($p['menu_nama']) ?></p>
        <p><b>Jumlah:</b> <?= $p['jumlah'] ?> porsi</p>
        <p><b>Harga Satuan:</b> <?= rupiah($p['harga']) ?></p>
        <p><b>Pilihan Sambal:</b> <?= e($p['jenis_sambal'] ?? 'Sambal Geprek') ?></p>
        <?php if ($p['catatan']): ?>
            <p><b>Catatan:</b> <?= nl2br(e($p['catatan'])) ?></p>
        <?php endif; ?>
        <hr style="margin:12px 0; border:none; border-top:1px solid #EAE5E0;">
        <p style="font-size:1.2rem;">
            <b>Total:</b> 
            <span style="color:var(--primary); font-weight:800;">
                <?= rupiah($p['total']) ?>
            </span>
        </p>
    </div>
</div>

<!-- ===== PEMBAYARAN ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>💳 Pembayaran</h2>
        <span class="badge badge-<?= $p['status_bayar'] ?>">
            <?= strtoupper($p['status_bayar']) ?>
        </span>
    </div>
    <div class="panel-body">
        <p><b>Metode:</b> <?= e($p['metode_bayar']) ?></p>
        <p><b>Status Bayar:</b>
            <span class="badge badge-<?= $p['status_bayar'] ?>">
                <?= strtoupper($p['status_bayar']) ?>
            </span>
        </p>

        <?php if ($p['bukti_bayar']): ?>
            <div style="margin-top:16px;">
                <p><b>Bukti Bayar:</b></p>
                <a href="../bukti/<?= e($p['bukti_bayar']) ?>" target="_blank">
                    <img src="../bukti/<?= e($p['bukti_bayar']) ?>" 
                         style="max-width:300px; border-radius:10px; border:2px solid #EAE5E0;">
                </a>
                <div style="margin-top:10px;">
                    <a href="?id=<?= $id ?>&hapus_bukti=1" 
                       class="btn-xs btn-xs-danger"
                       onclick="return confirm('Hapus bukti pembayaran ini?')">
                        🗑️ Hapus Bukti
                    </a>
                </div>
            </div>
        <?php else: ?>
            <p style="color:#888; margin-top:12px;">Belum ada bukti pembayaran.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ===== UPDATE STATUS ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>⚙️ Update Status</h2>
    </div>
    <div class="panel-body">
        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="update_status">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Status Pembayaran</label>
                    <select name="status_bayar">
                        <?php foreach (['belum','menunggu','lunas','gagal'] as $s): ?>
                            <option value="<?= $s ?>" <?= $p['status_bayar']==$s?'selected':'' ?>>
                                <?= strtoupper($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Pesanan</label>
                    <select name="status_pesanan">
                        <?php foreach (['baru','diproses','dikirim','selesai','batal'] as $s): ?>
                            <option value="<?= $s ?>" <?= $p['status_pesanan']==$s?'selected':'' ?>>
                                <?= strtoupper($s) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button class="btn btn-primary btn-block btn-lg">💾 Simpan Perubahan</button>
        </form>
    </div>
</div>

<!-- ===== GANTI METODE PEMBAYARAN ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>🔄 Ganti Metode Pembayaran</h2>
    </div>
    <div class="panel-body">
        <p style="margin-bottom:16px; font-size:0.9rem; color:#888;">
            Ganti metode pembayaran pesanan ini. Status bayar akan otomatis di-reset.
        </p>

        <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="ganti_metode">
            
            <div class="form-group">
                <label>Metode Baru</label>
                <select name="metode_bayar" required>
                    <option value="">-- Pilih Metode --</option>
                    <option value="COD" <?= $p['metode_bayar']=='COD'?'disabled':'' ?>>
                        💵 COD (Bayar di Tempat) <?= $p['metode_bayar']=='COD'?'(saat ini)':'' ?>
                    </option>
                    <option value="Transfer" <?= $p['metode_bayar']=='Transfer'?'disabled':'' ?>>
                        🏦 Transfer Bank BRI <?= $p['metode_bayar']=='Transfer'?'(saat ini)':'' ?>
                    </option>
                    <option value="QRIS" <?= $p['metode_bayar']=='QRIS'?'disabled':'' ?>>
                        📱 QRIS <?= $p['metode_bayar']=='QRIS'?'(saat ini)':'' ?>
                    </option>
                    <option value="DANA" <?= $p['metode_bayar']=='DANA'?'disabled':'' ?>>
                        📲 DANA <?= $p['metode_bayar']=='DANA'?'(saat ini)':'' ?>
                    </option>
                </select>
            </div>

            <button class="btn btn-primary btn-block"
                    onclick="return confirm('Yakin ganti metode pembayaran? Status bayar akan di-reset.')">
                🔄 Ganti Metode
            </button>
        </form>
    </div>
</div>

<!-- ===== TIMELINE ===== -->
<div class="panel">
    <div class="panel-head">
        <h2>📅 Timeline</h2>
    </div>
    <div class="panel-body">
        <p><b>Dibuat:</b> <?= date('d F Y, H:i:s', strtotime($p['waktu'])) ?> WIB</p>
        <p><b>Update Terakhir:</b> <?= date('d F Y, H:i:s', strtotime($p['waktu_update'])) ?> WIB</p>
    </div>
</div>

<?php include 'footer_admin.php'; ?>