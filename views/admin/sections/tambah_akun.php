<?php // sections/tambah_akun.php ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">

    <!-- ══ KOLOM KIRI: MURID & GURU ══ -->
    <div class="form-card">
        <div style="display:flex; gap:8px; margin-bottom:18px">
            <button id="tabMuridAkun" type="button" onclick="switchTabAkun('murid')"
                style="flex:1; padding:9px; border-radius:8px; border:1.5px solid var(--green); background:var(--green); color:#fff; font-weight:600; font-size:13px; cursor:pointer; transition:.2s">
                <i class="fa-solid fa-graduation-cap" style="margin-right:5px"></i>Murid
            </button>
            <button id="tabGuruAkun" type="button" onclick="switchTabAkun('guru')"
                style="flex:1; padding:9px; border-radius:8px; border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-weight:600; font-size:13px; cursor:pointer; transition:.2s">
                <i class="fa-solid fa-chalkboard-teacher" style="margin-right:5px"></i>Guru
            </button>
        </div>

        <!-- Form Tambah Murid -->
        <div id="formAkunMurid">
            <h2><i class="fa-solid fa-user-plus" style="color:var(--green); margin-right:8px"></i>Tambah Akun Murid</h2>
            <form method="POST">
                <input type="hidden" name="action" value="pembeli_tambah_murid">
                <input type="hidden" name="_section" value="tambah_akun">
                <div class="form-group">
                    <label>Nama Murid</label>
                    <input type="text" name="nama" placeholder="cth. Budi Santoso" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" name="nisn" placeholder="cth. 0012345678" required autocomplete="off"
                        maxlength="10" minlength="10" pattern="\d{10}" title="NISN harus tepat 10 digit angka">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="inputPassMuridAkun"
                            placeholder="Kosongkan = pakai NISN" autocomplete="new-password">
                        <button type="button" class="btn-eye" onclick="togglePw('inputPassMuridAkun','eyeMuridAkun')">
                            <i class="fa-solid fa-eye" id="eyeMuridAkun"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tingkat Kelas</label>
                    <select name="tingkat_pembeli" class="form-select" required>
                        <option value="">Pilih tingkat...</option>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jurusan & Rombel</label>
                    <select name="rombel_pembeli" class="form-select" required>
                        <option value="">Pilih jurusan...</option>
                        <?php
                        $listRombel = [];
                        foreach ($semuaKelas as $k) {
                            $label = preg_replace('/^\d+\s+/', '', $k['nama_kelas']);
                            $listRombel[$label] = $label;
                        }
                        foreach ($listRombel as $rombel): ?>
                            <option value="<?= htmlspecialchars($rombel) ?>">
                                <?= htmlspecialchars($rombel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun Murid
                </button>
            </form>
        </div>

        <!-- Form Tambah Guru -->
        <div id="formAkunGuru" style="display:none">
            <h2><i class="fa-solid fa-user-plus" style="color:var(--green); margin-right:8px"></i>Tambah Akun Guru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="pembeli_tambah_guru">
                <input type="hidden" name="_section" value="tambah_akun">
                <div class="form-group">
                    <label>Nama Guru</label>
                    <input type="text" name="nama" placeholder="cth. Pak Fajar" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>NUPTK</label>
                    <input type="text" name="nuptk" placeholder="cth. 1234567890123456" required autocomplete="off"
                        maxlength="16" minlength="16" pattern="\d{16}" title="NUPTK harus tepat 16 digit angka">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="inputPassGuruAkun"
                            placeholder="Kosongkan = pakai NUPTK" autocomplete="new-password">
                        <button type="button" class="btn-eye" onclick="togglePw('inputPassGuruAkun','eyeGuruAkun')">
                            <i class="fa-solid fa-eye" id="eyeGuruAkun"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun Guru
                </button>
            </form>
        </div>
    </div>

    <!-- ══ KOLOM KANAN: PENJUAL & KANTIN ══ -->
    <div style="display:flex; flex-direction:column; gap:20px;">

        <!-- Form Tambah Owner Penjual -->
        <div class="form-card">
            <h2><i class="fa-solid fa-user-tie" style="color:var(--green); margin-right:8px"></i>Tambah Owner Kantin
            </h2>
            <form method="POST" action="?section=tambah_akun">
                <input type="hidden" name="action" value="penjual_tambah">
                <input type="hidden" name="_section" value="tambah_akun">
                <input type="hidden" name="role" value="owner">
                <div class="form-group">
                    <label>Nama Owner</label>
                    <input type="text" name="nama" placeholder="cth. Bu Sari" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="cth. busari" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="inputPassOwnerAkun" placeholder="Minimal 6 karakter"
                            required>
                        <button type="button" class="btn-eye" onclick="togglePw('inputPassOwnerAkun','eyeOwnerAkun')">
                            <i class="fa-solid fa-eye" id="eyeOwnerAkun"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Assign ke Kantin <span
                            style="color:var(--text-light);font-weight:400">(opsional)</span></label>
                    <select name="id_toko" class="form-select">
                        <option value="">Pilih kantin...</option>
                        <?php foreach ($semuaToko as $t): ?>
                            <option value="<?= $t['id_toko'] ?>">
                                <?= htmlspecialchars($t['nama_toko']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Simpan Akun Owner
                </button>
            </form>
        </div>

        <!-- Form Tambah Kantin -->
        <div class="form-card">
            <h2><i class="fa-solid fa-store" style="color:var(--green); margin-right:8px"></i>Tambah Stand Kantin</h2>
            <form method="POST" enctype="multipart/form-data" action="?section=tambah_akun">
                <input type="hidden" name="action" value="kantin_tambah">
                <input type="hidden" name="_section" value="tambah_akun">
                <div class="form-group">
                    <label>Nama Kantin / Stand</label>
                    <input type="text" name="nama_toko" placeholder="cth. Warung Bu Sari" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <input type="text" name="deskripsi" placeholder="cth. Nasi, lauk, dan minuman">
                </div>
                <div class="form-group">
                    <label>Foto Kantin <span style="color:var(--text-light);font-weight:400">(opsional)</span></label>
                    <input type="file" name="foto_toko" accept="image/*">
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:6px"></i>Daftarkan Stand
                </button>
            </form>
        </div>

    </div>
</div>

<!-- Responsive -->
<style>
    @media (max-width: 768px) {
        #section-tambah_akun>div:first-child {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    function switchTabAkun(tab) {
        const isMurid = tab === 'murid';
        document.getElementById('formAkunMurid').style.display = isMurid ? '' : 'none';
        document.getElementById('formAkunGuru').style.display = isMurid ? 'none' : '';
        const on = 'flex:1; padding:9px; border-radius:8px; border:1.5px solid var(--green); background:var(--green); color:#fff; font-weight:600; font-size:13px; cursor:pointer; transition:.2s';
        const off = 'flex:1; padding:9px; border-radius:8px; border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-weight:600; font-size:13px; cursor:pointer; transition:.2s';
        document.getElementById('tabMuridAkun').style.cssText = isMurid ? on : off;
        document.getElementById('tabGuruAkun').style.cssText = isMurid ? off : on;
    }
</script>