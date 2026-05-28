<?php
/** @var array $profilPenjual */
global $conn;

$penjualId = (int)($_SESSION['user_id'] ?? 0);

// Ambil data id_toko milik owner
$query_toko = mysqli_query($conn, "SELECT id_toko FROM toko_penjual WHERE id_penjual = $penjualId LIMIT 1");
$r_toko = mysqli_fetch_assoc($query_toko);
$id_toko_owner = (int)($r_toko['id_toko'] ?? 0);

// Load data tabel
$total_staf = 0; $staf_aktif = 0; $tampil_staf = false;
if ($id_toko_owner > 0) {
    $q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM penjual p JOIN toko_penjual tp ON p.id_penjual = tp.id_penjual WHERE tp.id_toko = $id_toko_owner AND LOWER(p.role) != 'owner'");
    if ($q_total && $r = mysqli_fetch_assoc($q_total)) $total_staf = $r['total'];

    $q_aktif = mysqli_query($conn, "SELECT COUNT(*) as aktif FROM penjual p JOIN toko_penjual tp ON p.id_penjual = tp.id_penjual WHERE tp.id_toko = $id_toko_owner AND LOWER(p.role) != 'owner' AND LOWER(p.status) = 'aktif'");
    if ($q_aktif && $r = mysqli_fetch_assoc($q_aktif)) $staf_aktif = $r['aktif'];

    $tampil_staf = mysqli_query($conn, "SELECT p.*, tp.shift FROM penjual p JOIN toko_penjual tp ON p.id_penjual = tp.id_penjual WHERE tp.id_toko = $id_toko_owner AND LOWER(p.role) != 'owner' ORDER BY p.id_penjual DESC");
}
?>

<link rel="stylesheet" href="sections/staf.css?v=<?= time() ?>">

<div class="staf-stats-container">
    <div class="staf-stat-card">
        <div class="staf-stat-info"><span class="staf-stat-title">Total Petugas / Staf</span><span class="staf-stat-number"><?= $total_staf ?></span></div>
        <div class="staf-stat-icon-wrap"><i class="fa-solid fa-users"></i></div>
    </div>
    <div class="staf-stat-card">
        <div class="staf-stat-info"><span class="staf-stat-title">Staf Aktif Bekerja</span><span class="staf-stat-number"><?= $staf_aktif ?> <span class="staf-stat-sub">/ <?= $total_staf ?></span></span></div>
        <div class="staf-stat-icon-wrap icon-green-light"><i class="fa-solid fa-circle-check"></i></div>
    </div>
</div>

<div class="staf-table-card">
    <div class="staf-table-header">
        <h3>Daftar Staf & Shift Kantin</h3>
        <button type="button" onclick="bukaFormTambahStaf()" class="btn-tambah-staf-link" style="border:none; cursor:pointer;">
            <i class="fa-solid fa-user-plus"></i> Tambah Staf Baru
        </button>
        <p>Klik pada baris tabel staf untuk langsung mengedit data di form bawah.</p>
    </div>
    <div class="staf-responsive-table-wrapper">
        <table class="staf-custom-table">
            <thead>
                <tr>
                    <th>NAMA STAF</th>
                    <th>USERNAME</th>
                    <th>JADWAL SHIFT</th>
                    <th>STATUS</th>
                    <th style="text-align: center;">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tampil_staf && mysqli_num_rows($tampil_staf) > 0): ?>
                    <?php while ($staf = mysqli_fetch_assoc($tampil_staf)): ?>
                        <tr class="row-staf-clickable" 
                            data-id="<?= $staf['id_penjual'] ?>"
                            data-nama="<?= htmlspecialchars($staf['nama']) ?>"
                            data-username="<?= htmlspecialchars($staf['username']) ?>"
                            data-shift="<?= htmlspecialchars($staf['shift'] ?? 'Pagi') ?>"
                            onclick="bukaFormEditStaf(this)">
                            <td>
                                <div class="staf-profile-cell">
                                    <div class="staf-avatar-circle">
                                        <?php if (!empty($staf['foto_profil'])): ?>
                                            <img src="../../../assets/img/penjual/<?= htmlspecialchars($staf['foto_profil']) ?>" alt="Avatar">
                                        <?php else: ?>
                                            <span><?= strtoupper(substr($staf['nama'], 0, 1)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="staf-cell-name"><?= htmlspecialchars($staf['nama']) ?></span>
                                </div>
                            </td>
                            <td><span class="staf-text-muted"><?= htmlspecialchars($staf['username']) ?></span></td>
                            <td><span class="staf-shift-badge"><i class="fa-solid fa-clock"></i> Shift <?= htmlspecialchars($staf['shift'] ?? '-') ?></span></td>
                            <td>
                                <?php if (strtolower($staf['status'] ?? '') === 'aktif'): ?>
                                    <span class="staf-badge badge-active"><i class="fa-solid fa-circle"></i> Aktif</span>
                                <?php else: ?>
                                    <span class="staf-badge badge-inactive"><i class="fa-solid fa-circle"></i> Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation();">
                                <div class="staf-action-buttons">
                                    <form method="POST" action="" style="margin:0; display:inline-block;">
                                        <input type="hidden" name="action" value="toggle_status_staf">
                                        <input type="hidden" name="id_staf" value="<?= $staf['id_penjual'] ?>">
                                        <button type="submit" class="staf-btn-action btn-status-staf"><i class="fa-solid fa-user-slash"></i></button>
                                    </form>
                                    <form method="POST" action="" style="margin:0; display:inline-block;" onsubmit="return confirm('Apakah Anda yakin?')">
                                        <input type="hidden" name="action" value="hapus_staf">
                                        <input type="hidden" name="id_staf" value="<?= $staf['id_penjual'] ?>">
                                        <button type="submit" class="staf-btn-action btn-delete-staf"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="staf-table-empty"><i class="fa-solid fa-users-slash"></i> Belum ada staf.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="containerFormStaf" class="form-staf-container">
    <h3 id="judulFormStaf">Form Staf</h3>
    
    <form method="POST" action="">
        <input type="hidden" name="action" id="inputActionStaf" value="action_staf_tambah">
        <input type="hidden" name="id_staf" id="inputIdStaf" value="">
        
        <div class="form-staf-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" id="inputNamaStaf" class="form-staf-control" required placeholder="Nama karyawan">
        </div>
        <div class="form-staf-group">
            <label>Username</label>
            <input type="text" name="username" id="inputUsernameStaf" class="form-staf-control" required placeholder="Username log in">
        </div>
        <div class="form-staf-group">
            <label id="labelPasswordStaf">Password</label>
            <input type="password" name="password" id="inputPasswordStaf" class="form-staf-control" placeholder="Minimal 6 karakter">
        </div>
        <div class="form-staf-group">
            <label>Jadwal Shift Kerja</label>
            <select name="shift" id="inputShiftStaf" class="form-staf-control">
                <option value="Pagi">Shift Pagi</option>
                <option value="Siang">Shift Siang</option>
                <option value="Istirahat">Shift Istirahat (Khusus Jam Istirahat Sekolah)</option>
                <option value="Full Time">Full Time</option>
            </select>
        </div>
        <div class="form-staf-actions">
            <button type="button" onclick="tutupFormStaf()" class="btn-staf-batal">Tutup / Batal</button>
            <button type="submit" id="btnSubmitStaf" class="btn-staf-submit" style="background:#22c55e;">Simpan</button>
        </div>
    </form>
</div>

<script>
function bukaFormTambahStaf() {
    // Reset status aktif baris tabel memakai class CSS
    document.querySelectorAll('.row-staf-clickable').forEach(tr => tr.classList.remove('active-edit'));

    // Set form ke Mode Tambah
    document.getElementById('inputIdStaf').value = "";
    document.getElementById('inputNamaStaf').value = "";
    document.getElementById('inputUsernameStaf').value = "";
    document.getElementById('inputShiftStaf').value = "Pagi";
    
    document.getElementById('inputPasswordStaf').required = true;
    document.getElementById('inputPasswordStaf').placeholder = "Minimal 6 karakter";
    document.getElementById('labelPasswordStaf').innerText = "Password Akun Baru";

    document.getElementById('inputActionStaf').value = "action_staf_tambah";
    document.getElementById('judulFormStaf').innerHTML = '<i class="fa-solid fa-user-plus"></i> Tambah Staf / Kasir Baru';
    
    const btnSubmit = document.getElementById('btnSubmitStaf');
    btnSubmit.style.background = '#22c55e';
    btnSubmit.innerText = 'Daftarkan Staf';

    const container = document.getElementById('containerFormStaf');
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth' });
}

function bukaFormEditStaf(element) {
    const id = element.getAttribute('data-id');
    const nama = element.getAttribute('data-nama');
    const username = element.getAttribute('data-username');
    const shift = element.getAttribute('data-shift');

    // Beri class aktif ganti warna baris via CSS class
    document.querySelectorAll('.row-staf-clickable').forEach(tr => tr.classList.remove('active-edit'));
    element.classList.add('active-edit');

    // Set form ke Mode Edit
    document.getElementById('inputIdStaf').value = id;
    document.getElementById('inputNamaStaf').value = nama;
    document.getElementById('inputUsernameStaf').value = username;
    document.getElementById('inputShiftStaf').value = shift;
    
    document.getElementById('inputPasswordStaf').required = false;
    document.getElementById('inputPasswordStaf').placeholder = "Kosongkan jika tidak ingin diubah";
    document.getElementById('labelPasswordStaf').innerText = "Ganti Password (Opsional)";

    document.getElementById('inputActionStaf').value = "action_staf_edit";
    document.getElementById('judulFormStaf').innerHTML = '<i class="fa-solid fa-user-gear";"></i> Edit Staf / Shift: ' + nama;
    
    const btnSubmit = document.getElementById('btnSubmitStaf');
    btnSubmit.style.background = '#22c55e';
    btnSubmit.innerText = 'Simpan Perubahan';

    const container = document.getElementById('containerFormStaf');
    container.style.display = 'block';
    container.scrollIntoView({ behavior: 'smooth' });
}

function tutupFormStaf() {
    document.querySelectorAll('.row-staf-clickable').forEach(tr => tr.classList.remove('active-edit'));
    document.getElementById('containerFormStaf').style.display = 'none';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>