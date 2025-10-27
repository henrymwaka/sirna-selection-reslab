
---

## ✅ 2. `SECURITY.md`
```markdown
# Security Overview (Public)

This fork enhances the original siRNA selection program with modern security practices.

## 🔒 Highlights
- reCAPTCHA v2 verification on login  
- CSRF tokens and prepared statements  
- Password hashing (`password_hash`, `password_verify`)  
- Content-Security-Policy and safe headers  
- Audit logging with IP + timestamp  
- Sensitive configs moved outside webroot  

## 🌐 Server Guidelines
- Serve via **Nginx + php-fpm + fcgiwrap**  
- Restrict access to `config/` and `logs/`  
- Enforce HTTPS and modern TLS (v1.2 / v1.3)  
- Remove write access for `www-data` except logs  

## 🗂️ Sensitive Paths (excluded by .gitignore)

## 📣 Responsible Use
This repository is provided for research continuity.  
Do **not** expose real credentials or API keys in public forks.  
Report issues via GitHub Issues without including sensitive information.
