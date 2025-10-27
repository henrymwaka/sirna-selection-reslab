#!/usr/bin/perl
use strict;
use warnings;
use CGI qw(:standard);
use FindBin qw($Bin);
use lib "$Bin/lib";
use lib "/home/shaykins/Projects/siRNA/config";
use siRNA_env;
use DBI;

print header(-type => 'text/html', -charset => 'UTF-8');
print start_html(-title => 'siRNA Environment Test', -style => 'body {font-family: Arial; margin: 20px;}');

print h2("✅ siRNA Environment Diagnostic");

# --- Perl module check
print "<h3>1. Perl Modules</h3>\n";
print "<ul>";
print "<li>Perl Version: $]</li>";
print "<li>Config Module Loaded: <b>$siRNA_env::DB_NAME</b></li>";
print "<li>Using Config Path: /home/shaykins/Projects/siRNA/config/</li>";
print "</ul>";

# --- Database check
print "<h3>2. Database Connection</h3>\n";
my $dsn = "DBI:mysql:database=$siRNA_env::DB_NAME;host=$siRNA_env::DB_HOST";
eval {
    my $dbh = DBI->connect($dsn, $siRNA_env::DB_USER, $siRNA_env::DB_PASS, { RaiseError => 1, PrintError => 0 });
    my $sth = $dbh->prepare("SELECT COUNT(*) FROM accounts");
    $sth->execute();
    my ($count) = $sth->fetchrow_array();
    print "<p style='color:green;'>✅ Connected successfully. Accounts table count: <b>$count</b></p>";
    $dbh->disconnect();
};
if ($@) {
    print "<p style='color:red;'>❌ Database connection failed: $@</p>";
}

# --- reCAPTCHA keys
print "<h3>3. reCAPTCHA Configuration</h3>\n";
if ($siRNA_env::RECAPTCHA_SITE_KEY && $siRNA_env::RECAPTCHA_SECRET_KEY) {
    print "<p style='color:green;'>✅ Keys present (site + secret).</p>";
} else {
    print "<p style='color:red;'>❌ Missing reCAPTCHA keys in config.</p>";
}

# --- File and permissions checks
print "<h3>4. File System Checks</h3>\n";
my @paths = (
    "/home/shaykins/Projects/siRNA/config/siRNA_env.pm",
    "/home/shaykins/Projects/siRNA/www/lib",
    "/home/shaykins/Projects/siRNA/www/logs",
);
foreach my $path (@paths) {
    if (-e $path) {
        my $perm = sprintf("%04o", (stat($path))[2] & 07777);
        print "<li>✅ $path — exists (mode: $perm)</li>\n";
    } else {
        print "<li style='color:red;'>❌ $path — missing</li>\n";
    }
}

# --- Perl include paths
print "<h3>5. Perl \@INC Paths</h3>\n";
print "<pre>";
print join("\n", @INC);
print "</pre>";

print "<hr><p><small>Generated at " . scalar(localtime) . "</small></p>";
print end_html;
