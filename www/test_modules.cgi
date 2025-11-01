#!/usr/bin/perl -w
# ===============================================================
# siRNA System Module Test Script — ResLab (2025)
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

use CGI qw(:standard);
use CGI::Carp qw(fatalsToBrowser);

print header('text/html; charset=UTF-8');
print "<html><head><title>Perl Module Test</title></head><body>";
print "<h2>✅ siRNA Perl Module Environment Check</h2>";

my @modules = qw(
    Check
    GetSession
    siRNA_env
    siRNA_util
    Time::Local
    DBI
);

foreach my $mod (@modules) {
    eval "use $mod;";
    if ($@) {
        print "<p style='color:red;'>❌ Failed to load <b>$mod</b><br><code>$@</code></p>";
    } else {
        print "<p style='color:green;'>✅ Loaded $mod successfully</p>";
    }
}

print "<hr><p>All paths in \@INC:</p><ul>";
foreach my $inc (@INC) {
    print "<li>$inc</li>";
}
print "</ul>";

print "<hr><p><i>Test complete — ResLab siRNA CGI validation (2025)</i></p>";
print "</body></html>";

