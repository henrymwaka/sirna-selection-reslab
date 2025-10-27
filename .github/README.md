<div align="center">
  <img src="https://sirna.reslab.dev/keep/header_wi_01.jpg" width="600" alt="siRNA Selection Program Banner">
  <h3>🧬 siRNA Selection — ResLab Edition</h3>
  <p>
    A modernized version of the <strong>Whitehead Institute siRNA selection platform</strong>, re-engineered for Ubuntu/nginx environments.<br>
    Integrated with <strong>secure PHP login</strong>, <strong>reCAPTCHA v2</strong>, <strong>MySQL 8.0</strong>, and <strong>Perl CGI modules</strong> optimized for production.
  </p>
  <p>
    <a href="https://sirna.reslab.dev"><b>Live Demo</b></a> |
    <a href="https://github.com/henrymwaka/sirna-selection-reslab"><b>Source Code</b></a>
  </p>
</div>

---

### 📘 Overview
This project refactors the original Whitehead Institute siRNA Selection Program into a modular, secure, and modern web platform for sequence design and validation.

### 🔧 Tech Stack
- **PHP 8.1+** (Login, session, and reCAPTCHA integration)
- **Perl 5.34+** (Core CGI scripts and algorithm logic)
- **MySQL 8.0** (Legacy-compatible schema)
- **nginx + fcgiwrap** (High-performance web stack)
- **reCAPTCHA v2** (Bot protection)
- **Audit Logging & Daily Limits**

### ⚙️ Modern Additions
- Secure password hashing and optional migration
- Config modularization (`config/siRNA_env.pm`)
- Hardened PHP session management and CSRF protection
- Nginx+Perl CGI compatibility for Ubuntu
- MIT-licensed fork with full credit to original WI authors

### 🔗 License
MIT License — see [LICENSE](../LICENSE) for details.
