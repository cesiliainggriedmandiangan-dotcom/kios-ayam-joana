<?php
include 'header.php';

$menu_list = [];
$q = $conn->query("SELECT * FROM menu WHERE status='aktif' ORDER BY nama");
while ($r = $q->fetch_assoc()) $menu_list[] = $r;

$prefill_id = (int)($_GET['menu_id'] ?? 0);
?>

<div class="section-head">
    <div class="eyebrow">FORM ORDER</div>
    <h1>🛒 Form Pemesanan</h1>
    <p>Isi data di bawah untuk memesan ayam favoritmu</p>
</div>

<?php if (isset($_SESSION['sukses'])): ?>
    <div class="alert alert-success">
        <div>
            <?= $_SESSION['sukses'] ?><br><br>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php if (!empty($_SESSION['kode_pesanan'])): ?>
                    <a href="cek_pesanan.php?kode=<?= urlencode($_SESSION['kode_pesanan']) ?>" 
                       class="btn btn-primary btn-sm">🔍 Lihat Detail</a>
                <?php endif; ?>
                <a href="pesanan_saya.php" class="btn btn-accent btn-sm">📋 Pesanan Saya</a>
            </div>
        </div>
    </div>
    <?php 
    unset($_SESSION['sukses']); 
    unset($_SESSION['kode_pesanan']);
    ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">❌ <?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="form-card">
<form action="proses_order.php" method="POST">
    <div class="form-group">
        <label>Nama Lengkap <span class="req">*</span></label>
        <input type="text" name="nama" required placeholder="Masukkan nama Anda">
    </div>
    <div class="form-group">
        <label>No. HP / WhatsApp <span class="req">*</span></label>
        <input type="tel" name="hp" required pattern="[0-9]{10,15}" placeholder="08xxxxxxxxxx">
    </div>
    <div class="form-group">
        <label>Alamat Pengiriman <span class="req">*</span></label>
        <textarea name="alamat" rows="3" required placeholder="Jalan, No. Rumah, RT/RW, Kelurahan"></textarea>
    </div>
    <div class="form-group">
        <label>Pilih Menu <span class="req">*</span></label>
        <select name="menu_id" id="menu" required onchange="hitungTotal()">
            <option value="">-- Pilih Menu --</option>
            <?php foreach ($menu_list as $m): ?>
                <option value="<?= $m['id'] ?>" data-harga="<?= $m['harga'] ?>"
                    <?= $prefill_id==$m['id']?'selected':'' ?>>
                    <?= e($m['nama']) ?> — <?= rupiah($m['harga']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Jumlah Porsi <span class="req">*</span></label>
        <input type="number" name="jumlah" id="jumlah" min="1" value="1" required oninput="hitungTotal()">
    </div>

    <div class="form-group">
        <label>Pilihan Sambal <span class="req">*</span></label>
        <div class="pay-options">
            <label class="pay-opt">
                <input type="radio" name="jenis_sambal" value="Sambal Geprek" checked>
                <span>🌶️ Sambal Geprek (Pedas Khas)</span>
            </label>
            <label class="pay-opt">
                <input type="radio" name="jenis_sambal" value="Saus Sambal">
                <span>🥫 Saus Sambal (Manis Pedas)</span>
            </label>
        </div>
    </div>

    <div class="form-group">
        <label>Catatan Tambahan</label>
        <textarea name="catatan" rows="2" placeholder="Contoh: tanpa nasi, extra sambal, dll"></textarea>
    </div>

    <!-- ===== METODE PEMBAYARAN ===== -->
    <div class="form-group">
        <label>Metode Pembayaran <span class="req">*</span></label>
        <div class="pay-options">
            <label class="pay-opt">
                <input type="radio" name="metode_bayar" value="COD" checked>
                <span>💵 COD (Bayar di Tempat)</span>
            </label>
            <label class="pay-opt">
                <input type="radio" name="metode_bayar" value="Transfer">
                <span>🏦 Transfer Bank BRI</span>
            </label>
            <label class="pay-opt">
                <input type="radio" name="metode_bayar" value="QRIS">
                <span>📱 QRIS (Semua E-Wallet & M-Banking)</span>
            </label>
            <label class="pay-opt">
                <input type="radio" name="metode_bayar" value="Midtrans">
                <span>📲 DANA (0853-9879-0908)</span>
            </label>
        </div>
    </div>

    <div class="total-box">
        <span class="label">Total Bayar:</span>
        <span class="amount" id="total">Rp 0</span>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">✅ Kirim Pesanan</button>
</form>
</div>

<script>
function hitungTotal() {
    const menu = document.getElementById('menu');
    const jml  = parseInt(document.getElementById('jumlah').value) || 0;
    const hrg  = parseInt(menu.options[menu.selectedIndex]?.dataset.harga) || 0;
    document.getElementById('total').innerText = 'Rp ' + (hrg * jml).toLocaleString('id-ID');
}
hitungTotal();
</script>

<?php include 'footer.php'; ?>