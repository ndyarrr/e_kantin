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

    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- NEW CARD: PENGATURAN QRIS KANTIN -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="pcard">
        <div class="pcard-inner" style="padding: 25px;">
            <h3 style="margin: 0 0 15px 0; font-size: 18px;"><i class="fa-solid fa-qrcode"></i> Metode Pembayaran QRIS Kantin</h3>
            <div class="pcard-divider" style="margin-bottom: 20px;"></div>

            <div style="display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start;">
                <!-- QRIS Preview Area -->
                <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; align-items: center; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <span style="font-weight: bold; font-size: 13.5px; color: #4a5568; margin-bottom: 15px; display: block; text-align: center;">Tampilan QRIS Aktif</span>
                    <div style="width: 200px; height: 200px; background: #ffffff; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0;">
                        <?php if (!empty($tokoData['qris_image'])): ?>
                            <img src="../../../assets/img/qris/<?= htmlspecialchars($tokoData['qris_image']) ?>?v=<?= time() ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <div style="text-align: center; color: #94a3b8; padding: 10px;">
                                <i class="fa-solid fa-qrcode" style="font-size: 50px; margin-bottom: 10px; display: block; color: #cbd5e1; margin-left: auto; margin-right: auto;"></i>
                                <span style="font-size: 12px;">QRIS belum di-upload</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($tokoData['qris_image'])): ?>
                        <form action="index.php?section=kantin" method="POST" onsubmit="return confirm('Yakin ingin menghapus QRIS kantin ini?');" style="margin-top: 15px; width: 100%;">
                            <input type="hidden" name="_current_section" value="kantin">
                            <input type="hidden" name="action" value="hapus_qris_kantin">
                            <button type="submit" class="btn-delete-banner" style="width: 100%; padding: 10px; font-size: 13px;">
                                <i class="fa-solid fa-trash-can"></i> Hapus QRIS
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- QRIS Upload Area -->
                <div style="flex: 2; min-width: 280px;">
                    <form action="index.php?section=kantin" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 18px;">
                        <input type="hidden" name="_current_section" value="kantin">
                        <input type="hidden" name="action" value="update_qris_kantin">

                        <div class="kantin-form-group">
                            <label style="font-weight: bold;">Pilih Foto Kode QRIS</label>
                            <input type="file" name="qris_image" accept="image/jpeg, image/jpg, image/png, image/webp" required style="font-size: 13px;">
                            <small style="color: #64748b; display: block; margin-top: 4px; line-height: 1.4;">
                                Upload gambar/foto kode QRIS toko Anda agar pembeli dapat melakukan pembayaran non-tunai.<br>
                                Format yang didukung: <strong>JPG, JPEG, PNG, WEBP</strong> (Maksimal 2MB).
                            </small>
                        </div>

                        <button type="submit" class="pcard-btn"
                                style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; background: #27ae60; color: #fff; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; transition: background 0.2s;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Gambar QRIS
                        </button>
                    </form>
                </div>
            </div>
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

                    <form action="index.php?section=kantin" method="POST" enctype="multipart/form-data" class="form-banner-lokal" id="addBannerForm">
                        <input type="hidden" name="_current_section" value="kantin">
                        <input type="hidden" name="action" value="add_banner">
                        
                        <!-- Hidden inputs for canvas values -->
                        <input type="hidden" name="banner_scale" id="bannerScale" value="1.0">
                        <input type="hidden" name="banner_bgx" id="bannerBgX" value="50">
                        <input type="hidden" name="banner_bgy" id="bannerBgY" value="50">

                        <div class="kantin-form-group">
                            <label>File Gambar Banner</label>
                            <input type="file" name="gambar_banner" id="gambarBannerInput" accept="image/jpeg, image/jpg, image/png, image/webp" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px;">
                            <small style="color: #64748b; display:block; margin-top:4px;">Format: JPG, JPEG, PNG, WEBP (Max 2MB). Rekomendasi Rasio 3:1.</small>
                        </div>

                        <!-- Canvas Preview Box -->
                        <div class="kantin-form-group" style="margin-top: 15px;">
                            <label>Pratinjau Posisi Kanvas (3:1)</label>
                            <div class="banner-canvas-preview" id="canvasPreview">
                                <img id="canvasPreviewImg" src="" alt="Pratinjau Banner" style="display: none;">
                                <div class="canvas-placeholder" id="canvasPlaceholder">
                                    <i class="fa-solid fa-image" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px;"></i>
                                    <span style="font-size: 12px; color: #94a3b8;">Pilih file gambar untuk mengatur kanvas</span>
                                </div>
                            </div>
                        </div>

                        <!-- Zoom and Position Controls -->
                        <div id="canvasControls" style="display: none; margin-top: 15px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div class="kantin-form-group" style="margin-bottom: 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Zoom Gambar (Skala):</label>
                                    <span id="scaleValText" style="font-size:12px; font-weight:bold; color:#3498db;">1.0x</span>
                                </div>
                                <input type="range" id="sliderScale" min="1.0" max="3.0" step="0.05" value="1.0" style="margin-top: 4px; cursor: pointer;">
                            </div>
                            <div class="kantin-form-group" style="margin-bottom: 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Geser Kiri/Kanan (X):</label>
                                    <span id="xValText" style="font-size:12px; font-weight:bold; color:#3498db;">Tengah</span>
                                </div>
                                <input type="range" id="sliderX" min="0" max="100" step="1" value="50" style="margin-top: 4px; cursor: pointer;">
                            </div>
                            <div class="kantin-form-group" style="margin-bottom: 5px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Geser Atas/Bawah (Y):</label>
                                    <span id="yValText" style="font-size:12px; font-weight:bold; color:#3498db;">Tengah</span>
                                </div>
                                <input type="range" id="sliderY" min="0" max="100" step="1" value="50" style="margin-top: 4px; cursor: pointer;">
                            </div>
                            <div style="margin-top: 8px; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-circle-info" style="color: #3498db;"></i>
                                <span><strong>Tips:</strong> Klik & drag/geser langsung gambar di kanvas preview!</span>
                            </div>
                        </div>

                        <div class="kantin-form-group" style="margin-top: 15px;">
                            <label>Kode Promo</label>
                            <input type="text" name="kode_promo" placeholder="Contoh: DISKONHEBAT, KANTINJOSS25" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px; font-weight: bold; text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                        </div>

                        <div class="kantin-form-group" style="margin-top: 15px;">
                            <label>Diskon (%)</label>
                            <input type="number" name="diskon_persen" placeholder="Contoh: 25" min="1" max="100" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px;">
                        </div>

                        <div class="kantin-form-group" style="margin-top: 15px;">
                            <label>Berlaku Hingga Tanggal</label>
                            <input type="date" name="berlaku_hingga" min="<?= date('Y-m-d') ?>" required <?= $isLocked ? 'disabled' : '' ?> style="font-size: 13px;">
                            <small style="color: #64748b;">Banner otomatis diturunkan dari pembeli jika melewati tanggal ini.</small>
                        </div>
                        
                        <button type="submit" class="pcard-btn" <?= $isLocked ? 'disabled' : '' ?>
                                style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; background: <?= $isLocked ? '#94a3b8' : '#27ae60' ?>; color: #fff; border: none; border-radius: 6px; cursor: <?= $isLocked ? 'not-allowed' : 'pointer' ?>; margin-top: 15px;">
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
                                    <th>Kode Promo</th>
                                    <th>Diskon</th>
                                    <th>Berlaku Hingga</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$queryBanner || mysqli_num_rows($queryBanner) == 0): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 60px 10px; text-align: center; color: #94a3b8; font-weight: 500;">Belum ada banner promo di database toko Anda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = mysqli_fetch_assoc($queryBanner)): 
                                        $isExpired = (strtotime($row['berlaku_hingga']) < strtotime(date('Y-m-d')));
                                        $isMurniAktif = ($row['aktif'] == 1);
                                        $statusAsliActive = ($isMurniAktif && !$isExpired);

                                        // Dekode koordinat canvas jika ada
                                        $scale = 1.0;
                                        $bgX = 50;
                                        $bgY = 50;
                                        if (!empty($row['canvas_config'])) {
                                            $conf = json_decode($row['canvas_config'], true);
                                            if (is_array($conf)) {
                                                $scale = $conf['scale'] ?? 1.0;
                                                // Format baru: bgX/bgY (0-100)
                                                if (isset($conf['bgX'])) {
                                                    $bgX = $conf['bgX'];
                                                    $bgY = $conf['bgY'] ?? 50;
                                                } else {
                                                    // Kompatibilitas format lama (x/y = -100 sampai 100)
                                                    $bgX = 50; // abaikan posisi lama, gunakan tengah
                                                    $bgY = 50;
                                                }
                                            }
                                        }
                                        $inlineStyle = "object-fit: cover; object-position: {$bgX}% {$bgY}%;" . ($scale > 1.0 ? " transform: scale($scale); transform-origin: center;" : "");
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="thumbnail-canvas-container">
                                                    <img src="../../../assets/img/banner/<?= htmlspecialchars($row['gambar']) ?>?v=<?= time() ?>" 
                                                         class="banner-thumbnail"
                                                         style="<?= $inlineStyle ?>"
                                                         onerror="this.src='../../../assets/img/promo_banner.png'; this.style.objectPosition='50% 50%'; this.style.transform='none';">
                                                </div>
                                            </td>
                                            <td>
                                                <span style="font-weight: 800; font-family: 'Poppins', sans-serif; color: #0f172a; text-transform: uppercase; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-size: 12px; border: 1px solid #e2e8f0; letter-spacing: 0.5px;">
                                                    <?= htmlspecialchars($row['kode_promo'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-weight: 800; color: #10b981; font-family: 'Poppins', sans-serif; font-size: 14px;">
                                                    <?= (int)($row['diskon_persen'] ?? 0) ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <small style="display:block; font-weight:bold; color: #334155;">
                                                    <?= date('d M Y', strtotime($row['berlaku_hingga'])) ?>
                                                </small>
                                                <span style="font-size:10px; color:#64748b; display:block;">Dibuat: <?= date('d/m/y', strtotime($row['dibuat_pada'])) ?></span>
                                                
                                                <?php if ($statusAsliActive): ?>
                                                    <div class="banner-countdown" data-expired-date="<?= $row['berlaku_hingga'] ?> 23:59:59">
                                                        <i class="fa-solid fa-hourglass-start animate-pulse-slow"></i>
                                                        <span class="countdown-text">Menghitung...</span>
                                                    </div>
                                                <?php endif; ?>
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
                                                <form action="index.php?section=kantin" method="POST" onsubmit="return confirm('Yakin ingin menghapus banner ini?');" style="display: inline;">
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

<!-- Interactive Banner Canvas Position & Countdown Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------
    // 1. DYNAMIC INTERACTIVE CANVAS PREVIEW LOGIC
    // -------------------------------------------------------------
    const gambarBannerInput = document.getElementById('gambarBannerInput');
    const canvasPreview = document.getElementById('canvasPreview');
    const canvasPreviewImg = document.getElementById('canvasPreviewImg');
    const canvasPlaceholder = document.getElementById('canvasPlaceholder');
    const canvasControls = document.getElementById('canvasControls');
    
    const sliderScale = document.getElementById('sliderScale');
    const sliderX = document.getElementById('sliderX');
    const sliderY = document.getElementById('sliderY');
    
    const scaleValText = document.getElementById('scaleValText');
    const xValText = document.getElementById('xValText');
    const yValText = document.getElementById('yValText');
    
    const bannerScale = document.getElementById('bannerScale');
    const bannerBgX = document.getElementById('bannerBgX');
    const bannerBgY = document.getElementById('bannerBgY');
    
    if (gambarBannerInput) {
        gambarBannerInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    canvasPreviewImg.src = evt.target.result;
                    canvasPreviewImg.style.display = 'block';
                    canvasPlaceholder.style.display = 'none';
                    canvasControls.style.display = 'block';
                    resetCanvasParams();
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // bgX & bgY = nilai object-position (0-100, 50 = tengah/center)
    let scale = 1.0;
    let bgX = 50;
    let bgY = 50;
    
    function posLabel(val) {
        if (val === 50) return 'Tengah';
        if (val < 50) return Math.round((50 - val) * 2) + '% ke kiri/atas';
        return Math.round((val - 50) * 2) + '% ke kanan/bawah';
    }
    
    function resetCanvasParams() {
        scale = 1.0;
        bgX = 50;
        bgY = 50;
        sliderScale.value = 1.0;
        sliderX.value = 50;
        sliderY.value = 50;
        updateCanvasTransforms();
    }
    
    function updateCanvasTransforms() {
        // object-position memindahkan "jendela crop" di dalam gambar
        // Tidak pernah menampilkan celah karena gambar sudah di-cover terlebih dahulu
        canvasPreviewImg.style.objectPosition = `${bgX}% ${bgY}%`;
        // Scale hanya memperbesar gambar dari tengah, object-position tetap aktif
        canvasPreviewImg.style.transform = scale > 1.0 ? `scale(${scale})` : '';
        canvasPreviewImg.style.transformOrigin = 'center';
        
        scaleValText.textContent = scale.toFixed(2) + 'x';
        xValText.textContent = posLabel(bgX);
        yValText.textContent = posLabel(bgY);
        
        bannerScale.value = scale;
        bannerBgX.value = bgX;
        bannerBgY.value = bgY;
    }
    
    if (sliderScale) {
        sliderScale.addEventListener('input', function() {
            scale = parseFloat(this.value);
            updateCanvasTransforms();
        });
    }
    
    if (sliderX) {
        sliderX.addEventListener('input', function() {
            bgX = parseInt(this.value);
            updateCanvasTransforms();
        });
    }
    
    if (sliderY) {
        sliderY.addEventListener('input', function() {
            bgY = parseInt(this.value);
            updateCanvasTransforms();
        });
    }
    
    // Panning drag — drag ke kanan = geser konten ke kanan (bgX naik)
    let isDragging = false;
    let startMouseX = 0;
    let startMouseY = 0;
    let startBgX = 50;
    let startBgY = 50;
    
    if (canvasPreview) {
        canvasPreview.style.cursor = 'move';
        
        canvasPreview.addEventListener('mousedown', function(e) {
            if (!canvasPreviewImg.src || canvasPreviewImg.style.display === 'none') return;
            e.preventDefault();
            isDragging = true;
            startMouseX = e.clientX;
            startMouseY = e.clientY;
            startBgX = bgX;
            startBgY = bgY;
            canvasPreview.style.cursor = 'grabbing';
        });
        
        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            const canvasW = canvasPreview.offsetWidth || 300;
            const canvasH = canvasPreview.offsetHeight || 100;
            
            const dx = e.clientX - startMouseX;
            const dy = e.clientY - startMouseY;
            
            // Sensitivitas: bergeser 1 piksel = berubah (100 / canvasW) %, disesuaikan skala
            const sensitivityX = 100 / (canvasW * scale);
            const sensitivityY = 100 / (canvasH * scale);
            
            // Drag ke kanan → gambar bergeser ke kanan (bgX berkurang karena kita geser jendela ke kiri)
            bgX = Math.max(0, Math.min(100, Math.round(startBgX - dx * sensitivityX)));
            bgY = Math.max(0, Math.min(100, Math.round(startBgY - dy * sensitivityY)));
            
            sliderX.value = bgX;
            sliderY.value = bgY;
            updateCanvasTransforms();
        });
        
        window.addEventListener('mouseup', function() {
            if (isDragging) {
                isDragging = false;
                canvasPreview.style.cursor = 'move';
            }
        });
        
        // Touch panning
        canvasPreview.addEventListener('touchstart', function(e) {
            if (!canvasPreviewImg.src || canvasPreviewImg.style.display === 'none') return;
            isDragging = true;
            startMouseX = e.touches[0].clientX;
            startMouseY = e.touches[0].clientY;
            startBgX = bgX;
            startBgY = bgY;
        }, { passive: true });
        
        window.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            const canvasW = canvasPreview.offsetWidth || 300;
            const canvasH = canvasPreview.offsetHeight || 100;
            
            const dx = e.touches[0].clientX - startMouseX;
            const dy = e.touches[0].clientY - startMouseY;
            
            const sensitivityX = 100 / (canvasW * scale);
            const sensitivityY = 100 / (canvasH * scale);
            
            bgX = Math.max(0, Math.min(100, Math.round(startBgX - dx * sensitivityX)));
            bgY = Math.max(0, Math.min(100, Math.round(startBgY - dy * sensitivityY)));
            
            sliderX.value = bgX;
            sliderY.value = bgY;
            updateCanvasTransforms();
        }, { passive: true });
        
        window.addEventListener('touchend', function() {
            isDragging = false;
        });
    }

    // -------------------------------------------------------------
    // 2. LIVE COUNTDOWN TIMERS LOGIC
    // -------------------------------------------------------------
    function updateCountdownTimers() {
        const timers = document.querySelectorAll('.banner-countdown');
        timers.forEach(function(timer) {
            const expiredDateStr = timer.getAttribute('data-expired-date');
            if (!expiredDateStr) return;
            
            const expiredTime = new Date(expiredDateStr.replace(/-/g, '/')).getTime();
            const now = new Date().getTime();
            const diff = expiredTime - now;
            
            const textEl = timer.querySelector('.countdown-text');
            if (!textEl) return;
            
            if (diff <= 0) {
                textEl.textContent = 'Expired';
                textEl.style.color = '#ef4444';
                textEl.style.fontWeight = 'bold';
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let formattedTime = '';
            if (days > 0) {
                formattedTime += days + 'd ';
            }
            formattedTime += hours + 'h ' + minutes + 'm ' + seconds + 's';
            
            textEl.textContent = ' ' + formattedTime;
        });
    }
    
    setInterval(updateCountdownTimers, 1000);
    updateCountdownTimers();
});
</script>