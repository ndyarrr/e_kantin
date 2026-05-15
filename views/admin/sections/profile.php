<div id="modalProfil">
    <div class="modal-backdrop" onclick="tutupProfil()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <h2>Profil Saya</h2>
            <button class="modal-close" onclick="tutupProfil()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-avatar-wrap">
            <div class="modal-avatar">
                <?php if (!empty($profilAdmin['foto_profil'])): ?>
                    <img src="../../assets/img/admin/<?= htmlspecialchars($profilAdmin['foto_profil']) ?>?v=<?= time() ?>"
                        style="width:100%;height:100%;object-fit:cover;border-radius:10px;cursor:zoom-in"
                        onclick="event.stopPropagation();bukaFotoAdmin(this.src)">
                <?php else: ?>
                    <?= strtoupper(substr($adminNama, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <span class="modal-role-badge">Administrator</span>
            <div class="modal-dibuat">Dibuat: <?= date('d M Y', strtotime($profilAdmin['dibuat_pada'])) ?></div>
        </div>
        <div class="modal-kode-wrap">
            <div>
                <div class="modal-kode-label">KODE AKTIVASI</div>
                <span class="modal-kode-text" id="modalKode"
                    data-plain="<?= htmlspecialchars($profilAdmin['kode_aktivasi']) ?>"
                    data-hidden="1"><?= substr(htmlspecialchars($profilAdmin['kode_aktivasi']), 0, 2) . str_repeat('•', max(0, strlen($profilAdmin['kode_aktivasi']) - 2)) ?></span>
            </div>
            <button class="btn-reveal" onclick="revealModalKode()" id="modalKodeEye">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="action" value="admin_profil">
            <input type="hidden" name="_section" id="profilSection" value="dashboard">
            <div class="form-group">
                <label>Nama</label>
                <input type="text" name="nama_baru" value="<?= htmlspecialchars($adminNama) ?>">
            </div>
            <div class="form-group">
                <label>Foto Profil</label>
                <?php if (!empty($profilAdmin['foto_profil'])): ?>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                        <img src="../../assets/img/admin/<?= htmlspecialchars($profilAdmin['foto_profil']) ?>?v=<?= time() ?>"
                            style="width:40px;height:40px;border-radius:8px;object-fit:cover">
                        <label
                            style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--red);cursor:pointer;font-weight:500">
                            <input type="checkbox" name="hapus_foto" value="1" id="hapusFoto"
                                style="accent-color:var(--red)">
                            Hapus foto profil
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" name="foto_profil" accept="image/*" id="inputFotoProfil">
                <div class="form-note">Kosongkan jika tidak ingin mengubah foto.</div>
            </div>

            <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
            <div style="font-size:13px;font-weight:600;color:var(--text-muted);margin-bottom:12px">
                <i class="fa-solid fa-lock" style="margin-right:6px"></i>Ubah Password
            </div>
            <div class="form-group">
                <label>Password Lama</label>
                <div class="password-wrap">
                    <input type="password" name="pw_lama" id="pwLama" placeholder="Masukkan password lama"
                        autocomplete="current-password">
                    <button type="button" class="btn-eye" onclick="togglePw('pwLama','eyePwLama')">
                        <i class="fa-solid fa-eye" id="eyePwLama"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Password Baru</label>
                <div class="password-wrap">
                    <input type="password" name="pw_baru" id="pwBaru" placeholder="Minimal 8 karakter"
                        autocomplete="new-password">
                    <button type="button" class="btn-eye" onclick="togglePw('pwBaru','eyePwBaru')">
                        <i class="fa-solid fa-eye" id="eyePwBaru"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <div class="password-wrap">
                    <input type="password" name="pw_konfirmasi" id="pwKonfirmasi" placeholder="Ulangi password baru"
                        autocomplete="new-password">
                    <button type="button" class="btn-eye" onclick="togglePw('pwKonfirmasi','eyePwKonfirmasi')">
                        <i class="fa-solid fa-eye" id="eyePwKonfirmasi"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="modal-btn-submit">
                <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Perubahan
            </button>
        </form>
    </div>
</div>