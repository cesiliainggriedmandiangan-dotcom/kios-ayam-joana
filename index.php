<?php include 'header.php'; ?>

<?php
// Cek apakah ada pesanan tersimpan di cookie
$kode_cookie = $_COOKIE['kios_pesanan'] ?? '';
$kode_list = array_filter(explode(',', $kode_cookie));

if (!empty($kode_list)) {
    $kode_terakhir = $kode_list[0];
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode=? LIMIT 1");
    $stmt->bind_param('s', $kode_terakhir);
    $stmt->execute();
    $last_order = $stmt->get_result()->fetch_assoc();
    
    if ($last_order): ?>
        <div class="alert alert-info" style="margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <b>📋 Pesanan Terakhir:</b> 
                <span style="font-family:monospace;"><?= e($last_order['kode']) ?></span>
                <span class="badge badge-<?= $last_order['status_pesanan'] ?>" style="margin-left:8px;">
                    <?= strtoupper($last_order['status_pesanan']) ?>
                </span>
            </div>
            <a href="pesanan_saya.php" class="btn btn-primary btn-sm">Lihat Semua →</a>
        </div>
    <?php endif;
}
?>

<div class="hero">
    <div class="hero-content">
        <div class="hero-badge">🔥 BEST SELLER SEJAK 2025</div>
        <h1>Ayam Crispy Sambal Geprek Paling <span class="highlight">Pedas</span> & Lezat! 🔥</h1>
        <p>Pesan online, siap antar ke rumahmu.</p>
        <div class="hero-actions">
            <a href="pemesanan.php" class="btn btn-accent btn-lg">🛒 Pesan Sekarang</a>
            <a href="menu.php" class="btn btn-outline btn-lg">👀 Lihat Menu</a>
        </div>
    </div>
</div>

<div class="section-head">
    <div class="eyebrow">FAVORIT</div>
    <h2>🔥 Menu Favorit Kami</h2>
    <p>Pilihan terbaik dari dapur kami untuk kamu</p>
</div>

<div class="menu-grid" style="margin-bottom:32px;">
<?php
$q = $conn->query("SELECT * FROM menu WHERE status='aktif' ORDER BY id LIMIT 3");
while ($m = $q->fetch_assoc()): ?>
    <div class="menu-card">
        <div class="menu-card-img">
            <img src="<?= e($m['gambar']) ?>" alt="<?= e($m['nama']) ?>">
        </div>
        <div class="menu-card-body">
            <h3><?= e($m['nama']) ?></h3>
            <p class="desc"><?= e($m['deskripsi']) ?></p>
            <div class="menu-card-foot">
                <div class="price"><?= rupiah($m['harga']) ?><small>per porsi</small></div>
                <a href="pemesanan.php?menu_id=<?= $m['id'] ?>" class="btn-order">+</a>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<a href="menu.php" class="btn btn-dark btn-block">Lihat Semua Menu →</a>

<?php include 'footer.php'; ?>