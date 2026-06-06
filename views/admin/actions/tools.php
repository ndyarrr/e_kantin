<?php // actions/tools.php

$action = $_POST['action'] ?? '';

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

function bacaCSVutf8(string $filePath): string
{
    $content = file_get_contents($filePath);
    $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'UTF-16'], true);
    if ($encoding && strtolower($encoding) !== 'utf-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    return ltrim($content, "\xEF\xBB\xBF");
}

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
            $header = array_map('trim', $rows[0]);
            $colNisn = cariKolom($header, ['nisn']);
            $colNama = cariKolom($header, ['nama lengkap', 'nama_lengkap', 'nama']);
            $colPassword = cariKolom($header, ['password', 'sandi', 'pw']);
            $colKelas = cariKolom($header, ['id_kelas', 'kelas', 'tingkat', 'grade']);
            $colJurusan = cariKolom($header, ['id_jurusan', 'jurusan', 'kompetensi', 'program keahlian', 'prodi']);

            if ($colNisn === -1 || $colNama === -1) {
                $feedback = ['type' => 'error', 'msg' => 'Kolom NISN atau Nama tidak ditemukan.'];
            } else {
                // Build map kelas
                $mapKelasToJurusan = []; // id_kelas → id_jurusan
                $res = mysqli_query($conn, "
    SELECT k.id_kelas, k.kelas, k.rombel, j.id_jurusan, j.nama_jurusan
    FROM kelas k
    JOIN jurusan j ON j.id_jurusan = k.id_jurusan
    WHERE k.deleted_at IS NULL
");
                while ($r = mysqli_fetch_assoc($res)) {
                    $id = (int) $r['id_kelas'];
                    $jid = (int) $r['id_jurusan'];
                    $tk = strtolower(trim($r['kelas']));
                    $jur = strtolower(trim($r['nama_jurusan']));
                    $rb = (string) (int) $r['rombel'];

                    $mapKelas[(string) $id] = $id;
                    $mapKelas["$tk $jur $rb"] = $id;
                    $mapKelas["$tk-$jur-$rb"] = $id;
                    $mapKelas["$tk/$jur/$rb"] = $id;
                    $mapKelas["$tk$jur$rb"] = $id;
                    $mapKelas["$tk $jur"] = $id;

                    $mapKelasToJurusan[$id] = $jid; // ← simpan mapping kelas → jurusan
                }

                // Build map jurusan
                $mapJurusan = [];
                $res = mysqli_query($conn, "SELECT id_jurusan, nama_jurusan FROM jurusan");
                while ($r = mysqli_fetch_assoc($res)) {
                    $mapJurusan[strtolower(trim($r['nama_jurusan']))] = (int) $r['id_jurusan'];
                    $mapJurusan[(string) $r['id_jurusan']] = (int) $r['id_jurusan']; // ID langsung
                }

                foreach (array_slice($rows, 1) as $rowNum => $data) {
                    $row = $rowNum + 2;
                    if (count($data) < 2)
                        continue;

                    $nisn = trim($data[$colNisn] ?? '');
                    $nama = trim($data[$colNama] ?? '');
                    $password = $colPassword !== -1 ? trim($data[$colPassword] ?? '') : '';

                    // Pad NISN
                    if (ctype_digit($nisn) && strlen($nisn) >= 8 && strlen($nisn) < 10) {
                        $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
                    }

                    // Validasi NISN & nama dulu sebelum resolve kelas
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

                    // Resolve kelas
                    // Resolve kelas dulu
                    $id_kelas = null;
                    $kelasRaw = $colKelas !== -1 ? trim($data[$colKelas] ?? '') : '';
                    if ($kelasRaw !== '') {
                        $id_kelas = $mapKelas[strtolower($kelasRaw)] ?? null;
                    }

                    // Resolve jurusan — dari kolom CSV kalau ada, fallback dari kelas
                    $id_jurusan = null;
                    $jurusanRaw = $colJurusan !== -1 ? trim($data[$colJurusan] ?? '') : '';
                    if ($jurusanRaw !== '') {
                        $id_jurusan = $mapJurusan[strtolower($jurusanRaw)] ?? null;
                    }
                    // Kalau kolom jurusan kosong/tidak ada, ambil dari id_kelas yang sudah resolve
                    if ($id_jurusan === null && $id_kelas !== null) {
                        $id_jurusan = $mapKelasToJurusan[$id_kelas] ?? null;
                    }

                    if ($id_kelas === null || $id_jurusan === null) {
                        $errors[] = "Baris $row: Kelas '$kelasRaw' tidak ditemukan.";
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

                    // INSERT — id_kelas & id_jurusan sudah pasti integer, tidak NULL
                    if (
                        mysqli_query($conn, "INSERT INTO murid (nisn, nama, password, id_kelas, id_jurusan, status)
                                             VALUES ('$ni','$n','$hash',$id_kelas,$id_jurusan,'aktif')")
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
                $feedback = ['type' => 'error', 'msg' => 'Kolom NUPTK atau Nama tidak ditemukan.'];
            } else {
                foreach (array_slice($rows, 1) as $rowNum => $data) {
                    $row = $rowNum + 2;
                    if (count($data) < 2)
                        continue;

                    $nuptk = trim($data[$colNuptk] ?? '');
                    $nama = trim($data[$colNama] ?? '');
                    $password = $colPassword !== -1 ? trim($data[$colPassword] ?? '') : '';

                    if (ctype_digit($nuptk) && strlen($nuptk) >= 14 && strlen($nuptk) < 16) {
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

/* ══ RESTORE DATA (Hanya Super Admin) ══ */
if ($action === 'tools_restore') {
    if (!$isAdminSuper)
        die("Akses Ilegal.");

    $tabel = $_POST['tabel'] ?? '';
    $id_col = $_POST['id_col'] ?? '';
    $id_val = $_POST['id_val'] ?? '';

    $allowedTabel = ['murid', 'guru', 'penjual', 'toko', 'menu', 'admin', 'kelas'];
    $allowedCol = ['nisn', 'nuptk', 'id_penjual', 'id_toko', 'id_menu', 'id_admin', 'id_kelas'];

    if (in_array($tabel, $allowedTabel) && in_array($id_col, $allowedCol) && $id_val !== '') {
        $id = mysqli_real_escape_string($conn, $id_val);
        $allowExecute = true;

        // --- VALIDASI SEBELUM RESTORE ---
        if ($tabel === 'murid') {
            $m_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_kelas, id_jurusan FROM murid WHERE nisn = '$id'"));
            if ($m_data) {
                $id_k = (int) $m_data['id_kelas'];
                $id_j = (int) $m_data['id_jurusan'];

                // 1. Cek apakah Jurusan ada di database
                $j_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jurusan FROM jurusan WHERE id_jurusan = $id_j"));
                if (!$j_exists) {
                    $feedback = ['type' => 'error', 'msg' => 'Gagal memulihkan murid: Jurusan asal tidak ditemukan di database. Silakan buat jurusan tersebut terlebih dahulu.'];
                    $allowExecute = false;
                } else {
                    // 2. Cek apakah Kelas ada dan aktif (tidak di-softdelete)
                    $k_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kelas, rombel, deleted_at FROM kelas WHERE id_kelas = $id_k"));
                    if (!$k_data) {
                        $feedback = ['type' => 'error', 'msg' => 'Gagal memulihkan murid: Kelas asal tidak ditemukan di database.'];
                        $allowExecute = false;
                    } elseif ($k_data['deleted_at'] !== null) {
                        $nama_kelas_err = $k_data['kelas'] . ' ' . $j_exists['nama_jurusan'] . ' ' . $k_data['rombel'];
                        $feedback = ['type' => 'error', 'msg' => "Gagal memulihkan murid: Kelas asal (<strong>$nama_kelas_err</strong>) sedang terhapus (soft-delete). Silakan pulihkan kelas tersebut terlebih dahulu."];
                        $allowExecute = false;
                    }
                }
            }
        } elseif ($tabel === 'kelas') {
            $k_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_jurusan FROM kelas WHERE id_kelas = '$id'"));
            if ($k_data) {
                $id_j = (int) $k_data['id_jurusan'];
                // Cek apakah Jurusan ada di database
                $j_exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_jurusan FROM jurusan WHERE id_jurusan = $id_j"));
                if (!$j_exists) {
                    $feedback = ['type' => 'error', 'msg' => 'Gagal memulihkan kelas: Jurusan asal tidak ditemukan di database. Silakan buat jurusan tersebut terlebih dahulu.'];
                    $allowExecute = false;
                }
            }
        }

        if ($allowExecute) {
            if ($tabel === 'toko') {
                require_once __DIR__ . '/../../../config/kantin_slot.php';
                $revive = kantinReviveToSlot($conn, (int) $id);
                if ($revive['ok']) {
                    catatLog($conn, 'Restore Kantin Bertingkat', 'Toko ID ' . $id . ' dipulihkan ke slot ' . (int) $revive['nomor']);
                    $feedback = ['type' => 'success', 'msg' => 'Kantin <strong>' . htmlspecialchars($revive['nama'] ?? '') . '</strong> dipulihkan dan mengisi <strong>slot kosong ' . (int) $revive['nomor'] . '</strong> (slot kosong pertama yang tersedia, bukan slot lamanya). Assign owner ulang jika perlu.'];
                } else {
                    mysqli_query($conn, "UPDATE toko SET deleted_at = NULL WHERE id_toko = '$id'");
                    mysqli_query($conn, "UPDATE menu SET deleted_at = NULL WHERE id_toko = '$id'");
                    catatLog($conn, 'Restore Kantin Bertingkat', 'Toko ID ' . $id . ' dipulihkan tanpa slot');
                    $feedback = ['type' => 'warning', 'msg' => $revive['msg'] . ' Kantin aktif kembali tetapi belum terhubung ke slot stand.'];
                }
            } else {
                mysqli_query($conn, "UPDATE `$tabel` SET deleted_at = NULL WHERE `$id_col` = '$id'");

                if ($tabel === 'kelas') {
                    catatLog($conn, 'Restore Kelas', "Restore kelas ID $id");
                } else {
                    catatLog($conn, 'Restore Data', "Restore $tabel dengan $id_col: $id");
                }

                $feedback = ['type' => 'success', 'msg' => 'Data berhasil dipulihkan.'];
            }
        }
    } else {
        $feedback = ['type' => 'error', 'msg' => 'Request tidak valid.'];
    }
}

/* ══ PERMANENT DELETE (Hanya Super Admin) ══ */
if ($action === 'tools_permanent_delete') {
    if (!$isAdminSuper)
        die("Akses Ilegal.");

    $tabel = $_POST['tabel'] ?? '';
    $id_col = $_POST['id_col'] ?? '';
    $id_val = $_POST['id_val'] ?? '';

    $allowedTabel = ['murid', 'guru', 'penjual', 'toko', 'menu', 'admin', 'kelas'];
    $allowedCol = ['nisn', 'nuptk', 'id_penjual', 'id_toko', 'id_menu', 'id_admin', 'id_kelas'];

    if (in_array($tabel, $allowedTabel) && in_array($id_col, $allowedCol) && $id_val !== '') {
        $id = mysqli_real_escape_string($conn, $id_val);

        if ($tabel === 'toko') {
            // Delete owner(s) permanently
            mysqli_query($conn, "DELETE FROM penjual WHERE role = 'owner' AND id_penjual IN (SELECT id_penjual FROM toko_penjual WHERE id_toko = '$id')");
            
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE id_toko = '$id')");
            mysqli_query($conn, "DELETE dp FROM detail_pesanan dp JOIN pesanan p ON p.id_pesanan = dp.id_pesanan WHERE p.id_toko = '$id'");
            mysqli_query($conn, "DELETE FROM pesanan WHERE id_toko = '$id'");
            mysqli_query($conn, "DELETE FROM menu WHERE id_toko = '$id'");
            mysqli_query($conn, "DELETE FROM toko_penjual WHERE id_toko = '$id'");
        }
        if ($tabel === 'penjual') {
            mysqli_query($conn, "DELETE FROM toko_penjual WHERE id_penjual = '$id'");
        }
        if ($tabel === 'kelas') {
            mysqli_query($conn, "DELETE FROM keranjang WHERE user_role = 'siswa' AND user_id IN (SELECT nisn FROM murid WHERE id_kelas = '$id')");
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE nisn_pembeli IN (SELECT nisn FROM murid WHERE id_kelas = '$id'))");
            mysqli_query($conn, "DELETE dp FROM detail_pesanan dp JOIN pesanan p ON p.id_pesanan = dp.id_pesanan JOIN murid m ON p.nisn_pembeli = m.nisn WHERE m.id_kelas = '$id'");
            mysqli_query($conn, "DELETE FROM pesanan WHERE nisn_pembeli IN (SELECT nisn FROM murid WHERE id_kelas = '$id')");
            mysqli_query($conn, "DELETE FROM murid WHERE id_kelas = '$id'");
        }
        if ($tabel === 'murid') {
            mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = '$id' AND user_role = 'siswa'");
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE nisn_pembeli = '$id')");
            mysqli_query($conn, "DELETE dp FROM detail_pesanan dp JOIN pesanan p ON p.id_pesanan = dp.id_pesanan WHERE p.nisn_pembeli = '$id'");
            mysqli_query($conn, "DELETE FROM pesanan WHERE nisn_pembeli = '$id'");
        }
        if ($tabel === 'guru') {
            mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = '$id' AND user_role = 'guru'");
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE nuptk_pembeli = '$id')");
            mysqli_query($conn, "DELETE dp FROM detail_pesanan dp JOIN pesanan p ON p.id_pesanan = dp.id_pesanan WHERE p.nuptk_pembeli = '$id'");
            mysqli_query($conn, "DELETE FROM pesanan WHERE nuptk_pembeli = '$id'");
        }
        if ($tabel === 'menu') {
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_pesanan IN (SELECT DISTINCT id_pesanan FROM detail_pesanan WHERE id_menu = '$id')");
            mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_pesanan IN (SELECT DISTINCT id_pesanan FROM detail_pesanan WHERE id_menu = '$id')");
            mysqli_query($conn, "DELETE FROM pesanan WHERE id_pesanan IN (SELECT DISTINCT id_pesanan FROM detail_pesanan WHERE id_menu = '$id')");
            mysqli_query($conn, "DELETE FROM detail_pesanan WHERE id_menu = '$id'");
        }

        mysqli_query($conn, "DELETE FROM `$tabel` WHERE `$id_col` = '$id'");
        catatLog($conn, 'Permanent Delete', "Hapus permanen $tabel dengan $id_col: $id");
        $feedback = ['type' => 'success', 'msg' => 'Data berhasil dihapus permanen.'];
    } else {
        $feedback = ['type' => 'error', 'msg' => 'Request tidak valid.'];
    }
}

/* ══ HAPUS LOG (Hanya Super Admin) ══ */
if ($action === 'tools_hapus_log') {
    if (!$isAdminSuper)
        die("Akses Ilegal.");

    mysqli_query($conn, "DELETE FROM log_sistem");
    catatLog($conn, 'Hapus Log', 'Semua log sistem dihapus');
    $feedback = ['type' => 'success', 'msg' => 'Semua log berhasil dihapus.'];
}