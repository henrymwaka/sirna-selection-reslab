#!/bin/bash
# ===============================================================
# Whitehead Institute siRNA Program - Ensembl BLAST DB Updater
# Maintainer: Henry Mwaka <admin@reslab.dev>
# Location:   /home/shaykins/Projects/siRNA/cgi-bin/db/bin
# Purpose:    Download and update Ensembl FASTA files for BLAST
# Updated:    30-Oct-2025
# ===============================================================

set -euo pipefail

# ==== CONFIGURATION ============================================================
PROJECT_ROOT="/home/shaykins/Projects/siRNA"
DOWNLOAD="${PROJECT_ROOT}/cgi-bin/db/downloads"
DATA="${PROJECT_ROOT}/cgi-bin/db"
BIN="${PROJECT_ROOT}/cgi-bin/db/bin"
LOGDIR="${PROJECT_ROOT}/www/logs"
LOGFILE="${LOGDIR}/ensembl_update.log"
ADMIN_EMAIL="admin@reslab.dev"

# ==== INITIALIZATION ===========================================================
mkdir -p "${DOWNLOAD}" "${DATA}" "${LOGDIR}"
rm -f "${LOGFILE}"
touch "${LOGFILE}"

cd "${DOWNLOAD}"

echo "===== $(date) Starting Ensembl database update =====" | tee -a "${LOGFILE}"

# ==== FTP SOURCES ==============================================================
declare -A ENSEMBL_URLS=(
  ["human"]="ftp://ftp.ensembl.org/pub/current_fasta/homo_sapiens/cdna/Homo_sapiens.GRC*.cdna.all.fa.gz"
  ["mouse"]="ftp://ftp.ensembl.org/pub/current_fasta/mus_musculus/cdna/Mus_musculus.GRC*.cdna.all.fa.gz"
  ["rat"]="ftp://ftp.ensembl.org/pub/current_fasta/rattus_norvegicus/cdna/Rattus_norvegicus.*.cdna.all.fa.gz"
)

# ==== DOWNLOAD SEQUENCES =======================================================
for species in "${!ENSEMBL_URLS[@]}"; do
  echo "Downloading ${species} cDNA from Ensembl..." | tee -a "${LOGFILE}"
  wget --passive-ftp -nv "${ENSEMBL_URLS[$species]}" \
    -O "${species}.Ensembl.cdna.fa.gz" -a "${LOGFILE}" || {
      echo "❌ Download failed for ${species}" | tee -a "${LOGFILE}"
      mail -s "Ensembl ${species} download error" "${ADMIN_EMAIL}" < "${LOGFILE}"
      exit 1
  }
  echo "✅ Downloaded ${species}.Ensembl.cdna.fa.gz" | tee -a "${LOGFILE}"
  gunzip -f "${species}.Ensembl.cdna.fa.gz"
done

# ==== VERIFY DOWNLOADS ========================================================
echo "Verifying downloaded files..." | tee -a "${LOGFILE}"
ls -lh *.fa | tee -a "${LOGFILE}"

# ==== ADD ENSEMBL PREFIX AND DESCRIPTIONS =====================================
echo "Processing FASTA headers and annotations..." | tee -a "${LOGFILE}"

for species in human mouse rat; do
  input="${species}.Ensembl.cdna.fa"
  output="ensembl_${species}.na"

  "${BIN}/change_fasta_header.pl" "${input}" gnl ensembl > "${output}"
  "${BIN}/add_ensembl_desc.pl" "${output}" "${species}" transcript > "${output}.tmp"
  mv -f "${output}.tmp" "${output}"
done

# ==== FORMAT FOR BLAST ========================================================
echo "Formatting Ensembl databases for BLAST..." | tee -a "${LOGFILE}"

TODAY=$(date +"%Y-%m-%d")
MAKEBLASTDB=$(command -v makeblastdb || echo "/usr/local/bin/makeblastdb")

for species in human mouse rat; do
  db_file="ensembl_${species}.na"
  "${MAKEBLASTDB}" -dbtype 'nucl' \
    -title "${species} ensembl.na ${TODAY}" \
    -parse_seqids \
    -in "${db_file}" \
    -out "${db_file}" >> "${LOGFILE}" 2>&1 || {
      mail -s "BLAST DB formatting error for ${species}" "${ADMIN_EMAIL}" < "${LOGFILE}"
      exit 1
  }
done

# ==== DEPLOYMENT ==============================================================
echo "Deploying formatted BLAST databases..." | tee -a "${LOGFILE}"

# Copy to permanent db folder
rsync -avz "${DOWNLOAD}/" "${PROJECT_ROOT}/cgi-bin/db/" >> "${LOGFILE}" 2>&1

# Move formatted database files
for species in human mouse rat; do
  mv -f ensembl_${species}.na* "${DATA}/"
done

# ==== CLEANUP ================================================================
echo "Cleaning up temporary files..." | tee -a "${LOGFILE}"
rm -f *.fa *.gz || true

echo "===== $(date) Ensembl update completed successfully =====" | tee -a "${LOGFILE}"
mail -s "✅ Ensembl database update completed successfully" "${ADMIN_EMAIL}" < "${LOGFILE}"
exit 0
