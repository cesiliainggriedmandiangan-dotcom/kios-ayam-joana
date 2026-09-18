<?php
// =====================================================
// BLOK PROSES — HARUS DI ATAS include 'header_admin.php'
// =====================================================
require_once __DIR__ . '/../config.php';

// Proteksi halaman admin
if (!isset($_SESSION['admin'])) {
    redirect('login.php');
}

// ===== PROSES UPDATE STATUS VIA GET =====
if (isset($_GET['set_status'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $s  = $_GET['set_status'];
    $allowed = ['baru', 'diproses', 'dikirim', 'selesai', 'batal'];

    if (in_array($s, $allowed)) {
        $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan=? WHERE id=?");
        $stmt->bind_param('si', $s, $id);
        $stmt->execute();
        $_SESSION['flash'] = '✅ Status pesanan berhasil diperbarui.';
    }
    redirect('pesanan.php');
}

// ===== FILTER =====
$filter = $_GET['filter'] ?? 'aktif';
$where = match($filter) {
    'baru'     => "WHERE status_pesanan='baru'",
    'diproses' => "WHERE status_pesanan='diproses'",
    'dikirim'  => "WHERE status_pesanan='dikirim'",
    default    => "WHERE status_pesanan IN ('baru','diproses','dikirim')",
};

// ===== SEARCH =====
$search = trim($_GET['q'] ?? '');
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (kode LIKE '%$s%' OR nama LIKE '%$s%' OR hp LIKE '%$s%')";
}

// ===== QUERY DATA =====
$q = $conn->query("SELECT * FROM pesanan $where ORDER BY waktu DESC");

// =====================================================
// BARU INCLUDE HEADER
// =====================================================
include 'header_admin.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h1>🛒 Pesanan Aktif</h1>
    <a href="riwayat.php" class="btn-xs btn-xs-info">📜 Lihat Riwayat</a>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= $_SESSION['flash'] ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-head">
        <div class="filter-bar">
            <a href="?filter=aktif" class="btn-xs <?= $filter=='aktif'?'btn-xs-primary':'btn-xs-ghost' ?>">Semua Aktif</a>
            <a href="?filter=baru" class="btn-xs <?= $filter=='baru'?'btn-xs-primary':'btn-xs-ghost' ?>">🆕 Baru</a>
            <a href="?filter=diproses" class="btn-xs <?= $filter=='diproses'?'btn-xs-primary':'btn-xs-ghost' ?>">⚙️ Diproses</a>
            <a href="?filter=dikirim" class="btn-xs <?= $filter=='dikirim'?'btn-xs-primary':'btn-xs-ghost' ?>">🚚 Dikirim</a>
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
                        <th>Bayar</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($q->num_rows === 0): ?>
                    <tr><td colspan="7">
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <h3>Belum ada pesanan</h3>
                            <p>Pesanan baru akan muncul di sini</p>
                        </div>
                    </td></tr>
                <?php else:
                while ($p = $q->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Kode">
                            <span class="kode"><?= e($p['kode']) ?></span><br>
                            <small style="color:#888;"><?= date('d/m H:i', strtotime($p['waktu'])) ?></small>
                        </td>
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
                            <br><small style="color:#888;"><?= e($p['metode_bayar']) ?></small>
                        </td>
                        <td data-label="Status">
                            <span class="badge badge-<?= $p['status_pesanan'] ?>"><?= strtoupper($p['status_pesanan']) ?></span>
                        </td>
                        <td data-label="Aksi">
                            <div class="btn-row">
                                <a href="detail.php?id=<?= $p['id'] ?>" class="btn-xs btn-xs-info">Detail</a>
                                
                                <?php if ($p['status_pesanan'] === 'baru'): ?>
                                    <a href="?set_status=diproses&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-warning"
                                       onclick="return confirm('Proses pesanan <?= e($p['kode']) ?>?')">Proses</a>
                                    <a href="?set_status=batal&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-danger"
                                       onclick="return confirm('Batalkan pesanan <?= e($p['kode']) ?>?')">Batal</a>
                                
                                <?php elseif ($p['status_pesanan'] === 'diproses'): ?>
                                    <a href="?set_status=dikirim&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-primary"
                                       onclick="return confirm('Kirim pesanan <?= e($p['kode']) ?>?')">Kirim</a>
                                    <a href="?set_status=batal&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-danger"
                                       onclick="return confirm('Batalkan pesanan?')">Batal</a>
                                
                                <?php elseif ($p['status_pesanan'] === 'dikirim'): ?>
                                    <a href="?set_status=selesai&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-success"
                                       onclick="return confirm('Tandai pesanan ini selesai?')">Selesai</a>
                                    <a href="?set_status=batal&id=<?= $p['id'] ?>&filter=<?= e($filter) ?>" 
                                       class="btn-xs btn-xs-danger"
                                       onclick="return confirm('Batalkan pesanan?')">Batal</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer_admin.php'; ?>