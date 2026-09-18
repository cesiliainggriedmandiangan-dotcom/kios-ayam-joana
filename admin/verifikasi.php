<?php
include 'header_admin.php';

// ===== AKSI VERIFIKASI =====
if (isset($_GET['aksi'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $aksi = $_GET['aksi'];

    if ($aksi === 'lunas') {
        $stmt = $conn->prepare("UPDATE pesanan SET status_bayar='lunas', status_pesanan='diproses' WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash'] = '✅ Pembayaran berhasil diverifikasi!';
    } elseif ($aksi === 'tolak') {
        $stmt = $conn->prepare("UPDATE pesanan SET status_bayar='gagal' WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['flash'] = '❌ Pembayaran ditolak.';
    }
    redirect('verifikasi.php');
}

// ===== MAPPING LABEL METODE =====
$metode_label = [
    'COD'      => ['label' => '💵 COD', 'color' => 'belum'],
    'Transfer' => ['label' => '🏦 Transfer BRI', 'color' => 'diproses'],
    'QRIS'     => ['label' => '📱 QRIS', 'color' => 'dikirim'],
    'DANA'     => ['label' => '📲 DANA', 'color' => 'lunas'],
    'Midtrans' => ['label' => '📲 DANA', 'color' => 'lunas'],
];
?>

<h1 style="margin-bottom:20px;">💳 Verifikasi Pembayaran</h1>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= $_SESSION['flash'] ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <h2>Bukti Pembayaran Menunggu Verifikasi</h2>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q = $conn->query("SELECT * FROM pesanan WHERE status_bayar='menunggu' ORDER BY waktu DESC");
                if ($q->num_rows === 0): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="icon">✅</div>
                            <h3>Tidak Ada Pembayaran Menunggu</h3>
                            <p>Semua bukti sudah diverifikasi</p>
                        </div>
                    </td></tr>
                <?php else:
                while ($p = $q->fetch_assoc()):
                    $metode_info = $metode_label[$p['metode_bayar']] ?? ['label' => $p['metode_bayar'], 'color' => 'batal'];
                ?>
                    <tr>
                        <td data-label="Kode">
                            <span class="kode"><?= e($p['kode']) ?></span><br>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($p['waktu'])) ?></small>
                        </td>
                        <td data-label="Pelanggan">
                            <b><?= e($p['nama']) ?></b><br>
                            <small style="color:#888;"><?= e($p['hp']) ?></small><br>
                            <small style="color:#888; font-size:0.75rem;"><?= e($p['menu_nama']) ?> × <?= $p['jumlah'] ?></small>
                        </td>
                        <td data-label="Total">
                            <span class="total"><?= rupiah($p['total']) ?></span>
                        </td>
                        <td data-label="Metode">
                            <span class="badge badge-<?= $metode_info['color'] ?>">
                                <?= $metode_info['label'] ?>
                            </span>
                        </td>
                        <td data-label="Bukti">
                            <?php if ($p['bukti_bayar']): ?>
                                <a href="../bukti/<?= e($p['bukti_bayar']) ?>" target="_blank">
                                    <img src="../bukti/<?= e($p['bukti_bayar']) ?>" 
                                         style="width:70px; height:70px; object-fit:cover; border-radius:8px; border:2px solid #EAE5E0; cursor:pointer; transition:.2s;"
                                         onmouseover="this.style.transform='scale(1.05)'"
                                         onmouseout="this.style.transform='scale(1)'">
                                </a>
                            <?php else: ?>
                                <span style="color:#888; font-size:0.85rem;">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Aksi">
                            <div class="btn-row">
                                <a href="?aksi=lunas&id=<?= $p['id'] ?>" 
                                   class="btn-xs btn-xs-success" 
                                   onclick="return confirm('Verifikasi pesanan <?= e($p['kode']) ?> sebagai LUNAS?')">
                                   ✅ Lunas
                                </a>
                                <a href="?aksi=tolak&id=<?= $p['id'] ?>" 
                                   class="btn-xs btn-xs-danger"
                                   onclick="return confirm('Tolak pembayaran ini?')">
                                   ❌ Tolak
                                </a>
                                <a href="detail.php?id=<?= $p['id'] ?>" 
                                   class="btn-xs btn-xs-info">
                                   🔍 Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Statistik -->
<div class="panel">
    <div class="panel-head">
        <h2>📊 Statistik Pembayaran</h2>
    </div>
    <div class="panel-body">
        <?php
        $stats = $conn->query("SELECT 
            SUM(CASE WHEN status_bayar='menunggu' THEN 1 ELSE 0 END) AS menunggu,
            SUM(CASE WHEN status_bayar='lunas' THEN 1 ELSE 0 END) AS lunas,
            SUM(CASE WHEN status_bayar='gagal' THEN 1 ELSE 0 END) AS gagal,
            SUM(CASE WHEN status_bayar='belum' THEN 1 ELSE 0 END) AS belum
            FROM pesanan")->fetch_assoc();
        ?>
        <div class="stats-grid" style="grid-template-columns:repeat(auto-fit, minmax(140px,1fr));">
            <div class="stat-card">
                <div class="stat-icon gold">⏳</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['menunggu'] ?></h3>
                    <p>Menunggu</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['lunas'] ?></h3>
                    <p>Lunas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">❌</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['gagal'] ?></h3>
                    <p>Gagal</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">💵</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['belum'] ?></h3>
                    <p>COD Belum Bayar</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer_admin.php'; ?>