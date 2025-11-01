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

use siRNA_env;
use siRNA_log;
use siRNA_util;
use GetSession;
use Check;
use JobStatus;

# --------------------------------------------------------------------
# Globals
# --------------------------------------------------------------------
our $PROJECT_ROOT = '/home/shaykins/Projects/siRNA';
our $TMP_DIR      = "$PROJECT_ROOT/www/tmp";
our $LOG_CONF     = "$PROJECT_ROOT/www/siRNA_log.conf";
$ENV{'SIRNA_LOG_CONF'} = $LOG_CONF;

my $q = CGI->new;
print $q->header("text/html; charset=UTF-8");

# --------------------------------------------------------------------
# Parameters
# --------------------------------------------------------------------
my $incoming_sid = $q->param('MySessionID') || $q->param('tasto') || '';
my $sequence     = $q->param('SEQUENCE')    || '';
my $species      = $q->param('SPECIES')     || 'Human';
my $database     = $q->param('DATABASE')    || 'RefSeq';
my $blast_engine = $q->param('BLAST')       || 'NCBI-BLAST';

# --------------------------------------------------------------------
# Input validation
# --------------------------------------------------------------------
if (!$incoming_sid || $incoming_sid !~ /^S_\d+_[0-9a-f]+$/i) {
    print "<html><body><b>Error:</b> Missing or invalid session ID.<br>";
    print "Please <a href='home.php'>start a new session</a>.</body></html>";
    exit;
}

if (!$sequence || $sequence !~ /[A-Za-z]/) {
    print "<html><body><b>Error:</b> No sequence entered.<br>";
    print "Please go back and enter a valid target sequence.</body></html>";
    exit;
}

# --------------------------------------------------------------------
# Prepare session directory (non-fatal if exists)
# --------------------------------------------------------------------
my $session_dir = "$TMP_DIR/$incoming_sid";
unless (-d $session_dir) {
    make_path($session_dir, { mode => 0775 }) or warn "Cannot create session dir: $!";
}

# Sanitize sequence and write to file
$sequence =~ s/\r//g;
$sequence =~ s/^\s+|\s+$//g;

my $seq_file = "$session_dir/input_sequence.txt";
open(my $fh, '>:encoding(UTF-8)', $seq_file) or die "Cannot write $seq_file: $!";
print $fh $sequence;
close($fh);

# --------------------------------------------------------------------
# Log submission
# --------------------------------------------------------------------
siRNA_log::info("New siRNA submission [$incoming_sid] Species=$species DB=$database BLAST=$blast_engine");

# --------------------------------------------------------------------
# Launch background analysis (non-blocking)
# --------------------------------------------------------------------
my $job_script = "$PROJECT_ROOT/cgi-bin/lib/siRNA_step2.cgi";
if (-e $job_script) {
    my $cmd = "/usr/bin/perl $job_script '$seq_file' '$species' '$database' '$blast_engine' '$incoming_sid' > '$session_dir/job.log' 2>&1 &";
    system($cmd);
    siRNA_log::info("Started analysis job: $cmd");
} else {
    siRNA_log::error("Missing analysis script: $job_script");
    print "<b>Error:</b> Analysis module not found. Please contact administrator.";
    exit;
}

# --------------------------------------------------------------------
# Redirect user to monitor page
# --------------------------------------------------------------------
print <<"HTML";
<html>
<head>
  <meta http-equiv="refresh" content="2; url=show_result.cgi?tasto=$incoming_sid&my_session_id=$incoming_sid">
  <meta charset="UTF-8">
  <title>siRNA Job Submitted</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f5f7fa; text-align:center; margin-top:100px; }
    .box { display:inline-block; background:#fff; padding:30px; border-radius:8px;
           box-shadow:0 0 10px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="box">
    <h2>Sequence Submitted Successfully</h2>
    <p>Your siRNA job (<b>$incoming_sid</b>) is now running.</p>
    <p>You will be redirected to the job monitor page shortly.</p>
    <p>If not, <a href="show_result.cgi?tasto=$incoming_sid&my_session_id=$incoming_sid">click here</a>.</p>
  </div>
</body>
</html>
HTML

exit;
