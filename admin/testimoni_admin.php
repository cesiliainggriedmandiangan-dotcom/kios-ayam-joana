<?php
include 'header_admin.php';

// ===== AKSI =====
if (isset($_GET['aksi'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $aksi = $_GET['aksi'];

    if ($aksi === 'approve') {
        $conn->query("UPDATE testimoni SET status='approved' WHERE id=$id");
        $_SESSION['flash'] = '✅ Testimoni dipublikasikan.';
    } elseif ($aksi === 'hide') {
        $conn->query("UPDATE testimoni SET status='hidden' WHERE id=$id");
        $_SESSION['flash'] = '🙈 Testimoni disembunyikan.';
    } elseif ($aksi === 'hapus') {
        $conn->query("DELETE FROM testimoni WHERE id=$id");
        $_SESSION['flash'] = '🗑️ Testimoni dihapus.';
    }
    redirect('testimoni_admin.php');
}

// ===== FILTER =====
$filter = $_GET['filter'] ?? 'semua';
$where = '';
if ($filter === 'approved') $where = "WHERE status='approved'";
elseif ($filter === 'hidden') $where = "WHERE status='hidden'";
elseif ($filter === 'pending') $where = "WHERE status='pending'";

// ===== SEARCH =====
$search = trim($_GET['q'] ?? '');
if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= $where ? " AND (nama LIKE '%$s%' OR pesan LIKE '%$s%')" : "WHERE (nama LIKE '%$s%' OR pesan LIKE '%$s%')";
}

// ===== STATISTIK =====
$stats = $conn->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status='hidden' THEN 1 ELSE 0 END) AS hidden,
    COALESCE(AVG(CASE WHEN status='approved' THEN rating END), 0) AS avg_rating
    FROM testimoni")->fetch_assoc();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h1>💬 Kelola Testimoni</h1>
    <a href="../testimoni.php" target="_blank" class="btn-xs btn-xs-info">👁️ Lihat Publik</a>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= $_SESSION['flash'] ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Statistik -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon red">💬</div>
        <div class="stat-info">
            <h3><?= (int)$stats['total'] ?></h3>
            <p>Total Testimoni</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
            <h3><?= (int)$stats['approved'] ?></h3>
            <p>Ditampilkan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">🙈</div>
        <div class="stat-info">
            <h3><?= (int)$stats['hidden'] ?></h3>
            <p>Disembunyikan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon gold">⭐</div>
        <div class="stat-info">
            <h3><?= round((float)$stats['avg_rating'], 1) ?></h3>
            <p>Rata-rata Rating</p>
        </div>
    </div>
</div>

<!-- Panel -->
<div class="panel">
    <div class="panel-head">
        <div class="filter-bar">
            <a href="?filter=semua" class="btn-xs <?= $filter=='semua'?'btn-xs-primary':'btn-xs-ghost' ?>">Semua</a>
            <a href="?filter=approved" class="btn-xs <?= $filter=='approved'?'btn-xs-primary':'btn-xs-ghost' ?>">✅ Ditampilkan</a>
            <a href="?filter=hidden" class="btn-xs <?= $filter=='hidden'?'btn-xs-primary':'btn-xs-ghost' ?>">🙈 Disembunyikan</a>
        </div>
        <form method="GET" style="display:flex; gap:8px;">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <input type="text" name="q" placeholder="Cari nama / isi..." value="<?= e($search) ?>"
                   style="padding:9px 14px; border:1.5px solid #EAE5E0; border-radius:999px; font-size:0.85rem; min-width:180px;">
            <button type="submit" class="btn-xs btn-xs-primary">🔍</button>
        </form>
    </div>
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Rating</th>
                        <th>Testimoni</th>
                        <th>Menu</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $q = $conn->query("SELECT * FROM testimoni $where ORDER BY waktu DESC LIMIT 100");
                if ($q->num_rows === 0): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="icon">💬</div>
                            <h3>Belum ada testimoni</h3>
                        </div>
                    </td></tr>
                <?php else:
                while ($t = $q->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Nama">
                            <b><?= e($t['nama']) ?></b><br>
                            <small style="color:#888;"><?= date('d/m/Y H:i', strtotime($t['waktu'])) ?></small>
                        </td>
                        <td data-label="Rating">
                            <span style="color:var(--gold); font-size:1.1rem;">
                                <?= str_repeat('⭐', $t['rating']) ?>
                            </span><br>
                            <small style="color:#888;"><?= $t['rating'] ?>/5</small>
                        </td>
                        <td data-label="Testimoni" style="max-width:300px;">
                            <div style="font-size:0.85rem; line-height:1.5;">
                                <?= e(mb_strimwidth($t['pesan'], 0, 120, '...')) ?>
                            </div>
                        </td>
                        <td data-label="Menu">
                            <?php if ($t['menu_nama']): ?>
                                <small><?= e($t['menu_nama']) ?></small>
                            <?php else: ?>
                                <small style="color:#888;">-</small>
                            <?php endif; ?>
                            <?php if ($t['kode_pesanan']): ?>
                                <br><small style="font-family:monospace; color:#888; font-size:0.7rem;">
                                    <?= e($t['kode_pesanan']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status">
                            <span class="badge badge-<?= $t['status']=='approved'?'lunas':'batal' ?>">
                                <?= strtoupper($t['status']) ?>
                            </span>
                        </td>
                        <td data-label="Aksi">
                            <div class="btn-row">
                                <?php if ($t['status'] !== 'approved'): ?>
                                    <a href="?aksi=approve&id=<?= $t['id'] ?>" class="btn-xs btn-xs-success">✅ Tampil</a>
                                <?php endif; ?>
                                <?php if ($t['status'] !== 'hidden'): ?>
                                    <a href="?aksi=hide&id=<?= $t['id'] ?>" class="btn-xs btn-xs-warning">🙈 Sembunyikan</a>
                                <?php endif; ?>
                                <a href="?aksi=hapus&id=<?= $t['id'] ?>" 
                                   class="btn-xs btn-xs-danger"
                                   onclick="return confirm('Hapus testimoni dari <?= e($t['nama']) ?>?')">
                                   🗑️
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

<?php include 'footer_admin.php'; ?>