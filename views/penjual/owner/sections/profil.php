<?php
//owner//

/** @var array $profilPenjual */
/** @var string $penjualNama */
/** @var int $penjualId */

// 🌟 VALIDASI AKSES: Pastikan hanya user dengan role owner yang bisa membuka halaman ini
if (!isset($profilPenjual['role']) || strtolower($profilPenjual['role']) !== 'owner') {
    echo "<div class='akses-ditolak-banner'>
            <i class='fa-solid fa-triangle-exclamation'></i> Akses Ditolak: Halaman ini khusus untuk Owner Toko!
          </div>";
    exit;
}
?>

<div class="profil-grid">

    <div class="profil-avatar-card">
        <div class="profil-avatar-big" id="previewWrap">
            <?php if (!empty($profilPenjual['foto_profil'])): ?>
                <img id="previewImg" class="avatar-img-render"
                     src="../../../assets/img/penjual/<?= htmlspecialchars($profilPenjual['foto_profil']) ?>?v=<?= time() ?>">
            <?php else: ?>
                <span id="previewInisial"><?= strtoupper(substr($penjualNama, 0, 1)) ?></span>
            <?php endif; ?>
        </div>

        <div class="profil-nama-big"><?= htmlspecialchars($profilPenjual['nama']) ?></div>
        
        <div class="profil-toko">
            <i class="fa-solid fa-store"></i>
            <?= htmlspecialchars($profilPenjual['nama_toko'] ?? '-') ?>
        </div>
        
        <div class="profil-shift">
            <i class="fa-solid fa-clock"></i>
            Shift <?= htmlspecialchars($profilPenjual['shift'] ?? '-') ?>
        </div>

        <div class="profil-action-foto-group">
            <label class="profil-btn-foto" for="inputFotoTrigger">
                <i class="fa-solid fa-camera"></i> Ganti Foto Profil
            </label>
            
            <?php if (!empty($profilPenjual['foto_profil'])): ?>
                <form method="POST" action="../actions/proses_profil.php" class="form-hapus-foto-wrap">
                    <input type="hidden" name="action" value="hapus_foto_profil">
                    <button type="submit" class="btn-hapus-foto">
                        <i class="fa-solid fa-trash-can"></i> Hapus Foto Profil
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="profil-info-kecil">
            <div class="profil-info-row">
                <span><i class="fa-solid fa-crown icon-gold"></i> Tingkat Akun</span>
                <span class="profil-role-badge badge-gold">
                    <i class="fa-solid fa-circle-check"></i> Owner
                </span>
            </div>
            <div class="profil-info-row">
                <span><i class="fa-solid fa-calendar"></i> Tanggal Registrasi</span>
                <span><?= date('d M Y', strtotime($profilPenjual['dibuat_pada'])) ?></span>
            </div>
        </div>
    </div>

    <div class="profil-form-column">

        <div class="form-card">
            <h2><i class="fa-solid fa-user-gear icon-header-section"></i>Pengaturan Akun & Toko</h2>
            <form id="formDataDiriOwner" method="POST" enctype="multipart/form-data" onsubmit="console.log('submit fired')">
                <input type="hidden" name="_section" value="profil">
                <input type="hidden" name="action"   value="edit_profil">
                
                <input type="file" id="inputFotoTrigger" name="foto_profil"
                       accept="image/*" style="display:none"
                       onchange="previewFoto(this)">

                <div class="form-group">
                    <label>Nama Lengkap Owner</label>
                    <input type="text" name="nama"
                           value="<?= htmlspecialchars($profilPenjual['nama']) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label>Nama Kantin / Toko</label>
                    <input type="text" name="nama_toko"
                           value="<?= htmlspecialchars($profilPenjual['nama_toko'] ?? '') ?>"
                           placeholder="Contoh: Warung Barokah" required>
                </div>

                <div class="form-group">
                    <label>Username Log In</label>
                    <input type="text" name="username"
                           value="<?= htmlspecialchars($profilPenjual['username']) ?>"
                           required>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Data Owner
                </button>
            </form>
        </div>

        <div class="form-card">
            <h2><i class="fa-solid fa-shield-halved icon-header-section"></i>Keamanan Akun</h2>
            <form method="POST">
                <input type="hidden" name="_section" value="profil">
                <input type="hidden" name="action"   value="ganti_password">

                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="password-wrap">
                        <input type="password" name="password_lama" id="pwLama" required>
                        <button type="button" class="btn-eye" onclick="togglePw('pwLama',this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-wrap">
                        <input type="password" name="password_baru" id="pwBaru"
                               minlength="6" required>
                        <button type="button" class="btn-eye" onclick="togglePw('pwBaru',this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-note">Minimal 6 karakter demi keamanan akun owner</div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="password-wrap">
                        <input type="password" name="password_konfirm" id="pwKonfirm" required>
                        <button type="button" class="btn-eye" onclick="togglePw('pwKonfirm',this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit btn-password-blue">
                    <i class="fa-solid fa-key"></i> Perbarui Password Utama
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const wrap = document.getElementById('previewWrap');
        wrap.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
    };
    reader.readAsDataURL(input.files[0]);
    
    /* 🌟 FIX OWNER: Mengincar ID form secara presisi dan aman */
    const form = document.getElementById('formDataDiriOwner');
    if (form) {
        form.submit();
    }
}

function togglePw(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.querySelector('i').className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}
</script>