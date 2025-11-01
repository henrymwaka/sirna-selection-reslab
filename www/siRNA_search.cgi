#!/usr/bin/perl -w
# ===============================================================
# siRNA Selection Program — ResLab Modern Edition (2025)
# Modernized siRNA_search.cgi
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
use Check;
use GetSession;
use siRNA_env;
use siRNA_util;
use Time::Local;

# --------------------------------------------------------------------
# Initialize CGI and session
# --------------------------------------------------------------------
my $query = CGI->new;
print $query->header('text/html; charset=UTF-8');

our $UserSessionID = $query->param("tasto") || '';
our $MySessionID   = $query->param("MySessionID") || '';

my $check = Check->new;
my $EMAIL = "";

# --------------------------------------------------------------------
# Validate MySQL session (if enabled)
# --------------------------------------------------------------------
our $MyCheckMySQL = 1;   # Force ON unless you’re disabling DB checks

if ($MyCheckMySQL) {
    my $dbh = $check->dbh_connection();
    my $user_auth_id = $check->checkUserSession($dbh, $UserSessionID);

    if (!$user_auth_id) {
        my $login_page = "home.php";
        $check->redirectToLoginPage($login_page);
        exit;
    }

    $EMAIL = $check->get_email($dbh, $user_auth_id);
    $check->dbh_disconnect($dbh);
}

# --------------------------------------------------------------------
# Output HTML Main Page
# --------------------------------------------------------------------
print <<'EOF';
<!--
* File Name:  siRNA.html
* Author:  Bingbing Yuan
* Modernized: Henry Mwaka (2025)
* Description: Mainpage for siRNA search input form
-->
<HTML>
<HEAD>
    <TITLE>siRNA Selection Program</TITLE>
    <SCRIPT language=JavaScript src="siRNAhelp.js"></SCRIPT>
</HEAD>

<BODY BGCOLOR="#FFFFFF" LINK="#0000FF" VLINK="#660099" ALINK="#660099">

<H2 style="position: relative; left:150px;">
<FONT color="0000FF">
<a href="javascript:help('./keep/help.html')">siRNA Selection Program</a>
</FONT>
</H2>

<FORM ACTION="show_oligo.cgi" METHOD="POST" NAME="MainRNAiForm" ENCTYPE="application/x-www-form-urlencoded">
<input type="hidden" name="tasto" value="$UserSessionID">
<input type="hidden" name="pid" value="$MySessionID">

<LI><font color=red>*</font> Enter your sequence in <a href="javascript:help('./keep/fasta.html')"><b>Raw</b> or <b>FASTA</b></a> format:
<UL><textarea name="SEQUENCE" rows=6 cols=70></textarea></UL>

<LI><font color=red>*</font>Choose siRNA pattern:
<UL>
<table border=1 cellspacing=1>
<tr><th>Recommended patterns</th><th>Custom</th></tr>
<tr><td><input type="radio" checked name="PATTERN" value="PEI">&nbsp;N2[CG]N8[AUT]N8[AUT]N2</td>
<td rowspan=3><input type="radio" name="PATTERN" value="custom">&nbsp;
<input type="text" name="CUSTOM_PATTERN" size=45 maxlength=50><br>
<center><a href="javascript:help('./keep/ending.html')">Enter pattern (23 bases)</a></center></td></tr>
<tr><td><input type="radio" name="PATTERN" value="AA">&nbsp;AAN19TT</td></tr>
<tr><td><input type="radio" name="PATTERN" value="NA">&nbsp;NAN21</td></tr>
</table>
</UL>

<LI>Filter criteria:
<UL>
<LI><font color=red>*</font>GC%: from <input type="text" name="GC_MIN" size=2 value="30"> to <input type="text" name="GC_MAX" size=2 value="52">
<LI><font color=red>*</font>Exclude T/A runs ≥ <input type="text" name="TA_RUN_NUM" size=1 value="4">
<LI><font color=red>*</font>Exclude G runs ≥ <input type="text" name="G_RUN_NUM" size=1 value="4">
<LI><font color=red>*</font>Max GC run length: <input type="text" name="GC_RUN_MAX" size=2 value="7">
<LI><input type="checkbox" name="BASE_VARIATION" value="base_variation"> Equal base % (+/- <input type="text" name="BASE_VARIATION_NUM" size=4 value="10">%)
</UL>

<LI><font color=red>*</font>End siRNAs with:
<select name="ENDING">
<option>UU</option>
<option>TT</option>
<option>dNdN</option>
<option>NN</option>
</select>

<p><input type="submit" value="Search"> <input type="reset" value="Reset"></p>
</FORM>

<HR>
<small>© 2004–2025 Whitehead Institute / ResLab Edition. All rights reserved.</small>
</BODY></HTML>
EOF

exit;
