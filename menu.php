<?php include 'header.php'; ?>

<div class="section-head">
    <div class="eyebrow">DAFTAR MENU</div>
    <h1>📋 Menu Kami</h1>
    <p>Pilih menu favoritmu, lalu pesan online</p>
</div>

<div class="menu-grid">
<?php
$q = $conn->query("SELECT * FROM menu WHERE status='aktif' ORDER BY nama");
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

<?php include 'footer.php'; ?>