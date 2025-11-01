#!/usr/bin/perl -w
#################################################################
# Copyright(c) 2001 Whitehead Institute for Biomedical Research.
#              All Rights Reserved
#
# Author:      Bingbing Yuan <siRNA-help@wi.mit.edu>
# Created:     12/4/2002
# Updated:     10/30/2025 by Henry Mwaka
# Purpose:     Environment constants for siRNA Selection Program
#################################################################

package SiRNA;
use strict;

#===============================================================
# Environment selector
#===============================================================
our $sirnaEnv = "production";     # or "test" if needed

our ($PERL, $Home, $SiRNAUrlHome, $cgiHome, $MyHomePage);
our ($MyClusterLib, $MyClusterHome, $MyCheckMySQL, $MyForkProcess);
our ($MyBlastDir, $MyBlastDataDir, $MyBlastDB);
our ($MyClusterLDLib, $LSRUN_DIR);

#===============================================================
# MySQL checking
#===============================================================
if ($sirnaEnv =~ /test/) {
    $MyCheckMySQL = 0;
} else {
    $MyCheckMySQL = 1;
}

#===============================================================
# Directory mappings for production installation
#===============================================================
if ($sirnaEnv eq "production") {

    my $ProjectRoot = "/home/shaykins/Projects/siRNA";

    *PERL = \"/usr/bin/perl";

    $MyClusterLDLib = "$ProjectRoot/cgi-bin/lib";
    $MyBlastDataDir = "$ProjectRoot/cgi-bin/db";

    *Home          = \"$ProjectRoot/www/tmp";
    *MyClusterHome = \"$ProjectRoot/www/tmp";
    *MyClusterLib  = \"$ProjectRoot/cgi-bin/lib";

    *SiRNAUrlHome  = \"https://sirna.reslab.dev";
    *cgiHome       = \"https://sirna.reslab.dev";
    *MyHomePage    = \"home.php";
}

#===============================================================
# Optional test environment override
#===============================================================
elsif ($sirnaEnv eq "test") {

    my $ProjectRoot = "/home/shaykins/Projects/siRNA_test";

    *PERL = \"/usr/bin/perl";

    $MyClusterLDLib = "$ProjectRoot/cgi-bin/lib";
    $MyBlastDataDir = "$ProjectRoot/cgi-bin/db";

    *Home          = \"$ProjectRoot/www/tmp";
    *MyClusterHome = \"$ProjectRoot/www/tmp";
    *MyClusterLib  = \"$ProjectRoot/cgi-bin/lib";

    *SiRNAUrlHome  = \"http://localhost/siRNA_test";
    *cgiHome       = \"http://localhost/siRNA_test";
    *MyHomePage    = \"home.php";
}

1;
