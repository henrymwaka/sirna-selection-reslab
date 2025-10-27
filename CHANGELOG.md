# Changelog – WI siRNA Selection Program
All notable changes will be documented in this file.

## [2025-10-26] Security & reCAPTCHA Modernization
**Contributor:** Dr. Henry Shaykins Mwaka  

### Core Enhancements
- ✅ **ReCAPTCHA v2:** Implemented full Google v2 siteverify API call with correct secret/site key separation and error handling.  
- ✅ **Password Security:** Added support for bcrypt/Argon2 hashes via `password_verify()` with logged fallback for legacy plaintext passwords.  
- ✅ **SQL Injection Protection:** Converted all dynamic queries to parameterized prepared statements.  
- ✅ **Session Hardening:** Introduced secure PHP sessions with `httponly`, `samesite=Lax`, and optional HTTPS-only cookies.  
- ✅ **CSRF Defense:** Added token generation and validation on form submissions.  
- ✅ **Randomness:** Replaced `rand()` with `bin2hex(random_bytes(8))` for secure session identifiers.  
- ✅ **Error & Logging Policy:** Disabled on-screen errors in production; standardized audit logging to `logs/login_audit.log`.  
- ✅ **Security Headers:** Added CSP, X-Frame-Options, Referrer-Policy, and optional HTTPS enforcement.  
- ✅ **Code Structure:** Modularized logic into helpers for readability and maintainability.  
- ✅ **UI Preservation:** Legacy Whitehead interface preserved with no visual regressions.  

### New / Updated Files
| File | Description |
|-------|-------------|
| `home.php` | Completely refactored secure login script. |
| `config.php` | Non-versioned configuration file for secrets and DB credentials. |
| `.gitignore` | Added exclusions for sensitive files and logs. |
| `SECURITY.md` | New contributor guidance on secure coding & secret handling. |

### Migration Notes
- Existing plaintext passwords will still authenticate but are logged for migration.  
  Run the upcoming migration utility to re-hash all accounts with `password_hash()`.  
- Ensure reCAPTCHA v2 keys are generated per deployment; do **not** commit them.  


# Changelog

## 2025-10-26 — Security hardening & runtime restoration
- Added hardened `www/home.php`:
  - reCAPTCHA v2 verification (server side), CSRF, prepared statements
  - Password hashing support (bcrypt/argon2) with legacy fallback
  - Audit logging to `www/logs/login_audit.log`
  - Safer `rId` (tasto) generation; changed DB `logins.rId` to `VARCHAR(32)`
- Restored legacy Perl CGI (`www/siRNA_search.cgi`) via `fcgiwrap`
  - Fixed shebang to `/usr/bin/perl`
  - Organized local Perl modules under `www/lib/`
  - Set Perl include paths via `FindBin` + `lib`
- Nginx configuration updated to handle `*.php` and `*.cgi`
- Moved sensitive configuration to `config/` (excluded from repo)
- Updated documentation and `.gitignore`
