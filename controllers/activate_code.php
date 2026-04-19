<?php
require_once __DIR__ . '/../config/database.php';

// Validasi kode aktivasi — return role kalau valid, false kalau tidak
function validasiKode($kode)
{
    global $conn;
    $kodeEsc = mysqli_real_escape_string($conn, $kode);
    $result = mysqli_query($conn, "SELECT * FROM kode_aktivasi WHERE kode = '$kodeEsc' AND dipakai = 0 LIMIT 1");

    if (mysqli_num_rows($result) === 0) {
        return false;
    }

    $data = mysqli_fetch_assoc($result);
    return $data['role']; // return 'guru'
}

// Tandai kode sudah dipakai setelah register berhasil
function tandaiKodeTerpakai($kode, $user_id)
{
    global $conn;
    $kodeEsc = mysqli_real_escape_string($conn, $kode);
    mysqli_query($conn, "UPDATE kode_aktivasi SET dipakai = 1, used_by = $user_id WHERE kode = '$kodeEsc'");
}

// Generate kode aktivasi baru (dipanggil dari dashboard admin)
function generateKode($role = 'guru')
{
    global $conn;
    $roleEsc = mysqli_real_escape_string($conn, $role);

    // Format kode: GURU-XXXXXX (6 karakter random)
    do {
        $kode = strtoupper($role) . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $kodeEsc = mysqli_real_escape_string($conn, $kode);
        $cek = mysqli_query($conn, "SELECT id FROM kode_aktivasi WHERE kode = '$kodeEsc' LIMIT 1");
    } while (mysqli_num_rows($cek) > 0); // ulangi kalau kode udah ada

    mysqli_query($conn, "INSERT INTO kode_aktivasi (kode, role) VALUES ('$kodeEsc', '$roleEsc')");

    return $kode;
}

// Ambil semua kode aktivasi (buat ditampilin di dashboard admin)
function semuaKode()
{
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM kode_aktivasi ORDER BY created_at DESC");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

