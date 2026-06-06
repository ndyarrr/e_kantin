<?php
/**
 * Manajemen slot stand kantin — posisi slot tetap meski kantin dihapus.
 */

if (!function_exists('kantinSlotMigrate')) {
    function kantinSlotMigrate($conn): void
    {
        $check = mysqli_query($conn, "SHOW TABLES LIKE 'slot_stand_kantin'");
        if ($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "CREATE TABLE `slot_stand_kantin` (
                `id_slot` INT AUTO_INCREMENT PRIMARY KEY,
                `nomor` INT NOT NULL,
                `id_toko` INT NULL DEFAULT NULL,
                UNIQUE KEY `uq_slot_nomor` (`nomor`),
                KEY `idx_slot_toko` (`id_toko`),
                CONSTRAINT `fk_slot_toko` FOREIGN KEY (`id_toko`) REFERENCES `toko`(`id_toko`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        }

        $slotKantin = 10;
        $qSlot = mysqli_query($conn, "SELECT nilai FROM pengaturan WHERE kunci = 'slot_kantin' LIMIT 1");
        if ($qSlot && mysqli_num_rows($qSlot) > 0) {
            $slotKantin = max(1, (int) mysqli_fetch_assoc($qSlot)['nilai']);
        }

        $existing = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM slot_stand_kantin"))['c'];

        if ($existing === 0) {
            for ($i = 1; $i <= $slotKantin; $i++) {
                mysqli_query($conn, "INSERT INTO slot_stand_kantin (nomor, id_toko) VALUES ($i, NULL)");
            }

            $tokos = mysqli_fetch_all(mysqli_query(
                $conn,
                "SELECT id_toko FROM toko WHERE deleted_at IS NULL ORDER BY urutan ASC, id_toko ASC"
            ), MYSQLI_ASSOC);

            foreach ($tokos as $idx => $t) {
                $nomor = $idx + 1;
                if ($nomor > $slotKantin) {
                    break;
                }
                $idToko = (int) $t['id_toko'];
                mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = $idToko WHERE nomor = $nomor");
                mysqli_query($conn, "UPDATE toko SET urutan = " . ($nomor - 1) . " WHERE id_toko = $idToko");
            }
        } elseif ($existing < $slotKantin) {
            for ($i = $existing + 1; $i <= $slotKantin; $i++) {
                mysqli_query($conn, "INSERT INTO slot_stand_kantin (nomor, id_toko) VALUES ($i, NULL)");
            }
        }

        mysqli_query(
            $conn,
            "UPDATE slot_stand_kantin s
             INNER JOIN toko t ON t.id_toko = s.id_toko
             SET s.id_toko = NULL
             WHERE t.deleted_at IS NOT NULL"
        );
    }

    function kantinSlotCount($conn): int
    {
        return (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM slot_stand_kantin"))['c'];
    }

    function kantinSlotCountOccupied($conn): int
    {
        return (int) mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COUNT(*) AS c FROM slot_stand_kantin WHERE id_toko IS NOT NULL"
        ))['c'];
    }

    function kantinSlotCountEmpty($conn): int
    {
        return (int) mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COUNT(*) AS c FROM slot_stand_kantin WHERE id_toko IS NULL"
        ))['c'];
    }

    function kantinSlotSyncUrutan($conn, int $idToko, int $nomor): void
    {
        if ($idToko > 0 && $nomor > 0) {
            $urutan = $nomor - 1;
            mysqli_query($conn, "UPDATE toko SET urutan = $urutan WHERE id_toko = $idToko");
        }
    }

    function kantinSlotAssign($conn, int $nomor, int $idToko): bool
    {
        if ($nomor < 1 || $idToko < 1) {
            return false;
        }

        $row = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT id_slot, id_toko FROM slot_stand_kantin WHERE nomor = $nomor LIMIT 1"
        ));
        if (!$row || $row['id_toko'] !== null) {
            return false;
        }

        mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = NULL WHERE id_toko = $idToko");
        mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = $idToko WHERE nomor = $nomor");
        kantinSlotSyncUrutan($conn, $idToko, $nomor);
        return true;
    }

    function kantinSlotClearByToko($conn, int $idToko): void
    {
        if ($idToko < 1) {
            return;
        }
        mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = NULL WHERE id_toko = $idToko");
    }

    function kantinSlotFirstEmpty($conn): ?int
    {
        $row = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT nomor FROM slot_stand_kantin WHERE id_toko IS NULL ORDER BY nomor ASC LIMIT 1"
        ));
        return $row ? (int) $row['nomor'] : null;
    }

    function kantinSlotSwap($conn, int $nomorA, int $nomorB): bool
    {
        if ($nomorA === $nomorB) {
            return false;
        }

        $slotA = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT nomor, id_toko FROM slot_stand_kantin WHERE nomor = $nomorA LIMIT 1"
        ));
        $slotB = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT nomor, id_toko FROM slot_stand_kantin WHERE nomor = $nomorB LIMIT 1"
        ));

        if (!$slotA || !$slotB) {
            return false;
        }

        $tokoA = $slotA['id_toko'] !== null ? (int) $slotA['id_toko'] : 'NULL';
        $tokoB = $slotB['id_toko'] !== null ? (int) $slotB['id_toko'] : 'NULL';

        mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = $tokoB WHERE nomor = $nomorA");
        mysqli_query($conn, "UPDATE slot_stand_kantin SET id_toko = $tokoA WHERE nomor = $nomorB");

        if ($tokoA !== 'NULL') {
            kantinSlotSyncUrutan($conn, (int) $tokoA, $nomorB);
        }
        if ($tokoB !== 'NULL') {
            kantinSlotSyncUrutan($conn, (int) $tokoB, $nomorA);
        }

        return true;
    }

    function kantinSlotAdd($conn): bool
    {
        $maxNomor = (int) mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT COALESCE(MAX(nomor), 0) AS m FROM slot_stand_kantin"
        ))['m'];
        $next = $maxNomor + 1;

        if (!mysqli_query($conn, "INSERT INTO slot_stand_kantin (nomor, id_toko) VALUES ($next, NULL)")) {
            return false;
        }

        mysqli_query($conn, "UPDATE pengaturan SET nilai = '$next' WHERE kunci = 'slot_kantin'");
        if (mysqli_affected_rows($conn) === 0) {
            mysqli_query($conn, "INSERT INTO pengaturan (kunci, nilai) VALUES ('slot_kantin', '$next')");
        }
        return true;
    }

    function kantinSlotRemoveLast($conn): bool
    {
        $last = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT nomor, id_toko FROM slot_stand_kantin ORDER BY nomor DESC LIMIT 1"
        ));
        if (!$last || $last['id_toko'] !== null) {
            return false;
        }

        $nomor = (int) $last['nomor'];
        mysqli_query($conn, "DELETE FROM slot_stand_kantin WHERE nomor = $nomor");

        $newCount = kantinSlotCount($conn);
        mysqli_query($conn, "UPDATE pengaturan SET nilai = '$newCount' WHERE kunci = 'slot_kantin'");
        return true;
    }

    function kantinSlotGetAll($conn): array
    {
        return mysqli_fetch_all(mysqli_query(
            $conn,
            "SELECT s.id_slot, s.nomor, s.id_toko,
                t.nama_toko, t.deskripsi, t.foto_toko, t.status, t.dibuat_pada,
                (SELECT COUNT(id_menu) FROM menu WHERE id_toko = t.id_toko AND deleted_at IS NULL) AS total_menu,
                (SELECT COUNT(tp1.id) FROM toko_penjual tp1
                 JOIN penjual p1 ON p1.id_penjual = tp1.id_penjual
                 WHERE tp1.id_toko = t.id_toko AND tp1.status = 'aktif'
                 AND p1.role = 'staf' AND p1.deleted_at IS NULL) AS total_penjual,
                (SELECT p2.nama FROM penjual p2
                 JOIN toko_penjual tp2 ON tp2.id_penjual = p2.id_penjual
                 WHERE tp2.id_toko = t.id_toko AND p2.role = 'owner'
                 AND tp2.status = 'aktif' AND p2.deleted_at IS NULL LIMIT 1) AS nama_owner
             FROM slot_stand_kantin s
             LEFT JOIN toko t ON t.id_toko = s.id_toko AND t.deleted_at IS NULL
             ORDER BY s.nomor ASC"
        ), MYSQLI_ASSOC);
    }

    function kantinSlotGetEmptyList($conn): array
    {
        return mysqli_fetch_all(mysqli_query(
            $conn,
            "SELECT nomor FROM slot_stand_kantin WHERE id_toko IS NULL ORDER BY nomor ASC"
        ), MYSQLI_ASSOC);
    }

    /**
     * Pulihkan kantin terhapus ke slot kosong pertama yang tersedia.
     *
     * @return array{ok: bool, msg: string, nomor?: int, nama?: string}
     */
    function kantinReviveToSlot($conn, int $idToko, ?int $nomor = null): array
    {
        if ($idToko < 1) {
            return ['ok' => false, 'msg' => 'Kantin tidak valid.'];
        }

        $row = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT id_toko, nama_toko FROM toko WHERE id_toko = $idToko AND deleted_at IS NOT NULL LIMIT 1"
        ));
        if (!$row) {
            return ['ok' => false, 'msg' => 'Kantin tidak ditemukan atau tidak dalam status terhapus.'];
        }

        if ($nomor === null || $nomor < 1) {
            $nomor = kantinSlotFirstEmpty($conn);
        }
        if (!$nomor) {
            return ['ok' => false, 'msg' => 'Tidak ada slot kosong yang tersedia. Pulihkan kantin hanya bisa mengisi slot kosong pertama — kosongkan atau tambah slot terlebih dahulu.'];
        }

        $slotRow = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT id_toko FROM slot_stand_kantin WHERE nomor = $nomor LIMIT 1"
        ));
        if (!$slotRow) {
            return ['ok' => false, 'msg' => 'Slot stand tidak ditemukan.'];
        }
        if ($slotRow['id_toko'] !== null) {
            return ['ok' => false, 'msg' => 'Slot ' . $nomor . ' sudah terisi kantin lain.'];
        }

        mysqli_query($conn, "UPDATE toko SET deleted_at = NULL WHERE id_toko = $idToko");
        mysqli_query($conn, "UPDATE menu SET deleted_at = NULL WHERE id_toko = $idToko");
        kantinSlotAssign($conn, $nomor, $idToko);

        return [
            'ok' => true,
            'msg' => 'Kantin dipulihkan ke slot ' . $nomor . '.',
            'nomor' => $nomor,
            'nama' => $row['nama_toko'],
        ];
    }
}
