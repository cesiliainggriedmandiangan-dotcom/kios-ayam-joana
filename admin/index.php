<?php
include 'header_admin.php';

$today = date('Y-m-d');
$bulan = date('Y-m');

$stats = ['total'=>0,'hari_ini'=>0,'baru'=>0,'diproses'=>0,'dikirim'=>0,'selesai'=>0,'menunggu_bayar'=>0];

$q = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN DATE(waktu)='$today' THEN 1 ELSE 0 END) AS hari_ini,
    SUM(CASE WHEN status_pesanan='baru' THEN 1 ELSE 0 END) AS baru,
    SUM(CASE WHEN status_pesanan='diproses' THEN 1 ELSE 0 END) AS diproses,
    SUM(CASE WHEN status_pesanan='dikirim' THEN 1 ELSE 0 END) AS dikirim,
    SUM(CASE WHEN status_pesanan='selesai' THEN 1 ELSE 0 END) AS selesai,
    SUM(CASE WHEN status_bayar='menunggu' THEN 1 ELSE 0 END) AS menunggu_bayar
    FROM pesanan");
if ($q && $row = $q->fetch_assoc()) {
    foreach ($stats as $k => $v) $stats[$k] = (int)($row[$k] ?? 0);
}

$q = $conn->query("SELECT COALESCE(SUM(total),0) AS t FROM pesanan WHERE status_bayar='lunas' AND DATE(waktu)='$today'");
$pendapatan_hari = (int)$q->fetch_assoc()['t'];

$q = $conn->query("SELECT COALESCE(SUM(total),0) AS t FROM pesanan WHERE status_bayar='lunas' AND DATE_FORMAT(waktu,'%Y-%m')='$bulan'");
$pendapatan_bulan = (int)$q->fetch_assoc()['t'];
?>

<h1 style="margin-bottom:20px;">📊 Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red">🛒</div>
        <div class="stat-info"><h3><?= $stats['total'] ?></h3><p>Total Pesanan</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">📅</div>
        <div class="stat-info"><h3><?= $stats['hari_ini'] ?></h3><p>Hari Ini</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">🆕</div>
        <div class="stat-info"><h3><?= $stats['baru'] ?></h3><p>Pesanan Baru</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">💳</div>
        <div class="stat-info"><h3><?= $stats['menunggu_bayar'] ?></h3><p>Menunggu Bayar</p></div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
            <h3 style="font-size:1.15rem;"><?= rupiah($pendapatan_hari) ?></h3>
            <p>Pendapatan Hari Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">📈</div>
        <div class="stat-info">
            <h3 style="font-size:1.15rem;"><?= rupiah($pendapatan_bulan) ?></h3>
            <p>Pendapatan Bulan Ini</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">⚙️</div>
        <div class="stat-info"><h3><?= $stats['diproses'] ?></h3><p>Diproses</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue">🚚</div>
        <div class="stat-info"><h3><?= $stats['dikirim'] ?></h3><p>Dikirim</p></div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2>🕐 Pesanan Terbaru</h2>
        <a href="pesanan.php" class="btn-xs btn-xs-primary">Lihat Semua →</a>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Menu</th>
                        <th>Total</th>
                        <th>Bayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q = $conn->query("SELECT * FROM pesanan ORDER BY waktu DESC LIMIT 5");
                if ($q->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px; color:#888;">
                        Belum ada pesanan.
                    </td></tr>
                <?php else:
                while ($p = $q->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Kode"><span class="kode"><?= e($p['kode']) ?></span></td>
                        <td data-label="Pelanggan">
                            <b><?= e($p['nama']) ?></b><br>
                            <small style="color:#888;"><?= e($p['hp']) ?></small>
                        </td>
                        <td data-label="Menu">
                            <div class="menu-cell">
                                <?= e($p['menu_nama']) ?>
                                <small>× <?= $p['jumlah'] ?> — <?= e($p['jenis_sambal'] ?? 'Sambal Geprek') ?></small>
                            </div>
                        </td>
                        <td data-label="Total"><span class="total"><?= rupiah($p['total']) ?></span></td>
                        <td data-label="Bayar">
                            <span class="badge badge-<?= $p['status_bayar'] ?>"><?= strtoupper($p['status_bayar']) ?></span>
                        </td>
                        <td data-label="Status">
                            <span class="badge badge-<?= $p['status_pesanan'] ?>"><?= strtoupper($p['status_pesanan']) ?></span>
                        </td>
                        <td data-label="Aksi">
                            <a href="detail.php?id=<?= $p['id'] ?>" class="btn-xs btn-xs-primary">Detail</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer_admin.php'; ?>