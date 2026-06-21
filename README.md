<div align="center">

```
███████╗    ██╗  ██╗ █████╗ ███╗   ██╗████████╗██╗███╗   ██╗
██╔════╝    ██║ ██╔╝██╔══██╗████╗  ██║╚══██╔══╝██║████╗  ██║
█████╗      █████╔╝ ███████║██╔██╗ ██║   ██║   ██║██╔██╗ ██║
██╔══╝      ██╔═██╗ ██╔══██║██║╚██╗██║   ██║   ██║██║╚██╗██║
███████╗    ██║  ██╗██║  ██║██║ ╚████║   ██║   ██║██║ ╚████║
╚══════╝    ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═╝   ╚═╝╚═╝  ╚═══╝
```

# 🍽️ E-Kantin
### *by Error 404*

> Sistem manajemen kantin digital — order makanan jadi lebih mudah, cepat, dan modern.

![PHP](https://img.shields.io/badge/PHP-000?style=flat-square&logo=php&logoColor=00f7ff)
![JavaScript](https://img.shields.io/badge/JavaScript-000?style=flat-square&logo=javascript&logoColor=00f7ff)
![CSS3](https://img.shields.io/badge/CSS3-000?style=flat-square&logo=css3&logoColor=00f7ff)
![MySQL](https://img.shields.io/badge/MySQL-000?style=flat-square&logo=mysql&logoColor=00f7ff)
![Status](https://img.shields.io/badge/Status-Active-00f7ff?style=flat-square)

</div>

---

## 📋 Tentang Proyek

**E-Kantin** adalah aplikasi web manajemen kantin yang memudahkan siswa/pengguna untuk melihat menu, melakukan pemesanan, dan melacak transaksi secara digital — tanpa perlu antri panjang.

---

## ✨ Fitur

| Fitur | Deskripsi |
|-------|-----------|
| 🔐 **Login & Register** | Autentikasi pengguna & admin |
| 🍜 **Menu Makanan** | Tampilan menu clean |
| 🛒 **Order** | Pemesanan makanan secara online |
| 📊 **Dashboard Admin** | Kelola admin, kantin, penjual, pembeli |

---

## 🛠️ Tech Stack

```
Frontend  → HTML · CSS · JavaScript
Backend   → PHP
Database  → MySQL
```

---

## 🚀 Cara Install (Pertama Kali)

### Prasyarat
- XAMPP / Laragon sudah terinstall
- PHP >= 7.4
- Git

### Langkah-langkah

```bash
# Verivikasi contibuttor
git config --global user.name "nama lu"
git config --global user.email "email_lu_@example.com"

# 1. Clone repo ke folder htdocs/www
git clone https://github.com/ndyarrr/e_kantin.git

# 2. Masuk ke folder project
cd e-kantin
```

3. **Import database** → buka phpMyAdmin → import file `database/ekantin.sql`
4. **Konfigurasi DB** → edit `config/database.php` sesuai settingan lokal
5. Buka browser → `http://localhost/e-kantin`


---

## 🔄 Workflow Sebelum Ngoding

> Wajib dilakukan setiap kali mau mulai kerja!

```bash
# Cek apakah ada perubahan dari contributor lain
git status

# Kalau ada update, pull dulu sebelum mulai ngoding
git pull origin master
```

---

## 📤 Upload Perubahan

```bash
# 1. Tambahkan semua file yang berubah
git add .

# 2. Tulis pesan commit yang jelas
git commit -m "tambah fitur"

# 3. Push ke repository
git push -u origin master
```

---

## 📁 Struktur Folder

```
$ tree
.
├── assets
│   ├── css
│   │   ├── admin.css
│   │   ├── admin_kantin.css
│   │   ├── auth.css
│   │   ├── chat.css
│   │   ├── login.css
│   │   ├── pembeli.css
│   │   ├── penjual.css
│   │   ├── staf.css
│   │   └── styles.css
│   ├── img
│   │   ├── admin
│   │   │   ├── admin_2.png
│   │   │   └── admin_4.jpg
│   │   ├── ayam.png
│   │   ├── banner
│   │   │   ├── banner_1_21.jpg
│   │   │   ├── banner_14_20.jpg
│   │   │   ├── banner_1_4.jpeg
│   │   │   ├── banner_1_5.jpeg
│   │   │   ├── banner_18_13.png
│   │   │   ├── banner_18_14.png
│   │   │   ├── banner_18_15.jpg
│   │   │   ├── banner_18_16.jpg
│   │   │   ├── banner_18_17.jpeg
│   │   │   ├── banner_18_18.jpg
│   │   │   ├── banner_18_19.png
│   │   │   ├── banner_2_10.png
│   │   │   ├── banner_2_11.png
│   │   │   ├── banner_2_12.png
│   │   │   ├── banner_2_6.png
│   │   │   ├── banner_2_7.png
│   │   │   ├── banner_2_8.png
│   │   │   ├── banner_2_9.png
│   │   │   ├── banner_7_5.png
│   │   │   └── banner_7_6.jpg
│   │   ├── bukti_bayar
│   │   │   ├── bukti_0011000003_1780397369_6a1eb5392b37d.jpg
│   │   │   ├── bukti_0011000003_1780402053_6a1ec7852401f.jpg
│   │   │   ├── bukti_0011000003_1780403278_6a1ecc4e058b7.jpg
│   │   │   ├── bukti_0011000003_1780403736_6a1ece18da676.jpg
│   │   │   ├── bukti_0011000003_1780466511_6a1fc34f23f9b.jpg
│   │   │   ├── bukti_0011000003_1780581355_6a2183ebb04b9.png
│   │   │   ├── bukti_1234567890123456_1780391679_6a1e9effed6d4.jpg
│   │   │   ├── bukti_1234567890123456_1780475587_6a1fe6c373187.png
│   │   │   ├── bukti_1234567890123456_1780582760_6a21896833927.jpg
│   │   │   └── bukti_1234567890123456_1780633716_6a225074371c8.png
│   │   ├── colase.png
│   │   ├── contoh_banner
│   │   │   ├── agus.jpeg
│   │   │   ├── angga.jpeg
│   │   │   ├── basuni.jpeg
│   │   │   ├── dian.jpeg
│   │   │   ├── fajar.jpeg
│   │   │   ├── kom.jpeg
│   │   │   ├── mardika.jpeg
│   │   │   ├── sahudi.jpeg
│   │   │   ├── sukamto.jpeg
│   │   │   └── tika.jpeg
│   │   ├── gb1.jpeg
│   │   ├── gb2.jpeg
│   │   ├── gb3.jpeg
│   │   ├── gb4.png
│   │   ├── kantin
│   │   │   ├── kantin_10.jpeg
│   │   │   ├── kantin_11.jpeg
│   │   │   ├── kantin_12.jpeg
│   │   │   ├── kantin_13.jpeg
│   │   │   ├── kantin_14.jpeg
│   │   │   ├── kantin_15.jpeg
│   │   │   ├── kantin_16.jpeg
│   │   │   ├── kantin_17.jpeg
│   │   │   ├── kantin_18.jpeg
│   │   │   ├── kantin_19.jpeg
│   │   │   ├── kantin_1.jpeg
│   │   │   ├── kantin_20.jpeg
│   │   │   ├── kantin_21.jpeg
│   │   │   ├── kantin_22.png
│   │   │   ├── kantin_2.jpeg
│   │   │   ├── kantin_5.jpeg
│   │   │   ├── kantin_6.jpeg
│   │   │   ├── kantin_7.jpeg
│   │   │   ├── kantin_9.jpeg
│   │   │   ├── kantin_bu_dian.jpeg
│   │   │   ├── kantin_bu_kom.jpeg
│   │   │   ├── kantin_bu_tika.jpeg
│   │   │   ├── kantin_pak_agus.jpeg
│   │   │   ├── kantin_pak_angga.jpeg
│   │   │   ├── kantin_pak_basuni.jpeg
│   │   │   ├── kantin_pak_fajar.jpeg
│   │   │   ├── kantin_pak_mardika.jpeg
│   │   │   ├── kantin_pak_sahudi.jpeg
│   │   │   ├── kantin_pak_sukamto.jpeg
│   │   │   └── profil_owner_9.png
│   │   ├── latar_belakang
│   │   │   ├── latar_14_5.jpeg
│   │   │   ├── latar_18_1.jpg
│   │   │   ├── latar_18_2.jpg
│   │   │   ├── latar_18_5.png
│   │   │   ├── latar_18_6.jpeg
│   │   │   ├── latar_18_7.jpg
│   │   │   ├── latar_18_8.jpg
│   │   │   └── latar_18_9.jpg
│   │   ├── logo_ekantin_hijau.png
│   │   ├── logo_ekantin_putih.png
│   │   ├── logo-esemkita1.png
│   │   ├── menu
│   │   │   ├── menu_10_2.jpg
│   │   │   ├── menu_11_2.jpg
│   │   │   ├── menu_12_2.jpg
│   │   │   ├── menu_12_7.jpg
│   │   │   ├── menu_13_12.jpg
│   │   │   ├── menu_13_18.jpeg
│   │   │   ├── menu_13_7.png
│   │   │   ├── menu_14_12.jpg
│   │   │   ├── menu_14_18.jpg
│   │   │   ├── menu_15_12.jpg
│   │   │   ├── menu_15_18.jpg
│   │   │   ├── menu_15_7.jpg
│   │   │   ├── menu_16_12.jpg
│   │   │   ├── menu_16_13.jpg
│   │   │   ├── menu_16_2.jpg
│   │   │   ├── menu_17_13.jpg
│   │   │   ├── menu_17_2.jpg
│   │   │   ├── menu_1779753828.jpg
│   │   │   ├── menu_17_7.jpg
│   │   │   ├── menu_18_13.jpg
│   │   │   ├── menu_18_14.jpg
│   │   │   ├── menu_18_5.jpg
│   │   │   ├── menu_18_7.jpg
│   │   │   ├── menu_19_17.jpg
│   │   │   ├── menu_19_5.jpg
│   │   │   ├── menu_19_7.jpg
│   │   │   ├── menu_20_5.jpg
│   │   │   ├── menu_21_5.jpg
│   │   │   ├── menu_22_14.jpg
│   │   │   ├── menu_22_5.jpg
│   │   │   ├── menu_23_14.jpg
│   │   │   ├── menu_23_6.jpeg
│   │   │   ├── menu_24_6.jpeg
│   │   │   ├── menu_25_6.jpeg
│   │   │   ├── menu_4_2.jpg
│   │   │   ├── menu_6_2.jpg
│   │   │   ├── menu_7_2.jpg
│   │   │   ├── menu_8_12.jpg
│   │   │   ├── menu_8_7.jpg
│   │   │   └── menu_9_7.jpg
│   │   ├── penjual
│   │   │   ├── penjual_12.jpeg
│   │   │   ├── penjual_13.jpeg
│   │   │   ├── penjual_14.jpeg
│   │   │   ├── penjual_15.jpeg
│   │   │   ├── penjual_16.jpeg
│   │   │   ├── penjual_17.jpeg
│   │   │   ├── penjual_18.jpeg
│   │   │   ├── penjual_19.jpeg
│   │   │   ├── penjual_20.jpeg
│   │   │   ├── profilhgasgd.jpg
│   │   │   ├── profil_owner_5.jpeg
│   │   │   ├── profil_owner_7.jpg
│   │   │   └── profil_staf_24.jpg
│   │   ├── PPAcin.jpeg
│   │   ├── PPAril.jpeg
│   │   ├── PPDanes.jpeg
│   │   ├── PPDedi.jpeg
│   │   ├── PPFandy.png
│   │   ├── PPVaro.jpeg
│   │   ├── profil_murid_0011000003.jpg
│   │   ├── profil_murid_1234567890.png
│   │   ├── promo_banner.png
│   │   ├── qris
│   │   │   ├── qris_18.jpg
│   │   │   ├── qris_1.jpg
│   │   │   ├── qris_2.jpg
│   │   │   ├── qris_7.jpg
│   │   │   └── qris_7.png
│   │   ├── role_admin.jpg
│   │   ├── role_pembeli.jpg
│   │   ├── role_pembeli.png
│   │   ├── role_penjual.jpg
│   │   └── soto.png
│   └── js
│       ├── banner-canvas.js
│       └── inbox-realtime.js
├── auth
│   ├── login.php
│   └── logout.php
├── backend
│   ├── admin.php
│   ├── ambil_chat.php
│   ├── ambil_kontak.php
│   ├── ambil_unread_chat.php
│   ├── hapus_chat.php
│   └── kirim_chat.php
├── config
│   ├── banner_canvas.php
│   ├── database.local.example.php
│   ├── database.php
│   ├── kantin_slot.php
│   └── toko_foto.php
├── controllers
│   └── auth.php
├── e_kantin("error_404").zip
├── get_detail.php
├── hash.php
├── index.php
├── README.md
├── scratch
│   ├── check_html_pesanan.php
│   ├── db_check_orders.php
│   ├── db_test.php
│   ├── read_errors.php
│   ├── read_rendered_debug_file.php
│   ├── read_rendered_debug.php
│   ├── read_sessions.php
│   ├── run_db_inc.php
│   ├── test_autocancel.php
│   ├── test_autoclose.php
│   ├── test_include.php
│   ├── test_kantin_query.php
│   ├── test_menu_details.php
│   ├── test_menu_terlaris.php
│   ├── test_pesanan_directly.php
│   ├── test_render.php
│   └── verify_status_and_menu.php
└── views
    ├── admin
    │   ├── actions
    │   │   ├── kantin.php
    │   │   ├── pembeli.php
    │   │   ├── penjual.php
    │   │   └── tools.php
    │   ├── index.php
    │   └── sections
    │       ├── admin.php
    │       ├── chat.php
    │       ├── dashboard.php
    │       ├── kantin_data.php
    │       ├── kantin.php
    │       ├── pembeli_data.php
    │       ├── pembeli.php
    │       ├── penjual_data.php
    │       ├── penjual.php
    │       ├── profile.php
    │       ├── tambah_akun.php
    │       ├── tools_data.php
    │       └── tools.php
    ├── chat.php
    ├── layouts
    │   ├── about.php
    │   ├── footer.php
    │   ├── hero.php
    │   ├── kantin.php
    │   ├── leaderboard.php
    │   └── navbar.php
    ├── login
    │   └── index.php
    ├── pembeli
    │   ├── actions
    │   │   ├── batalkan_pesanan.php
    │   │   ├── favorit.php
    │   │   ├── keranjang.php
    │   │   ├── proses_profil.php
    │   │   └── upload_bukti.php
    │   ├── checkout.php
    │   ├── index.php
    │   ├── sections
    │   │   ├── beranda.php
    │   │   ├── chat.php
    │   │   ├── favorit.php
    │   │   ├── kantin.php
    │   │   ├── pesanan.php
    │   │   └── profil.php
    │   └── toko.php
    ├── penjual
    │   ├── actions
    │   │   ├── ambil_inbox.php
    │   │   ├── proses_inbox.php
    │   │   ├── proses_kantin.php
    │   │   ├── proses_latar_belakang.php
    │   │   ├── proses_menu.php
    │   │   ├── proses_profil.php
    │   │   └── proses_staf.php
    │   ├── includes
    │   │   └── inbox_query.php
    │   ├── owner
    │   │   ├── index.php
    │   │   └── sections
    │   │       ├── dashboard_data.php
    │   │       ├── dashboard.php
    │   │       ├── export_csv.php
    │   │       ├── inbox_data.php
    │   │       ├── inbox.php
    │   │       ├── kantin.php
    │   │       ├── keuangan_data.php
    │   │       ├── keuangan.php
    │   │       ├── laporan_stok.php
    │   │       ├── menu_data.php
    │   │       ├── menu.php
    │   │       ├── print_keuangan.php
    │   │       ├── print_stok.php
    │   │       ├── profil.php
    │   │       └── staf.php
    │   ├── sections
    │   │   ├── inbox_fragment.php
    │   │   └── inbox.php
    │   └── staf
    │       ├── index.php
    │       └── sections
    │           ├── dashboard_data.php
    │           ├── dashboard.php
    │           ├── inbox_data.php
    │           ├── inbox.php
    │           ├── menu_data.php
    │           ├── menu.php
    │           └── profil.php
    └── report.php

36 directories, 283 files
```

---

## 👥 Tim

<div align="center">

**Error 404** — *damn bro.*

</div>