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
git clone https://github.com/ndyarrr/e-kantin.git

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
tree
.
├── assets
│   ├── css
│   │   ├── admin.css
│   │   ├── admin_kantin.css
│   │   ├── auth.css
│   │   └── styles.css
│   └── img
│       ├── admin
│       │   └── admin_2.jpg
│       ├── colase.png
│       ├── gb1.jpeg
│       ├── gb2.jpeg
│       ├── gb3.jpeg
│       ├── kantin
│       │   ├── toko_3.jpeg
│       │   └── toko_4.jpeg
│       ├── kls.png
│       ├── kolase.png
│       ├── logo-esemkita.png
│       ├── menu
│       └── pak-fajar.png
├── auth
│   ├── login.php
│   └── logout.php
├── backend
│   └── admin.php
├── config
│   └── database.php
├── controllers
│   └── auth.php
├── hash.php
├── index.php
├── README.md
└── views
    ├── admin
    │   ├── actions
    │   │   └── kantin.php
    │   ├── index.php
    │   └── sections
    │       ├── admin.php
    │       ├── dashboard.php
    │       ├── kantin_data.php
    │       ├── kantin.php
    │       └── profile.php
    ├── layouts
    │   ├── about.php
    │   ├── footer.php
    │   ├── hero.php
    │   ├── kantin.php
    │   ├── leaderboard.php
    │   └── navbar.php
    └── siswa
        └── dashboard.php

17 directories, 37 files
```

---

## 👥 Tim

<div align="center">

**Error 404** — *damn bro.*

</div>