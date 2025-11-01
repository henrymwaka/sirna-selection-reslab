<?php
declare(strict_types=1);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Algorithm Documentation | siRNA Portal</title>
<style>
body { font-family: Arial, sans-serif; background:#f4f6f8; padding:30px; }
h1 { color:#003366; }
pre { background:#eee; padding:10px; border-radius:6px; }
</style>
</head>
<body>
<h1>Algorithm & API Documentation</h1>
<p>This page documents the computational workflow of the siRNA Selection Program.</p>
<ul>
  <li><strong>Step 1:</strong> Sequence parsing and ORF extraction using EMBOSS <code>getorf</code>.</li>
  <li><strong>Step 2:</strong> BLAST filtering against RefSeq using <code>blastn</code>.</li>
  <li><strong>Step 3:</strong> Folding energy evaluation via <code>RNAfold</code> (ViennaRNA).</li>
  <li><strong>Step 4:</strong> Scoring and off-target filtering using Perl modules in <code>siRNA_util.pm</code>.</li>
  <li><strong>Step 5:</strong> Result formatting and HTML generation.</li>
</ul>

<p>The Perl API endpoints are compatible with <code>MySessionID</code> and
<code>tasto</code> tokens for backward compatibility.</p>

<p style="font-size:12px; color:#777;">For detailed implementation, refer to <code>/cgi-bin/lib/</code>.</p>
</body>
</html>
