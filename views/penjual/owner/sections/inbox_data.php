<?php
// views/penjual/owner/sections/inbox_data.php

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
        'message' => "Halo B.Sukamto,\n\nSaldo anda telah diperbarui. Tambahan Rp.150.000 dari penarikan sebelumnya.\n\nSaldo saat ini : Rp.1.900.000.\n\nTerima Kasih."
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
        'message' => "Halo Bos,\n\nUntuk jadwal shift besok pagi biar saya yang handle, lalu shift siang dilanjut oleh Fandi ya.\n\nMohon konfirmasinya."
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
        'message' => "Halo Kantin,\n\nSaya mau kasi ulasan buat menu Ayam Gorengnya. Rasanya enak banget dan bumbunya pas, tapi porsinya sedikit bang kalo buat harga segitu hehe. Sukses terus!"
    ],
    [
        'id' => 4,
        'sender' => 'Sistem',
        'email' => 'admin@gmail.com',
        'subject' => 'Pengumuman sekolah',
        'snippet' => 'Kantin tutup sementara pukul 12.00-13.00 untuk pembersihan.',
        'time' => '3days ago',
        'full_date' => '08:00 AM, 21 May 2026',
        'status' => 'read',
        'avatar' => '<i class="fa-solid fa-graduation-cap"></i>',
        'message' => "Pemberitahuan kepada seluruh pemilik stan kantin,\n\nDiharapkan menutup stan sementara pada pukul 12.00-13.00 WIB sehubungan dengan diadakannya agenda inspeksi kebersihan berkala oleh tim sarpras sekolah.\n\nTerima kasih atas kerjasamanya."
    ]
];