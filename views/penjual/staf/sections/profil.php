<?php
/** @var array $profilPenjual */
/** @var string $penjualNama */
/** @var int $penjualId */

// 🌟 VALIDASI AKSES: Pastikan yang mengakses adalah Staf atau Owner/Penjual
if (!isset($profilPenjual['role']) || !in_array(strtolower($profilPenjual['role']), ['staf', 'owner', 'penjual'])) {
    echo "<div class='akses-ditolak-banner' style='padding: 20px; background: #fee2e2; color: #991b1b; text-align: center; font-family: sans-serif;'>
            <i class='fa-solid fa-triangle-exclamation'></i> Akses Ditolak: Halaman tidak dikenali!
          </div>";
    exit;
}
?>

<div class="profil-grid">

    <div class="profil-avatar-card">
        <div class="profil-avatar-big" id="previewWrap">
            <?php if (!empty($profilPenjual['foto_profil'])): ?>
                <img id="previewImg"
                     src="../../../assets/img/penjual/<?= htmlspecialchars($profilPenjual['foto_profil']) ?>?v=<?= time() ?>"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            <?php else: ?>
                <span id="previewInisial"><?= strtoupper(substr($penjualNama, 0, 1)) ?></span>
            <?php endif; ?>
        </div>

        <div class="profil-nama-big"><?= htmlspecialchars($profilPenjual['nama']) ?></div>
        <div class="profil-toko">
            <i class="fa-solid fa-store" style="font-size:12px"></i>
            <?= htmlspecialchars($profilPenjual['nama_toko'] ?? '-') ?>
        </div>
        <div class="profil-shift">
            <i class="fa-solid fa-clock"></i>
            Shift <?= htmlspecialchars($profilPenjual['shift'] ?? '-') ?>
        </div>

        <div class="profil-action-foto-group" style="margin-top:18px; display:flex; flex-direction:column; gap:8px; align-items:center;">
            <label class="profil-btn-foto" for="inputFotoTrigger" style="cursor:pointer;">
                <i class="fa-solid fa-camera"></i> Ganti Foto
            </label>
            
            <?php if (!empty($profilPenjual['foto_profil'])): ?>
                <form method="POST" action="" class="form-hapus-foto-wrap" style="margin:0;">
                    <input type="hidden" name="action" value="hapus_foto_profil">
                    <button type="submit" class="btn-hapus-foto" style="background:none; border:none; color:#dc2626; cursor:pointer; font-size:13px;">
                        <i class="fa-solid fa-trash-can"></i> Hapus Foto
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="profil-info-kecil" style="margin-top:14px">
            <div class="profil-info-row">
                <span><i class="fa-solid fa-circle-user"></i> Role</span>
                <span class="profil-role-badge"><?= ucfirst($profilPenjual['role'] ?? 'staf') ?></span>
            </div>
            <div class="profil-info-row">
                <span><i class="fa-solid fa-calendar"></i> Bergabung</span>
                <span><?= date('d M Y', strtotime($profilPenjual['dibuat_pada'])) ?></span>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:18px; flex:1;">

        <div class="form-card">
            <h2><i class="fa-solid fa-pen-to-square" style="color:var(--green);margin-right:8px"></i>Data Diri Staf</h2>
            <form id="formDataDiri" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_section" value="profil">
                <input type="hidden" name="action"   value="edit_profil">
                
                <input type="file" id="inputFotoTrigger" name="foto_profil"
                       accept="image/*" style="display:none"
                       onchange="previewFoto(this)">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama"
                           value="<?= htmlspecialchars($profilPenjual['nama']) ?>"
                           required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username"
                           value="<?= htmlspecialchars($profilPenjual['username']) ?>"
                           required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="form-card">
            <h2><i class="fa-solid fa-lock" style="color:var(--green);margin-right:8px"></i>Ganti Password</h2>
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
                    <div class="form-note">Minimal 6 karakter</div>
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
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-key"></i> Ganti Password
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
    
    /* 🌟 FIX: Menembak ID form data diri secara presisi */
    const form = document.getElementById('formDataDiri');
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