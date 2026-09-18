<?php
include 'header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM pesanan WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo '<div class="alert alert-error">Pesanan tidak ditemukan.</div>';
    include 'footer.php'; exit;
}

// ============ KONFIGURASI PEMBAYARAN ============
// Data rekening Kios Ayam Crispy Sambal Geprek JOANA
$rekening = [
    [
        'bank' => 'BRI',
        'no'   => '517901028678532',
        'no_format' => '5179 0102 8678 532',
        'nama' => 'Kios Ayam Crispy Sambal Geprek JOANA'
    ],
];

// DANA
$dana = [
    'no' => '085398790908',
    'no_format' => '0853-9879-0908',
    'nama' => 'Kios Ayam Crispy Sambal Geprek JOANA'
];

// QRIS (file gambar di folder gambar/)
$qris_image = 'gambar/qris.png';
$qris_nmid  = 'ID1026585512837';

// WhatsApp admin untuk konfirmasi
$wa_admin = '6285398790908';
// ================================================
?>

<div class="section-head">
    <div class="eyebrow">PEMBAYARAN</div>
    <h1>💳 Selesaikan Pembayaran</h1>
</div>

<div class="alert alert-success">
    ✅ Pesanan <b><?= e($order['kode']) ?></b> berhasil dibuat. Silakan selesaikan pembayaran.
</div>

<div class="info-card">
    <h3>📋 Ringkasan Pesanan</h3>
    <p><b>Kode:</b> <span style="font-family:monospace; color:var(--primary); font-weight:700;"><?= e($order['kode']) ?></span></p>
    <p><b>Menu:</b> <?= e($order['menu_nama']) ?> × <?= $order['jumlah'] ?></p>
    <p><b>Pilihan Sambal:</b> <?= e($order['jenis_sambal'] ?? 'Sambal Geprek') ?></p>
    <p><b>Metode:</b> <?= e($order['metode_bayar']) ?></p>
    <hr style="border:none; border-top:1px solid var(--border); margin:12px 0;">
    <p style="font-size:1.2rem;"><b>Total Bayar:</b> 
        <span style="color:var(--primary); font-weight:800;">
            <?= rupiah($order['total']) ?>
        </span>
    </p>
</div>

<?php if ($order['metode_bayar'] === 'Transfer'): ?>
    <!-- ============================================ -->
    <!-- TRANSFER BANK BRI                            -->
    <!-- ============================================ -->
    <div class="info-card">
        <h3>🏦 Transfer Bank BRI</h3>
        <p>Silakan transfer ke rekening berikut:</p>
        
        <?php foreach ($rekening as $r): ?>
            <div style="background:#EBF3F9; padding:18px; border-radius:12px; margin:12px 0; border-left:4px solid #00529C;">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <div style="flex:1; min-width:200px;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                            <span style="background:#00529C; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:800; letter-spacing:0.05em;">
                                <?= e($r['bank']) ?>
                            </span>
                        </div>
                        <div style="font-family:monospace; font-size:1.3rem; color:#1D1D1F; font-weight:800; letter-spacing:1px; margin-bottom:4px;">
                            <?= e($r['no_format']) ?>
                        </div>
                        <small style="color:#666; font-size:0.85rem;">a/n <?= e($r['nama']) ?></small>
                    </div>
                    <button onclick="copyText('<?= e($r['no']) ?>', this)" 
                            class="btn-copy">
                        📋 Copy
                    </button>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="background:#FFF3CD; padding:16px; border-radius:12px; margin-top:16px; border:1.5px dashed #FFB703;">
            <p style="margin:0; font-size:0.85rem; color:#8A5A00;"><b>⚠️ Nominal Transfer:</b></p>
            <p style="margin:8px 0 0; font-size:1.5rem; color:var(--primary); font-weight:800;">
                <?= rupiah($order['total']) ?>
            </p>
            <p style="margin:8px 0 0; font-size:0.8rem; color:#8A5A00;">
                Transfer <b>PERSIS</b> sesuai nominal agar mudah diverifikasi
            </p>
        </div>
    </div>

    <div class="form-card">
        <h3 style="margin-bottom:16px;">📤 Upload Bukti Transfer</h3>
        <form action="proses_bayar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="upload">
            <div class="form-group">
                <label>Screenshot / Foto Bukti Transfer <span class="req">*</span></label>
                <input type="file" name="bukti" accept="image/*" required>
                <small style="color:#888; display:block; margin-top:6px;">
                    Format: JPG, PNG, WEBP (max 5 MB)
                </small>
            </div>
            <button class="btn btn-primary btn-block btn-lg">📤 Kirim Bukti Pembayaran</button>
        </form>
    </div>

    <div class="alert alert-info" style="margin-top:16px;">
        💡 <b>Tips:</b> Setelah upload bukti, admin akan verifikasi dalam <b>1x24 jam</b>. 
        Cek status di halaman <a href="pesanan_saya.php" style="color:var(--primary); font-weight:700;">📋 Pesanan Saya</a>.
    </div>

<?php elseif ($order['metode_bayar'] === 'QRIS'): ?>
    <!-- ============================================ -->
    <!-- QRIS                                          -->
    <!-- ============================================ -->
    <div class="info-card" style="text-align:center;">
        <h3>📱 Scan QRIS</h3>
        <p style="margin-bottom:16px; color:#666;">Scan QR di bawah pakai aplikasi e-wallet atau m-banking:</p>
        
        <div style="background:#fff; border:2px solid #EAE5E0; border-radius:16px; padding:16px; max-width:320px; margin:0 auto 16px;">
            <img src="<?= e($qris_image) ?>" 
                 alt="QRIS Kios Ayam Geprek JOA" 
                 style="display:block; width:100%; border-radius:8px;"
                 onerror="this.parentElement.innerHTML='<div style=&quot;padding:40px 20px;color:#999;&quot;>⚠️ QR belum diupload<br><small>Simpan sebagai gambar/qris.png</small></div>'">
        </div>

        <div style="font-size:0.8rem; color:#888; margin-bottom:16px;">
            NMID: <b><?= e($qris_nmid) ?></b><br>
            Kios Ayam Crispy Sambal Geprek Joana
        </div>

        <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
            <span style="background:#EBF3F9; color:#1E4E6E; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;">GoPay</span>
            <span style="background:#E8F6F1; color:#1B6E5E; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;">OVO</span>
            <span style="background:#FFF6E5; color:#8A5A00; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;">DANA</span>
            <span style="background:#FFE0E3; color:#A11426; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;">ShopeePay</span>
            <span style="background:#EAE5E0; color:#333; padding:5px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;">M-Banking</span>
        </div>

        <div style="background:#FFF3CD; padding:16px; border-radius:12px; border:1.5px dashed #FFB703;">
            <p style="margin:0; font-size:0.85rem; color:#8A5A00;"><b>💰 Nominal Bayar:</b></p>
            <p style="margin:8px 0 0; font-size:1.5rem; color:var(--primary); font-weight:800;">
                <?= rupiah($order['total']) ?>
            </p>
            <p style="margin:8px 0 0; font-size:0.8rem; color:#8A5A00;">
                Masukkan <b>PERSIS</b> sesuai nominal
            </p>
        </div>

        <p style="margin-top:16px; font-size:0.85rem; color:#666;">
            Setelah bayar, <b>screenshot</b> struk sukses dan upload di bawah 👇
        </p>
    </div>

    <div class="form-card">
        <h3 style="margin-bottom:16px;">📤 Upload Bukti Pembayaran</h3>
        <form action="proses_bayar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="upload">
            <div class="form-group">
                <label>Screenshot Bukti Pembayaran <span class="req">*</span></label>
                <input type="file" name="bukti" accept="image/*" required>
                <small style="color:#888; display:block; margin-top:6px;">
                    Screenshot dari e-wallet / m-banking yang menunjukkan pembayaran sukses
                </small>
            </div>
            <button class="btn btn-primary btn-block btn-lg">📤 Konfirmasi Pembayaran</button>
        </form>
    </div>

    <div class="alert alert-info" style="margin-top:16px;">
        💡 <b>Tips:</b> Pastikan nominal yang dibayar <b>PERSIS</b> sama dengan total pesanan.
    </div>

<?php elseif ($order['metode_bayar'] === 'Midtrans'): ?>
    <!-- ============================================ -->
    <!-- DANA / E-WALLET                              -->
    <!-- ============================================ -->
    <div class="info-card">
        <h3>📱 Pembayaran DANA</h3>
        <p>Silakan transfer DANA ke nomor berikut:</p>

        <div style="background:#EBF3F9; padding:18px; border-radius:12px; margin:12px 0; border-left:4px solid #118EEA;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="flex:1; min-width:200px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                        <span style="background:#118EEA; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:800; letter-spacing:0.05em;">
                            📱 DANA
                        </span>
                    </div>
                    <div style="font-family:monospace; font-size:1.3rem; color:#1D1D1F; font-weight:800; letter-spacing:1px; margin-bottom:4px;">
                        <?= e($dana['no_format']) ?>
                    </div>
                    <small style="color:#666; font-size:0.85rem;">a/n <?= e($dana['nama']) ?></small>
                </div>
                <button onclick="copyText('<?= e($dana['no']) ?>', this)" class="btn-copy">
                    📋 Copy
                </button>
            </div>
        </div>

        <div style="background:#FFF3CD; padding:16px; border-radius:12px; margin-top:16px; border:1.5px dashed #FFB703;">
            <p style="margin:0; font-size:0.85rem; color:#8A5A00;"><b>⚠️ Nominal Transfer:</b></p>
            <p style="margin:8px 0 0; font-size:1.5rem; color:var(--primary); font-weight:800;">
                <?= rupiah($order['total']) ?>
            </p>
        </div>
    </div>

    <div class="form-card">
        <h3 style="margin-bottom:16px;">📤 Upload Bukti Transfer</h3>
        <form action="proses_bayar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="upload">
            <div class="form-group">
                <label>Screenshot Bukti Transfer DANA <span class="req">*</span></label>
                <input type="file" name="bukti" accept="image/*" required>
            </div>
            <button class="btn btn-primary btn-block btn-lg">📤 Kirim Bukti Pembayaran</button>
        </form>
    </div>

<?php elseif ($order['metode_bayar'] === 'COD'): ?>
    <!-- ============================================ -->
    <!-- COD                                          -->
    <!-- ============================================ -->
    <div class="info-card" style="text-align:center; padding:40px 20px;">
        <div style="font-size:4rem; margin-bottom:12px;">💵</div>
        <h3>Bayar di Tempat (COD)</h3>
        <p>Siapkan uang tunai sebesar:</p>
        <p style="font-size:1.6rem; color:var(--primary); font-weight:800; margin:16px 0;">
            <?= rupiah($order['total']) ?>
        </p>
        <p style="font-size:0.9rem; color:#666;">Kurir akan menghubungi kamu sebelum sampai.</p>
        <a href="pesanan_saya.php" class="btn btn-primary" style="margin-top:16px;">📋 Lihat Pesanan Saya</a>
    </div>
<?php endif; ?>

<!-- Kontak Admin -->
<div class="info-card" style="margin-top:24px;">
    <h3>💬 Butuh Bantuan?</h3>
    <p style="margin-bottom:12px;">Hubungi admin kalau ada kendala pembayaran:</p>
    <a href="https://wa.me/<?= e($wa_admin) ?>?text=Halo%20Admin%2C%20saya%20sudah%20bayar%20pesanan%20<?= urlencode($order['kode']) ?>" 
       target="_blank" 
       class="btn btn-primary btn-block"
       style="background:#25D366; box-shadow:0 6px 16px rgba(37,211,102,0.3);">
        💬 Chat Admin via WhatsApp
    </a>
</div>

<style>
.btn-copy {
    padding: 10px 18px;
    border: none;
    background: var(--primary);
    color: #fff;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.85rem;
    font-family: inherit;
    transition: 0.2s;
    white-space: nowrap;
}
.btn-copy:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}
</style>

<script>
function copyText(text, btn) {
    // Fallback untuk browser lama
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            showCopied(btn);
        }).catch(function() {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        showCopied(btn);
    } catch (err) {
        alert('Gagal copy. Nomor: ' + text);
    }
    document.body.removeChild(textarea);
}

function showCopied(btn) {
    const original = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    btn.style.background = '#2A9D8F';
    setTimeout(function() {
        btn.innerHTML = original;
        btn.style.background = '';
    }, 2000);
}
</script>

<?php include 'footer.php'; ?>