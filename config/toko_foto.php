<?php
/**
 * Helper foto profil kantin (toko) — simpan di assets/img/kantin/
 */

function tokoFotoDir(): string
{
    $dir = dirname(__DIR__) . '/assets/img/kantin';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function tokoFotoImgRoot(): string
{
    return dirname(__DIR__) . '/assets/img';
}

/** Nama file standar: kantin_{id_toko}.{ext} */
function tokoFotoNamaFile(int $idToko, string $ext): string
{
    return 'kantin_' . $idToko . '.' . strtolower($ext);
}

/** Hapus semua varian foto toko (kantin_ / toko_ + legacy di root img) */
function tokoFotoHapusLama(int $idToko, ?string $namaDiDb = null): void
{
    $dir = tokoFotoDir();
    $root = tokoFotoImgRoot();

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        foreach (['kantin_', 'toko_'] as $prefix) {
            $path = $dir . '/' . $prefix . $idToko . '.' . $ext;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $legacyRoot = $root . '/kantin_' . $idToko . '.' . $ext;
        if (file_exists($legacyRoot)) {
            @unlink($legacyRoot);
        }
    }

    if (!empty($namaDiDb)) {
        $inKantin = $dir . '/' . $namaDiDb;
        if (file_exists($inKantin)) {
            @unlink($inKantin);
        }
        $inRoot = $root . '/' . $namaDiDb;
        if (file_exists($inRoot)) {
            @unlink($inRoot);
        }
    }
}

/** Ukuran maksimal foto kantin (sama owner & admin): 2MB */
if (!defined('TOKO_FOTO_MAX_BYTES')) {
    define('TOKO_FOTO_MAX_BYTES', 2 * 1024 * 1024);
}

/**
 * Upload foto toko. Mengembalikan nama file untuk kolom foto_toko, atau null jika gagal.
 * Set $errorMsg untuk pesan ke pengguna (opsional).
 */
function tokoFotoUpload(int $idToko, array $file, ?string &$errorMsg = null): ?string
{
    $errorMsg = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
        $errorMsg = 'Gagal mengunggah file. Pastikan ukuran tidak melebihi 2MB.';
        return null;
    }

    if (($file['size'] ?? 0) > TOKO_FOTO_MAX_BYTES) {
        $errorMsg = 'Ukuran foto maksimal 2MB.';
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $errorMsg = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
        return null;
    }

    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    $mime = '';
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']) ?: '';
    }
    if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
        $errorMsg = 'File bukan gambar yang valid.';
        return null;
    }

    tokoFotoHapusLama($idToko);

    $namaFile = tokoFotoNamaFile($idToko, $ext);
    $tujuan = tokoFotoDir() . '/' . $namaFile;

    if (move_uploaded_file($file['tmp_name'], $tujuan)) {
        return $namaFile;
    }

    $errorMsg = 'Gagal menyimpan file ke server.';
    return null;
}

/**
 * Proses upload foto kantin dari $_FILES['foto_toko'] (alur sama owner).
 *
 * @return array{filename: ?string, error: ?string, attempted: bool}
 */
function tokoFotoProsesUpload(int $idToko, array $fileField): array
{
    if (($fileField['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['filename' => null, 'error' => null, 'attempted' => false];
    }

    $err = null;
    $nama = tokoFotoUpload($idToko, $fileField, $err);

    return [
        'filename' => $nama,
        'error'    => $err,
        'attempted' => true,
    ];
}

/** Path relatif untuk tag &lt;img&gt; (dengan prefix ../../ dll) */
function tokoFotoUrl(string $namaFile, string $urlPrefix = ''): string
{
    if ($namaFile === '') {
        return '';
    }

    $root = tokoFotoImgRoot();
    if (file_exists($root . '/kantin/' . $namaFile)) {
        return $urlPrefix . 'assets/img/kantin/' . $namaFile;
    }
    if (file_exists($root . '/' . $namaFile)) {
        return $urlPrefix . 'assets/img/' . $namaFile;
    }

    return $urlPrefix . 'assets/img/kantin/' . $namaFile;
}
