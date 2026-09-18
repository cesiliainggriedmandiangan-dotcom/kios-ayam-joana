<?php
require_once __DIR__ . '/config.php';
$current = basename($_SERVER['PHP_SELF']);

// ===== CEK UPDATE PESANAN UNTUK TOAST CUSTOMER =====
$toast_update = null;
$kode_cookie = $_COOKIE['kios_pesanan'] ?? '';
$kode_list = array_filter(explode(',', $kode_cookie));

if (!empty($kode_list)) {
    $kode_terakhir = $kode_list[0];
    
    $stmt = $conn->prepare("SELECT * FROM pesanan WHERE kode=? LIMIT 1");
    $stmt->bind_param('s', $kode_terakhir);
    $stmt->execute();
    $last_order = $stmt->get_result()->fetch_assoc();
    
    if ($last_order) {
        $last_seen = $_COOKIE['kios_last_seen_' . $last_order['kode']] ?? '';
        $current_status = $last_order['status_pesanan'];
        
        if ($last_seen && $last_seen !== $current_status) {
            $status_labels = [
                'diproses' => ['⚙️', 'Pesananmu sedang diproses!'],
                'dikirim'  => ['🚚', 'Pesananmu sedang dalam perjalanan!'],
                'selesai'  => ['✅', 'Pesananmu sudah selesai!'],
                'batal'    => ['❌', 'Pesananmu telah dibatalkan.'],
            ];
            
            if (isset($status_labels[$current_status])) {
                $toast_update = [
                    'kode'   => $last_order['kode'],
                    'status' => $current_status,
                    'label'  => $status_labels[$current_status][0],
                    'pesan'  => $status_labels[$current_status][1],
                ];
            }
        }
        
        setcookie(
            'kios_last_seen_' . $last_order['kode'], 
            $current_status, 
            time() + (365 * 24 * 3600), 
            '/'
        );
    }
}

// ===== CEK PROMO POPUP (muncul 1x per hari) =====
$show_promo_popup = false;
if (!isset($_COOKIE['promo_shown_today'])) {
    $show_promo_popup = true;
    setcookie('promo_shown_today', '1', time() + (24 * 3600), '/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#E63946">
    <meta name="description" content="Kios Ayam Crispy Sambal Geprek Joana — Ayam crispy & geprek lezat, pedas, harga terjangkau. Pesan online, antar cepat!">
    <title>Kios Ayam Crispy Sambal Geprek Joana — Lezat & Pedas</title>
    
    <!-- PWA Meta -->
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="gambar/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kios Ayam">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo">
            <div class="logo-icon">🍗</div>
            <div class="logo-text">
                KIOS AYAM CRISPY SAMBAL GEPREK JOANA
                <small>Geprek Spayci</small>
            </div>
        </a>

        <button class="nav-toggle" onclick="toggleNav()" aria-label="Menu">☰</button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">Beranda</a></li>
            <li><a href="menu.php" class="<?= $current=='menu.php'?'active':'' ?>">Menu</a></li>
            <li><a href="promo.php" class="<?= $current=='promo.php'?'active':'' ?>">Promo</a></li>
            <li><a href="tentang.php" class="<?= $current=='tentang.php'?'active':'' ?>">Tentang</a></li>
            <li><a href="testimoni.php" class="<?= $current=='testimoni.php'?'active':'' ?>">Testimoni</a></li>
            <li><a href="pesanan_saya.php" class="<?= $current=='pesanan_saya.php'?'active':'' ?>">📋 Pesanan Saya</a></li>
            <li><a href="kontak.php" class="<?= $current=='kontak.php'?'active':'' ?>">Kontak</a></li>
            <li><a href="pemesanan.php" class="nav-cta <?= $current=='pemesanan.php'?'active':'' ?>">🛒 Pesan</a></li>
        </ul>
    </div>
</header>

<!-- ===== TOAST NOTIFIKASI CUSTOMER ===== -->
<?php if ($toast_update): ?>
    <div class="toast-notif" id="toastNotif">
        <div class="toast-icon"><?= $toast_update['label'] ?></div>
        <div class="toast-body">
            <div class="toast-title">Update Pesanan</div>
            <div class="toast-message"><?= $toast_update['pesan'] ?></div>
            <a href="cek_pesanan.php?kode=<?= urlencode($toast_update['kode']) ?>" class="toast-link">
                Lihat detail →
            </a>
        </div>
        <button onclick="document.getElementById('toastNotif').remove()" class="toast-close" aria-label="Tutup">×</button>
    </div>

    <script>
        setTimeout(function() {
            var toast = document.getElementById('toastNotif');
            if (toast) {
                toast.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(function() { 
                    if (toast.parentElement) toast.remove(); 
                }, 300);
            }
        }, 10000);
    </script>
<?php endif; ?>

<!-- ===== POPUP PROMO (muncul otomatis 1x sehari) ===== -->
<?php if ($show_promo_popup && $current == 'index.php'): ?>
    <div class="popup-overlay" id="popupPromo">
        <div class="popup-box" style="max-width:420px;">
            <div class="popup-header" style="background:linear-gradient(135deg, #FFB703, #E63946); color:#fff; border-radius:20px 20px 0 0;">
                <h3 style="color:#fff;">🎉 Promo Spesial!</h3>
                <button class="popup-close" onclick="closePromo()" style="background:rgba(255,255,255,0.2); color:#fff;" aria-label="Tutup">×</button>
            </div>
            <div class="popup-body" style="text-align:center;">
                <div style="font-size:4rem; margin-bottom:16px;">🎁</div>
                <h3 style="margin-bottom:12px; color:var(--text);">Paket Hemat Berdua</h3>
                <p style="color:#666; margin-bottom:8px; font-size:0.95rem;">
                    2 Ayam Geprek + 2 Es Teh + 1 Kerupuk
                </p>
                <div style="font-size:1.8rem; font-weight:800; color:var(--primary); margin:16px 0;">
                    Rp 30.000 <s style="font-size:1rem; color:#999;">Rp 45.000</s>
                </div>
                <p style="color:#888; font-size:0.85rem; margin-bottom:20px;">
                    Berlaku Senin - Kamis
                </p>
                <a href="pemesanan.php" class="btn btn-primary btn-block btn-lg">
                    🛒 Pesan Sekarang
                </a>
                <button onclick="closePromo()" 
                        style="background:none; border:none; color:#999; margin-top:12px; cursor:pointer; font-size:0.85rem; text-decoration:underline;">
                    Nanti saja
                </button>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            document.getElementById('popupPromo')?.classList.add('show');
            document.body.style.overflow = 'hidden';
        }, 2000);

        function closePromo() {
            document.getElementById('popupPromo')?.classList.remove('show');
            document.body.style.overflow = '';
        }
    </script>
<?php endif; ?>

<main class="container"></main>