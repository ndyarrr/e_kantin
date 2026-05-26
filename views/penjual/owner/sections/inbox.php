<?php
// views/penjual/owner/sections/inbox.php

$dummyMails = [
    [
        'id' => 1,
        'sender' => 'Sistem',
        'email' => 'admin@gmail.com',
        'subject' => 'Pembaruan Saldo Anda Berhasil',
        'snippet' => 'Sistem: Pembaruan saldo anda berhasil.',
        'time' => 'Yesterday',
        'full_date' => '10:25 AM, 30 April 2026',
        'status' => 'read',
        'avatar' => '<i class="fa-solid fa-graduation-cap"></i>',
        'message' => "Halo B.Sukamto,\n\nSaldo anda telah diperbarui. Tambahan Rp.150.000 dari penarikan sebelumnya.\n\nSaldo saat ini : Rp.1.900.000."
    ],
    [
        'id' => 2,
        'sender' => 'Staff Kantin',
        'email' => 'staff_siti@gmail.com',
        'subject' => 'Jadwal Shift',
        'snippet' => 'Staf kantin: Jadwal shift - Jadwal shift membarang na......',
        'time' => '10:15 AM',
        'full_date' => '10:15 AM, 24 May 2026',
        'status' => 'unread',
        'avatar' => '<i class="fa-solid fa-user-tie"></i>',
        'message' => "Halo Bos,\n\nUntuk jadwal shift besok pagi biar saya yang handle, lalu shift siang dilanjut oleh Fandi ya."
    ],
    [
        'id' => 3,
        'sender' => 'Fandi (Murid)',
        'email' => 'fandi_ganteng@student.sch.id',
        'subject' => 'Feedback Menu',
        'snippet' => 'Rasanya enak dan bumbunya pas, tapi porsinya sedikit.',
        'time' => '2days ago',
        'full_date' => '01:14 PM, 22 May 2026',
        'status' => 'read',
        'avatar' => '<i class="fa-solid fa-user"></i>',
        'message' => "Saya mau kasi ulasan buat menu Ayam Gorengnya. Rasanya enak banget, tapi porsinya sedikit bang."
    ]
];
?>

<div class="inbox-search-container" id="inboxSearchContainer">
    <input type="text" class="inbox-search-input" placeholder="🔍 Cari pesan masuk...">
</div>

<div class="inbox-wrapper" id="inboxContainerWrapper">
    
    <div class="inbox-list-wrapper" id="inboxListLeft">
        <div class="inbox-grid-items">
            <?php foreach ($dummyMails as $mail): ?>
            <div class="inbox-item <?= $mail['status'] === 'unread' ? 'is-unread' : '' ?>" id="mail-item-<?= $mail['id'] ?>" onclick="slideMailDetail(<?= htmlspecialchars(json_encode($mail)) ?>)">
                
                <div class="avatar-container">
                    <div class="unread-dot"></div>
                    <div class="avatar-box">
                        <?= $mail['avatar'] ?>
                    </div>
                </div>
                
                <div class="msg-content-left">
                    <h5 class="sender-name"><?= htmlspecialchars($mail['sender']) ?></h5>
                    <div class="msg-subject"><?= htmlspecialchars($mail['subject']) ?></div>
                    <p class="msg-snippet"><?= htmlspecialchars($mail['snippet']) ?></p>
                </div>

                <div class="msg-meta-right">
                    <span class="msg-time"><?= $mail['time'] ?></span>
                    <span class="badge-status <?= $mail['status'] ?>"><?= ucfirst($mail['status']) ?></span>
                </div>
                
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="inbox-detail-panel" id="inboxDetailPanel"></div>

</div>

<script>
function slideMailDetail(mail) {
    const wrapper = document.getElementById('inboxContainerWrapper');
    const detailPanel = document.getElementById('inboxDetailPanel');
    const listLeft = document.getElementById('inboxListLeft');
    const searchBox = document.getElementById('inboxSearchContainer');

    document.querySelectorAll('.inbox-item').forEach(item => item.classList.remove('active'));
    const currentItem = document.getElementById('mail-item-' + mail.id);
    if(currentItem) currentItem.classList.add('active');

    detailPanel.innerHTML = `
        <div class="detail-active-wrapper">
            <div class="detail-actions-top">
                <button class="btn-close-panel" onclick="closeMailDetail()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-inbox-action delete" onclick="alert('Pesan dihapus!'); closeMailDetail();">
                        <i class="fa-solid fa-trash"></i> Hapus
                    </button>
                    <button class="btn-inbox-action reply" onclick="alert('Balas pesan...')">
                        <i class="fa-solid fa-reply"></i> Balas
                    </button>
                </div>
            </div>

            <div class="detail-header-info">
                <div class="detail-subject-title">\${escapeHtml(mail.subject)}</div>
                <div class="detail-meta-row">
                    <div class="avatar-box">\${mail.avatar}</div>
                    <div class="detail-meta-text">
                        <div><span class="from-name">\${escapeHtml(mail.sender)}</span> <span class="from-email">(\${escapeHtml(mail.email)})</span></div>
                        <div class="full-time">\${mail.full_date}</div>
                    </div>
                </div>
            </div>

            <div class="detail-body-text">\${escapeHtml(mail.message)}</div>
        </div>
    `;

    // 🌟 LOGIK UTAMA: Sembunyikan list kiri & search box, lalu buat detail panel melebar 100%
    listLeft.style.display = 'none';
    searchBox.style.display = 'none';
    
    detailPanel.style.width = '100%';
    detailPanel.style.right = '0';

    wrapper.classList.add('detail-open');
    detailPanel.classList.add('open');
}

function closeMailDetail() {
    const detailPanel = document.getElementById('inboxDetailPanel');
    const listLeft = document.getElementById('inboxListLeft');
    const searchBox = document.getElementById('inboxSearchContainer');

    // 🌟 KEMBALIKAN: Munculkan kembali list kiri dan box search ke bentuk semula
    listLeft.style.display = 'flex';
    searchBox.style.display = 'block';
    
    // Kembalikan ukuran panel detail ke default (biar kalau dibuka lagi nanti gak ngebug)
    detailPanel.style.width = '';
    detailPanel.style.right = '';

    document.getElementById('inboxContainerWrapper').classList.remove('detail-open');
    detailPanel.classList.remove('open');
    document.querySelectorAll('.inbox-item').forEach(item => item.classList.remove('active'));
}

function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>