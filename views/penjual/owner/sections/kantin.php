<?php
// views/penjual/owner/sections/kantin.php

require_once __DIR__ . '/../../../../config/toko_foto.php';
require_once __DIR__ . '/../../../../config/banner_canvas.php';

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

// =========================================================================
// 3. QUERY AMBIL DATA FOTO LATAR BELAKANG
// =========================================================================
$queryLatar = mysqli_query($conn, "SELECT * FROM `foto_latar_belakang` 
                                    WHERE `id_toko` = $idToko 
                                    ORDER BY `urutan` ASC");
$jumlahLatar = 0;
if ($queryLatar) {
    $jumlahLatar = mysqli_num_rows($queryLatar);
}
$isLatarLimit = ($jumlahLatar >= 5);
?>

<div class="kantin-container">

    <div class="pcard">
        <div class="pcard-inner" style="padding: 25px;">
            <h3 style="margin: 0 0 15px 0; font-size: 18px;"><i class="fa-solid fa-store"></i> Edit Profil & Informasi Kantin</h3>
            <div class="pcard-divider" style="margin-bottom: 20px;"></div>

            <!-- Form tersembunyi untuk hapus foto kantin -->
            <form id="form-hapus-foto" action="index.php?section=kantin" method="POST" style="display: none;">
                <input type="hidden" name="_current_section" value="kantin">
                <input type="hidden" name="action" value="hapus_foto_kantin">
            </form>

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
                        <?php if (!empty($tokoData['foto_toko'])): ?>
                            <button type="button" onclick="if(confirm('Yakin ingin menghapus foto profil kantin ini?')) document.getElementById('form-hapus-foto').submit();" class="btn-delete-banner" style="margin-top: 8px; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;">
                                <i class="fa-solid fa-trash-can"></i> Hapus Foto
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="kantin-form-group form-col-2">
                        <label>Nama Kantin</label>
                        <input type="text" name="nama_toko" value="<?= htmlspecialchars($tokoData['nama_toko'] ?? '') ?>" required>
                    </div>
                    <div class="kantin-form-group form-col-1">
                        <label>Status Kantin</label>
                        <input type="hidden" name="status_toko" id="inputStatusToko" value="<?= htmlspecialchars($tokoData['status'] ?? 'buka') ?>">
                        <div style="display: flex; gap: 8px;">
                            <button type="button" id="btnStatusBuka" onclick="setStatusToko('buka', true)"
                                    style="flex: 1; padding: 10px; border-radius: 6px; font-weight: 700; border: 2.5px solid #22c55e; cursor: pointer; transition: all 0.2s; font-family: inherit; font-size: 13px;">
                                <i class="fa-solid fa-circle-check"></i> Buka
                            </button>
                            <button type="button" id="btnStatusTutup" onclick="setStatusToko('tutup', true)"
                                    style="flex: 1; padding: 10px; border-radius: 6px; font-weight: 700; border: 2.5px solid #dc2626; cursor: pointer; transition: all 0.2s; font-family: inherit; font-size: 13px;">
                                <i class="fa-solid fa-circle-xmark"></i> Tutup
                            </button>
                        </div>
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
            <script>
            function showStatusToast(message, type = 'success') {
                let container = document.getElementById('statusToastContainer');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'statusToastContainer';
                    container.style.cssText = `
                        position: fixed;
                        top: 24px;
                        right: 24px;
                        z-index: 999999;
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                        pointer-events: none;
                    `;
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.style.cssText = `
                    background: #ffffff;
                    color: #0f172a;
                    padding: 14px 20px;
                    border-radius: 16px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
                    border: 1.5px solid ${type === 'success' ? '#22c55e' : '#ef4444'};
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    font-family: 'Poppins', sans-serif;
                    font-size: 13.5px;
                    font-weight: 600;
                    opacity: 0;
                    transform: translateY(-20px);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    pointer-events: auto;
                `;

                const iconColor = type === 'success' ? '#22c55e' : '#ef4444';
                const iconClass = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';

                toast.innerHTML = `
                    <i class="fa-solid ${iconClass}" style="color: ${iconColor}; font-size: 16px;"></i>
                    <span>${message}</span>
                `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                }, 10);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            function setStatusToko(status, shouldSave = false) {
                document.getElementById('inputStatusToko').value = status;
                
                const btnBuka = document.getElementById('btnStatusBuka');
                const btnTutup = document.getElementById('btnStatusTutup');
                
                if (status === 'buka') {
                    btnBuka.style.background = '#22c55e';
                    btnBuka.style.color = '#ffffff';
                    btnBuka.style.borderColor = '#22c55e';
                    btnBuka.style.boxShadow = '0 2px 6px rgba(34,197,94,0.3)';
                    
                    btnTutup.style.background = '#ffffff';
                    btnTutup.style.color = '#dc2626';
                    btnTutup.style.borderColor = '#dc2626';
                    btnTutup.style.boxShadow = 'none';
                } else {
                    btnBuka.style.background = '#ffffff';
                    btnBuka.style.color = '#22c55e';
                    btnBuka.style.borderColor = '#22c55e';
                    btnBuka.style.boxShadow = 'none';
                    
                    btnTutup.style.background = '#dc2626';
                    btnTutup.style.color = '#ffffff';
                    btnTutup.style.borderColor = '#dc2626';
                    btnTutup.style.boxShadow = '0 2px 6px rgba(220,38,38,0.3)';
                }

                if (shouldSave) {
                    btnBuka.disabled = true;
                    btnTutup.disabled = true;

                    const formData = new FormData();
                    formData.append('action', 'toggle_status_ajax');
                    formData.append('status', status);

                    fetch('../actions/proses_kantin.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnBuka.disabled = false;
                        btnTutup.disabled = false;
                        if (data.success) {
                            showStatusToast('Kantin berhasil ' + (status === 'buka' ? 'di-Buka' : 'di-Tutup') + '!', 'success');
                        } else {
                            alert('Gagal memperbarui status: ' + data.message);
                            setStatusToko(status === 'buka' ? 'tutup' : 'buka', false);
                        }
                    })
                    .catch(err => {
                        btnBuka.disabled = false;
                        btnTutup.disabled = false;
                        console.error(err);
                        alert('Koneksi gagal saat memperbarui status!');
                        setStatusToko(status === 'buka' ? 'tutup' : 'buka', false);
                    });
                }
            }
            setStatusToko('<?= $tokoData['status'] ?? 'buka' ?>', false);
            </script>
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

    <!-- CARD FOTO LATAR BELAKANG TOKO -->
    <div class="pcard" style="margin-top: 25px;">
        <div class="pcard-inner" style="padding: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; font-size: 18px;"><i class="fa-solid fa-images"></i> Foto Latar Belakang Toko (Slideshow)</h3>
                <span class="banner-badge-status <?= $isLatarLimit ? 'locked' : 'active' ?>" style="font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 12px; background: <?= $isLatarLimit ? '#fef2f2; color: #ef4444; border: 1px solid #fee2e2;' : '#f0fdf4; color: #15803d; border: 1px solid #dcfce7;' ?>">
                    <?= $jumlahLatar ?> / 5 Foto Aktif
                </span>
            </div>
            <div class="pcard-divider" style="margin-bottom: 20px;"></div>

            <?php if ($isLatarLimit): ?>
                <div class="alert-limit-banner" style="background: #fff5f5; color: #c53030; padding: 12px; border-radius: 8px; font-size: 13.5px; border-left: 4px solid #f56565; margin-bottom: 20px; font-family: 'Poppins', sans-serif;">
                    <strong>Batas Maksimal Terpenuhi:</strong> Maksimal 5 foto latar belakang telah diunggah. Hapus salah satu foto lama untuk dapat menambahkan foto baru.
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start;">
                <!-- Upload & Canvas Tools (Only if not limit) -->
                <div style="flex: 1; min-width: 300px;">
                    <form action="index.php?section=kantin" method="POST" enctype="multipart/form-data" id="addLatarForm" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="_current_section" value="kantin">
                        <input type="hidden" name="action" value="add_latar_belakang">
                        
                        <!-- Hidden inputs for canvas values -->
                        <input type="hidden" name="latar_scale" id="latarScale" value="1.0">
                        <input type="hidden" name="latar_pan_norm_x" id="latarPanNormX" value="0">
                        <input type="hidden" name="latar_pan_norm_y" id="latarPanNormY" value="0">

                        <div class="kantin-form-group">
                            <label style="font-weight: bold; margin-bottom: 6px; display: block; font-size: 13.5px;">File Gambar Latar Belakang</label>
                            <input type="file" name="gambar_latar" id="gambarLatarInput" accept="image/jpeg, image/jpg, image/png, image/webp" required <?= $isLatarLimit ? 'disabled' : '' ?> style="font-size: 13px;">
                            <small style="color: #64748b; display: block; margin-top: 4px; line-height: 1.4;">
                                Format: <strong>JPG, JPEG, PNG, WEBP</strong> (Maksimal 2MB). Rekomendasi rasio banner lebar (seperti 3:1 atau 16:9).
                            </small>
                        </div>

                        <!-- Canvas Preview Box -->
                        <div class="kantin-form-group" style="margin-top: 5px;">
                            <label style="font-weight: bold; margin-bottom: 6px; display: block; font-size: 13.5px;">Pratinjau Posisi Kanvas (Hero Toko)</label>
                            <div class="latar-canvas-preview" id="latarCanvasPreview" style="position: relative; width: 100%; height: 180px; background: #e2e8f0; border-radius: 12px; overflow: hidden; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center;">
                                <img id="latarCanvasPreviewImg" src="" alt="Pratinjau Latar" style="display: none; position: absolute; max-width: none;">
                                <div class="canvas-placeholder" id="latarCanvasPlaceholder" style="text-align: center; pointer-events: none;">
                                    <i class="fa-solid fa-images" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px;"></i>
                                    <span style="display: block; font-size: 12px; color: #94a3b8; padding: 0 10px;">Pilih file gambar untuk mengatur posisi latar belakang</span>
                                </div>
                            </div>
                        </div>

                        <!-- Zoom and Position Controls -->
                        <div id="latarCanvasControls" style="display: none; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 5px;">
                            <div class="kantin-form-group" style="margin-bottom: 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Zoom Gambar:</label>
                                    <span id="latarScaleValText" style="font-size:12px; font-weight:bold; color:#27ae60;">1.0x</span>
                                </div>
                                <input type="range" id="latarSliderScale" min="1.0" max="3.0" step="0.05" value="1.0" style="margin-top: 4px; cursor: pointer; width: 100%;">
                            </div>
                            <div class="kantin-form-group" style="margin-bottom: 10px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Geser Kiri/Kanan (X):</label>
                                    <span id="latarXValText" style="font-size:12px; font-weight:bold; color:#27ae60;">Tengah</span>
                                </div>
                                <input type="range" id="latarSliderX" min="0" max="100" step="1" value="50" style="margin-top: 4px; cursor: pointer; width: 100%;">
                            </div>
                            <div class="kantin-form-group" style="margin-bottom: 5px;">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <label style="margin: 0; font-size:12px; font-weight:600; color:#475569;">Geser Atas/Bawah (Y):</label>
                                    <span id="latarYValText" style="font-size:12px; font-weight:bold; color:#27ae60;">Tengah</span>
                                </div>
                                <input type="range" id="latarSliderY" min="0" max="100" step="1" value="50" style="margin-top: 4px; cursor: pointer; width: 100%;">
                            </div>
                            <div style="margin-top: 8px; font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-circle-info" style="color: #27ae60;"></i>
                                <span><strong>Tips:</strong> Drag foto di atas atau geser slider untuk posisi terbaik.</span>
                            </div>
                        </div>

                        <button type="submit" class="pcard-btn" <?= $isLatarLimit ? 'disabled' : '' ?>
                                style="width: 100%; padding: 12px; font-size: 14px; font-weight: bold; background: <?= $isLatarLimit ? '#94a3b8' : '#27ae60' ?>; color: #fff; border: none; border-radius: 6px; cursor: <?= $isLatarLimit ? 'not-allowed' : 'pointer' ?>; margin-top: 5px; transition: background 0.2s;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Simpan Foto Latar Belakang
                        </button>
                    </form>
                </div>

                <!-- Existing background photos grid with reordering -->
                <div style="flex: 1.2; min-width: 320px;">
                    <label style="font-weight: bold; margin-bottom: 12px; display: block; font-size: 14px;"><i class="fa-solid fa-list-ol"></i> Daftar & Urutan Slideshow (Drag untuk Reorder)</label>
                    
                    <div class="latar-grid" id="latarListGrid" style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (!$queryLatar || mysqli_num_rows($queryLatar) == 0): ?>
                            <div style="text-align: center; padding: 40px 20px; border: 1px dashed #cbd5e1; border-radius: 12px; color: #94a3b8; background: #f8fafc;">
                                <i class="fa-solid fa-images" style="font-size: 40px; margin-bottom: 10px; color: #cbd5e1; display: block; margin-left: auto; margin-right: auto;"></i>
                                <span style="font-size: 13px;">Belum ada foto latar belakang toko. Halaman pembeli akan menggunakan foto utama kantin secara default.</span>
                            </div>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($queryLatar)): 
                                $latarCanvasData = bannerCanvasDataAttrs($row['canvas_config'] ?? '');
                            ?>
                                <div class="latar-item-row" data-id="<?= $row['id'] ?>" style="display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 10px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); cursor: grab; position: relative;" draggable="true">
                                    <div class="latar-handle" style="color: #94a3b8; font-size: 18px; padding: 0 5px; cursor: grab;">
                                        <i class="fa-solid fa-grip-vertical"></i>
                                    </div>
                                    <div class="latar-order-badge" style="width: 24px; height: 24px; border-radius: 50%; background: #e8f5e9; color: #2e7d32; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                                        <?= (int)$row['urutan'] + 1 ?>
                                    </div>
                                    <!-- Canvas Preview thumbnail -->
                                    <div class="thumbnail-canvas-container banner-canvas-viewport" <?= $latarCanvasData ?> style="width: 120px; height: 50px; border-radius: 6px; overflow: hidden; background: #e2e8f0; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                        <img src="../../../assets/img/latar_belakang/<?= htmlspecialchars($row['gambar']) ?>?v=<?= time() ?>" 
                                             class="banner-thumbnail"
                                             alt="Foto Latar"
                                             style="display: block;"
                                             onerror="this.onerror=null; this.src='../../../assets/img/promo_banner.png';">
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <span style="font-size: 11px; color: #64748b; display: block; word-break: break-all;"><?= htmlspecialchars($row['gambar']) ?></span>
                                    </div>
                                    <form action="index.php?section=kantin" method="POST" onsubmit="return confirm('Yakin ingin menghapus foto latar belakang ini?');" style="margin: 0; padding: 0;">
                                        <input type="hidden" name="_current_section" value="kantin">
                                        <input type="hidden" name="action" value="hapus_latar_belakang">
                                        <input type="hidden" name="id_latar" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn-delete-banner" style="padding: 8px 12px; font-size: 12px; margin: 0; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; border-radius: 6px; cursor: pointer;">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
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
                        <input type="hidden" name="banner_pan_norm_x" id="bannerPanNormX" value="0">
                        <input type="hidden" name="banner_pan_norm_y" id="bannerPanNormY" value="0">

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
                                <span><strong>Tips:</strong> Drag untuk geser area banner, geser sumbu z untuk mengatur zoom.</span>
                            </div>
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
                    
                    <div class="table-scroll" style="flex: 1; display: flex; flex-direction: column; width: 100%; overflow-x: auto;">
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

                                        // Dekode koordinat canvas jika ada
                                        $bannerCanvasData = bannerCanvasDataAttrs($row['canvas_config'] ?? '');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="thumbnail-canvas-container banner-canvas-viewport" <?= $bannerCanvasData ?>>
                                                    <img src="../../../assets/img/banner/<?= htmlspecialchars($row['gambar']) ?>?v=<?= time() ?>" 
                                                         class="banner-thumbnail"
                                                         alt="Banner"
                                                         onerror="this.onerror=null; this.src='../../../assets/img/promo_banner.png';">
                                                </div>
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
    const bannerPanNormX = document.getElementById('bannerPanNormX');
    const bannerPanNormY = document.getElementById('bannerPanNormY');

    let scale = 1.0;
    let panNormX = 0;
    let panNormY = 0;
    let imgReady = false;
    let isDragging = false;
    let startMouseX = 0;
    let startMouseY = 0;
    let startPanX = 0;
    let startPanY = 0;

    function panLabel(norm, axis) {
        if (Math.abs(norm) < 0.03) return 'Tengah';
        const pct = Math.round(Math.abs(norm) * 100);
        if (axis === 'X') return norm < 0 ? pct + '% ke kiri' : pct + '% ke kanan';
        return norm < 0 ? pct + '% ke atas' : pct + '% ke bawah';
    }

    function getMetrics() {
        if (!imgReady || typeof BannerCanvas === 'undefined') return null;
        return BannerCanvas.getMetrics(canvasPreview, canvasPreviewImg, scale);
    }

    function syncHiddenFields() {
        const cfg = BannerCanvas.buildSaveConfig(scale, panNormX, panNormY);
        bannerScale.value = cfg.scale;
        bannerPanNormX.value = cfg.panNormX;
        bannerPanNormY.value = cfg.panNormY;
    }

    function updateControlsUI() {
        const m = getMetrics();
        scaleValText.textContent = scale.toFixed(2) + 'x';

        if (!m) return;

        const canPanX = m.maxPanX > 0.5;
        const canPanY = m.maxPanY > 0.5;

        sliderX.disabled = !canPanX;
        sliderY.disabled = !canPanY;
        xValText.textContent = canPanX ? panLabel(panNormX, 'X') : 'Tidak tersedia';
        yValText.textContent = canPanY ? panLabel(panNormY, 'Y') : 'Tidak tersedia';
        sliderX.value = Math.round(panNormX * 50 + 50);
        sliderY.value = Math.round(panNormY * 50 + 50);
        syncHiddenFields();
    }

    function refreshCanvas() {
        if (!imgReady || typeof BannerCanvas === 'undefined') return;
        BannerCanvas.apply(canvasPreview, canvasPreviewImg, BannerCanvas.buildSaveConfig(scale, panNormX, panNormY));
        updateControlsUI();
    }

    function setScale(newScale) {
        const m = getMetrics();
        if (!m) {
            scale = newScale;
            refreshCanvas();
            return;
        }

        const { panX, panY } = BannerCanvas.normToPan(m, panNormX, panNormY);
        scale = BannerCanvas.clamp(newScale, 1, parseFloat(sliderScale.max));

        const m2 = BannerCanvas.getMetrics(canvasPreview, canvasPreviewImg, scale);
        if (m2) {
            const clampedPanX = BannerCanvas.clamp(panX, -m2.maxPanX, m2.maxPanX);
            const clampedPanY = BannerCanvas.clamp(panY, -m2.maxPanY, m2.maxPanY);
            const norm = BannerCanvas.panToNorm(m2, clampedPanX, clampedPanY);
            panNormX = norm.panNormX;
            panNormY = norm.panNormY;
        }

        sliderScale.value = scale;
        refreshCanvas();
    }

    function resetCanvasParams() {
        scale = 1.0;
        panNormX = 0;
        panNormY = 0;

        if (typeof BannerCanvas !== 'undefined' && imgReady) {
            const maxZoom = BannerCanvas.getMaxZoom(canvasPreview, canvasPreviewImg);
            sliderScale.min = 1;
            sliderScale.max = maxZoom.toFixed(2);
            sliderScale.step = 0.05;
        } else {
            sliderScale.min = 1;
            sliderScale.max = 3;
        }

        sliderScale.value = 1;
        sliderX.value = 50;
        sliderY.value = 50;
        refreshCanvas();
    }

    if (gambarBannerInput) {
        gambarBannerInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            imgReady = false;
            const reader = new FileReader();
            reader.onload = function(evt) {
                canvasPreviewImg.onload = function() {
                    imgReady = true;
                    canvasPreviewImg.style.display = 'block';
                    canvasPlaceholder.style.display = 'none';
                    canvasControls.style.display = 'block';
                    resetCanvasParams();
                };
                canvasPreviewImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (sliderScale) {
        sliderScale.addEventListener('input', function() {
            setScale(parseFloat(this.value));
        });
    }

    if (sliderX) {
        sliderX.addEventListener('input', function() {
            panNormX = BannerCanvas.clamp((parseInt(this.value, 10) - 50) / 50, -1, 1);
            refreshCanvas();
        });
    }

    if (sliderY) {
        sliderY.addEventListener('input', function() {
            panNormY = BannerCanvas.clamp((parseInt(this.value, 10) - 50) / 50, -1, 1);
            refreshCanvas();
        });
    }

    if (canvasPreview) {
        canvasPreview.style.cursor = 'grab';
        canvasPreview.style.touchAction = 'none';

        canvasPreview.addEventListener('mousedown', function(e) {
            if (!imgReady) return;
            const m = getMetrics();
            if (!m) return;
            e.preventDefault();
            isDragging = true;
            startMouseX = e.clientX;
            startMouseY = e.clientY;
            const pan = BannerCanvas.normToPan(m, panNormX, panNormY);
            startPanX = pan.panX;
            startPanY = pan.panY;
            canvasPreview.style.cursor = 'grabbing';
        });

        canvasPreview.addEventListener('wheel', function(e) {
            if (!imgReady) return;
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.08 : 0.08;
            setScale(scale + delta);
        }, { passive: false });

        window.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            const m = getMetrics();
            if (!m) return;

            const dx = e.clientX - startMouseX;
            const dy = e.clientY - startMouseY;
            const newPanX = BannerCanvas.clamp(startPanX + dx, -m.maxPanX, m.maxPanX);
            const newPanY = BannerCanvas.clamp(startPanY + dy, -m.maxPanY, m.maxPanY);
            const norm = BannerCanvas.panToNorm(m, newPanX, newPanY);
            panNormX = norm.panNormX;
            panNormY = norm.panNormY;
            refreshCanvas();
        });

        window.addEventListener('mouseup', function() {
            if (!isDragging) return;
            isDragging = false;
            canvasPreview.style.cursor = 'grab';
        });

        canvasPreview.addEventListener('touchstart', function(e) {
            if (!imgReady) return;
            const m = getMetrics();
            if (!m) return;
            isDragging = true;
            startMouseX = e.touches[0].clientX;
            startMouseY = e.touches[0].clientY;
            const pan = BannerCanvas.normToPan(m, panNormX, panNormY);
            startPanX = pan.panX;
            startPanY = pan.panY;
        }, { passive: true });

        window.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            const m = getMetrics();
            if (!m) return;

            const dx = e.touches[0].clientX - startMouseX;
            const dy = e.touches[0].clientY - startMouseY;
            const newPanX = BannerCanvas.clamp(startPanX + dx, -m.maxPanX, m.maxPanX);
            const newPanY = BannerCanvas.clamp(startPanY + dy, -m.maxPanY, m.maxPanY);
            const norm = BannerCanvas.panToNorm(m, newPanX, newPanY);
            panNormX = norm.panNormX;
            panNormY = norm.panNormY;
            refreshCanvas();
        }, { passive: true });

        window.addEventListener('touchend', function() {
            isDragging = false;
        });
    }

    // Gunakan ResizeObserver agar canvas otomatis re-render saat ukuran container berubah
    // (akibat browser zoom Ctrl+/Ctrl-, sidebar toggle, resize window, dsb.)
    if (canvasPreview && typeof ResizeObserver !== 'undefined') {
        let resizeRafId = null;
        const ro = new ResizeObserver(function() {
            // debounce agar tidak terlalu sering memanggil refreshCanvas
            if (resizeRafId) cancelAnimationFrame(resizeRafId);
            resizeRafId = requestAnimationFrame(function() {
                refreshCanvas();
                BannerCanvas.initAll(canvasPreview.closest('[id]') || document);
            });
        });
        ro.observe(canvasPreview);
    } else {
        // Fallback untuk browser lama yang tidak mendukung ResizeObserver
        window.addEventListener('resize', function() {
            refreshCanvas();
            BannerCanvas.initAll(canvasPreview ? canvasPreview.closest('[id]') : document);
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

    // -------------------------------------------------------------
    // 3. FOTO LATAR BELAKANG CANVAS PREVIEW LOGIC
    // -------------------------------------------------------------
    const gambarLatarInput = document.getElementById('gambarLatarInput');
    const latarCanvasPreview = document.getElementById('latarCanvasPreview');
    const latarCanvasPreviewImg = document.getElementById('latarCanvasPreviewImg');
    const latarCanvasPlaceholder = document.getElementById('latarCanvasPlaceholder');
    const latarCanvasControls = document.getElementById('latarCanvasControls');
    
    const latarSliderScale = document.getElementById('latarSliderScale');
    const latarSliderX = document.getElementById('latarSliderX');
    const latarSliderY = document.getElementById('latarSliderY');
    
    const latarScaleValText = document.getElementById('latarScaleValText');
    const latarXValText = document.getElementById('latarXValText');
    const latarYValText = document.getElementById('latarYValText');
    
    const latarScale = document.getElementById('latarScale');
    const latarPanNormX = document.getElementById('latarPanNormX');
    const latarPanNormY = document.getElementById('latarPanNormY');

    let l_scale = 1.0;
    let l_panNormX = 0;
    let l_panNormY = 0;
    let l_imgReady = false;
    let l_isDragging = false;
    let l_startMouseX = 0;
    let l_startMouseY = 0;
    let l_startPanX = 0;
    let l_startPanY = 0;

    function getLatarMetrics() {
        if (!l_imgReady || typeof BannerCanvas === 'undefined') return null;
        return BannerCanvas.getMetrics(latarCanvasPreview, latarCanvasPreviewImg, l_scale);
    }

    function syncLatarHiddenFields() {
        const cfg = BannerCanvas.buildSaveConfig(l_scale, l_panNormX, l_panNormY);
        latarScale.value = cfg.scale;
        latarPanNormX.value = cfg.panNormX;
        latarPanNormY.value = cfg.panNormY;
    }

    function updateLatarControlsUI() {
        const m = getLatarMetrics();
        latarScaleValText.textContent = l_scale.toFixed(2) + 'x';

        if (!m) return;

        const canPanX = m.maxPanX > 0.5;
        const canPanY = m.maxPanY > 0.5;

        latarSliderX.disabled = !canPanX;
        latarSliderY.disabled = !canPanY;
        latarXValText.textContent = canPanX ? panLabel(l_panNormX, 'X') : 'Tidak tersedia';
        latarYValText.textContent = canPanY ? panLabel(l_panNormY, 'Y') : 'Tidak tersedia';
        latarSliderX.value = Math.round(l_panNormX * 50 + 50);
        latarSliderY.value = Math.round(l_panNormY * 50 + 50);
        syncLatarHiddenFields();
    }

    function refreshLatarCanvas() {
        if (!l_imgReady || typeof BannerCanvas === 'undefined') return;
        BannerCanvas.apply(latarCanvasPreview, latarCanvasPreviewImg, BannerCanvas.buildSaveConfig(l_scale, l_panNormX, l_panNormY));
        updateLatarControlsUI();
    }

    function setLatarScale(newScale) {
        const m = getLatarMetrics();
        if (!m) {
            l_scale = newScale;
            refreshLatarCanvas();
            return;
        }

        const { panX, panY } = BannerCanvas.normToPan(m, l_panNormX, l_panNormY);
        l_scale = BannerCanvas.clamp(newScale, 1, parseFloat(latarSliderScale.max));

        const m2 = BannerCanvas.getMetrics(latarCanvasPreview, latarCanvasPreviewImg, l_scale);
        if (m2) {
            const clampedPanX = BannerCanvas.clamp(panX, -m2.maxPanX, m2.maxPanX);
            const clampedPanY = BannerCanvas.clamp(panY, -m2.maxPanY, m2.maxPanY);
            const norm = BannerCanvas.panToNorm(m2, clampedPanX, clampedPanY);
            l_panNormX = norm.panNormX;
            l_panNormY = norm.panNormY;
        }

        latarSliderScale.value = l_scale;
        refreshLatarCanvas();
    }

    function resetLatarCanvasParams() {
        l_scale = 1.0;
        l_panNormX = 0;
        l_panNormY = 0;

        if (typeof BannerCanvas !== 'undefined' && l_imgReady) {
            const maxZoom = BannerCanvas.getMaxZoom(latarCanvasPreview, latarCanvasPreviewImg);
            latarSliderScale.min = 1;
            latarSliderScale.max = maxZoom.toFixed(2);
            latarSliderScale.step = 0.05;
        } else {
            latarSliderScale.min = 1;
            latarSliderScale.max = 3;
        }

        latarSliderScale.value = 1;
        latarSliderX.value = 50;
        latarSliderY.value = 50;
        refreshLatarCanvas();
    }

    if (gambarLatarInput) {
        gambarLatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            l_imgReady = false;
            const reader = new FileReader();
            reader.onload = function(evt) {
                latarCanvasPreviewImg.onload = function() {
                    l_imgReady = true;
                    latarCanvasPreviewImg.style.display = 'block';
                    latarCanvasPlaceholder.style.display = 'none';
                    latarCanvasControls.style.display = 'block';
                    resetLatarCanvasParams();
                };
                latarCanvasPreviewImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    if (latarSliderScale) {
        latarSliderScale.addEventListener('input', function() {
            setLatarScale(parseFloat(this.value));
        });
    }

    if (latarSliderX) {
        latarSliderX.addEventListener('input', function() {
            l_panNormX = BannerCanvas.clamp((parseInt(this.value, 10) - 50) / 50, -1, 1);
            refreshLatarCanvas();
        });
    }

    if (latarSliderY) {
        latarSliderY.addEventListener('input', function() {
            l_panNormY = BannerCanvas.clamp((parseInt(this.value, 10) - 50) / 50, -1, 1);
            refreshLatarCanvas();
        });
    }

    if (latarCanvasPreview) {
        latarCanvasPreview.style.cursor = 'grab';
        latarCanvasPreview.style.touchAction = 'none';

        latarCanvasPreview.addEventListener('mousedown', function(e) {
            if (!l_imgReady) return;
            const m = getLatarMetrics();
            if (!m) return;
            e.preventDefault();
            l_isDragging = true;
            l_startMouseX = e.clientX;
            l_startMouseY = e.clientY;
            const pan = BannerCanvas.normToPan(m, l_panNormX, l_panNormY);
            l_startPanX = pan.panX;
            l_startPanY = pan.panY;
            latarCanvasPreview.style.cursor = 'grabbing';
        });

        latarCanvasPreview.addEventListener('wheel', function(e) {
            if (!l_imgReady) return;
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.08 : 0.08;
            setLatarScale(l_scale + delta);
        }, { passive: false });

        window.addEventListener('mousemove', function(e) {
            if (!l_isDragging) return;
            const m = getLatarMetrics();
            if (!m) return;

            const dx = e.clientX - l_startMouseX;
            const dy = e.clientY - l_startMouseY;
            const newPanX = BannerCanvas.clamp(l_startPanX + dx, -m.maxPanX, m.maxPanX);
            const newPanY = BannerCanvas.clamp(l_startPanY + dy, -m.maxPanY, m.maxPanY);
            const norm = BannerCanvas.panToNorm(m, newPanX, newPanY);
            l_panNormX = norm.panNormX;
            l_panNormY = norm.panNormY;
            refreshLatarCanvas();
        });

        window.addEventListener('mouseup', function() {
            if (!l_isDragging) return;
            l_isDragging = false;
            latarCanvasPreview.style.cursor = 'grab';
        });

        latarCanvasPreview.addEventListener('touchstart', function(e) {
            if (!l_imgReady) return;
            const m = getLatarMetrics();
            if (!m) return;
            l_isDragging = true;
            l_startMouseX = e.touches[0].clientX;
            l_startMouseY = e.touches[0].clientY;
            const pan = BannerCanvas.normToPan(m, l_panNormX, l_panNormY);
            l_startPanX = pan.panX;
            l_startPanY = pan.panY;
        }, { passive: true });

        window.addEventListener('touchmove', function(e) {
            if (!l_isDragging) return;
            const m = getLatarMetrics();
            if (!m) return;

            const dx = e.touches[0].clientX - l_startMouseX;
            const dy = e.touches[0].clientY - l_startMouseY;
            const newPanX = BannerCanvas.clamp(l_startPanX + dx, -m.maxPanX, m.maxPanX);
            const newPanY = BannerCanvas.clamp(l_startPanY + dy, -m.maxPanY, m.maxPanY);
            const norm = BannerCanvas.panToNorm(m, newPanX, newPanY);
            l_panNormX = norm.panNormX;
            l_panNormY = norm.panNormY;
            refreshLatarCanvas();
        }, { passive: true });

        window.addEventListener('touchend', function() {
            l_isDragging = false;
        });
    }

    if (latarCanvasPreview && typeof ResizeObserver !== 'undefined') {
        let l_resizeRafId = null;
        const l_ro = new ResizeObserver(function() {
            if (l_resizeRafId) cancelAnimationFrame(l_resizeRafId);
            l_resizeRafId = requestAnimationFrame(function() {
                refreshLatarCanvas();
                BannerCanvas.initAll(latarCanvasPreview.closest('[id]') || document);
            });
        });
        l_ro.observe(latarCanvasPreview);
    } else {
        window.addEventListener('resize', function() {
            refreshLatarCanvas();
            BannerCanvas.initAll(latarCanvasPreview ? latarCanvasPreview.closest('[id]') : document);
        });
    }

    // Initialize Canvas on thumbnails in case they are not initialized
    if (typeof BannerCanvas !== 'undefined') {
        BannerCanvas.initAll(document.getElementById('latarListGrid') || document);
    }

    // -------------------------------------------------------------
    // 4. DRAG AND DROP FOR BACKGROUND PHOTOS REORDERING
    // -------------------------------------------------------------
    const latarListGrid = document.getElementById('latarListGrid');
    if (latarListGrid) {
        const rows = latarListGrid.querySelectorAll('.latar-item-row');
        let dragSrcEl = null;

        rows.forEach(function(row) {
            row.addEventListener('dragstart', function(e) {
                dragSrcEl = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
                this.style.opacity = '0.4';
                this.classList.add('dragging');
            });

            row.addEventListener('dragover', function(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';
                return false;
            });

            row.addEventListener('dragenter', function(e) {
                this.classList.add('over');
            });

            row.addEventListener('dragleave', function(e) {
                this.classList.remove('over');
            });

            row.addEventListener('drop', function(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }

                if (dragSrcEl !== this) {
                    const allItems = Array.from(latarListGrid.querySelectorAll('.latar-item-row'));
                    const srcIndex = allItems.indexOf(dragSrcEl);
                    const targetIndex = allItems.indexOf(this);

                    if (srcIndex < targetIndex) {
                        latarListGrid.insertBefore(dragSrcEl, this.nextSibling);
                    } else {
                        latarListGrid.insertBefore(dragSrcEl, this);
                    }

                    saveNewLatarOrder();
                }
                return false;
            });

            row.addEventListener('dragend', function() {
                this.style.opacity = '1';
                this.classList.remove('dragging');
                rows.forEach(function(r) {
                    r.classList.remove('over');
                });
            });
        });

        function saveNewLatarOrder() {
            const allItems = Array.from(latarListGrid.querySelectorAll('.latar-item-row'));
            const ids = allItems.map(item => item.getAttribute('data-id'));
            
            allItems.forEach((item, idx) => {
                const badge = item.querySelector('.latar-order-badge');
                if (badge) badge.textContent = idx + 1;
            });

            const formData = new FormData();
            formData.append('action', 'update_urutan_latar');
            formData.append('ids_json', JSON.stringify(ids));
            formData.append('is_ajax', '1');

            fetch('index.php?section=kantin', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log('Order updated successfully');
                } else {
                    alert('Gagal memperbarui urutan: ' + (data.message || 'unknown'));
                }
            })
            .catch(err => {
                console.error('Error updating order:', err);
                alert('Koneksi terputus saat memperbarui urutan.');
            });
        }
    }
});
</script>