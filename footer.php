</main>

<footer>
    <div class="footer-inner">
        <div class="footer-brand">
            <a href="index.php" class="logo">
                <div class="logo-icon">🍗</div>
                <div class="logo-text">
                    Kios Ayam Crispy Sambal Geprek Joana
                    <small>Geprek Spayci</small>
                </div>
            </a>
            <p>Ayam crispy & geprek dengan sambal khas dan harga terjangkau. Pesan online, antar cepat!</p>
        </div>

        <div class="footer-col">
            <h4>Menu</h4>
            <ul>
                <li><a href="index.php">Beranda</a></li>
                <li><a href="menu.php">Daftar Menu</a></li>
                <li><a href="promo.php">Promo</a></li>
                <li><a href="tentang.php">Tentang Kami</a></li>
                <li><a href="testimoni.php">Testimoni</a></li>
                <li><a href="pesanan_saya.php">Pesanan Saya</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Kontak</h4>
            <ul>
                <li>📍Jl. Raya Maumbi. jaga V Desa Wori Kec.Wori, Manado</li>
                <li>📞 <a href="tel:085398790908">0853-9879-0908</a></li>
                <li>✉️ <a href="mailto:info@kiosayam.id">info@kiosayam.id</a></li>
                <li>🕒 10.00 - 22.00 WIB</li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> <strong>Kios Ayam Crispy Sambal Geprek Joana</strong> — Lezat, Pedas, Bikin Nagih! 🍗
    </div>
</footer>

<!-- ============================================ -->
<!-- FLOATING ACTION BUTTONS                       -->
<!-- ============================================ -->
<div class="fab-wrap">
    <button class="fab-btn fab-order" onclick="openQuickOrder()" aria-label="Pesan Cepat" title="Pesan Cepat">
        🍗
    </button>
    <button class="fab-btn fab-chat" onclick="openChat()" aria-label="Chat WhatsApp" title="Chat WhatsApp">
        💬
    </button>
</div>

<!-- ============================================ -->
<!-- POPUP: PESAN CEPAT                            -->
<!-- ============================================ -->
<div class="popup-overlay" id="popupOrder">
    <div class="popup-box">
        <div class="popup-header">
            <h3>🍗 Pesan Cepat</h3>
            <button class="popup-close" onclick="closeQuickOrder()" aria-label="Tutup">×</button>
        </div>
        <div class="popup-body">
            <p style="margin-bottom:16px; color:#666; font-size:0.9rem;">
                Isi form di bawah untuk pesan cepat tanpa buka halaman baru
            </p>
            
            <form action="proses_order.php" method="POST" id="quickOrderForm">
                <div class="form-group">
                    <label>Nama <span class="req">*</span></label>
                    <input type="text" name="nama" required placeholder="Masukkan nama">
                </div>
                
                <div class="form-group">
                    <label>No. HP / WhatsApp <span class="req">*</span></label>
                    <input type="tel" name="hp" required pattern="[0-9]{10,15}" placeholder="08xxxxxxxxxx">
                </div>
                
                <div class="form-group">
                    <label>Alamat Pengiriman <span class="req">*</span></label>
                    <textarea name="alamat" rows="2" required placeholder="Jalan, No. Rumah, RT/RW"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Pilih Menu <span class="req">*</span></label>
                    <select name="menu_id" id="quickMenu" required>
                        <option value="">-- Pilih Menu --</option>
                        <?php
                        $q_menu = $conn->query("SELECT id, nama, harga FROM menu WHERE status='aktif' ORDER BY nama");
                        if ($q_menu) {
                            while ($m = $q_menu->fetch_assoc()):
                        ?>
                            <option value="<?= $m['id'] ?>" data-harga="<?= $m['harga'] ?>">
                                <?= e($m['nama']) ?> — <?= rupiah($m['harga']) ?>
                            </option>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jumlah Porsi <span class="req">*</span></label>
                    <input type="number" name="jumlah" id="quickJumlah" min="1" value="1" required oninput="hitungQuickTotal()">
                </div>
                
                <div class="form-group">
                    <label>Pilihan Sambal</label>
                    <select name="jenis_sambal">
                        <option value="Sambal Geprek">🌶️ Sambal Geprek (Pedas Khas)</option>
                        <option value="Saus Sambal">🥫 Saus Sambal (Manis Pedas)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Metode Bayar</label>
                    <select name="metode_bayar">
                        <option value="COD">💵 COD (Bayar di Tempat)</option>
                        <option value="Transfer">🏦 Transfer Bank BRI</option>
                        <option value="QRIS">📱 QRIS</option>
                        <option value="DANA">📲 DANA</option>
                    </select>
                </div>
                
                <input type="hidden" name="catatan" value="Pesan cepat dari popup">
                
                <div class="total-box" style="margin:16px 0;">
                    <span class="label">Total:</span>
                    <span class="amount" id="quickTotal">Rp 0</span>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    ✅ Kirim Pesanan
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- POPUP: CHAT WHATSAPP                          -->
<!-- ============================================ -->
<div class="popup-overlay" id="popupChat">
    <div class="popup-box" style="max-width:420px;">
        <div class="popup-header" style="background:linear-gradient(135deg, #25D366, #128C7E); color:#fff;">
            <h3 style="color:#fff;">💬 Chat Kami</h3>
            <button class="popup-close" onclick="closeChat()" style="background:rgba(255,255,255,0.2); color:#fff;" aria-label="Tutup">×</button>
        </div>
        <div class="popup-body">
            <p style="margin-bottom:16px; color:#666; font-size:0.9rem;">
                Pilih topik yang ingin kamu tanyakan:
            </p>
            
            <a href="https://wa.me/6285398790908?text=Halo%20Admin%2C%20saya%20mau%20tanya%20tentang%20menu" 
               target="_blank" 
               class="chat-option">
                <div class="chat-option-icon">🍗</div>
                <div class="chat-option-text">
                    <strong>Tanya Menu</strong>
                    <small>Info menu & harga</small>
                </div>
                <div class="chat-option-arrow">→</div>
            </a>
            
            <a href="https://wa.me/6285398790908?text=Halo%20Admin%2C%20saya%20mau%20cek%20pesanan%20saya" 
               target="_blank" 
               class="chat-option">
                <div class="chat-option-icon">📦</div>
                <div class="chat-option-text">
                    <strong>Cek Pesanan</strong>
                    <small>Status pesanan saya</small>
                </div>
                <div class="chat-option-arrow">→</div>
            </a>
            
            <a href="https://wa.me/6285398790908?text=Halo%20Admin%2C%20saya%20mau%20pesan%20dalam%20jumlah%20banyak" 
               target="_blank" 
               class="chat-option">
                <div class="chat-option-icon">🎉</div>
                <div class="chat-option-text">
                    <strong>Pesanan Besar</strong>
                    <small>Untuk acara/party</small>
                </div>
                <div class="chat-option-arrow">→</div>
            </a>
            
            <a href="https://wa.me/6285398790908?text=Halo%20Admin%2C%20saya%20mau%20bertanya" 
               target="_blank" 
               class="chat-option">
                <div class="chat-option-icon">💬</div>
                <div class="chat-option-text">
                    <strong>Pertanyaan Lain</strong>
                    <small>Chat langsung ke admin</small>
                </div>
                <div class="chat-option-arrow">→</div>
            </a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SCRIPT: TOGGLE NAV + POPUP                    -->
<!-- ============================================ -->
<script>
// ===== TOGGLE MENU MOBILE =====
function toggleNav() {
    document.getElementById('navMenu').classList.toggle('open');
}
document.querySelectorAll('#navMenu a').forEach(function(a) {
    a.addEventListener('click', function() {
        document.getElementById('navMenu').classList.remove('open');
    });
});

// ===== POPUP: PESAN CEPAT =====
function openQuickOrder() {
    document.getElementById('popupOrder').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeQuickOrder() {
    document.getElementById('popupOrder').classList.remove('show');
    document.body.style.overflow = '';
}

// ===== POPUP: CHAT =====
function openChat() {
    document.getElementById('popupChat').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeChat() {
    document.getElementById('popupChat').classList.remove('show');
    document.body.style.overflow = '';
}

// ===== HITUNG TOTAL PESAN CEPAT =====
function hitungQuickTotal() {
    const menu = document.getElementById('quickMenu');
    const jumlah = parseInt(document.getElementById('quickJumlah').value) || 0;
    const harga = parseInt(menu.options[menu.selectedIndex]?.dataset.harga) || 0;
    const total = harga * jumlah;
    
    document.getElementById('quickTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

// Panggil saat menu berubah
document.getElementById('quickMenu')?.addEventListener('change', hitungQuickTotal);

// ===== TUTUP POPUP SAAT KLIK OVERLAY =====
document.querySelectorAll('.popup-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});

// ===== TUTUP POPUP DENGAN ESC =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.popup-overlay.show').forEach(function(p) {
            p.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
});
</script>

</body>
</html>