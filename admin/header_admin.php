<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['admin'])) {
    redirect('login.php');
}

$current = basename($_SERVER['PHP_SELF']);

// ===== HITUNG NOTIFIKASI =====
$notif_baru = 0;
$notif_bayar = 0;
$notif_testimoni = 0;
$notif_bell = 0;

$cek_pesanan = $conn->query("SHOW TABLES LIKE 'pesanan'");
if ($cek_pesanan && $cek_pesanan->num_rows > 0) {
    $res = $conn->query("SELECT 
        SUM(CASE WHEN status_pesanan='baru' THEN 1 ELSE 0 END) AS baru,
        SUM(CASE WHEN status_bayar='menunggu' THEN 1 ELSE 0 END) AS menunggu
        FROM pesanan");
    if ($res && $row = $res->fetch_assoc()) {
        $notif_baru  = (int)($row['baru'] ?? 0);
        $notif_bayar = (int)($row['menunggu'] ?? 0);
    }
}

$cek_testi = $conn->query("SHOW TABLES LIKE 'testimoni'");
if ($cek_testi && $cek_testi->num_rows > 0) {
    $res2 = $conn->query("SELECT COUNT(*) AS c FROM testimoni WHERE status='pending'");
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $notif_testimoni = (int)($row2['c'] ?? 0);
    }
}

$cek_notif = $conn->query("SHOW TABLES LIKE 'notifikasi'");
if ($cek_notif && $cek_notif->num_rows > 0) {
    $res3 = $conn->query("SELECT COUNT(*) AS c FROM notifikasi WHERE dibaca=0");
    if ($res3 && $row3 = $res3->fetch_assoc()) {
        $notif_bell = (int)($row3['c'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Kios Ayam Crispy Sambal Geprek Joana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style_admin.css">
</head>
<body>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="icon">🍗</div>
            <div class="text">
                Kios Ayam
                <small>Admin Panel</small>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li><a href="index.php" class="<?= $current=='index.php'?'active':'' ?>">
                <span class="icon">📊</span> Dashboard</a></li>
            <li><a href="pesanan.php" class="<?= $current=='pesanan.php'?'active':'' ?>">
                <span class="icon">🛒</span> Pesanan Aktif
                <?php if ($notif_baru > 0): ?>
                    <span class="sidebar-badge"><?= $notif_baru ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="verifikasi.php" class="<?= $current=='verifikasi.php'?'active':'' ?>">
                <span class="icon">💳</span> Verifikasi Bayar
                <?php if ($notif_bayar > 0): ?>
                    <span class="sidebar-badge"><?= $notif_bayar ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="riwayat.php" class="<?= $current=='riwayat.php'?'active':'' ?>">
                <span class="icon">📜</span> Riwayat</a></li>
            <li><a href="menu_admin.php" class="<?= $current=='menu_admin.php'?'active':'' ?>">
                <span class="icon">🍗</span> Kelola Menu</a></li>
            <li><a href="testimoni_admin.php" class="<?= $current=='testimoni_admin.php'?'active':'' ?>">
                <span class="icon">💬</span> Testimoni
                <?php if ($notif_testimoni > 0): ?>
                    <span class="sidebar-badge"><?= $notif_testimoni ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="../index.php" target="_blank">
                <span class="icon">🌐</span> Lihat Website</a></li>
            <li style="margin-top:20px; padding-top:16px; border-top:1px solid #2A2A2E;">
                <a href="logout.php" style="color:#FF6B7A;">
                    <span class="icon">🚪</span> Logout</a></li>
        </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- MAIN -->
    <div class="admin-main">

        <div class="admin-topbar">
            <div style="display:flex; align-items:center; gap:12px;">
                <button class="topbar-toggle" onclick="toggleSidebar()">☰</button>
                <h1>Admin Panel</h1>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
                <!-- TOMBOL SUARA ON/OFF -->
                <button id="soundToggle" 
                        onclick="toggleSound()" 
                        title="Suara Notifikasi"
                        style="width:42px; height:42px; border-radius:12px; border:none; background:#F5F1ED; cursor:pointer; font-size:1.2rem; display:flex; align-items:center; justify-content:center; transition:.2s;">
                    🔊
                </button>

                <!-- NOTIFIKASI BELL -->
                <div class="notif-wrap">
                    <button class="notif-bell" onclick="toggleNotif()" aria-label="Notifikasi">
                        🔔
                        <span class="notif-count" id="notifCount" style="<?= $notif_bell > 0 ? '' : 'display:none;' ?>">
                            <?= $notif_bell > 99 ? '99+' : $notif_bell ?>
                        </span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-head">
                            <h4>🔔 Notifikasi</h4>
                            <a href="#" onclick="bacaSemua(event)">Tandai semua dibaca</a>
                        </div>
                        <div class="notif-body" id="notifBody">
                            <div class="notif-empty">
                                <div class="icon">🔔</div>
                                Memuat...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USER -->
                <div class="admin-user">
                    <span style="display:none;">Halo, <b><?= e($_SESSION['admin']['nama'] ?? 'Admin') ?></b></span>
                    <div class="avatar">
                        <?= strtoupper(substr($_SESSION['admin']['nama'] ?? 'A', 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content">

<script>
// ===== AUDIO NOTIFIKASI =====
const notifSound = new Audio('sound/notification.mp3');
notifSound.volume = 0.7;
notifSound.preload = 'auto';

let lastNotifId = 0;
let soundEnabled = localStorage.getItem('notifSound') !== 'off';

// Update tombol suara saat load
document.addEventListener('DOMContentLoaded', function() {
    updateSoundButton();
});

function updateSoundButton() {
    const btn = document.getElementById('soundToggle');
    if (!btn) return;
    
    if (soundEnabled) {
        btn.innerHTML = '🔊';
        btn.style.background = '#E8F6F1';
        btn.style.color = '#1B6E5E';
        btn.title = 'Suara: ON (klik untuk matikan)';
    } else {
        btn.innerHTML = '🔇';
        btn.style.background = '#FDECEE';
        btn.style.color = '#A11426';
        btn.title = 'Suara: OFF (klik untuk nyalakan)';
    }
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('notifSound', soundEnabled ? 'on' : 'off');
    updateSoundButton();
    
    if (soundEnabled) {
        // Play suara sebagai konfirmasi
        playNotifSound();
    }
}

function playNotifSound() {
    if (!soundEnabled) return;
    
    try {
        notifSound.currentTime = 0;
        notifSound.play().catch(function(err) {
            console.log('Autoplay blocked, need user interaction first');
        });
    } catch (e) {
        console.log('Sound error:', e);
    }
}

// ===== TOGGLE SIDEBAR =====
function toggleSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

document.querySelectorAll('.sidebar-nav a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) closeSidebar();
    });
});

// ===== NOTIFIKASI =====
let notifOpen = false;

function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    notifOpen = !notifOpen;
    dropdown.classList.toggle('show', notifOpen);
    if (notifOpen) loadNotif();
}

document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.notif-wrap');
    const dropdown = document.getElementById('notifDropdown');
    if (wrap && !wrap.contains(e.target)) {
        dropdown.classList.remove('show');
        notifOpen = false;
    }
});

function loadNotif() {
    fetch('notif_ajax.php?action=get')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            
            const countEl = document.getElementById('notifCount');
            if (data.unread > 0) {
                countEl.style.display = 'flex';
                countEl.textContent = data.unread > 99 ? '99+' : data.unread;
            } else {
                countEl.style.display = 'none';
            }
            
            // ===== CEK NOTIFIKASI BARU → PLAY SUARA =====
            if (data.latest_id > 0 && lastNotifId > 0 && data.latest_id > lastNotifId) {
                playNotifSound();
                bellShake();
            }
            lastNotifId = data.latest_id;
            
            const body = document.getElementById('notifBody');
            if (!data.notifs || data.notifs.length === 0) {
                body.innerHTML = '<div class="notif-empty"><div class="icon">🔔</div>Tidak ada notifikasi</div>';
                return;
            }
            
            body.innerHTML = data.notifs.map(n => {
                let iconClass = 'pesanan';
                let iconEmoji = '🛒';
                if (n.tipe === 'bukti_bayar') { iconClass = 'bayar'; iconEmoji = '💳'; }
                else if (n.tipe === 'testimoni') { iconClass = 'testimoni'; iconEmoji = '💬'; }
                else if (n.tipe === 'customer_batal') { iconClass = 'batal'; iconEmoji = '❌'; }
                
                return '<a href="' + (n.link || '#') + '" class="notif-item ' + (n.dibaca ? '' : 'unread') + '" onclick="bacaNotif(' + n.id + ', event)">' +
                    '<div class="notif-icon ' + iconClass + '">' + iconEmoji + '</div>' +
                    '<div class="notif-content">' +
                    '<h5>' + escapeHtml(n.judul) + '</h5>' +
                    '<p>' + escapeHtml(n.pesan || '') + '</p>' +
                    '<div class="notif-time">' + n.waktu_ago + '</div>' +
                    '</div></a>';
            }).join('');
        })
        .catch(err => console.error('Notif error:', err));
}

// Bell bergetar saat notif baru
function bellShake() {
    const bell = document.querySelector('.notif-bell');
    if (!bell) return;
    
    bell.style.animation = 'bellShake 0.5s ease';
    setTimeout(() => {
        bell.style.animation = '';
    }, 500);
}

function bacaNotif(id, e) {
    fetch('notif_ajax.php?action=baca&id=' + id);
}

function bacaSemua(e) {
    e.preventDefault();
    fetch('notif_ajax.php?action=baca_semua')
        .then(r => r.json())
        .then(() => {
            document.getElementById('notifCount').style.display = 'none';
            loadNotif();
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== AUTO CHECK SETIAP 15 DETIK =====
setInterval(() => {
    fetch('notif_ajax.php?action=get')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            
            const countEl = document.getElementById('notifCount');
            if (data.unread > 0) {
                countEl.style.display = 'flex';
                countEl.textContent = data.unread > 99 ? '99+' : data.unread;
            } else {
                countEl.style.display = 'none';
            }
            
            // Play suara kalau ada notif baru
            if (data.latest_id > 0 && lastNotifId > 0 && data.latest_id > lastNotifId) {
                playNotifSound();
                bellShake();
            }
            lastNotifId = data.latest_id;
        })
        .catch(err => console.error('Notif error:', err));
}, 15000);

// ===== UNLOCK AUDIO SAAT USER INTERACT =====
document.addEventListener('click', function unlockAudio() {
    notifSound.play().then(() => {
        notifSound.pause();
        notifSound.currentTime = 0;
    }).catch(() => {});
    document.removeEventListener('click', unlockAudio);
}, { once: true });

// Load awal
loadNotif();
</script>