<?php
include 'header.php';

// Ambil testimoni yang approved
$testimoni = [];
$q = $conn->query("SELECT * FROM testimoni WHERE status='approved' ORDER BY waktu DESC LIMIT 30");
while ($r = $q->fetch_assoc()) $testimoni[] = $r;

// Statistik rating
$stats = $conn->query("SELECT 
    COUNT(*) AS total,
    COALESCE(AVG(rating), 0) AS rata_rata,
    SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS bintang5,
    SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS bintang4,
    SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS bintang3,
    SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS bintang2,
    SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS bintang1
    FROM testimoni WHERE status='approved'")->fetch_assoc();

$total = (int)$stats['total'];
$rata = round((float)$stats['rata_rata'], 1);

// Cek apakah ada pesanan dari cookie (untuk auto-fill form)
$kode_cookie = $_COOKIE['kios_pesanan'] ?? '';
$kode_list = array_filter(explode(',', $kode_cookie));
$last_order = null;

if (!empty($kode_list)) {
    $kode_terakhir = $kode_list[0];
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode=? AND status_pesanan='selesai' LIMIT 1");
    $stmt->bind_param('s', $kode_terakhir);
    $stmt->execute();
    $last_order = $stmt->get_result()->fetch_assoc();
}

// Pesan flash
if (isset($_SESSION['sukses_testimoni'])) {
    echo '<div class="alert alert-success">' . $_SESSION['sukses_testimoni'] . '</div>';
    unset($_SESSION['sukses_testimoni']);
}
if (isset($_SESSION['error_testimoni'])) {
    echo '<div class="alert alert-error">' . $_SESSION['error_testimoni'] . '</div>';
    unset($_SESSION['error_testimoni']);
}
?>

<div class="section-head">
    <div class="eyebrow">TESTIMONI</div>
    <h1>💬 Apa Kata Mereka?</h1>
    <p>Cerita dari pelanggan setia Kios Ayam</p>
</div>

<!-- ===== STATISTIK RATING ===== -->
<div class="info-card" style="text-align:center; padding:28px 20px;">
    <div style="display:flex; justify-content:center; align-items:center; gap:32px; flex-wrap:wrap;">
        <div>
            <div style="font-size:3rem; font-weight:800; color:var(--primary); line-height:1;">
                <?= $rata ?>
            </div>
            <div style="color:var(--gold); font-size:1.4rem; margin:8px 0; letter-spacing:2px;">
                <?php
                $full = floor($rata);
                $half = ($rata - $full) >= 0.5;
                for ($i = 0; $i < 5; $i++) {
                    if ($i < $full) echo '⭐';
                    elseif ($i == $full && $half) echo '⭐';
                    else echo '☆';
                }
                ?>
            </div>
            <div style="color:#888; font-size:0.85rem;">
                <?= $total ?> testimoni
            </div>
        </div>

        <div style="flex:1; max-width:300px; min-width:200px;">
            <?php for ($i = 5; $i >= 1; $i--):
                $jml = (int)$stats['bintang'.$i];
                $persen = $total > 0 ? ($jml / $total * 100) : 0;
            ?>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px; font-size:0.85rem;">
                    <span style="color:var(--gold); width:50px; text-align:right;">
                        <?= $i ?> ⭐
                    </span>
                    <div style="flex:1; height:8px; background:#EAE5E0; border-radius:999px; overflow:hidden;">
                        <div style="width:<?= $persen ?>%; height:100%; background:var(--gold); border-radius:999px;"></div>
                    </div>
                    <span style="color:#888; width:35px; text-align:right; font-size:0.8rem;">
                        <?= $jml ?>
                    </span>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- ===== FORM TULIS TESTIMONI ===== -->
<div class="form-card" id="formTestimoni" style="margin-top:24px; margin-bottom:32px;">
    <h3 style="margin-bottom:6px;">✍️ Tulis Testimoni Kamu</h3>
    <p style="color:#888; margin-bottom:20px; font-size:0.9rem;">
        Bagikan pengalamanmu memesan di Kios Ayam!
    </p>

    <form action="proses_testimoni.php" method="POST" id="formTambahTesti">
        <div class="form-group">
            <label>Nama Kamu <span class="req">*</span></label>
            <input type="text" name="nama" required 
                   placeholder="Contoh: Budi Santoso"
                   value="<?= e($last_order['nama'] ?? '') ?>"
                   maxlength="100">
        </div>

        <div class="form-group">
            <label>No. HP (opsional)</label>
            <input type="tel" name="hp" 
                   placeholder="08xxxxxxxxxx"
                   value="<?= e($last_order['hp'] ?? '') ?>"
                   pattern="[0-9]{10,15}">
            <small style="color:#888; display:block; margin-top:4px; font-size:0.8rem;">
                💡 No. HP tidak akan ditampilkan ke publik
            </small>
        </div>

        <div class="form-group">
            <label>Kode Pesanan (opsional)</label>
            <input type="text" name="kode_pesanan" 
                   placeholder="Contoh: AYM-260916-AB123"
                   value="<?= e($last_order['kode'] ?? '') ?>"
                   style="text-transform: uppercase;">
            <small style="color:#888; display:block; margin-top:4px; font-size:0.8rem;">
                💡 Isi kalau kamu sudah pernah pesan
            </small>
        </div>

        <div class="form-group">
            <label>Rating <span class="req">*</span></label>
            <div id="ratingStars" style="display:flex; gap:8px; font-size:2rem; cursor:pointer; user-select:none;">
                <span class="star" data-value="1" style="color:#DDD; transition:.2s;">⭐</span>
                <span class="star" data-value="2" style="color:#DDD; transition:.2s;">⭐</span>
                <span class="star" data-value="3" style="color:#DDD; transition:.2s;">⭐</span>
                <span class="star" data-value="4" style="color:#DDD; transition:.2s;">⭐</span>
                <span class="star" data-value="5" style="color:#DDD; transition:.2s;">⭐</span>
            </div>
            <input type="hidden" name="rating" id="ratingValue" value="5" required>
            <small style="color:#888; display:block; margin-top:6px;">
                Pilih 1-5 bintang
            </small>
        </div>

        <div class="form-group">
            <label>Testimoni Kamu <span class="req">*</span></label>
            <textarea name="pesan" rows="4" required 
                      placeholder="Ceritakan pengalamanmu memesan di sini..."
                      maxlength="500"></textarea>
            <small style="color:#888; display:block; margin-top:4px; font-size:0.8rem;">
                Max 500 karakter
            </small>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">📤 Kirim Testimoni</button>
    </form>
</div>

<!-- ===== DAFTAR TESTIMONI ===== -->
<?php if (empty($testimoni)): ?>
    <div class="info-card" style="text-align:center; padding:60px 20px;">
        <div style="font-size:4rem; margin-bottom:12px; opacity:0.4;">💬</div>
        <h3 style="color:#6B6B70;">Belum Ada Testimoni</h3>
        <p>Jadilah yang pertama memberi testimoni!</p>
    </div>
<?php else: ?>
    <h3 style="margin-bottom:20px;">📋 Testimoni Terbaru</h3>
    <div class="testi-grid">
        <?php foreach ($testimoni as $t): ?>
            <div class="testi-card">
                <div class="testi-quote">"</div>
                <div class="testi-stars">
                    <?= str_repeat('⭐', $t['rating']) ?>
                    <?= str_repeat('☆', 5 - $t['rating']) ?>
                </div>
                <p class="testi-text"><?= e($t['pesan']) ?></p>
                <div class="testi-author">
                    <div class="testi-avatar"><?= strtoupper(substr($t['nama'], 0, 1)) ?></div>
                    <div>
                        <div class="testi-name"><?= e($t['nama']) ?></div>
                        <div class="testi-role">
                            <?php if ($t['menu_nama']): ?>
                                Pembeli <?= e($t['menu_nama']) ?>
                            <?php else: ?>
                                Pelanggan Setia
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($t['waktu']): ?>
                    <div style="font-size:0.75rem; color:#AAA; margin-top:10px;">
                        <?= date('d M Y', strtotime($t['waktu'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
#ratingStars .star:hover,
#ratingStars .star.active {
    color: var(--gold) !important;
    transform: scale(1.15);
}
</style>

<script>
// Rating bintang interaktif
const stars = document.querySelectorAll('#ratingStars .star');
const ratingInput = document.getElementById('ratingValue');

function updateStars(value) {
    stars.forEach(s => {
        const v = parseInt(s.dataset.value);
        if (v <= value) {
            s.style.color = 'var(--gold)';
            s.classList.add('active');
        } else {
            s.style.color = '#DDD';
            s.classList.remove('active');
        }
    });
}

stars.forEach(star => {
    star.addEventListener('click', function() {
        const value = parseInt(this.dataset.value);
        ratingInput.value = value;
        updateStars(value);
    });
});

// Default 5 bintang
updateStars(5);

// Auto-scroll ke form kalau dari tombol "Tulis Testimoni"
if (window.location.hash === '#formTestimoni') {
    document.getElementById('formTestimoni').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'footer.php'; ?>