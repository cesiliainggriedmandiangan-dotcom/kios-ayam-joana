<?php
require_once __DIR__ . '/../config.php';

if (isset($_SESSION['admin'])) redirect('index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $u);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($p, $admin['password'])) {
        $_SESSION['admin'] = ['id'=>$admin['id'], 'nama'=>$admin['nama'] ?: $admin['username']];
        redirect('index.php');
    } else {
        $err = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Kios Ayam</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display:flex; justify-content:center; align-items:center; min-height:100vh;
               background: linear-gradient(135deg, #C1121F 0%, #E63946 50%, #F4A261 100%);
               padding:20px; margin:0; }
        .login-box { background:#fff; padding:36px 32px; border-radius:20px;
                     box-shadow: 0 24px 48px rgba(0,0,0,0.3); width:100%; max-width:400px; }
        .login-logo { width:64px; height:64px; border-radius:16px;
                      background: linear-gradient(135deg, #E63946, #E76F51);
                      display:flex; align-items:center; justify-content:center;
                      font-size:2rem; margin:0 auto 20px;
                      box-shadow: 0 12px 24px rgba(230,57,70,0.35); }
        .login-box h1 { text-align:center; font-size:1.5rem; margin-bottom:6px; }
        .login-box .subtitle { text-align:center; color:#6B6B70; font-size:0.9rem; margin-bottom:28px; }
        .login-box .form-group { margin-bottom:16px; }
        .login-box label { display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px; }
        .login-box input { width:100%; padding:13px 16px; border:1.5px solid #EAE5E0;
                           border-radius:12px; font-size:0.95rem; outline:none;
                           transition:.2s; font-family:inherit; }
        .login-box input:focus { border-color:#E63946; box-shadow:0 0 0 4px rgba(230,57,70,0.1); }
        .login-box button { width:100%; padding:14px; border:none; border-radius:12px;
                            background:#E63946; color:#fff; font-weight:700; font-size:0.95rem;
                            cursor:pointer; transition:.2s; margin-top:8px; font-family:inherit; }
        .login-box button:hover { background:#C1121F; transform:translateY(-2px); }
        .login-info { text-align:center; margin-top:20px; font-size:0.82rem; color:#9B9B9F; }
        .login-info b { color:#6B6B70; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-logo">🍗</div>
        <h1>Admin Login</h1>
        <p class="subtitle">Masuk ke panel admin Kios Ayam</p>

        <?php if ($err): ?>
            <div class="alert alert-error" style="margin-bottom:18px;">❌ <?= e($err) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Masukkan password">
            </div>
            <button type="submit">🔐 Masuk</button>
        </form>

        <p class="login-info">Default: <b>admin</b> / <b>admin123</b></p>
    </div>
</body>
</html>