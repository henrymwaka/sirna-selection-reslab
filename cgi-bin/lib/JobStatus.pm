#!/usr/bin/perl -w
package JobStatus;
use strict;
use warnings;
use File::Spec;
use File::Path qw(make_path);
use siRNA_env;
use siRNA_log;

# -------------------------------------------------------------------
# Update or create a job status file
# -------------------------------------------------------------------
sub update_status {
    my ($session_id, $status) = @_;
    return unless $session_id && $status;

    my $base_dir = '/home/shaykins/Projects/siRNA/www/tmp';
    my $session_dir = File::Spec->catdir($base_dir, $session_id);
    my $status_file = File::Spec->catfile($session_dir, 'status.txt');

    # Ensure directory exists
    unless (-d $session_dir) {
        make_path($session_dir, {mode => 0775});
    }

    # Attempt to open the status file safely
    if (open(my $fh, '>', $status_file)) {
        print $fh "$status\n";
        close $fh;
        siRNA_log::myinfo("[$session_id] Status updated to '$status'");
    } else {
        my $err = "Cannot write to $status_file: $!";
        siRNA_log::myerror($err);
        warn "$err\n";
    }

    return 1;
}

1;

