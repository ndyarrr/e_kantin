<?php
// views/pembeli/sections/pesanan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = $koneksi ?? $conn ?? null;
if (!$db) {
    echo "<div class='empty-state'>Koneksi database tidak tersedia.</div>";
    return;
}

$s_role = $user_role ?? $_SESSION['user_role'] ?? 'siswa';
$s_id = $user_id ?? $_SESSION['user_id'] ?? '';
$col_pembeli = ($s_role === 'siswa') ? 'nisn_pembeli' : 'nuptk_pembeli';

// Query pesanan pembeli
$pesanan_list = [];
$q_pesanan = mysqli_query($db, "
    SELECT p.*, t.nama_toko, t.id_toko, t.qris_image
    FROM pesanan p
    JOIN toko t ON p.id_toko = t.id_toko
    WHERE p.$col_pembeli = '$s_id'
    ORDER BY p.waktu_pesan DESC
");

echo "<!-- DEBUG PESANAN: role=$s_role, id=$s_id, col=$col_pembeli, rows=" . ($q_pesanan ? mysqli_num_rows($q_pesanan) : 'query failed') . " -->\n";

if ($q_pesanan) {
    while ($r = mysqli_fetch_assoc($q_pesanan)) {
        // Ambil detail items untuk pesanan ini
        $id_pesanan = $r['id_pesanan'];
        $q_detail = mysqli_query($db, "
            SELECT dp.*, m.nama_menu, m.foto_menu 
            FROM detail_pesanan dp
            JOIN menu m ON dp.id_menu = m.id_menu
            WHERE dp.id_pesanan = $id_pesanan
        ");
        $details = [];
        if ($q_detail) {
            while ($d = mysqli_fetch_assoc($q_detail)) {
                $details[] = $d;
            }
        }
        $r['items'] = $details;

        // Ambil status pembayaran
        $q_pay = mysqli_query($db, "SELECT status, metode, bukti_foto FROM pembayaran WHERE id_pesanan = $id_pesanan LIMIT 1");
        $pay = mysqli_fetch_assoc($q_pay) ?? ['status' => 'belum_bayar', 'metode' => 'tunai', 'bukti_foto' => ''];
        $r['pembayaran'] = $pay;

        $pesanan_list[] = $r;
    }
}
?>
<!-- ═══════ SECTION: PESANAN ═══════ -->
<div class="page-section" id="section-pesanan">
    <section class="section-block">
        <h2 class="section-title">Pesanan Saya</h2>
        
        <?php if (empty($pesanan_list)): ?>
            <div class="empty-state" id="pesananEmpty">
                <i class="fa-solid fa-receipt"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Pesanan yang kamu buat akan muncul di sini. Yuk mulai pesan dari kantin favoritmu!</p>
                <br>
                <button class="btn-promo-blank" onclick="switchNav('kantin')"
                    style="font-size:13px;padding:10px 28px">Jelajahi Kantin</button>
            </div>
        <?php else: ?>
            <style>
                .pesanan-container {
                    display: flex;
                    flex-direction: column;
                    gap: 16px;
                    margin-top: 16px;
                }
                .pesanan-card {
                    background: #ffffff;
                    border: 1px solid #f1f5f9;
                    border-radius: 20px;
                    padding: 18px;
                    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.015);
                    display: flex;
                    flex-direction: column;
                    gap: 14px;
                    box-sizing: border-box;
                }
                .pesanan-card-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-bottom: 1px dashed #f1f5f9;
                    padding-bottom: 12px;
                }
                .pesanan-canteen-info {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }
                .pesanan-canteen-name {
                    font-size: 15px;
                    font-weight: 800;
                    color: #1e293b;
                }
                .pesanan-date {
                    font-size: 11px;
                    color: #94a3b8;
                    font-weight: 500;
                }
                .pesanan-status-badge {
                    font-size: 11px;
                    font-weight: 800;
                    padding: 5px 12px;
                    border-radius: 20px;
                    text-transform: capitalize;
                }
                .status-menunggu {
                    background: #fff8e1;
                    color: #ffb300;
                }
                .status-diproses {
                    background: #e3f2fd;
                    color: #1e88e5;
                }
                .status-siap {
                    background: #e8f5e9;
                    color: #4caf50;
                }
                .status-selesai {
                    background: #e0f2f1;
                    color: #00897b;
                }
                .status-dibatalkan {
                    background: #ffebee;
                    color: #e53935;
                }
                
                .pesanan-items-list {
                    display: flex;
                    flex-direction: column;
                    gap: 12px;
                }
                .pesanan-item-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .pesanan-item-left {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex: 1;
                    min-width: 0;
                }
                .pesanan-item-img-wrap {
                    width: 44px;
                    height: 44px;
                    border-radius: 8px;
                    overflow: hidden;
                    border: 1px solid #f1f5f9;
                    flex-shrink: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f8fafc;
                }
                .pesanan-item-img-wrap img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .pesanan-item-details {
                    display: flex;
                    flex-direction: column;
                    min-width: 0;
                }
                .pesanan-item-name {
                    font-size: 13px;
                    font-weight: 700;
                    color: #1e293b;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .pesanan-item-note {
                    font-size: 10.5px;
                    color: #64748b;
                    font-style: italic;
                    margin-top: 1px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .pesanan-item-qty {
                    font-size: 11px;
                    color: #64748b;
                    font-weight: 600;
                    margin-top: 1px;
                }
                .pesanan-item-price {
                    font-size: 13px;
                    font-weight: 700;
                    color: #475569;
                    flex-shrink: 0;
                }
                
                .pesanan-card-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 1px solid #f1f5f9;
                    padding-top: 12px;
                    margin-top: 4px;
                }
                .pesanan-total-info {
                    display: flex;
                    flex-direction: column;
                    line-height: 1.3;
                }
                .pesanan-total-label {
                    font-size: 10px;
                    color: #94a3b8;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .pesanan-total-val {
                    font-size: 15px;
                    font-weight: 900;
                    color: #0f172a;
                }
                
                .pesanan-footer-right {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .pesanan-payment-status {
                    font-size: 11.5px;
                    font-weight: 700;
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                }
                .pay-lunas {
                    color: #4caf50;
                }
                .pay-belum_bayar {
                    color: #e53935;
                }
                
                .btn-chat-kantin {
                    background: #f1f5f9;
                    border: none;
                    border-radius: 12px;
                    padding: 8px 14px;
                    font-size: 11.5px;
                    font-weight: 700;
                    color: #475569;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    transition: all 0.2s;
                }
                .btn-chat-kantin:hover {
                    background: #e2e8f0;
                    color: #1e293b;
                }
                .btn-batalkan-pesanan:hover {
                    background: #fecaca !important;
                    color: #b91c1c !important;
                    border-color: #f87171 !important;
                }
            </style>
            
            <div class="pesanan-container">
                <?php foreach ($pesanan_list as $pesanan): 
                    $badge_class = 'status-' . $pesanan['status'];
                    $status_text = $pesanan['status'];
                    if ($pesanan['status'] === 'menunggu') {
                        $status_text = 'Menunggu Konfirmasi';
                    } elseif ($pesanan['status'] === 'dikonfirmasi') {
                        $badge_class = 'status-diproses';
                        $status_text = 'Sedang Disiapkan';
                    } elseif ($pesanan['status'] === 'siap_diambil') {
                        $badge_class = 'status-siap';
                        $status_text = 'Siap Diambil';
                    } elseif ($pesanan['status'] === 'selesai') {
                        $status_text = 'Selesai';
                    } elseif ($pesanan['status'] === 'dibatalkan') {
                        $status_text = 'Dibatalkan';
                    }
                    
                    $waktu_format = date('d M Y, H:i', strtotime($pesanan['waktu_pesan']));
                    $is_lunas = isset($pesanan['pembayaran']['status']) && $pesanan['pembayaran']['status'] === 'lunas';
                ?>
                    <div class="pesanan-card">
                        <div class="pesanan-card-header">
                            <div class="pesanan-canteen-info">
                                <span class="pesanan-canteen-name"><?= htmlspecialchars($pesanan['nama_toko']) ?></span>
                                <span class="pesanan-date"><?= $waktu_format ?></span>
                            </div>
                            <span class="pesanan-status-badge <?= $badge_class ?>"><?= $status_text ?></span>
                        </div>
                        
                        <div class="pesanan-items-list">
                            <?php foreach ($pesanan['items'] as $item): 
                                $foto = $item['foto_menu'] ?? '';
                                $img_src = !empty($foto) && file_exists(__DIR__ . '/../../../assets/img/menu/' . $foto) ? '../../assets/img/menu/' . $foto : '';
                                $catatan = !empty($item['catatan']) ? $item['catatan'] : 'Tidak ada catatan';
                            ?>
                                <div class="pesanan-item-row">
                                    <div class="pesanan-item-left">
                                        <div class="pesanan-item-img-wrap">
                                            <?php if ($img_src): ?>
                                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['nama_menu']) ?>">
                                            <?php else: ?>
                                                <i class="fa-solid fa-utensils" style="color: #cbd5e1; font-size: 16px;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pesanan-item-details">
                                            <span class="pesanan-item-name"><?= htmlspecialchars($item['nama_menu']) ?></span>
                                            <span class="pesanan-item-note">Catatan: <?= htmlspecialchars($catatan) ?></span>
                                            <span class="pesanan-item-qty">Jumlah: <?= $item['jumlah'] ?>x</span>
                                        </div>
                                    </div>
                                    <span class="pesanan-item-price">Rp. <?= number_format($item['harga_satuan'] * $item['jumlah'], 0, ',', '.') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php 
                            $is_transfer = ($pesanan['pembayaran']['metode'] === 'transfer');
                        ?>
                        <div class="pesanan-card-footer">
                            <div class="pesanan-total-info">
                                <span class="pesanan-total-label">Total Pembayaran</span>
                                <span class="pesanan-total-val">Rp. <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                            </div>
                            <div class="pesanan-footer-right" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                                <?php if ($is_lunas): ?>
                                    <span class="pesanan-payment-status pay-lunas">
                                        <i class="fa-solid fa-circle-check"></i> Lunas
                                        <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $is_transfer ? 'QRIS' : 'Tunai' ?>)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="pesanan-payment-status pay-belum_bayar" style="display: flex; flex-direction: column; align-items: flex-end; gap: 2px;">
                                        <span>
                                            <i class="fa-solid fa-circle-xmark"></i> Belum Bayar <small style="color: #64748b; font-weight: 500; margin-left: 2px;">(<?= $is_transfer ? 'QRIS' : 'Tunai' ?>)</small>
                                        </span>
                                        <?php if ($is_transfer): ?>
                                            <span style="font-size: 10.5px; color: #0284c7; font-weight: 600;">Menunggu konfirmasi penjual</span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($is_transfer && !$is_lunas && !empty($pesanan['qris_image'])): 
                                    $has_uploaded_proof = !empty($pesanan['pembayaran']['bukti_foto']);
                                    $btn_text = $has_uploaded_proof ? 'Lihat/Kirim Ulang Bukti' : 'Bayar QRIS';
                                    $btn_icon = $has_uploaded_proof ? 'fa-file-invoice' : 'fa-qrcode';
                                    $btn_bg = $has_uploaded_proof ? '#0284c7' : '#16a34a'; // Sky blue for uploaded, green for not uploaded
                                ?>
                                    <button class="btn-bayar-qris" id="btn-qris-pay-<?= $pesanan['id_pesanan'] ?>" onclick="openPesananQrisModal('<?= htmlspecialchars(addslashes($pesanan['nama_toko']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($pesanan['qris_image']), ENT_QUOTES) ?>', <?= $pesanan['id_pesanan'] ?>, '<?= htmlspecialchars(addslashes($pesanan['pembayaran']['bukti_foto'] ?? ''), ENT_QUOTES) ?>')" style="background: <?= $btn_bg ?>; border: none; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; color: #ffffff; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <i class="fa-solid <?= $btn_icon ?>"></i> <?= $btn_text ?>
                                    </button>
                                <?php endif; ?>

                                <?php if ($pesanan['status'] === 'menunggu'): ?>
                                    <button class="btn-batalkan-pesanan" onclick="batalkanPesanan(<?= $pesanan['id_pesanan'] ?>)" style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 12px; padding: 8px 14px; font-size: 11.5px; font-weight: 700; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                        <i class="fa-solid fa-ban"></i> Batalkan Pesanan
                                    </button>
                                <?php endif; ?>

                                <button class="btn-chat-kantin" onclick="switchNav('chat'); setTimeout(() => { bukaRoomChat('toko_<?= $pesanan['id_toko'] ?>', '<?= htmlspecialchars(addslashes($pesanan['nama_toko']), ENT_QUOTES) ?>'); }, 200);">
                                    <i class="fa-solid fa-comment-dots"></i> Hubungi Kantin
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
function openPesananQrisModal(namaToko, qrisImage, idPesanan, existingBukti) {
    let oldModal = document.getElementById('qrisPaymentModal');
    if (oldModal) oldModal.remove();

    const hasExisting = existingBukti && existingBukti.trim() !== '';

    const modal = document.createElement('div');
    modal.id = 'qrisPaymentModal';
    modal.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999999;
        opacity: 0; transition: opacity 0.3s ease;
        padding: 12px; box-sizing: border-box;
    `;

    const card = document.createElement('div');
    card.style.cssText = `
        background: #ffffff;
        width: 100%; max-width: 400px;
        border-radius: 24px; padding: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        text-align: center;
        transform: scale(0.9); transition: transform 0.3s ease;
        max-height: 90vh; display: flex; flex-direction: column;
        box-sizing: border-box;
    `;

    // Uploader area HTML
    let uploaderHtml = '';
    if (hasExisting) {
        uploaderHtml = `
        <div style="margin-bottom:16px; text-align:left;">
            <div style="font-size:12px;font-weight:700;color:#0f172a;font-family:'Poppins',sans-serif;margin-bottom:8px;">
                <i class="fa-solid fa-file-image" style="color:#0284c7;margin-right:4px;"></i> Bukti Yang Sudah Diunggah
            </div>
            <div style="border-radius:12px;overflow:hidden;border:2px solid #0284c7;position:relative;background:#f8fafc;">
                <a href="../../assets/img/bukti_bayar/${existingBukti}" target="_blank">
                    <img src="../../assets/img/bukti_bayar/${existingBukti}" style="width:100%;max-height:140px;object-fit:cover;display:block;" alt="Bukti Bayar">
                </a>
                <div style="position:absolute;top:6px;right:6px;background:rgba(2,132,199,0.9);color:#fff;font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;">
                    <i class="fa-solid fa-circle-check" style="margin-right:3px;"></i>Terkirim
                </div>
            </div>
            <p style="font-size:11px;color:#64748b;margin:6px 0 0;font-family:'Poppins',sans-serif;">Anda sudah mengirim bukti. Bisa kirim ulang jika ada perubahan.</p>
        </div>`;
    }

    card.innerHTML = `
        <div style="overflow-y:auto; flex:1; padding-right:2px; position:relative;">
            <button id="btnPesananQrisX" style="position:absolute;top:0;right:0;width:32px;height:32px;border-radius:50%;border:none;background:#f1f5f9;color:#64748b;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;z-index:1;" onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';" onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b';">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="font-size:26px;color:#16a34a;margin-bottom:10px;">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <h3 style="margin:0 0 4px;font-size:16px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">QRIS - ${namaToko}</h3>
            <p style="margin:0 0 14px;font-size:12px;color:#64748b;font-family:'Poppins',sans-serif;">Scan QR lalu unggah bukti pembayaran Anda.</p>

            <div style="background:#fff;padding:10px;border-radius:14px;border:1.5px solid #e2e8f0;display:inline-block;margin-bottom:14px;">
                <img src="../../assets/img/qris/${qrisImage}" alt="QRIS" style="max-width:200px;width:100%;height:auto;display:block;border-radius:6px;">
            </div>

            ${uploaderHtml}

            <!-- Upload Area -->
            <div style="background:#f8fafc;border:1.5px dashed #cbd5e1;padding:16px;border-radius:14px;margin-bottom:16px;cursor:pointer;transition:all 0.2s;text-align:center;position:relative;" id="pesananUploaderArea" onclick="document.getElementById('pesananFileInput').click()">
                <input type="file" id="pesananFileInput" accept="image/*" style="display:none;">
                <div id="pesananUploadPrompt">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size:26px;color:#64748b;margin-bottom:6px;display:block;"></i>
                    <span style="font-size:13px;font-weight:700;color:#475569;display:block;">${hasExisting ? 'Kirim Ulang Bukti' : 'Unggah Bukti Transfer'}</span>
                    <span style="font-size:11px;color:#94a3b8;display:block;margin-top:2px;">Ketuk untuk memilih foto struk/bukti bayar</span>
                </div>
                <div id="pesananUploadPreviewWrap" style="display:none;flex-direction:column;align-items:center;gap:8px;">
                    <div style="width:72px;height:72px;border-radius:10px;overflow:hidden;border:1.5px solid #16a34a;background:#fff;padding:2px;">
                        <img id="pesananUploadPreview" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                    </div>
                    <span id="pesananFileName" style="font-size:11px;font-weight:600;color:#475569;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <button type="button" onclick="clearPesananFile(event)" style="border:none;cursor:pointer;font-size:11px;padding:4px 10px;color:#ef4444;border:1px solid #fee2e2;background:#fef2f2;border-radius:8px;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                        <i class="fa-solid fa-trash-can"></i> Hapus Foto
                    </button>
                </div>
            </div>

            <div style="background:#eff6ff;border:1px solid #bfdbfe;padding:12px;border-radius:14px;margin-bottom:16px;text-align:left;display:flex;align-items:flex-start;gap:10px;">
                <i class="fa-solid fa-circle-info" style="color:#3b82f6;font-size:15px;margin-top:2px;flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:#1e3a8a;line-height:1.4;font-family:'Poppins',sans-serif;">
                    Unggah struk pembayaran Anda. Bukti akan otomatis dikirim ke chat kantin dan penjual akan memverifikasi.
                </span>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-shrink:0;margin-top:4px;">
            <button id="btnPesananQrisClose" style="flex:1;padding:11px;font-weight:700;font-size:13px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;">
                Tutup
            </button>
            <button id="btnPesananQrisKirim" disabled style="flex:2;padding:11px;font-weight:800;font-size:13px;color:#fff;background:#94a3b8;border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;pointer-events:none;opacity:0.7;">
                <i class="fa-solid fa-paper-plane"></i> Kirim Bukti
            </button>
        </div>
    `;

    modal.appendChild(card);
    document.body.appendChild(modal);

    let selectedFile = null;

    setTimeout(() => {
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }, 10);

    function closeModal() {
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => modal.remove(), 300);
    }

    // File input change listener
    const fileInput = card.querySelector('#pesananFileInput');
    fileInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            selectedFile = e.target.files[0];
            const reader = new FileReader();
            reader.onload = function(ev) {
                card.querySelector('#pesananUploadPreview').src = ev.target.result;
                card.querySelector('#pesananUploadPrompt').style.display = 'none';
                card.querySelector('#pesananUploadPreviewWrap').style.display = 'flex';

                const kirimBtn = card.querySelector('#btnPesananQrisKirim');
                kirimBtn.disabled = false;
                kirimBtn.style.background = '#16a34a';
                kirimBtn.style.pointerEvents = 'auto';
                kirimBtn.style.opacity = '1';
                kirimBtn.style.boxShadow = '0 4px 12px rgba(22,163,74,0.25)';
            };
            reader.readAsDataURL(selectedFile);
            card.querySelector('#pesananFileName').textContent = selectedFile.name;
        }
    });

    window.clearPesananFile = function(event) {
        if (event) event.stopPropagation();
        fileInput.value = '';
        selectedFile = null;
        card.querySelector('#pesananUploadPrompt').style.display = 'block';
        card.querySelector('#pesananUploadPreviewWrap').style.display = 'none';
        const kirimBtn = card.querySelector('#btnPesananQrisKirim');
        kirimBtn.disabled = true;
        kirimBtn.style.background = '#94a3b8';
        kirimBtn.style.pointerEvents = 'none';
        kirimBtn.style.opacity = '0.7';
        kirimBtn.style.boxShadow = 'none';
    };

    // Kirim button listener
    const kirimBtn = card.querySelector('#btnPesananQrisKirim');
    kirimBtn.addEventListener('click', function() {
        if (!selectedFile) return;

        kirimBtn.disabled = true;
        kirimBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
        kirimBtn.style.background = '#15803d';
        kirimBtn.style.pointerEvents = 'none';

        const fd = new FormData();
        fd.append('ids', JSON.stringify([idPesanan]));
        fd.append('bukti_foto', selectedFile);

        fetch('actions/upload_bukti.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    // Success state
                    kirimBtn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Terkirim!';
                    kirimBtn.style.background = '#16a34a';
                    kirimBtn.style.pointerEvents = 'none';

                    // Lock uploader
                    const uploaderArea = card.querySelector('#pesananUploaderArea');
                    uploaderArea.style.pointerEvents = 'none';
                    uploaderArea.style.borderColor = '#16a34a';
                    uploaderArea.style.background = '#f0fdf4';
                    const removeBtn = card.querySelector('#pesananUploadPreviewWrap button');
                    if (removeBtn) removeBtn.remove();

                    // Show toast if function exists
                    if (typeof showToast === 'function') showToast('🎉 Bukti pembayaran berhasil terkirim ke kantin!', 'success');
                    else alert('Bukti pembayaran berhasil terkirim!');

                    // Update button in pesanan list
                    const btnQris = document.getElementById('btn-qris-pay-' + idPesanan);
                    if (btnQris) {
                        btnQris.style.background = '#0284c7';
                        btnQris.innerHTML = '<i class="fa-solid fa-file-invoice"></i> Lihat/Kirim Ulang Bukti';
                    }

                    // Auto close after 2s
                    setTimeout(closeModal, 2000);
                } else {
                    kirimBtn.disabled = false;
                    kirimBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Bukti';
                    kirimBtn.style.background = '#16a34a';
                    kirimBtn.style.pointerEvents = 'auto';
                    if (typeof showToast === 'function') showToast('Gagal: ' + res.message, 'error');
                    else alert('Gagal: ' + res.message);
                }
            })
            .catch(err => {
                console.error(err);
                kirimBtn.disabled = false;
                kirimBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Kirim Bukti';
                kirimBtn.style.background = '#16a34a';
                kirimBtn.style.pointerEvents = 'auto';
                if (typeof showToast === 'function') showToast('Koneksi gagal! Coba lagi.', 'error');
                else alert('Koneksi gagal!');
            });
    });

    card.querySelector('#btnPesananQrisClose').addEventListener('click', closeModal);
    card.querySelector('#btnPesananQrisX').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
}

function batalkanPesanan(idPesanan) {
    // Buat modal konfirmasi kustom yang cantik
    let oldModal = document.getElementById('cancelOrderConfirmModal');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.id = 'cancelOrderConfirmModal';
    modal.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center;
        z-index: 9999999;
        opacity: 0; transition: opacity 0.3s ease;
        padding: 16px; box-sizing: border-box;
    `;

    const card = document.createElement('div');
    card.style.cssText = `
        background: #ffffff;
        width: 100%; max-width: 360px;
        border-radius: 24px; padding: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        text-align: center;
        transform: scale(0.9); transition: transform 0.3s ease;
        box-sizing: border-box;
    `;

    card.innerHTML = `
        <div style="font-size:40px;color:#ef4444;margin-bottom:16px;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3 style="margin:0 0 8px;font-size:16px;font-weight:800;color:#0f172a;font-family:'Poppins',sans-serif;">Batalkan Pesanan?</h3>
        <p style="margin:0 0 20px;font-size:12.5px;color:#64748b;font-family:'Poppins',sans-serif;line-height:1.5;">Apakah Anda yakin ingin membatalkan pesanan <strong>#${idPesanan}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
        
        <div style="display:flex;gap:8px;">
            <button id="btnCancelOrderNo" style="flex:1;padding:11px;font-weight:700;font-size:13px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;">
                Kembali
            </button>
            <button id="btnCancelOrderYes" style="flex:1;padding:11px;font-weight:800;font-size:13px;color:#fff;background:#ef4444;border:none;border-radius:12px;cursor:pointer;font-family:'Poppins',sans-serif;transition:all 0.2s;box-shadow:0 4px 12px rgba(239,68,68,0.2);">
                Ya, Batalkan
            </button>
        </div>
    `;

    modal.appendChild(card);
    document.body.appendChild(modal);

    setTimeout(() => {
        modal.style.opacity = '1';
        card.style.transform = 'scale(1)';
    }, 10);

    function closeModal() {
        modal.style.opacity = '0';
        card.style.transform = 'scale(0.9)';
        setTimeout(() => modal.remove(), 300);
    }

    modal.querySelector('#btnCancelOrderNo').addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    modal.querySelector('#btnCancelOrderYes').addEventListener('click', function() {
        const btnYes = modal.querySelector('#btnCancelOrderYes');
        const btnNo = modal.querySelector('#btnCancelOrderNo');
        
        btnYes.disabled = true;
        btnYes.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
        btnYes.style.pointerEvents = 'none';
        btnYes.style.opacity = '0.7';
        btnNo.disabled = true;
        btnNo.style.pointerEvents = 'none';
        btnNo.style.opacity = '0.5';

        const fd = new FormData();
        fd.append('id_pesanan', idPesanan);

        fetch('actions/batalkan_pesanan.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                btnYes.innerHTML = '<i class="fa-solid fa-circle-check"></i> Berhasil';
                btnYes.style.background = '#16a34a';
                btnYes.style.boxShadow = 'none';

                if (typeof showToast === 'function') {
                    showToast('🎉 ' + res.message, 'success');
                } else {
                    alert(res.message);
                }

                setTimeout(() => {
                    closeModal();
                    window.location.reload();
                }, 1500);
            } else {
                btnYes.disabled = false;
                btnYes.innerHTML = 'Ya, Batalkan';
                btnYes.style.pointerEvents = 'auto';
                btnYes.style.opacity = '1';
                btnNo.disabled = false;
                btnNo.style.pointerEvents = 'auto';
                btnNo.style.opacity = '1';

                if (typeof showToast === 'function') {
                    showToast('Gagal: ' + res.message, 'error');
                } else {
                    alert('Gagal: ' + res.message);
                }
            }
        })
        .catch(err => {
            console.error(err);
            btnYes.disabled = false;
            btnYes.innerHTML = 'Ya, Batalkan';
            btnYes.style.pointerEvents = 'auto';
            btnYes.style.opacity = '1';
            btnNo.disabled = false;
            btnNo.style.pointerEvents = 'auto';
            btnNo.style.opacity = '1';

            if (typeof showToast === 'function') {
                showToast('Koneksi gagal! Silakan coba lagi.', 'error');
            } else {
                alert('Koneksi gagal!');
            }
        });
    });
}
</script>
