<?php
// views/penjual/owner/sections/kantin.php

require_once __DIR__ . '/../../../../config/toko_foto.php';

// =========================================================================
// 1. QUERY AMBIL DATA KANTIN/TOKO
// =========================================================================
$tokoRes = mysqli_query($conn, "SELECT * FROM `toko` WHERE `id_toko` = $idToko LIMIT 1");
$tokoData = mysqli_fetch_assoc($tokoRes);

// =========================================================================
// 2. QUERY AMBIL DATA BANNER (Menyaring yang belum ter-soft delete)
// =========================================================================
$queryBanner = mysqli_query($conn, "SELECT * FROM `banner_promo` 
                                    WHERE `id_toko` = $idToko 
                                    AND `deleted_at` IS NULL 
                                    ORDER BY `dibuat_pada` DESC");

// ATURAN BISNIS: Hitung banner aktif yang sesungguhnya (aktif murni dan belum expired)
$jumlahBannerAktif = 0;
$queryHitungAktif = "SELECT COUNT(*) as total_aktif FROM `banner_promo` 
                     WHERE `id_toko` = $idToko 
                     AND `aktif` = 1 
                     AND `deleted_at` IS NULL 
                     AND `berlaku_hingga` >= CURDATE()";
                     
$qHitungAktif = mysqli_query($conn, $queryHitungAktif);
if ($qHitungAktif) {
    $dataHitung = mysqli_fetch_assoc($qHitungAktif);
    $jumlahBannerAktif = (int)($dataHitung['total_aktif'] ?? 0);
}

$isLocked = ($jumlahBannerAktif >= 2);
?>

<div class="kantin-container">

    <div class="pcard">
        <div class="pcard-inner" style="padding: 25px;">
            <h3 style="margin: 0 0 15px 0; font-size: 18px;"><i class="fa-solid fa-store"></i> Edit Profil & Informasi Kantin</h3>
            <div class="pcard-divider" style="margin-bottom: 20px;"></div>

            <form action="index.php?section=kantin" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 18px;">
                <input type="hidden" name="_current_section" value="kantin">
                <input type="hidden" name="action" value="update_kantin_full">
                
                <div class="profile-preview-wrapper">
                    <div class="profile-avatar-circle">
                        <?php if (!empty($tokoData['foto_toko'])): ?>
                            <img src="<?= htmlspecialchars(tokoFotoUrl($tokoData['foto_toko'], '../../../')) ?>?v=<?= time() ?>">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder"><i class="fa-solid fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label style="font-weight: bold; font-size: 14px; display: block; margin-bottom: 5px;">Foto Profil Kantin</label>
                        <input type="file" name="foto_toko" accept="image/jpeg, image/jpg, image/png, image/webp" style="font-size: 13px;">
                        <small style="color: #666; display: block; margin-top: 3px;">Format: JPG, JPEG, PNG, WEBP (Max 2MB)</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="kantin-form-group form-col-2">
                        <label>Nama Kantin</label>
                        <input type="text" name="nama_toko" value="<?= htmlspecialchars($tokoData['nama_toko'] ?? '') ?>" required>
                    </div>
                    <div class="kantin-form-group form-col-1">
                        <label>Status Kantin</label>
                        <select name="status_toko">
                            <option value="buka" <?= ($tokoData['status'] ?? 'buka') === 'buka' ? 'selected' : '' ?>>Buka</option>
                            <option value="tutup" <?= ($tokoData['status'] ?? 'buka') === 'tutup' ? 'selected' : '' ?>>Tutup</option>
                        </select>
                    </div>
                </div>

                <div class="kantin-form-group">
                    <label>Deskripsi Pendek (Slogan / Info Singkat)</label>
                    <input type="text" name="deskripsi_singkat" value="<?= htmlspecialchars($tokoData['deskripsi'] ?? '') ?>" placeholder="Contoh: Makanan sehat khas Nusantara">
                </div>

                <div class="kantin-form-group">
                    <label>Deskripsi Panjang (Detail info, jam buka dll)</label>
                    <textarea name="deskripsi_panjang" rows="4" placeholder="Tuliskan detail kantin Anda..."><?= htmlspecialchars($tokoData['deskripsi_panjang'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="pcard-btn"
                        style="width: 100%; padding: 12px; font-size: 15px; font-weight: bold; background: #3498db; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>
    </div>

    <div class="banner-super-grid">
        
        <div class="banner-box-item">
            <div class="pcard-banner-lokal">
                <div class="pcard-inner-lokal">
                    <div class="pcard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin:0; font-size:17px; font-weight:600; color:#2c3e50;"><i class="fa-solid fa-rectangle-ad" style="color:#3498db;"></i> Tambah Banner Promosi</h3>
                        <span class="banner-badge-status <?= $isLocked ? 'locked' : 'active' ?>">
                            <?= $jumlahBannerAktif ?> / 2 Aktif
                        </span>
                    </div>
                    <div class="pcard-divider" style="height: 1px; background: #eaedf1; margin: 15px 0;"></div>

                    <?php if ($isLocked): ?>
                        <div class="alert-limit-banner">
                            <strong>Batas Maksimal:</strong> 2 banner aktif telah digunakan. Hapus banner lama atau tunggu tanggal expired terlewati untuk mengunggah baru.
                        </div>
                    <?php endif; ?>

                    <form action="index.php?section=kantin" method="POST" enctype="multipart/form-data" class="form-banner-lokal">
                        <input type="hidden" name="_current_section" value="kantin">
                        <input type="hidden" name="action" value="add_banner">
                        
                        <div class="kantin-form-group">
                            <label>File Gambar Banner</label>
                            <input type="file" name="gambar_banner" accept="image/jpeg, image/jpg, image/png, image/webp" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px;">
                            <small style="color: #64748b; display:block; margin-top:4px;">Format: JPG, JPEG, PNG, WEBP (Max 2MB). Rekomendasi Rasio 3:1.</small>
                        </div>

                        <div class="kantin-form-group" style="margin-top: 15px;">
                            <label>Berlaku Hingga Tanggal</label>
                            <input type="date" name="berlaku_hingga" min="<?= date('Y-m-d') ?>" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px;">
                            <small style="color: #64748b;">Banner otomatis diturunkan dari pembeli jika melewati tanggal ini.</small>
                        </div>
                        
                        <button type="submit" class="pcard-btn" <?= $isLocked ? 'disabled' : '' ?>
                                style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; background: <?= $isLocked ? '#94a3b8' : '#27ae60' ?>; color: #fff; border: none; border-radius: 6px; cursor: <?= $isLocked ? 'not-allowed' : 'pointer' ?>; margin-top: auto;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Banner
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="banner-box-item">
            <div class="pcard-banner-lokal">
                <div class="pcard-inner-lokal">
                    <h3 style="margin: 0 0 15px 0; font-size: 17px; font-weight:600; color:#2c3e50;"><i class="fa-solid fa-list" style="color:#3498db;"></i> Daftar Banner Toko</h3>
                    <div class="pcard-divider" style="height: 1px; background: #eaedf1; margin: 15px 0;"></div>
                    
                    <div style="flex: 1; display: flex; flex-direction: column; width: 100%;">
                        <table class="banner-table-custom">
                            <thead>
                                <tr>
                                    <th>Pratinjau</th>
                                    <th>Berlaku Hingga</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$queryBanner || mysqli_num_rows($queryBanner) == 0): ?>
                                    <tr>
                                        <td colspan="4" style="padding: 60px 10px; text-align: center; color: #94a3b8; font-weight: 500;">Belum ada banner promo di database toko Anda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($queryBanner)): 
                                        $isExpired = (strtotime($row['berlaku_hingga']) < strtotime(date('Y-m-d')));
                                        $isMurniAktif = ($row['aktif'] == 1);
                                        $statusAsliActive = ($isMurniAktif && !$isExpired);
                                    ?>
                                        <tr>
                                            <td>
                                                <img src="../../../assets/img/banner/<?= htmlspecialchars($row['gambar']) ?>?v=<?= time() ?>" 
                                                     class="banner-thumbnail"
                                                     onerror="this.src='../../../assets/img/promo_banner.png';">
                                            </td>
                                            <td>
                                                <small style="display:block; font-weight:bold; color: #334155;">
                                                    <?= date('d M Y', strtotime($row['berlaku_hingga'])) ?>
                                                </small>
                                                <span style="font-size:10px; color:#64748b;">Dibuat: <?= date('d/m/y', strtotime($row['dibuat_pada'])) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($statusAsliActive): ?>
                                                    <span class="status-pill active">🟢 Active</span>
                                                <?php elseif ($isExpired && $isMurniAktif): ?>
                                                    <span class="status-pill expired">⏳ Expired</span>
                                                <?php else: ?>
                                                    <span class="status-pill inactive">🔴 Off</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <form action="index.php?section=kantin" method="POST" onsubmit="return confirm('Yakin ingin menghapus banner ini? (Data akan di-soft delete)');" style="display: inline;">
                                                    <input type="hidden" name="_current_section" value="kantin">
                                                    <input type="hidden" name="action" value="hapus_banner_direct">
                                                    <input type="hidden" name="id_banner" value="<?= $row['id_banner'] ?>">
                                                    <button type="submit" class="btn-delete-banner">
                                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>