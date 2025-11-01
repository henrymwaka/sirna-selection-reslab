<?php
declare(strict_types=1);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Help & Tutorials | siRNA Portal</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f6f8; padding:30px; line-height:1.6; }
h1 { color:#003366; }
a { color:#0074D9; text-decoration:none; }
a:hover { text-decoration:underline; }
.section { background:#fff; padding:20px; border-radius:8px; margin-bottom:20px;
           box-shadow:0 2px 6px rgba(0,0,0,0.1); }
</style>
</head>
<body>
<h1>Help & Tutorials</h1>
<div class="section">
  <h2>1️⃣ Designing siRNAs</h2>
  <p>Use the <strong>Design New siRNA</strong> tool to submit a target mRNA sequence.
     Choose the organism and database source. The system will automatically identify
     candidate siRNA sites and filter them based on thermodynamic and off-target criteria.</p>
</div>

<div class="section">
  <h2>2️⃣ Viewing Results</h2>
  <p>After submission, the portal assigns a <code>Session ID</code> and processes
     the request asynchronously. Once completed, results appear under <em>View Design Results</em>.</p>
</div>

<div class="section">
  <h2>3️⃣ Searching the Database</h2>
  <p>Use <em>Search siRNA Database</em> to locate previously designed oligos by gene, accession number, or keyword.</p>
</div>

<div class="section">
  <h2>4️⃣ Contact and Support</h2>
  <p>If you encounter technical problems or have questions about algorithm parameters,
     please contact <a href="mailto:sirna-help@wimit.edu">sirna-help@wimit.edu</a>.</p>
</div>

<p style="font-size:12px; color:#777;">© 2025 siRNA Portal – Modernized from the original Whitehead Institute version.</p>
</body>
</html>
