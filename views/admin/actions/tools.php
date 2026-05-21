<?php // actions/tools.php

$action = $_POST['action'] ?? '';

/* ══ helper: catat log ══ */


/* ══ helper: auto-detect delimiter ══
   Baca baris pertama, hitung jumlah ; vs , vs tab
   Yang paling banyak = delimiter yang dipakai
*/
function detectDelimiter(string $filePath): string
{
    $handle = fopen($filePath, 'r');
    $line = fgets($handle);
    fclose($handle);

    $counts = [
        ',' => substr_count($line, ','),
        ';' => substr_count($line, ';'),
        "\t" => substr_count($line, "\t"),
    ];
    arsort($counts);
    return array_key_first($counts);
}

/* ══ helper: auto-detect encoding & konversi ke UTF-8 ══ */
function bacaCSVutf8(string $filePath): string
{
    $content = file_get_contents($filePath);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'UTF-16'], true);
    if ($encoding && strtolower($encoding) !== 'utf-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    // Hapus BOM UTF-8
    $content = ltrim($content, "\xEF\xBB\xBF");
    return $content;
}

/* ══ helper: cari index kolom berdasarkan substring ══ */
function cariKolom(array $header, array $kemungkinan): int
{
    foreach ($header as $i => $col) {
        $col = strtolower(trim($col));
        foreach ($kemungkinan as $k) {
            if (str_contains($col, strtolower($k)))
                return $i;
        }
    }
    return -1;
}

/* ══ helper: parse CSV dari string konten ══ */
function parseCSV(string $content, string $delimiter): array
{
    $rows = [];
    $handle = fopen('php://temp', 'r+');
    fwrite($handle, $content);
    rewind($handle);
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

/* ══ IMPORT MURID ══ */
if ($action === 'tools_import_murid') {
    $skipDuplikat = isset($_POST['skip_duplikat']);
    $berhasil = $dilewati = $gagal = 0;
    $errors = [];

    if (empty($_FILES['csv_file']['tmp_name'])) {
        $feedback = ['type' => 'error', 'msg' => 'File CSV wajib diupload.'];
    } elseif ($_FILES['csv_file']['size'] > 2 * 1024 * 1024) {
        $feedback = ['type' => 'error', 'msg' => 'File terlalu besar. Maksimal 2MB.'];
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $delimiter = detectDelimiter($filePath);
        $content = bacaCSVutf8($filePath);
        $rows = parseCSV($content, $delimiter);

        if (empty($rows)) {
            $feedback = ['type' => 'error', 'msg' => 'File CSV kosong atau tidak terbaca.'];
        } else {
            // Baris pertama = header
            $header = array_map('trim', $rows[0]);

            $colNisn = cariKolom($header, ['nisn']);
            $colNama = cariKolom($header, ['nama lengkap', 'nama_lengkap', 'nama']);
            $colPassword = cariKolom($header, ['password', 'sandi', 'pw']);
            $colKelas = cariKolom($header, ['id_kelas', 'kelas', 'tingkat', 'grade']);
            $colJurusan = cariKolom($header, ['id_jurusan', 'jurusan', 'kompetensi', 'program keahlian', 'prodi']);

            if ($colNisn === -1 || $colNama === -1) {
                $feedback = ['type' => 'error', 'msg' => 'Kolom NISN atau Nama tidak ditemukan. Pastikan header CSV mengandung kolom "nisn" dan "nama".'];
            } else {
                // Cache mapping kelas & jurusan nama → id
                // Ganti cache mapping kelas
                $mapKelas = [];
                $res = mysqli_query($conn, "
                    SELECT k.id_kelas, 
                        CONCAT(k.kelas, ' ', j.nama_jurusan, ' ', k.rombel) as nama_lengkap,
                        k.kelas, k.rombel, j.nama_jurusan
                    FROM kelas k 
                    JOIN jurusan j ON j.id_jurusan = k.id_jurusan
                ");
                while ($r = mysqli_fetch_assoc($res)) {
                    // Support berbagai format: "10 RPL 1", "10rpl1", "10", dll
                    $mapKelas[strtolower($r['nama_lengkap'])] = $r['id_kelas'];
                    $mapKelas[strtolower($r['kelas'] . $r['nama_jurusan'] . $r['rombel'])] = $r['id_kelas'];
                    $mapKelas[strtolower($r['kelas'])] = $r['id_kelas']; // fallback
                }

                $mapJurusan = [];
                $res = mysqli_query($conn, "SELECT id_jurusan, nama_jurusan FROM jurusan");
                while ($r = mysqli_fetch_assoc($res))
                    $mapJurusan[strtolower(trim($r['nama_jurusan']))] = $r['id_jurusan'];

                // Proses baris data (skip header di index 0)
                foreach (array_slice($rows, 1) as $rowNum => $data) {
                    $row = $rowNum + 2; // nomor baris asli (1=header, 2=data pertama)
                    if (count($data) < 2)
                        continue;

                    $nisn = trim($data[$colNisn] ?? '');
                    $nama = trim($data[$colNama] ?? '');
                    $password = $colPassword !== -1 ? trim($data[$colPassword] ?? '') : '';

                    // Fix leading zero NISN — Excel sering hapus 0 di depan
                    if (ctype_digit($nisn) && strlen($nisn) < 10 && strlen($nisn) >= 8) {
                        $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
                    }

                    // Resolve kelas
                    $id_kelas = 0;
                    if ($colKelas !== -1) {
                        $kelasRaw = strtolower(trim($data[$colKelas] ?? ''));
                        if (is_numeric($kelasRaw) && in_array((int) $kelasRaw, array_values($mapKelas))) {
                            $id_kelas = (int) $kelasRaw;
                        } else {
                            $id_kelas = $mapKelas[$kelasRaw] ?? 0;
                        }
                    }

                    // Resolve jurusan
                    $id_jurusan = 0;
                    if ($colJurusan !== -1) {
                        $jurusanRaw = strtolower(trim($data[$colJurusan] ?? ''));
                        if (is_numeric($jurusanRaw) && in_array((int) $jurusanRaw, array_values($mapJurusan))) {
                            $id_jurusan = (int) $jurusanRaw;
                        } else {
                            $id_jurusan = $mapJurusan[$jurusanRaw] ?? 0;
                        }
                    }

                    if ($nisn === '' || $nama === '') {
                        $errors[] = "Baris $row: NISN dan nama wajib diisi.";
                        $gagal++;
                        continue;
                    }
                    if (!ctype_digit($nisn) || strlen($nisn) !== 10) {
                        $errors[] = "Baris $row: NISN '$nisn' harus 10 digit angka.";
                        $gagal++;
                        continue;
                    }

                    $finalPass = $password !== '' ? $password : $nisn;
                    $hash = md5($finalPass);
                    $ni = mysqli_real_escape_string($conn, $nisn);
                    $n = mysqli_real_escape_string($conn, $nama);

                    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nisn FROM murid WHERE nisn='$ni'"));
                    if ($cek) {
                        if ($skipDuplikat) {
                            $dilewati++;
                            continue;
                        }
                        $errors[] = "Baris $row: NISN '$nisn' sudah terdaftar.";
                        $gagal++;
                        continue;
                    }

                    $kelasVal = $id_kelas ?: 'NULL';
                    $jurusanVal = $id_jurusan ?: 'NULL';

                    if (
                        mysqli_query($conn, "INSERT INTO murid (nisn, nama, password, id_kelas, id_jurusan, status)
                                             VALUES ('$ni','$n','$hash',$kelasVal,$jurusanVal,'aktif')")
                    ) {
                        $berhasil++;
                    } else {
                        $errors[] = "Baris $row: " . mysqli_error($conn);
                        $gagal++;
                    }
                }

                catatLog($conn, 'Import CSV Murid', "Delimiter: $delimiter | Berhasil: $berhasil, Dilewati: $dilewati, Gagal: $gagal");
                $_SESSION['import_result'] = compact('berhasil', 'dilewati', 'gagal', 'errors');
                $feedback = ['type' => 'success', 'msg' => "Import selesai: <strong>$berhasil</strong> berhasil, <strong>$dilewati</strong> dilewati, <strong>$gagal</strong> gagal."];
            }
        }
    }
}

/* ══ IMPORT GURU ══ */
if ($action === 'tools_import_guru') {
    $skipDuplikat = isset($_POST['skip_duplikat']);
    $berhasil = $dilewati = $gagal = 0;
    $errors = [];

    if (empty($_FILES['csv_file']['tmp_name'])) {
        $feedback = ['type' => 'error', 'msg' => 'File CSV wajib diupload.'];
    } elseif ($_FILES['csv_file']['size'] > 2 * 1024 * 1024) {
        $feedback = ['type' => 'error', 'msg' => 'File terlalu besar. Maksimal 2MB.'];
    } else {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $delimiter = detectDelimiter($filePath);
        $content = bacaCSVutf8($filePath);
        $rows = parseCSV($content, $delimiter);

        if (empty($rows)) {
            $feedback = ['type' => 'error', 'msg' => 'File CSV kosong atau tidak terbaca.'];
        } else {
            $header = array_map('trim', $rows[0]);

            $colNuptk = cariKolom($header, ['nuptk']);
            $colNama = cariKolom($header, ['nama lengkap', 'nama_lengkap', 'nama']);
            $colPassword = cariKolom($header, ['password', 'sandi', 'pw']);

            if ($colNuptk === -1 || $colNama === -1) {
                $feedback = ['type' => 'error', 'msg' => 'Kolom NUPTK atau Nama tidak ditemukan. Pastikan header CSV mengandung kolom "nuptk" dan "nama".'];
            } else {
                foreach (array_slice($rows, 1) as $rowNum => $data) {
                    $row = $rowNum + 2;
                    if (count($data) < 2)
                        continue;

                    $nuptk = trim($data[$colNuptk] ?? '');
                    $nama = trim($data[$colNama] ?? '');
                    $password = $colPassword !== -1 ? trim($data[$colPassword] ?? '') : '';

                    // Fix leading zero NUPTK
                    if (ctype_digit($nuptk) && strlen($nuptk) < 16 && strlen($nuptk) >= 14) {
                        $nuptk = str_pad($nuptk, 16, '0', STR_PAD_LEFT);
                    }

                    if ($nuptk === '' || $nama === '') {
                        $errors[] = "Baris $row: NUPTK dan nama wajib diisi.";
                        $gagal++;
                        continue;
                    }
                    if (!ctype_digit($nuptk) || strlen($nuptk) !== 16) {
                        $errors[] = "Baris $row: NUPTK '$nuptk' harus 16 digit angka.";
                        $gagal++;
                        continue;
                    }

                    $finalPass = $password !== '' ? $password : $nuptk;
                    $hash = md5($finalPass);
                    $nu = mysqli_real_escape_string($conn, $nuptk);
                    $n = mysqli_real_escape_string($conn, $nama);

                    $cek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nuptk FROM guru WHERE nuptk='$nu'"));
                    if ($cek) {
                        if ($skipDuplikat) {
                            $dilewati++;
                            continue;
                        }
                        $errors[] = "Baris $row: NUPTK '$nuptk' sudah terdaftar.";
                        $gagal++;
                        continue;
                    }

                    if (
                        mysqli_query($conn, "INSERT INTO guru (nuptk, nama, password, status)
                                             VALUES ('$nu','$n','$hash','aktif')")
                    ) {
                        $berhasil++;
                    } else {
                        $errors[] = "Baris $row: " . mysqli_error($conn);
                        $gagal++;
                    }
                }

                catatLog($conn, 'Import CSV Guru', "Delimiter: $delimiter | Berhasil: $berhasil, Dilewati: $dilewati, Gagal: $gagal");
                $_SESSION['import_result'] = compact('berhasil', 'dilewati', 'gagal', 'errors');
                $feedback = ['type' => 'success', 'msg' => "Import selesai: <strong>$berhasil</strong> berhasil, <strong>$dilewati</strong> dilewati, <strong>$gagal</strong> gagal."];
            }
        }
    }
}

/* ══ HAPUS LOG ══ */
if ($action === 'tools_hapus_log') {
    mysqli_query($conn, "DELETE FROM log_sistem");
    catatLog($conn, 'Hapus Log', 'Semua log sistem dihapus');
    $feedback = ['type' => 'success', 'msg' => 'Semua log berhasil dihapus.'];
}