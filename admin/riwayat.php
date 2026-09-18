<?php
// =====================================================
// BLOK PROSES — HARUS DI ATAS include 'header_admin.php'
// =====================================================
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin'])) {
    redirect('login.php');
}

// ===== FILTER =====
$filter = $_GET['filter'] ?? 'semua';
$where = "WHERE status_pesanan IN ('selesai','batal')";

if ($filter === 'selesai') {
    $where = "WHERE status_pesanan='selesai'";
} elseif ($filter === 'batal') {
    $where = "WHERE status_pesanan='batal'";
}

// ===== SEARCH =====
$search = trim($_GET['q'] ?? '');
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (kode LIKE '%$s%' OR nama LIKE '%$s%' OR hp LIKE '%$s%')";
}

// ===== STATISTIK =====
$stats = $conn->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status_pesanan='selesai' THEN 1 ELSE 0 END) AS selesai,
    SUM(CASE WHEN status_pesanan='batal' THEN 1 ELSE 0 END) AS batal,
    COALESCE(SUM(CASE WHEN status_pesanan='selesai' AND status_bayar='lunas' THEN total END), 0) AS total_pendapatan
    FROM pesanan WHERE status_pesanan IN ('selesai','batal')")->fetch_assoc();

// =====================================================
// BARU INCLUDE HEADER
// =====================================================
include 'header_admin.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h1>📜 Riwayat Pesanan</h1>
    <a href="pesanan.php" class="btn-xs btn-xs-info">🛒 Pesanan Aktif</a>
</div>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red">📜</div>
        <div class="stat-info">
            <h3><?= (int)$stats['total'] ?></h3>
            <p>Total Riwayat</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <h3><?= (int)$stats['selesai'] ?></h3>
            <p>Selesai</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">❌</div>
        <div class="stat-info">
            <h3><?= (int)$stats['batal'] ?></h3>
            <p>Dibatalkan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">💰</div>
        <div class="stat-info">
            <h3 style="font-size:1.1rem;"><?= rupiah((int)$stats['total_pendapatan']) ?></h3>
            <p>Total Pendapatan</p>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <div class="filter-bar">
            <a href="?filter=semua" class="btn-xs <?= $filter=='semua'?'btn-xs-primary':'btn-xs-ghost' ?>">Semua</a>
            <a href="?filter=selesai" class="btn-xs <?= $filter=='selesai'?'btn-xs-primary':'btn-xs-ghost' ?>">✅ Selesai</a>
            <a href="?filter=batal" class="btn-xs <?= $filter=='batal'?'btn-xs-primary':'btn-xs-ghost' ?>">❌ Batal</a>
        </div>
        <form method="GET" style="display:flex; gap:8px;">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <input type="text" name="q" placeholder="Cari kode/nama/HP..." value="<?= e($search) ?>"
                   style="padding:9px 14px; border:1.5px solid #EAE5E0; border-radius:999px; font-size:0.85rem; min-width:180px;">
            <button type="submit" class="btn-xs btn-xs-primary">🔍</button>
        </form>
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
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q = $conn->query("SELECT * FROM pesanan $where ORDER BY waktu DESC LIMIT 100");
                if ($q->num_rows === 0): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <h3>Belum ada riwayat</h3>
                            <p>Pesanan yang selesai/dibatalkan akan muncul di sini</p>
                        </div>
                    </td></tr>
                <?php else:
                while ($p = $q->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Kode">
                            <span class="kode"><?= e($p['kode']) ?></span>
                        </td>
                        <td data-label="Pelanggan">
                            <b><?= e($p['nama']) ?></b><br>
                            <small style="color:#888;"><?= e($p['hp']) ?></small>
                        </td>
                        <td data-label="Menu">
                            <div class="menu-cell">
                                <?= e($p['menu_nama']) ?>
                                <small>× <?= $p['jumlah'] ?></small>
                            </div>
                        </td>
                        <td data-label="Total">
                            <span class="total"><?= rupiah($p['total']) ?></span><br>
                            <small style="color:#888;"><?= e($p['status_bayar']) ?></small>
                        </td>
                        <td data-label="Tanggal">
                            <small><?= date('d/m/Y H:i', strtotime($p['waktu'])) ?></small>
                        </td>
                        <td data-label="Status">
                            <span class="badge badge-<?= $p['status_pesanan'] ?>">
                                <?= strtoupper($p['status_pesanan']) ?>
                            </span>
                        </td>
                        <td data-label="Aksi">
                            <a href="detail.php?id=<?= $p['id'] ?>" class="btn-xs btn-xs-info">Detail</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer_admin.php'; ?>