# 🧬 siRNA Selection Portal (ResLab Fork)

A modernized and resecured fork of the **siRNA Selection Program** originally developed at the [Whitehead Institute for Biomedical Research](https://sirna.wi.mit.edu/).

This independent project by **Dr. Henry Mwaka (2025)** rebuilds the legacy CGI–Perl and PHP system to meet modern standards of **security**, **compatibility**, and **maintainability**, while preserving the original scientific logic for siRNA target selection.

---

## 🚀 Overview

The siRNA Selection Portal enables researchers to design small interfering RNAs (siRNAs) targeting specific nucleotide sequences, applying empirical design rules established by the Whitehead Institute.

This fork modernizes the infrastructure and codebase without altering the biological algorithms.  
All computational logic remains consistent with the original Whitehead implementation.

---

## 🧩 Key Enhancements (2025 Fork)

| Category | Enhancement |
|-----------|-------------|
| **Security** | Added reCAPTCHA v2, CSRF protection, password hashing (`password_hash()` / bcrypt), and strict Content Security Policy |
| **Database** | Updated schema for MySQL 8.x (UTF8MB4 support, improved column definitions, transaction-safe operations) |
| **Authentication** | New password migration tool for legacy accounts (`migrate_passwords.php`) |
| **Perl Modules** | Refactored `Check.pm`, `GetSession.pm`, `siRNA_util.pm`; centralized configuration in `/config/siRNA_env.pm` |
| **Server Stack** | Compatible with **Nginx**, **PHP 8.1+**, **fcgiwrap**, and **Perl 5.34+** |
| **Logging** | Unified login and CGI audit logs in `/www/logs/` |
| **Documentation** | Added `DEPLOY_RUNBOOK.md`, `.gitignore`, and optional internal security notes |

---

## 🧱 Directory Structure

siRNA/
├── www/ → Webroot (PHP, CGI, JS, HTML)
│ ├── home.php → Secure login page
│ ├── siRNA_search.cgi → Core CGI for siRNA design
│ ├── lib/ → Local Perl modules
│ ├── logs/ → Runtime logs (excluded from Git)
│ ├── keep/ → Static assets and images
│ └── migrate_passwords.php → Password migration script
│
├── config/ → Private configuration (excluded from Git)
│ └── siRNA_env.pm
│
├── cgi-bin/ → Legacy sources (optional reference)
│
├── DEPLOY_RUNBOOK.md → Step-by-step setup guide
├── SECURITY_NOTES_INTERNAL.md → Optional internal notes (private)
├── .gitignore → Git exclusions
├── LICENSE → MIT license
└── README.md → This document

---

## ⚙️ System Requirements

| Component | Recommended Version |
|------------|--------------------|
| **Operating System** | Ubuntu 22.04 LTS or newer |
| **Web Server** | Nginx 1.18+ with PHP-FPM |
| **PHP** | 8.1+ with MySQLi extension |
| **Perl** | 5.34+ with CGI, DBI, and DBD::mysql |
| **Database** | MySQL 8.0+ or MariaDB equivalent |
| **FastCGI Wrapper** | fcgiwrap (for `.cgi` scripts) |

---

## 🧰 Quick Deployment Guide

1. **Clone the Repository**
   ```bash
   git clone https://github.com/henrymwaka/siRNA-ResLab.git
   cd siRNA-ResLab

2. Install Dependencies
sudo apt update
sudo apt install nginx php8.1-fpm fcgiwrap mariadb-server perl libdbi-perl libdbd-mysql-perl

3.Database Setup
CREATE DATABASE sirna CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sirna_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON sirna.* TO 'sirna_user'@'localhost';
FLUSH PRIVILEGES;
4. Nginx Configuration Example
5. Set Permissions

sudo chown -R www-data:www-data www
sudo chmod -R 775 www
sudo chown -R shaykins:www-data config
sudo chmod -R 770 config

6. Access the Web Interface

https://sirna.reslab.dev/home.php


🔐 Password Migration

Run this one-time utility if upgrading from plaintext passwords:

sudo -u www-data php /home/shaykins/Projects/siRNA/www/migrate_passwords.php


It:

Detects unencrypted passwords in accounts

Hashes them using bcrypt

Updates the database securely

Logs the results to /www/logs/login_audit.log

Ensure your schema is correct:

ALTER TABLE accounts MODIFY password VARCHAR(255) NOT NULL;


💡 Acknowledgements

Whitehead Institute for Biomedical Research – Original siRNA algorithm and system

Open-source community – Perl, PHP, Nginx, and MySQL maintainers 
