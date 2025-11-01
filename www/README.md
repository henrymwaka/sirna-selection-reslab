# siRNA Selection Program — `www` Directory

This directory hosts the web front-end of the siRNA Selection Program, originally developed at the Whitehead Institute for Biomedical Research.
It contains all PHP, CGI, JavaScript, and Perl interface components that interact with the back-end logic located under `cgi-bin/lib`.

---

## 📁 Project Structure

```
/home/shaykins/Projects/siRNA/
├── www/                # Web root (PHP, CGI, JS, assets)
│   ├── lib/            # Front-end Perl modules
│   ├── tmp/            # Temporary session directories (auto-created)
│   ├── logs/           # Log files (error, debug, siRNA.log)
│   └── *.cgi, *.php    # Application endpoints
└── cgi-bin/lib/        # Core Perl libraries and analysis scripts
```

---

## ⚙️ Configuration Overview

### 1. Environment File

**File:** `cgi-bin/lib/siRNA_env.pm`
Update absolute paths to match your local project root:

```perl
$MyClusterLDLib = "/home/shaykins/Projects/siRNA/cgi-bin/lib";
$MyBlastDataDir = "/home/shaykins/Projects/siRNA/cgi-bin/db";
*Home           = "/home/shaykins/Projects/siRNA/www/tmp";
*MyClusterHome  = "/home/shaykins/Projects/siRNA/www/tmp";
*MyHomePage     = "home.php";
```

### 2. Logging Configuration

**File:** `www/siRNA_log.conf`

```ini
log4j.appender.FILE.filename=/home/shaykins/Projects/siRNA/www/logs/siRNA.log
```

Ensure the log directory exists and is writable:

```bash
sudo -u www-data mkdir -p /home/shaykins/Projects/siRNA/www/logs
sudo chown -R www-data:www-data /home/shaykins/Projects/siRNA/www/logs
```

### 3. Database Credentials

Update MySQL credentials in the following files:

```
authenticate.php
home.php
register.php
Check.pm
Database.pm
```

Each should reference your valid database host, user, and password.

### 4. Administrator Email Address

Replace all placeholder emails (`admin@domain.com`) with your actual admin contact in:

```
Check.pm
siRNA.cgi
GenbankAcc.pm
GenbankGI.pm
siRNA_util.pm
register.php
```

Example:

```perl
From: admin@reslab.dev
Reply-To: admin@reslab.dev
```

---

## 🔗 Symbolic Links

Ensure these symlinks are created so the web application can access shared library modules.

**Link library folders:**

```bash
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Attribute
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Email
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Database.pm
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/File
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/JobStatus.pm
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Log
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Mail
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Params
ln -s /home/shaykins/Projects/siRNA/cgi-bin/lib/Sort.pm
```

**Link entry pages:**

```bash
ln -s home.php index.php
```

---

## 🧩 Temporary Directory

Create the working directory for user sessions and BLAST outputs:

```bash
mkdir -p /home/shaykins/Projects/siRNA/www/tmp
chmod 775 /home/shaykins/Projects/siRNA/www/tmp
chown www-data:www-data /home/shaykins/Projects/siRNA/www/tmp
```

Each new search session will automatically create a subfolder under this path:

```
tmp/S_<session_id>--/
```

---

## 🤮 Testing Your Setup

After completing configuration:

```bash
sudo systemctl restart fcgiwrap
curl -vk "https://sirna.reslab.dev/siRNA.cgi?tasto=1001"
```

If correctly configured, you should no longer see redirects for missing files and the application will advance to the next stage.

---

### ✅ Quick Checklist

| Component       | Correct Path                                       |
| --------------- | -------------------------------------------------- |
| Root web folder | `/home/shaykins/Projects/siRNA/www`                |
| CGI libraries   | `/home/shaykins/Projects/siRNA/cgi-bin/lib`        |
| Temporary files | `/home/shaykins/Projects/siRNA/www/tmp`            |
| Log files       | `/home/shaykins/Projects/siRNA/www/logs/siRNA.log` |
| Entry point     | `home.php` (symlinked as `index.php`)              |

---

**Maintainer:**
Henry Mwaka — [admin@reslab.dev](mailto:admin@reslab.dev)
Whitehead-based siRNA Selection Program (Customized Deployment)
