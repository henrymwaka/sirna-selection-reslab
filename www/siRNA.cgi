#!/usr/bin/perl -w
# ===============================================================
# siRNA CGI Script — ResLab 2025 Fixed Header
# ===============================================================

use strict;
use warnings;

BEGIN {
    $ENV{'PATH'} = '/usr/local/bin:/usr/bin:/bin';
    delete @ENV{'IFS', 'CDPATH', 'ENV', 'BASH_ENV'};
    $ENV{'LANG'}   = 'en_US.UTF-8';
    $ENV{'LC_ALL'} = 'en_US.UTF-8';
}

use FindBin qw($Bin);
use lib "$Bin";
use lib "$Bin/lib";
use lib '/home/shaykins/Projects/siRNA/cgi-bin/lib';
use lib '/home/shaykins/Projects/siRNA/config';
use lib '/home/shaykins/Projects/siRNA/www/lib';
use lib '/home/shaykins/Projects/siRNA/www';

use CGI qw(:standard);
use CGI::Carp qw(fatalsToBrowser);

use siRNA_util;
use siRNA_env;
use Check;
use GetSession;

# --------------------------------------------------------------------
# Globals
# --------------------------------------------------------------------
my $query = CGI->new;
print $query->header('text/html; charset=UTF-8');

our $PROJECT_ROOT = '/home/shaykins/Projects/siRNA';
our $TMP_DIR      = "$PROJECT_ROOT/www/tmp";
our $LOG_CONF     = "$PROJECT_ROOT/www/siRNA_log.conf";
our $LOG_FILE     = "$PROJECT_ROOT/www/logs/siRNA.log";

# Ensure tmp directory exists
unless (-d $TMP_DIR) {
    mkdir $TMP_DIR, 0775 or warn "Could not create $TMP_DIR: $!";
}

$ENV{'SIRNA_LOG_CONF'} = $LOG_CONF;

# --------------------------------------------------------------------
# Retrieve session ID
# --------------------------------------------------------------------
my $incoming_sid = $query->param('MySessionID') || $query->param('tasto') || '';

if (!$incoming_sid || $incoming_sid !~ /^S_\d+_[0-9a-f]+$/i) {
    print "<html><body><b>Error:</b> Invalid or missing session ID.<br>";
    print "Please <a href='home.php'>log in again</a>.</body></html>";
    exit;
}

my $session_dir = "$TMP_DIR/$incoming_sid";
unless (-d $session_dir) {
    mkdir $session_dir, 0775 or warn "Could not create session dir: $!";
}

# --------------------------------------------------------------------
# Start HTML output
# --------------------------------------------------------------------
print <<"HTML";
<html>
<head>
  <meta charset="UTF-8">
  <title>siRNA Selection Program</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f5f7fa; color: #222; margin: 0; padding: 0; }
    .container { margin: 30px auto; width: 80%; background: #fff; padding: 25px; border-radius: 8px;
                 box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    button { padding: 10px 20px; background: #005fa3; color: #fff; border: none;
             border-radius: 4px; cursor: pointer; }
    button:hover { background: #0076cc; }
    textarea { width: 100%; font-family: monospace; font-size: 14px; }
    footer { margin-top: 30px; text-align: center; color: #777; font-size: 13px; }
  </style>
</head>
<body>
  <center><img src="keep/header_wi_01.jpg" alt="Header"></center>
  <div class="container">
HTML

# --------------------------------------------------------------------
# Show Input Form
# --------------------------------------------------------------------
try {
    print "<h2>Enter Target Sequence</h2>";
    print "<p>Paste the target gene or transcript sequence (FASTA format or plain text):</p>";

    print $query->start_form(
        -method   => 'POST',
        -action   => 'post_sirna.cgi',
        -enctype  => 'multipart/form-data',
        -name     => 'siRNAInputForm'
    );

    print "<textarea name='SEQUENCE' rows='12' cols='80'></textarea><br><br>";

    print "<b>Species:</b> ",
          $query->popup_menu(
              -name    => 'SPECIES',
              -values  => ['Human','Mouse','Rat'],
              -default => 'Human'
          ), "<br><br>";

    print "<b>Database:</b> ",
          $query->popup_menu(
              -name    => 'DATABASE',
              -values  => ['RefSeq','UniGene','Ensembl'],
              -default => 'RefSeq'
          ), "<br><br>";

    print "<b>BLAST Engine:</b> ",
          $query->popup_menu(
              -name    => 'BLAST',
              -values  => ['WU-BLAST','NCBI-BLAST'],
              -default => 'NCBI-BLAST'
          ), "<br><br>";

    print qq{
        <input type="hidden" name="MySessionID" value="$incoming_sid">
        <input type="hidden" name="action" value="PROCESS">
        <button type="submit">Submit Sequence</button>
    };
    print $query->end_form;

    print qq{
        <p style="color:#555;font-size:13px;">
          After submission, the program will identify optimal siRNA candidates
          and perform BLAST filtering.
        </p>
    };
}
catch {
    print "<b>Runtime error while generating form:</b><br>$_<br>";
};

# --------------------------------------------------------------------
# Footer
# --------------------------------------------------------------------
print <<"HTML";
  </div>
  <footer><hr><i>siRNA Selection Program — ResLab Edition, 2025</i></footer>
</body>
</html>
HTML

exit;
