<?php
include 'header.php';

$kode = strtoupper(trim($_GET['kode'] ?? ''));
$id_param = (int)($_GET['id'] ?? 0);
$order = null;

if ($kode || $id_param) {
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode=? OR id=? LIMIT 1");
    $stmt->bind_param('si', $kode, $id_param);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

// Notif
if (isset($_SESSION['sukses_pesanan'])) {
    echo '<div class="alert alert-success">' . $_SESSION['sukses_pesanan'] . '</div>';
    unset($_SESSION['sukses_pesanan']);
}
if (isset($_SESSION['error_pesanan'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error_pesanan'] . '</div>';
    unset($_SESSION['error_pesanan']);
}
?>

<div class="section-head">
    <div class="eyebrow">CEK STATUS</div>
    <h1>📦 Cek Pesanan</h1>
    <p>Masukkan kode pesanan untuk melihat status terkini</p>
</div>

<div class="form-card">
    <form method="GET">
        <div class="form-group">
            <label>Kode Pesanan / ID</label>
            <input type="text" name="kode" 
                   placeholder="Contoh: AYM-260916-AB123" 
                   value="<?= e($kode) ?>" 
                   required
                   autocomplete="off"
                   style="text-transform: uppercase;">
            <small style="color:#888; display:block; margin-top:6px;">
                💡 Kode pesanan kamu terlihat setelah berhasil memesan
            </small>
        </div>
        <button class="btn btn-primary btn-block btn-lg">🔍 Cek Pesanan</button>
    </form>
</div>

<?php if (($kode || $id_param) && !$order): ?>
    <div class="alert alert-error" style="margin-top:16px;">
        ❌ Pesanan dengan kode <b><?= e($kode) ?></b> tidak ditemukan.
    </div>
<?php endif; ?>

<?php if ($order): 
    $bisa_batal = ($order['status_pesanan'] === 'baru');
    $bisa_bayar = ($order['metode_bayar'] !== 'COD' 
                   && $order['status_bayar'] !== 'lunas' 
                   && $order['status_bayar'] !== 'gagal');
    $bisa_ganti_metode = (
        $order['status_pesanan'] === 'baru' && 
        in_array($order['status_bayar'], ['belum', 'menunggu'])
    );
?>
    <div class="info-card" style="margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:12px;">
            <h3 style="margin:0;">🎫 <?= e($order['kode']) ?></h3>
            <span class="badge badge-<?= $order['status_pesanan'] ?>">
                <?= strtoupper($order['status_pesanan']) ?>
            </span>
        </div>
        
        <hr style="border:none; border-top:1px solid var(--border); margin:12px 0;">
        
        <p><b>Tanggal Pesan:</b> <?= date('d F Y, H:i', strtotime($order['waktu'])) ?> WIB</p>
        <p><b>Nama:</b> <?= e($order['nama']) ?></p>
        <p><b>No. HP:</b> <?= e($order['hp']) ?></p>
        <p><b>Alamat:</b> <?= nl2br(e($order['alamat'])) ?></p>
    </div>

    <div class="info-card">
        <h3>🍗 Detail Pesanan</h3>
        <p><b>Menu:</b> <?= e($order['menu_nama']) ?></p>
        <p><b>Jumlah:</b> <?= $order['jumlah'] ?> porsi</p>
        <p><b>Pilihan Sambal:</b> <?= e($order['jenis_sambal'] ?? 'Sambal Geprek') ?></p>
        <?php if ($order['catatan']): ?>
            <p><b>Catatan:</b> <?= e($order['catatan']) ?></p>
        <?php endif; ?>
        <hr style="border:none; border-top:1px solid var(--border); margin:12px 0;">
        <p style="font-size:1.2rem;"><b>Total:</b> 
            <span style="color:var(--primary); font-weight:800;">
                <?= rupiah($order['total']) ?>
            </span>
        </p>
    </div>

    <div class="info-card">
        <h3>💳 Status Pembayaran</h3>
        <p><b>Metode:</b> <?= e($order['metode_bayar']) ?></p>
        <p><b>Status Bayar:</b>
            <span class="badge badge-<?= $order['status_bayar'] ?>">
                <?= strtoupper($order['status_bayar']) ?>
            </span>
        </p>
        <p><b>Status Pesanan:</b>
            <span class="badge badge-<?= $order['status_pesanan'] ?>">
                <?= strtoupper($order['status_pesanan']) ?>
            </span>
        </p>

        <!-- Tombol Lanjut Bayar -->
        <?php if ($bisa_bayar): ?>
            <a href="pembayaran.php?id=<?= $order['id'] ?>" 
               class="btn btn-primary btn-block" 
               style="margin-top:14px;">
                💳 Lanjut Bayar
            </a>
        <?php endif; ?>

        <!-- Tombol Ganti Metode -->
        <?php if ($bisa_ganti_metode): ?>
            <a href="ganti_metode.php?id=<?= $order['id'] ?>" 
               class="btn btn-block" 
               style="margin-top:10px; background:#FFF6E5; color:#8A5A00; border:1.5px solid #FFB703;">
                🔄 Ganti Metode Pembayaran
            </a>
        <?php endif; ?>
    </div>

    <!-- Tombol Batal -->
    <?php if ($bisa_batal): ?>
        <div class="info-card" style="border-left:4px solid #E63946; background:#FDECEE;">
            <h3 style="color:#A11426;">⚠️ Batalkan Pesanan</h3>
            <p style="font-size:0.9rem; color:#A11426;">
                Pesanan masih bisa dibatalkan karena belum diproses.
            </p>
            <a href="batalkan_pesanan.php?id=<?= $order['id'] ?>" 
               class="btn btn-block" 
               style="background:#E63946; color:#fff; margin-top:12px;">
                ❌ Batalkan Pesanan
            </a>
        </div>
    <?php else: ?>
        <div class="info-card" style="background:#F5F1ED;">
            <p style="font-size:0.85rem; color:#888; margin:0;">
                ℹ️ Pesanan sudah <b><?= strtoupper($order['status_pesanan']) ?></b> — tidak bisa dibatalkan sendiri.
                Hubungi admin via <a href="https://wa.me/6285398790908" style="color:var(--primary); font-weight:700;">WhatsApp</a> untuk bantuan.
            </p>
        </div>
    <?php endif; ?>

    <!-- Progress Tracker -->
    <div class="info-card">
        <h3>📊 Progress Pesanan</h3>
        <div style="display:flex; justify-content:space-between; margin-top:20px; position:relative;">
            <?php
            $steps = [
                'baru'     => '🆕 Diterima', 
                'diproses' => '⚙️ Diproses', 
                'dikirim'  => '🚚 Dikirim', 
                'selesai'  => '✅ Selesai'
            ];
            $current_step = $order['status_pesanan'];
            $step_keys = array_keys($steps);
            $current_index = array_search($current_step, $step_keys);
            if ($current_index === false) $current_index = -1;
            
            foreach ($steps as $key => $label):
                $idx = array_search($key, $step_keys);
                $is_done = $idx <= $current_index;
                $is_active = $idx === $current_index;
            ?>
                <div style="text-align:center; flex:1; position:relative; z-index:2;">
                    <div style="width:38px; height:38px; border-radius:50%; 
                                background: <?= $is_done ? 'var(--primary)' : '#EAE5E0' ?>; 
                                color: <?= $is_done ? '#fff' : '#888' ?>; 
                                display:flex; align-items:center; justify-content:center; 
                                margin:0 auto 8px; font-weight:700; font-size:0.9rem;
                                <?= $is_active ? 'box-shadow: 0 0 0 6px rgba(230,57,70,0.2);' : '' ?>">
                        <?= $is_done ? '✓' : ($idx + 1) ?>
                    </div>
                    <small style="color: <?= $is_done ? 'var(--text)' : '#888' ?>; 
                                  font-weight: <?= $is_active ? '700' : '500' ?>; 
                                  font-size:0.72rem; display:block; line-height:1.2;">
                        <?= $label ?>
                    </small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'footer.php'; ?>