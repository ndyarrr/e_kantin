<?php
// views/pembeli/sections/profil.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../config/database.php';

$user_id = $_SESSION['user_id'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'siswa';
$user_nama = $_SESSION['user_nama'] ?? 'Pembeli';

$user_db = null;
if (!empty($user_id)) {
    if ($user_role === 'siswa') {
        $q_user = mysqli_query($conn, "
            SELECT m.*, CONCAT(k.kelas, ' ', j.nama_jurusan, ' ', k.rombel) AS nama_kelas 
            FROM murid m
            LEFT JOIN kelas k ON k.id_kelas = m.id_kelas
            LEFT JOIN jurusan j ON j.id_jurusan = k.id_jurusan
            WHERE m.nisn = '$user_id' LIMIT 1
        ");
        $user_db = mysqli_fetch_assoc($q_user);
    } else {
        $q_user = mysqli_query($conn, "SELECT * FROM guru WHERE nuptk = '$user_id' LIMIT 1");
        $user_db = mysqli_fetch_assoc($q_user);
    }
}

// Avatar
$avatar_file = $user_db['foto_profil'] ?? '';
$has_avatar = !empty($avatar_file) && file_exists(__DIR__ . '/../../../assets/img/' . $avatar_file);
$avatar_path = $has_avatar ? '../../assets/img/' . $avatar_file : '';
?>

<!-- ═══════ SECTION: PROFIL ═══════ -->
<div class="page-section" id="section-profil">
    <section class="section-block">
        <h2 class="section-title">Profil Saya</h2>
        
        <div class="profil-grid">
            
            <!-- Left Side: Profile Card -->
            <div class="profil-avatar-card">
                <div class="profil-avatar-big" id="profilPreviewWrap">
                    <?php if ($has_avatar): ?>
                        <img id="profilPreviewImg" src="<?= $avatar_path ?>?v=<?= time() ?>" alt="Avatar">
                    <?php else: ?>
                        <span id="profilPreviewInit"><?= strtoupper(substr($user_nama, 0, 1)) ?></span>
                    <?php endif; ?>
                </div>

                <div class="profil-nama-big" id="profilNamaDisplay"><?= htmlspecialchars($user_nama) ?></div>
                
                <div class="profil-role-badge">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= $user_role === 'siswa' ? 'Siswa' : 'Guru / Staf' ?>
                </div>

                <div class="profil-meta-info">
                    <div class="profil-meta-row">
                        <span>NISN / NUPTK</span>
                        <span><?= htmlspecialchars($user_id) ?></span>
                    </div>
                    <?php if ($user_role === 'siswa'): ?>
                        <div class="profil-meta-row">
                            <span>Kelas</span>
                            <span><?= htmlspecialchars($user_db['nama_kelas'] ?? '-') ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input File Trigger (Hidden) -->
                <input type="file" id="profilInputFotoTrigger" accept="image/*" style="display:none" onchange="profilUploadFoto(this)">
                
                <button type="button" class="profil-btn-avatar-upload" onclick="document.getElementById('profilInputFotoTrigger').click()">
                    <i class="fa-solid fa-camera"></i> Ganti Foto Profil
                </button>

                <a href="../../auth/logout.php" onclick="confirmLogout(event, this.href)" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; margin-top: 14px; border-radius: 12px; background: #fff1f2; color: #e11d48; border: 1.5px solid #ffe4e6; font-size: 13.5px; font-weight: 750; text-decoration: none; box-sizing: border-box; transition: all 0.2s;" onmouseover="this.style.background='#ffe4e6'" onmouseout="this.style.background='#fff1f2'">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar Akun
                </a>
            </div>

            <!-- Right Side: Edit Form Column -->
            <div class="profil-form-column">
                
                <!-- Card 1: Pengaturan Data Diri -->
                <div class="profil-card">
                    <h3><i class="fa-solid fa-user-gear"></i> Pengaturan Akun</h3>
                    <form id="profilGantiNamaForm" onsubmit="profilSubmitGantiNama(event)">
                        <div class="profil-form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" id="profilNamaInput" name="nama" value="<?= htmlspecialchars($user_nama) ?>" required>
                        </div>
                        <button type="submit" class="profil-btn-submit" id="btnSubmitGantiNama">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Nama Lengkap
                        </button>
                    </form>
                </div>

                <!-- Card 2: Keamanan / Ganti Password -->
                <div class="profil-card">
                    <h3><i class="fa-solid fa-shield-halved"></i> Keamanan Akun</h3>
                    <form id="profilGantiPasswordForm" onsubmit="profilSubmitGantiPassword(event)">
                        <div class="profil-form-group">
                            <label>Password Lama</label>
                            <input type="password" id="profilPwLamaInput" name="password_lama" required>
                        </div>
                        <div class="profil-form-group">
                            <label>Password Baru</label>
                            <input type="password" id="profilPwBaruInput" name="password_baru" minlength="6" required>
                            <small style="font-size: 11px; color: #64748b; display: block; margin-top: 4px; text-align: left;">Minimal 6 karakter</small>
                        </div>
                        <div class="profil-form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" id="profilPwKonfirmInput" name="password_konfirm" required>
                        </div>
                        <button type="submit" class="profil-btn-submit" id="btnSubmitGantiPassword">
                            <i class="fa-solid fa-key"></i> Perbarui Password
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </section>
</div>

<script>
function profilUploadFoto(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', 'ganti_foto');
    formData.append('foto_profil', file);

    const btn = document.querySelector('.profil-btn-avatar-upload');
    const oldBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengunggah...';

    fetch('actions/proses_profil.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = oldBtnText;

        if (data.status === 'success') {
            showToast('🎉 Foto profil berhasil diperbarui!', 'success');
            
            // Perbarui preview foto profil di halaman Profil
            const previewWrap = document.getElementById('profilPreviewWrap');
            const newPath = data.foto_path;
            previewWrap.innerHTML = `<img id="profilPreviewImg" src="${newPath}" alt="Avatar">`;
            
            // Perbarui foto profil di topbar (avatar)
            const topbarImg = document.getElementById('topbarAvatarImg');
            if (topbarImg) {
                topbarImg.src = newPath;
            } else {
                // Jika sebelumnya inisial, ubah menjadi image tag
                const topbarInit = document.getElementById('topbarAvatarInit');
                if (topbarInit) {
                    const imgEl = document.createElement('img');
                    imgEl.src = newPath;
                    imgEl.className = 'blank-avatar';
                    imgEl.id = 'topbarAvatarImg';
                    imgEl.alt = 'Profil';
                    imgEl.style.cursor = 'pointer';
                    imgEl.onclick = function() { switchNav('profil'); };
                    topbarInit.parentNode.replaceChild(imgEl, topbarInit);
                }
            }
        } else {
            showToast('Gagal: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = oldBtnText;
        showToast('Koneksi gagal saat mengunggah foto!', 'error');
    });
}

function profilSubmitGantiNama(e) {
    e.preventDefault();
    const namaInput = document.getElementById('profilNamaInput');
    const nama = namaInput.value.trim();
    if (!nama) return;

    const btn = document.getElementById('btnSubmitGantiNama');
    const oldBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    const formData = new FormData();
    formData.append('action', 'ganti_nama');
    formData.append('nama', nama);

    fetch('actions/proses_profil.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = oldBtnText;

        if (data.status === 'success') {
            showToast('🎉 Nama berhasil diperbarui!', 'success');
            // Update UI
            document.getElementById('profilNamaDisplay').textContent = nama;
            
            // Update in initials if no avatar is set
            const topbarInit = document.getElementById('topbarAvatarInit');
            if (topbarInit) {
                topbarInit.textContent = nama.charAt(0).toUpperCase();
            }
        } else {
            showToast('Gagal: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = oldBtnText;
        showToast('Koneksi gagal saat menyimpan nama!', 'error');
    });
}

function profilSubmitGantiPassword(e) {
    e.preventDefault();
    const pwLama = document.getElementById('profilPwLamaInput').value;
    const pwBaru = document.getElementById('profilPwBaruInput').value;
    const pwKonfirm = document.getElementById('profilPwKonfirmInput').value;

    if (pwBaru !== pwKonfirm) {
        showToast('Konfirmasi password tidak cocok!', 'error');
        return;
    }

    const btn = document.getElementById('btnSubmitGantiPassword');
    const oldBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

    const formData = new FormData();
    formData.append('action', 'ganti_password');
    formData.append('password_lama', pwLama);
    formData.append('password_baru', pwBaru);
    formData.append('password_konfirm', pwKonfirm);

    fetch('actions/proses_profil.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = oldBtnText;

        if (data.status === 'success') {
            showToast('🎉 Password berhasil diperbarui!', 'success');
            document.getElementById('profilPwLamaInput').value = '';
            document.getElementById('profilPwBaruInput').value = '';
            document.getElementById('profilPwKonfirmInput').value = '';
        } else {
            showToast('Gagal: ' + data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = oldBtnText;
    showToast('Koneksi gagal saat memperbarui password!', 'error');
    });
}
</script>

<style>
/* CSS Styling */

.profil-grid {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 24px;
    align-items: start;
    margin-top: 10px;
    font-family: 'Poppins', sans-serif;
}

@media (max-width: 768px) {
    .profil-grid {
        grid-template-columns: 1fr;
    }
}

.profil-avatar-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 32px 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
    text-align: center;
}

.profil-avatar-big {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    margin: 0 auto 20px;
    background: #f0fdf4;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    font-weight: 800;
    border: 4px solid #bbf7d0;
    box-shadow: 0 8px 20px rgba(22, 163, 74, 0.1);
    position: relative;
    overflow: hidden;
}

.profil-avatar-big img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profil-nama-big {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.profil-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
    margin-bottom: 20px;
}

.profil-meta-info {
    text-align: left;
    border-top: 1px solid #f1f5f9;
    padding-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.profil-meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.profil-meta-row span:first-child {
    color: #64748b;
    font-weight: 500;
}

.profil-meta-row span:last-child {
    color: #1e293b;
    font-weight: 700;
}

.profil-form-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.profil-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    border: 1px solid #f1f5f9;
}

.profil-card h3 {
    margin: 0 0 20px;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profil-card h3 i {
    color: #16a34a;
}

.profil-form-group {
    margin-bottom: 16px;
    text-align: left;
}

.profil-form-group label {
    display: block;
    font-size: 12.5px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}

.profil-form-group input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    outline: none;
    font-size: 13.5px;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s;
}

.profil-form-group input:focus {
    border-color: #16a34a;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
}

.profil-btn-submit {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, #16a34a, #15803d);
    color: #ffffff;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    font-weight: 750;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
}

.profil-btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3);
}

.profil-btn-avatar-upload {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 10px;
}

.profil-btn-avatar-upload:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}
</style>
