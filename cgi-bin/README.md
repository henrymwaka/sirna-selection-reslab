# siRNA Selection Program — `cgi-bin` Directory

This directory contains the core CGI scripts and backend utilities for the **siRNA Selection Program** originally developed at the Whitehead Institute for Biomedical Research.

It has been adapted for deployment under:

```
/home/shaykins/Projects/siRNA/cgi-bin/
```

---

## 🧩 Directory Overview

### **1. `lib/`**

Contains the Perl modules and utility scripts used throughout the siRNA system.

#### Files requiring configuration

**`Database.pm`**

```perl
$host       = 'localhost';       # MySQL host
$DBUser     = 'your_mysql_user';
$DBPassword = 'your_mysql_password';
```

**`siRNA_env.pm`**
Defines key environment paths. Update to match your local installation:

```perl
our $MyClusterLib   = '/home/shaykins/Projects/siRNA/cgi-bin/lib';
our $MyBlastDataDir = '/home/shaykins/Projects/siRNA/cgi-bin/db';
our $Home           = '/home/shaykins/Projects/siRNA/www/tmp';
```

**`siRNA_log.conf`**

```ini
log4j.appender.FILE.filename=/home/shaykins/Projects/siRNA/www/logs/siRNA.log
```

**`siRNA_step2.cgi`**

```perl
#!/usr/bin/perl -w
use lib '/home/shaykins/Projects/siRNA/cgi-bin/lib';
chdir '/home/shaykins/Projects/siRNA/cgi-bin/lib';
# Update contact details
From: admin@reslab.dev
Reply-To: admin@reslab.dev
```

**`siRNA_util.pm`**

```perl
mailto:admin@reslab.dev
```

---

### **2. `db/`**

Contains BLAST-formatted nucleotide databases and thermodynamic parameter files.

Due to space limits, this repository includes only truncated FASTA examples (top 10 lines).
Full reference datasets can be fetched from public sources.

#### Update scripts

Located under:

```
/home/shaykins/Projects/siRNA/cgi-bin/db/bin/
```

* **`download_refseq.sh`** — Downloads RefSeq nucleotide sequences and builds BLAST databases.
* **`download_ensemble.sh`** — Downloads and formats Ensembl human, mouse, and rat cDNA datasets for BLAST.

Each script automatically:

* Retrieves data via FTP from NCBI or Ensembl.
* Normalizes FASTA headers.
* Formats BLAST databases using `makeblastdb`.
* Logs progress to `/home/shaykins/Projects/siRNA/www/logs/ensembl_update.log`.

**Sample command:**

```bash
bash /home/shaykins/Projects/siRNA/cgi-bin/db/bin/download_ensemble.sh
```

---

### **3. `mysqldb/`**

Contains `.sql` definitions for initializing MySQL databases used by the siRNA application.

To create these databases:

```bash
mysql -u your_user -p < /home/shaykins/Projects/siRNA/cgi-bin/mysqldb/sirna.sql
```

#### Files

* **`entrez_gene.sql`** — Schema for the `entrez_gene` database (downloadable from NCBI: `ftp://ftp.ncbi.nlm.nih.gov/gene`)
* **`sirna.sql`** — Schema for the main siRNA user database.
* **`sirna2.sql`** — Schema for the `xref` database linking gene IDs with transcript isoforms.

---

## ⚙️ Deployment Notes

1. Ensure **fcgiwrap** and **Nginx** are correctly configured to serve CGI scripts.
2. Make the following directories writable by the web server (`www-data`):

   ```bash
   sudo chown -R www-data:www-data /home/shaykins/Projects/siRNA/www/tmp
   sudo chown -R www-data:www-data /home/shaykins/Projects/siRNA/www/logs
   ```
3. Confirm that environment variables in `siRNA_env.pm` point to the current project root.
4. Verify BLAST executables (e.g., `makeblastdb`) are installed and available in `/usr/bin` or `/usr/local/bin`.

---

# siRNA Selection Program — `cgi-bin` Directory

This directory contains the core CGI scripts and backend utilities for the **siRNA Selection Program** originally developed at the Whitehead Institute for Biomedical Research.

It has been adapted for deployment under:

```
/home/shaykins/Projects/siRNA/cgi-bin/
```

---

## 🧩 Directory Overview

### **1. `lib/`**
Contains the Perl modules and utility scripts used throughout the siRNA system.

#### Files requiring configuration

**`Database.pm`**
```perl
$host       = 'localhost';       # MySQL host
$DBUser     = 'your_mysql_user';
$DBPassword = 'your_mysql_password';
```

**`siRNA_env.pm`**
Defines key environment paths. Update to match your local installation:

```perl
our $MyClusterLib   = '/home/shaykins/Projects/siRNA/cgi-bin/lib';
our $MyBlastDataDir = '/home/shaykins/Projects/siRNA/cgi-bin/db';
our $Home           = '/home/shaykins/Projects/siRNA/www/tmp';
```

**`siRNA_log.conf`**
```ini
log4j.appender.FILE.filename=/home/shaykins/Projects/siRNA/www/logs/siRNA.log
```

**`siRNA_step2.cgi`**
```perl
#!/usr/bin/perl -w
use lib '/home/shaykins/Projects/siRNA/cgi-bin/lib';
chdir '/home/shaykins/Projects/siRNA/cgi-bin/lib';
# Update contact details
From: admin@reslab.dev
Reply-To: admin@reslab.dev
```

**`siRNA_util.pm`**
```perl
mailto:admin@reslab.dev
```

---

### **2. `db/`**
Contains BLAST-formatted nucleotide databases and thermodynamic parameter files.

Due to space limits, this repository includes only truncated FASTA examples (top 10 lines).  
Full reference datasets can be fetched from public sources.

#### Update scripts

Located under:
```
/home/shaykins/Projects/siRNA/cgi-bin/db/bin/
```

- **`download_refseq.sh`** — Downloads RefSeq nucleotide sequences and builds BLAST databases.  
- **`download_ensemble.sh`** — Downloads and formats Ensembl human, mouse, and rat cDNA datasets for BLAST.

Each script automatically:
- Retrieves data via FTP from NCBI or Ensembl.
- Normalizes FASTA headers.
- Formats BLAST databases using `makeblastdb`.
- Logs progress to `/home/shaykins/Projects/siRNA/www/logs/ensembl_update.log`.

**Sample command:**
```bash
bash /home/shaykins/Projects/siRNA/cgi-bin/db/bin/download_ensemble.sh
```

---

### **3. `mysqldb/`**
Contains `.sql` definitions for initializing MySQL databases used by the siRNA application.

To create these databases:
```bash
mysql -u your_user -p < /home/shaykins/Projects/siRNA/cgi-bin/mysqldb/sirna.sql
```

#### Files
- **`entrez_gene.sql`** — Schema for the `entrez_gene` database (downloadable from NCBI: `ftp://ftp.ncbi.nlm.nih.gov/gene`)
- **`sirna.sql`** — Schema for the main siRNA user database.
- **`sirna2.sql`** — Schema for the `xref` database linking gene IDs with transcript isoforms.

---

## ⚙️ Deployment Notes

1. Ensure **fcgiwrap** and **Nginx** are correctly configured to serve CGI scripts.
2. Make the following directories writable by the web server (`www-data`):
   ```bash
   sudo chown -R www-data:www-data /home/shaykins/Projects/siRNA/www/tmp
   sudo chown -R www-data:www-data /home/shaykins/Projects/siRNA/www/logs
   ```
3. Confirm that environment variables in `siRNA_env.pm` point to the current project root.
4. Verify BLAST executables (e.g., `makeblastdb`) are installed and available in `/usr/bin` or `/usr/local/bin`.

---

## 🧠 Maintenance Tips

- Schedule the Ensembl update script to run weekly:
  ```bash
  0 2 * * 0 bash /home/shaykins/Projects/siRNA/cgi-bin/db/bin/download_ensemble.sh
  ```
- Clean up temporary session directories older than 1 day:
  ```bash
  0 3 * * * find /home/shaykins/Projects/siRNA/www/tmp -type d -mtime +1 -exec rm -rf {} +
  ```
- Review the log file regularly for download or BLAST errors:
  ```bash
  tail -n 50 /home/shaykins/Projects/siRNA/www/logs/ensembl_update.log
  ```

---

## 📜 Version & Attribution

**Original Authors:**  
Whitehead Institute for Biomedical Research — *Bingbing Yuan* (2001–2004)

**Current Adaptation:**  
*Henry Mwaka* — ResLab / NARO Biotechnology Platform (2025)

**License:**  
For internal research use only. Redistribution or commercial use is prohibited without written permission.

---
