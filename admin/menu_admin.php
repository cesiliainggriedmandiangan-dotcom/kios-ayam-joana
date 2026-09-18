<?php
// =====================================================
// BLOK PROSES — HARUS DI ATAS include 'header_admin.php'
// =====================================================
require_once __DIR__ . '/../config.php';

// Proteksi halaman admin
if (!isset($_SESSION['admin'])) {
    redirect('login.php');
}

// ===== AKSI TAMBAH / EDIT MENU =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)($_POST['id'] ?? 0);
    $nama      = trim($_POST['nama'] ?? '');
    $harga     = (int)($_POST['harga'] ?? 0);
    $gambar    = trim($_POST['gambar'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status    = $_POST['status'] ?? 'aktif';

    if ($id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE menu SET nama=?, harga=?, gambar=?, deskripsi=?, status=? WHERE id=?");
        $stmt->bind_param('sisssi', $nama, $harga, $gambar, $deskripsi, $status, $id);
        $stmt->execute();
        $_SESSION['flash'] = '✅ Menu berhasil diperbarui!';
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO menu (nama, harga, gambar, deskripsi, status) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sisss', $nama, $harga, $gambar, $deskripsi, $status);
        $stmt->execute();
        $_SESSION['flash'] = '✅ Menu berhasil ditambahkan!';
    }
    redirect('menu_admin.php');
}

// ===== AKSI HAPUS MENU =====
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM menu WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $_SESSION['flash'] = '🗑️ Menu berhasil dihapus.';
    redirect('menu_admin.php');   // ← Sekarang AMAN, karena di atas include header
}

// ===== AMBIL DATA MENU =====
$q = $conn->query("SELECT * FROM menu ORDER BY id DESC");
$menus = $q->fetch_all(MYSQLI_ASSOC);

// =====================================================
// BARU INCLUDE HEADER — SETELAH SEMUA PROSES SELESAI
// =====================================================
include 'header_admin.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
    <h1>🍗 Kelola Menu</h1>
    <button class="btn-xs btn-xs-primary" onclick="openForm()">+ Tambah Menu</button>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;"><?= $_SESSION['flash'] ?></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="panel">
    <div class="panel-body no-pad">
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($menus)): ?>
                    <tr><td colspan="5">
                        <div class="empty-state">
                            <div class="icon">🍗</div>
                            <h3>Belum ada menu</h3>
                            <p>Tambahkan menu pertama kamu</p>
                        </div>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($menus as $m): ?>
                        <tr>
                            <td data-label="Gambar">
                                <img src="<?= e($m['gambar']) ?>" 
                                     alt="<?= e($m['nama']) ?>"
                                     style="width:60px; height:60px; object-fit:cover; border-radius:8px;">
                            </td>
                            <td data-label="Nama">
                                <b><?= e($m['nama']) ?></b><br>
                                <small style="color:#888;"><?= e($m['deskripsi']) ?></small>
                            </td>
                            <td data-label="Harga"><span class="total"><?= rupiah($m['harga']) ?></span></td>
                            <td data-label="Status">
                                <span class="badge badge-<?= $m['status']=='aktif'?'lunas':'batal' ?>">
                                    <?= strtoupper($m['status']) ?>
                                </span>
                            </td>
                            <td data-label="Aksi">
                                <div class="btn-row">
                                    <button class="btn-xs btn-xs-warning" 
                                        onclick='editMenu(<?= json_encode($m, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
                                    <a href="?hapus=<?= $m['id'] ?>" 
                                       class="btn-xs btn-xs-danger"
                                       onclick="return confirm('Hapus menu ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal-overlay" id="modalForm" style="position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:9999; padding:20px;">
    <div style="background:#fff; border-radius:16px; max-width:500px; width:100%; max-height:90vh; overflow-y:auto;">
        <div style="padding:20px 24px; border-bottom:1px solid #EAE5E0; display:flex; justify-content:space-between; align-items:center;">
            <h3 id="modalTitle" style="font-size:1.1rem;">Tambah Menu</h3>
            <button onclick="closeForm()" 
                    style="width:32px; height:32px; border-radius:8px; background:#F5F1ED; border:none; font-size:1.2rem; cursor:pointer;">×</button>
        </div>
        <form method="POST" class="admin-form" style="padding:24px;">
            <input type="hidden" name="id" id="f-id">
            <div class="form-group">
                <label>Nama Menu <span class="req">*</span></label>
                <input type="text" name="nama" id="f-nama" required>
            </div>
            <div class="form-group">
                <label>Harga <span class="req">*</span></label>
                <input type="number" name="harga" id="f-harga" required min="0">
            </div>
            <div class="form-group">
                <label>URL Gambar</label>
                <input type="text" name="gambar" id="f-gambar" placeholder="https://...">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="deskripsi" id="f-deskripsi">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="f-status">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <button class="btn btn-primary btn-block btn-lg">💾 Simpan</button>
        </form>
    </div>
</div>

<script>
function openForm() {
    document.getElementById('modalTitle').innerText = 'Tambah Menu';
    document.getElementById('f-id').value = '';
    document.getElementById('f-nama').value = '';
    document.getElementById('f-harga').value = '';
    document.getElementById('f-gambar').value = '';
    document.getElementById('f-deskripsi').value = '';
    document.getElementById('f-status').value = 'aktif';
    document.getElementById('modalForm').style.display = 'flex';
}

function editMenu(m) {
    document.getElementById('modalTitle').innerText = 'Edit Menu';
    document.getElementById('f-id').value = m.id;
    document.getElementById('f-nama').value = m.nama;
    document.getElementById('f-harga').value = m.harga;
    document.getElementById('f-gambar').value = m.gambar || '';
    document.getElementById('f-deskripsi').value = m.deskripsi || '';
    document.getElementById('f-status').value = m.status;
    document.getElementById('modalForm').style.display = 'flex';
}

function closeForm() {
    document.getElementById('modalForm').style.display = 'none';
}
</script>

<?php include 'footer_admin.php'; ?>