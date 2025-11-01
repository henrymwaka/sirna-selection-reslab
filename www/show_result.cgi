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

my $PROJECT_ROOT = '/home/shaykins/Projects/siRNA';
my $TMP_DIR      = "$PROJECT_ROOT/www/tmp";

my $q = CGI->new;
print $q->header("text/html; charset=UTF-8");
print $q->start_html("siRNA Job Monitor");

# --------------------------------------------------------------------
# Parameters
# --------------------------------------------------------------------
my $UserSessionID  = $q->param('tasto') || '';
my $MySessionID    = $q->param('my_session_id') || '';
my $MySessionIDSub = $q->param('my_session_id_sub') || '';

# Validate
if (!$UserSessionID || $UserSessionID !~ /^S_\d+_[0-9a-f]+$/i) {
    print "<html><body><b>Error:</b> Invalid or missing session ID.<br>";
    print "Please <a href='home.php'>start a new session</a>.</body></html>";
    exit;
}

my $session_dir = "$TMP_DIR/$UserSessionID";
my $status_file = "$session_dir/status.txt";
my $result_file = "$session_dir/$UserSessionID.html";
my $log_file    = "$session_dir/job.log";

# --------------------------------------------------------------------
# Read job status
# --------------------------------------------------------------------
my $status = 'queued';
if (-e $status_file) {
    open(my $sfh, '<', $status_file);
    chomp($status = <$sfh>);
    close $sfh;
}

# --------------------------------------------------------------------
# Page header
# --------------------------------------------------------------------
print <<'HTML';
<style>
body {
  font-family: Arial, sans-serif;
  background: #f5f7fa;
  text-align: center;
  margin-top: 80px;
}
.box {
  display: inline-block;
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  width: 80%;
  max-width: 700px;
}
.status {
  font-weight: bold;
  color: #005fa3;
}
.progress {
  width: 80%;
  background: #eee;
  border-radius: 6px;
  margin: 15px auto;
  height: 20px;
  overflow: hidden;
}
.progress-bar {
  height: 100%;
  background: linear-gradient(90deg,#4caf50,#81c784);
  width: 0%;
  transition: width 1s ease;
}
</style>
HTML

print "<div class='box'>";
print "<h2>siRNA Job Monitor</h2>";
print "<p>Session ID: <b>$UserSessionID</b></p>";

# --------------------------------------------------------------------
# Display depending on status
# --------------------------------------------------------------------
if ($status eq 'queued') {
    print "<p class='status'>Job queued...</p>";
    print "<div class='progress'><div class='progress-bar' style='width:10%'></div></div>";
    print "<p>Waiting for server to begin processing.</p>";
    print_refresh(10, $UserSessionID);
}
elsif ($status eq 'running') {
    print "<p class='status'>Job running...</p>";
    print "<div class='progress'><div class='progress-bar' style='width:60%'></div></div>";
    print "<p>Processing sequence and performing BLAST filtering.</p>";
    print_refresh(15, $UserSessionID);
}
elsif ($status eq 'done') {
    if (-e $result_file) {
        print "<p class='status' style='color:green;'>Job completed successfully!</p>";
        print "<div class='progress'><div class='progress-bar' style='width:100%'></div></div>";
        print "<p><a href='/tmp/$UserSessionID/$UserSessionID.html'>View Results</a></p>";
    } else {
        print "<p class='status' style='color:green;'>Job marked done but results not found.</p>";
        print "<p>Check logs below.</p>";
    }
}
elsif ($status eq 'error') {
    print "<p class='status' style='color:red;'>Job encountered an error.</p>";
    print "<p>Please check the log file below for details.</p>";
} else {
    print "<p class='status'>Unknown status: $status</p>";
    print_refresh(10, $UserSessionID);
}

# --------------------------------------------------------------------
# Display short job log tail (if available)
# --------------------------------------------------------------------
if (-e $log_file) {
    print "<hr><h3>Job Log (last 10 lines)</h3><pre style='text-align:left;background:#fafafa;border:1px solid #ddd;padding:10px;border-radius:6px;'>";
    my @lines = `tail -n 10 '$log_file' 2>/dev/null`;
    print @lines;
    print "</pre>";
}

# --------------------------------------------------------------------
# Footer
# --------------------------------------------------------------------
print "<hr><small><i>siRNA Result Monitor — ResLab Edition 2025</i></small>";
print "</div>";
print $q->end_html;
exit;

# --------------------------------------------------------------------
# Helper: auto-refresh block
# --------------------------------------------------------------------
sub print_refresh {
    my ($seconds, $sid) = @_;
    print "<meta http-equiv='refresh' content='$seconds; url=show_result.cgi?tasto=$sid'>";
    print "<script>setTimeout(function(){location.reload();}, ".($seconds*1000).");</script>";
}
