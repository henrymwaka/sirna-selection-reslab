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

print <<EOF;
<pre>
<p style="FONT-SIZE: 14pt; COLOR: BLACK"><b>
You have successfully logged out. You can now close your window.
To login again, click <a href="$SiRNAUrlHome/home.php">here</a>.
</p>
<p style="FONT-SIZE: 18pt; COLOR: BLUE">
Thank you for using siRNA selection program!
</p>
</pre>
EOF
;

print $query->end_html;
