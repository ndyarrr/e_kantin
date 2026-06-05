<?php
// views/penjual/owner/sections/keuangan.php

// Hubungkan otomatis dengan file data logic keuangan
include __DIR__ . '/keuangan_data.php';
?>

<div class="kantin-container">

    <?php if (isset($_SESSION['feedback_kas'])): ?>
        <div id="feedbackKas" style="background: <?= $_SESSION['feedback_kas']['type'] === 'success' ? '#e6f4ea' : '#fce8e6' ?>; 
                    color: <?= $_SESSION['feedback_kas']['type'] === 'success' ? '#137333' : '#c5221f' ?>; 
                    padding: 12px 16px; border-radius: 8px; font-weight: 600; font-size: 13.5px; margin-bottom: 15px;
                    border: 1px solid <?= $_SESSION['feedback_kas']['type'] === 'success' ? '#c2e7cd' : '#fad2cf' ?>;
                    transition: opacity 0.5s ease;">
            <?= $_SESSION['feedback_kas']['type'] === 'success' ? '✅' : '⚠️' ?>
            <?= $_SESSION['feedback_kas']['msg']; unset($_SESSION['feedback_kas']); ?>
        </div>
        <script>
            (function() {
                var el = document.getElementById('feedbackKas');
                if (el) {
                    setTimeout(function() {
                        el.style.opacity = '0';
                        setTimeout(function() { el.remove(); }, 500);
                    }, 4000);
                }
            })();
        </script>
    <?php endif; ?>

    <div class="kas-grid-cards">
        <div class="kas-card">
            <i class="fa-solid fa-wallet" style="color: #22c55e;"></i>
            <div class="kas-label">Saldo Saat Ini</div>
            <div class="kas-value">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-arrow-trend-up" style="color: #16a34a;"></i>
            <div class="kas-label"><?= $filter_tanggal === 'semua' ? 'Total Pemasukan' : 'Pemasukan Tanggal Terpilih' ?></div>
            <div class="kas-value">Rp <?= number_format($pemasukan_hari_ini, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-arrow-trend-down" style="color: #dc2626;"></i>
            <div class="kas-label"><?= $filter_tanggal === 'semua' ? 'Total Pengeluaran' : 'Pengeluaran Tanggal Terpilih' ?></div>
            <div class="kas-value">Rp <?= number_format($pengeluaran_hari_ini, 0, ',', '.') ?></div>
        </div>
        <div class="kas-card">
            <i class="fa-solid fa-receipt" style="color: #3498db;"></i>
            <div class="kas-label"><?= $filter_tanggal === 'semua' ? 'Total Aktivitas Transaksi' : 'Jumlah Transaksi Tanggal' ?></div>
            <div class="kas-value"><?= $total_transaksi ?> Catatan</div>
        </div>
    </div>

    <div class="kas-filter-bar">
        <div class="kas-filter-left" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <i class="fa-solid fa-filter" style="color:#3498db;"></i>
            <span>Pilih Tanggal Data:</span>
            <input type="date" class="kas-input-date" id="filterKasTanggal" value="<?= $filter_tanggal !== 'semua' ? $filter_tanggal : '' ?>" onchange="pindahTanggal(this.value)">
            <?php if ($filter_tanggal !== 'semua'): ?>
                <button class="kas-btn-all" onclick="pindahTanggal('semua')" style="padding: 6px 12px; border-radius: 6px; border: 1.5px solid #cbd5e1; background: #fff; color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                    <i class="fa-solid fa-list"></i> Tampilkan Semua
                </button>
            <?php else: ?>
                <span style="font-size: 12px; background: #e0f2fe; color: #0369a1; padding: 6px 10px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fa-solid fa-eye"></i> Semua Tanggal
                </span>
                <button class="kas-btn-today" onclick="pindahTanggal('<?= date('Y-m-d') ?>')" style="padding: 6px 12px; border-radius: 6px; border: 1.5px solid #3498db; background: #fff; color: #3498db; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;">
                    <i class="fa-solid fa-calendar-day"></i> Hari Ini
                </button>
            <?php endif; ?>
        </div>
        <div class="kas-btn-group">
            <button class="kas-btn-print" onclick="cetakLaporanKeuangan()">
                <i class="fa-solid fa-print"></i> Cetak Laporan
            </button>
            <button class="kas-btn-add" style="background-color: #16a34a;" onclick="bukaModalPemasukan()">
                <i class="fa-solid fa-circle-plus"></i> Catat Pemasukan
            </button>
            <button class="kas-btn-add" style="background-color: #dc2626;" onclick="bukaModalKas()">
                <i class="fa-solid fa-circle-minus"></i> Catat Pengeluaran
            </button>
        </div>
    </div>

    <div class="kas-table-container">
        <table class="kas-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No.</th>
                    <th>Tanggal Log</th>
                    <th>Jenis</th>
                    <th>Keterangan / Catatan</th>
                    <th>Nominal</th>
                    <th style="text-align: center; width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($query_riwayat) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 500;">Belum ada catatan keuangan yang tersimpan di database toko Anda.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1; 
                    while ($row = mysqli_fetch_assoc($query_riwayat)): 
                        $isPemasukan = $row['tipe'] === 'masuk';
                        $classWarna  = $isPemasukan ? 'text-pemasukan' : 'text-pengeluaran';
                        $prefixTanda = $isPemasukan ? '+ Rp ' : '- Rp ';
                    ?>
                        <tr>
                            <td style="text-align: center;"><?= $no++ ?>.</td>
                            <td>
                                <strong><?= date('d M Y', strtotime($row['tanggal'])) ?></strong>
                                <br><small style="color:#94a3b8; font-size:11px;">Input: <?= date('d/m/y H:i', strtotime($row['dibuat_pada'])) ?></small>
                            </td>
                            <td>
                                <span class="<?= $classWarna ?>" style="text-transform: uppercase; font-size: 11px; background: <?= $isPemasukan ? '#e6f4ea':'#fce8e6' ?>; padding: 4px 8px; border-radius:4px; display:inline-block;">
                                    <?= $isPemasukan ? 'Pemasukan' : 'Pengeluaran' ?>
                                </span>
                            </td>
                            <td style="max-width: 220px;">
                                <?php
                                    $ket_display = $row['keterangan'];
                                    // Ambil baris pertama saja untuk tampilan tabel
                                    $ket_baris   = explode("\n", $ket_display);
                                    $ket_singkat = trim($ket_baris[0]);
                                    if (mb_strlen($ket_singkat) > 60) {
                                        $ket_singkat = mb_substr($ket_singkat, 0, 57) . '...';
                                    }
                                ?>
                                <span title="<?= htmlspecialchars($ket_display) ?>"><?= htmlspecialchars($ket_singkat) ?></span>
                            </td>
                            <td class="<?= $classWarna ?>" style="font-size: 15px;">
                                <strong><?= $prefixTanda . number_format($row['jumlah'], 0, ',', '.') ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-detail-kas" onclick='bukaModalDetailKas(<?= htmlspecialchars(json_encode([
                                    'tanggal' => date('d M Y', strtotime($row['tanggal'])),
                                    'dibuat' => date('d/m/y H:i', strtotime($row['dibuat_pada'])),
                                    'tipe' => $isPemasukan ? 'Pemasukan' : 'Pengeluaran',
                                    'jumlah' => ($isPemasukan ? '+ Rp ' : '- Rp ') . number_format($row['jumlah'], 0, ',', '.'),
                                    'keterangan' => $row['keterangan']
                                ]), ENT_QUOTES, 'UTF-8') ?>)'>
                                    <i class="fa-solid fa-circle-info"></i> Detail
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="modalKasManual" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); box-sizing: border-box;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-arrow-trend-down" style="color:#dc2626;"></i> Catat Pengeluaran</h3>
            <button onclick="tutupModalKas()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
        </div>
        <div style="height: 1px; background: #e2e8f0; margin-bottom: 15px;"></div>

        <!-- Info otomatis: tanggal & dibuat_pada -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #475569;">
                <span><i class="fa-solid fa-calendar-day" style="color:#dc2626;"></i> <strong>Tanggal</strong></span>
                <span id="tanggalOtomatis" style="font-weight: 700; color: #1e293b;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8;">
                <span><i class="fa-solid fa-clock"></i> Dibuat pada</span>
                <span id="dibuatPadaOtomatis"></span>
            </div>
        </div>
        
        <form action="index.php?section=keuangan" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
            <input type="hidden" name="_current_section" value="keuangan">
            <input type="hidden" name="action" value="tambah_keuangan">
            <input type="hidden" name="tipe" value="keluar">
            <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">
            
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 12.5px; font-weight: 600; color: #475569;">Nominal Pengeluaran (Rp)</label>
                <input type="number" name="jumlah" id="inputJumlahKas" placeholder="Contoh: 35000" min="1" required
                       style="padding: 8px 10px; border: 1.5px solid #fca5a5; border-radius: 6px; font-size: 13px; outline: none;"
                       onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#fca5a5'">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 12.5px; font-weight: 600; color: #475569;">Keterangan Pengeluaran</label>
                <textarea name="keterangan" rows="3" placeholder="Contoh: Kulakan kertas bungkus soto & minyak goreng" required
                          style="padding: 8px 10px; border: 1.5px solid #fca5a5; border-radius: 6px; font-size: 13px; font-family: inherit; resize: vertical; outline: none;"
                          onfocus="this.style.borderColor='#dc2626'" onblur="this.style.borderColor='#fca5a5'"></textarea>
            </div>
            
            <button type="submit" style="background: #dc2626; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13.5px; margin-top: 5px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Pengeluaran
            </button>
        </form>
    </div>
</div>

<!-- Modal Pemasukan Manual -->
<div id="modalKasPemasukan" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); box-sizing: border-box;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px; color: #1e293b;"><i class="fa-solid fa-arrow-trend-up" style="color:#16a34a;"></i> Catat Pemasukan</h3>
            <button onclick="tutupModalPemasukan()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
        </div>
        <div style="height: 1px; background: #e2e8f0; margin-bottom: 15px;"></div>

        <!-- Info otomatis: tanggal & dibuat_pada -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px;">
            <div style="display: flex; justify-content: space-between; font-size: 12.5px; color: #475569;">
                <span><i class="fa-solid fa-calendar-day" style="color:#16a34a;"></i> <strong>Tanggal</strong></span>
                <span id="tanggalOtomatisMasuk" style="font-weight: 700; color: #1e293b;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #94a3b8;">
                <span><i class="fa-solid fa-clock"></i> Dibuat pada</span>
                <span id="dibuatPadaOtomatisMasuk"></span>
            </div>
        </div>

        <form action="index.php?section=keuangan" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
            <input type="hidden" name="_current_section" value="keuangan">
            <input type="hidden" name="action" value="tambah_keuangan">
            <input type="hidden" name="tipe" value="masuk">
            <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">

            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 12.5px; font-weight: 600; color: #475569;">Nominal Pemasukan (Rp)</label>
                <input type="number" name="jumlah" id="inputJumlahMasuk" placeholder="Contoh: 50000" min="1" required
                       style="padding: 8px 10px; border: 1.5px solid #86efac; border-radius: 6px; font-size: 13px; outline: none;"
                       onfocus="this.style.borderColor='#16a34a'" onblur="this.style.borderColor='#86efac'">
            </div>

            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 12.5px; font-weight: 600; color: #475569;">Keterangan Pemasukan</label>
                <textarea name="keterangan" rows="3" placeholder="Contoh: Pemasukan dari katering acara sekolah" required
                          style="padding: 8px 10px; border: 1.5px solid #86efac; border-radius: 6px; font-size: 13px; font-family: inherit; resize: vertical; outline: none;"
                          onfocus="this.style.borderColor='#16a34a'" onblur="this.style.borderColor='#86efac'"></textarea>
            </div>

            <button type="submit" style="background: #16a34a; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 13.5px; margin-top: 5px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Pemasukan
            </button>
        </form>
    </div>
</div>

<!-- Modal Detail Keuangan -->
<div id="modalDetailKeuangan" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);">
    <div style="background: #fff; padding: 24px; border-radius: 16px; width: 90%; max-width: 440px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); box-sizing: border-box; transform: scale(0.95); transition: transform 0.2s ease; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; font-family: 'Poppins', sans-serif;"><i class="fa-solid fa-circle-info" style="color:#0369a1;"></i> Detail Catatan Keuangan</h3>
            <button onclick="tutupModalDetailKas()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
        </div>
        <div style="height: 1px; background: #e2e8f0; margin-bottom: 16px;"></div>

        <div style="display: flex; flex-direction: column; gap: 12px; font-family: 'Poppins', sans-serif;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Tanggal Transaksi</span>
                <span id="detailTanggal" style="font-size: 13px; font-weight: 700; color: #1e293b;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Waktu Input</span>
                <span id="detailDibuat" style="font-size: 13px; font-weight: 700; color: #1e293b;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Jenis Transaksi</span>
                <span id="detailTipe" style="font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; display: inline-block;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Nominal</span>
                <span id="detailJumlah" style="font-size: 15px; font-weight: 800;"></span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px; padding-top: 4px;">
                <span style="font-size: 13px; color: #64748b; font-weight: 500;">Keterangan / Catatan:</span>
                <div id="detailKeterangan" style="font-size: 13px; color: #334155; line-height: 1.5; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; white-space: pre-wrap; word-break: break-word; font-family: inherit;"></div>
            </div>
        </div>

        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button onclick="tutupModalDetailKas()" style="padding: 9px 18px; font-weight: 700; font-size: 13px; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-family: 'Poppins', sans-serif; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
function cetakLaporanKeuangan() {
    const filterVal = document.getElementById('filterKasTanggal').value || 'semua';
    window.open('<?= $base_path ?>/views/penjual/owner/sections/print_keuangan.php?filter_date=' + filterVal, '_blank');
}
function pindahTanggal(val) {
    window.location.href = "index.php?section=keuangan&filter_date=" + val;
}
function bukaModalKas() {
    // Isi tanggal & waktu otomatis saat modal dibuka
    const now = new Date();
    const opsi = { day: '2-digit', month: 'long', year: 'numeric' };
    document.getElementById('tanggalOtomatis').textContent = now.toLocaleDateString('id-ID', opsi);
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('dibuatPadaOtomatis').textContent =
        `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
    document.getElementById('modalKasManual').style.display = 'flex';
    // Fokus ke input nominal
    setTimeout(() => document.getElementById('inputJumlahKas').focus(), 100);
}
function tutupModalKas() {
    document.getElementById('modalKasManual').style.display = 'none';
}
function bukaModalPemasukan() {
    const now = new Date();
    const opsi = { day: '2-digit', month: 'long', year: 'numeric' };
    document.getElementById('tanggalOtomatisMasuk').textContent = now.toLocaleDateString('id-ID', opsi);
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('dibuatPadaOtomatisMasuk').textContent =
        `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
    document.getElementById('modalKasPemasukan').style.display = 'flex';
    setTimeout(() => document.getElementById('inputJumlahMasuk').focus(), 100);
}
function tutupModalPemasukan() {
    document.getElementById('modalKasPemasukan').style.display = 'none';
}
function bukaModalDetailKas(data) {
    document.getElementById('detailTanggal').textContent = data.tanggal;
    document.getElementById('detailDibuat').textContent = data.dibuat;
    
    const tipeEl = document.getElementById('detailTipe');
    tipeEl.textContent = data.tipe;
    if (data.tipe === 'Pemasukan') {
        tipeEl.style.background = '#e6f4ea';
        tipeEl.style.color = '#137333';
        document.getElementById('detailJumlah').style.color = '#16a34a';
    } else {
        tipeEl.style.background = '#fce8e6';
        tipeEl.style.color = '#c5221f';
        document.getElementById('detailJumlah').style.color = '#dc2626';
    }
    
    document.getElementById('detailJumlah').textContent = data.jumlah;
    document.getElementById('detailKeterangan').textContent = data.keterangan;
    
    const modal = document.getElementById('modalDetailKeuangan');
    modal.style.display = 'flex';
}
function tutupModalDetailKas() {
    document.getElementById('modalDetailKeuangan').style.display = 'none';
}
// Menutup modal otomatis jika area luar diklik
window.onclick = function(event) {
    let modal        = document.getElementById('modalKasManual');
    let modalMasuk   = document.getElementById('modalKasPemasukan');
    let modalDetail  = document.getElementById('modalDetailKeuangan');
    if (event.target == modal)       { modal.style.display = 'none'; }
    if (event.target == modalMasuk)  { tutupModalPemasukan(); }
    if (event.target == modalDetail) { tutupModalDetailKas(); }
}
</script>