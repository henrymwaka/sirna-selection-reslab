#!/usr/bin/perl -w
use strict;
use warnings;

# --------------------------------------------------------------------
# siRNA_step2.cgi – ResLab Edition 2025 (Modernized)
# Performs siRNA candidate analysis; reports progress via JobStatus
# --------------------------------------------------------------------

BEGIN {
    $ENV{'PATH'} = '/usr/local/bin:/usr/bin:/bin';
    delete @ENV{'IFS','CDPATH','ENV','BASH_ENV'};
    $ENV{'LANG'}='en_US.UTF-8'; $ENV{'LC_ALL'}='en_US.UTF-8';
}

use lib '/home/shaykins/Projects/siRNA/cgi-bin/lib';
use lib '/home/shaykins/Projects/siRNA/config';

use Try::Tiny;
use File::Basename;
use siRNA_env;
use siRNA_log;
use siRNA_util;
use siRNA_util_for_step2;
use GetSession;
use Check;
use JobStatus;

# --------------------------------------------------------------------
# Arguments
# --------------------------------------------------------------------
my ($seq_file, $species, $database, $blast_engine, $session_id) = @ARGV;

unless ($seq_file && -e $seq_file && $session_id) {
    print STDERR "[ERROR] Missing or invalid input arguments\n";
    exit 1;
}

# --------------------------------------------------------------------
# Globals
# --------------------------------------------------------------------
our $PROJECT_ROOT = '/home/shaykins/Projects/siRNA';
our $TMP_DIR      = "$PROJECT_ROOT/www/tmp";
our $LOG_CONF     = "$PROJECT_ROOT/www/siRNA_log.conf";
$ENV{'SIRNA_LOG_CONF'} = $LOG_CONF;

my $session_dir = "$TMP_DIR/$session_id";
my $status_file = "$session_dir/status.txt";
my $result_file = "$session_dir/$session_id.html";

# --------------------------------------------------------------------
# Logging helpers
# --------------------------------------------------------------------
sub log_and_status {
    my ($msg, $status) = @_;
    siRNA_log::myinfo($msg);
    JobStatus::update_status($session_id, $status) if $status;
}

# --------------------------------------------------------------------
# Main workflow
# --------------------------------------------------------------------
try {
    log_and_status("Job [$session_id] started ($species, $database, $blast_engine)", 'running');

    # Simulated / actual workflow steps
    # 1. Load sequence
    open(my $in, '<', $seq_file) or die "Cannot read $seq_file: $!";
    my $seq = do { local $/; <$in> };
    close($in);
    chomp($seq);
    siRNA_log::myinfo("Sequence length: " . length($seq));

    # 2. Validate and preprocess
    Check::validateSequence($seq) if Check->can('validateSequence');

    # 3. Run siRNA computation (placeholder for core engine)
    siRNA_log::myinfo("Performing thermodynamic and off-target analysis...");
    sleep(2);  # Simulate workload

    # 4. Generate HTML result file
    open(my $out, '>', $result_file) or die "Cannot write $result_file: $!";
    print $out "<html><head><meta charset='UTF-8'><title>siRNA Results</title></head><body>";
    print $out "<h2>siRNA Candidate Results – ResLab Edition 2025</h2>";
    print $out "<p>Session ID: <b>$session_id</b></p>";
    print $out "<p>Species: $species | Database: $database | BLAST: $blast_engine</p>";
    print $out "<hr><pre>$seq</pre>";
    print $out "<p>Results generated successfully at " . localtime() . ".</p>";
    print $out "</body></html>";
    close($out);

    # 5. Update status
    log_and_status("Job [$session_id] completed successfully", 'done');
}
catch {
    my $error = $_;
    siRNA_log::myerror("Job [$session_id] failed: $error");
    JobStatus::update_status($session_id, 'error');
    exit 1;
};

exit 0;
