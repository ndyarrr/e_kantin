<div id="section-kantin" class="page-section">
    <div class="section-block">
        <h2 class="section-title">Daftar Kantin Esemkita</h2>
        
        <div class="kantin-grid" id="kantinGridAll">
            <?php if (empty($all_tokos)): ?>
                <!-- Tampilan jika belum ada kantin yang terdaftar -->
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fa-solid fa-store-slash"></i>
                    <h3>Belum Ada Kantin</h3>
                    <p>Saat ini belum ada kantin yang terdaftar atau sedang buka.</p>
                </div>
            <?php else: ?>
                <!-- Looping daftar kantin -->
                <?php foreach ($all_tokos as $t): ?>
                    <?php
                        $toko_img = resolveTokoImg($t['foto_toko'] ?? '');
                        $is_buka = ($t['status'] === 'buka');
                    ?>
                    <div class="kantin-card" data-nama="<?= strtolower($t['nama_toko']) ?>">
                        
                        <!-- Foto Kantin -->
                        <?php if ($toko_img): ?>
                            <img src="<?= $toko_img ?>" alt="<?= $t['nama_toko'] ?>" class="blank-image-square">
                        <?php else: ?>
                            <div class="toko-img-placeholder">
                                <i class="fa-solid fa-shop"></i>
                                <span><?= $t['nama_toko'] ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Info Kantin -->
                        <div class="kantin-info">
                            <h3><?= htmlspecialchars($t['nama_toko']) ?></h3>
                            <p><?= htmlspecialchars($t['deskripsi'] ?? 'Menyediakan berbagai menu makanan dan minuman pilihan.') ?></p>
                            
                            <!-- Indikator Buka / Tutup -->
                            <div class="status-indicator <?= $is_buka ? 'online' : 'offline' ?>">
                                <?= $is_buka ? 'Buka' : 'Tutup' ?>
                            </div>

                            <!-- Tombol Kunjungi -->
                            <a href="toko.php?id=<?= $t['id_toko'] ?>" class="btn-lihat-menu">
                                <i class="fa-solid fa-utensils"></i> Kunjungi Kantin
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>