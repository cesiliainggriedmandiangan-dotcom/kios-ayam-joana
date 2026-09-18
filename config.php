<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'kios_ayam';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Koneksi gagal: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

date_default_timezone_set('Asia/Jakarta');

function rupiah($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }
function kodePesanan() { return 'AYM-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5)); }
function redirect($url) { header("Location: $url"); exit; }
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }