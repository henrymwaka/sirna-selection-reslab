# 🧪 TESTING_NOTES.md — siRNA System Diagnostics (Private)

**Maintainer:** Dr. Henry Mwaka (@shaykins)  
**Host:** ubuntu-ciat.reslab.dev  
**Domain:** https://sirna.reslab.dev  
**Environment:** Production (Perl + PHP + MySQL + fcgiwrap)

---

## 1️⃣ Purpose

This document records the internal test procedures for validating the end-to-end deployment of the **siRNA Selection Web System** on the Ubuntu-CIAT host.  
The main tool for automated environment verification is `test_env.pl`, which runs live checks on configuration, database connectivity, and environment readiness.

---

## 2️⃣ Diagnostic Script: `test_env.pl`

**Location:**  
/home/shaykins/Projects/siRNA/www/test_env.pl
**Purpose:**  
Performs runtime verification of:

| Check | Description | Expected Result |
|--------|--------------|----------------|
| Perl version | Detects interpreter path and version | Displays e.g. `5.34` |
| Config module | Loads `/config/siRNA_env.pm` | Shows DB name (e.g., `sirna`) |
| Database connectivity | Connects to MySQL via `DBI` | “✅ Connected successfully” |
| reCAPTCHA keys | Verifies `$RECAPTCHA_SITE_KEY` and `$RECAPTCHA_SECRET_KEY` presence | “✅ Keys present” |
| File permissions | Confirms existence and mode of config, lib, and logs directories | “✅ Exists (mode: 770 / 775)” |
| @INC paths | Lists Perl include paths to ensure correct library resolution | Lists `/www/lib` and `/config` |

**Access via browser:**  
https://sirna.reslab.dev/test_env.pl

---

## 3️⃣ Expected HTML Output

Example of a successful run:


✅ siRNA Environment Diagnostic
Perl Modules:
Perl Version: 5.34
Config Module Loaded: sirna
Database Connection:
✅ Connected successfully. Accounts table count: 3
reCAPTCHA Configuration:
✅ Keys present (site + secret)
File System Checks:
✅ All expected files present
Perl @INC Paths:
/home/shaykins/Projects/siRNA/www/lib
/home/shaykins/Projects/siRNA/config

---

## 4️⃣ Common Failures and Fixes

| Symptom | Likely Cause | Fix |
|----------|---------------|-----|
| `.pl` file downloads instead of running | Missing `.pl` handler in Nginx | Add `location ~ \.pl$` block |
| `Can't locate siRNA_env.pm` | Missing config include path | Add `use lib "/home/shaykins/Projects/siRNA/config";` |
| `Database connection failed` | Wrong credentials or DB not running | Verify `DB_HOST`, `DB_USER`, `DB_PASS` in `siRNA_env.pm` |
| `Missing reCAPTCHA keys` | Keys not set in config | Add `$RECAPTCHA_SITE_KEY` and `$RECAPTCHA_SECRET_KEY` |
| Permission denied | `www-data` can’t access files | Run `sudo chmod 755 *.pl` and `chown www-data` |
| 502 Bad Gateway | `fcgiwrap` inactive | Restart service: `sudo systemctl restart fcgiwrap` |

---

## 5️⃣ Usage Workflow

**Typical sequence after deployment or config change:**

1. `sudo nginx -t && sudo systemctl reload nginx`
2. `sudo systemctl restart fcgiwrap`
3. Visit `https://sirna.reslab.dev/test_env.pl`
4. Review:
   - Database connection result  
   - reCAPTCHA key presence  
   - File system and permissions  
   - Perl @INC paths
5. Once verified, **remove or disable** `test_env.pl` for production security.

---

## 6️⃣ Maintenance Notes

- This script intentionally outputs HTML without login protection.  
  It **should never remain public** beyond deployment verification.
- For security, keep it **locally** or rename to `_test_env.pl` and restrict via `.htaccess` or Nginx `allow/deny` rules.
- Rotate MySQL and reCAPTCHA credentials quarterly and re-test with this file.

---

## 7️⃣ Git Rules

**Do not commit this file** or any environment-specific diagnostics.  
Already excluded via `.gitignore`:


Private diagnostics

TESTING_NOTES.md
test_env.pl
---

**Last Updated:** 2025-10-27  
**Verified by:** Dr. Henry Mwaka  
**Status:** ✅ Production environment operational

