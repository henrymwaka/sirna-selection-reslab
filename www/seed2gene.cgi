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

    	print "Got an error: $msg";
        }
       set_message(\&handle_errors);
}



my $QUERY = new CGI;
print $QUERY->header("text/html");
print $QUERY->start_html("sirna");

my $Seed = $QUERY->param("seed");
#my $Seed = "AAAAUCA";
if (! $Seed)
	{
		$QUERY->p("no seed sequence");
		exit;	
	}	

my $dbh = Database::connect_db("sirna");
my $gene_aref = Database::get7merGenes($dbh,$Seed);
Database::disconnect_db($dbh);

SiRNA::mydebug( "seed=$Seed, number_of_genes=", $#$gene_aref );

print $QUERY->h2("The Genes whose 3'UTR have binding site(s) for seed $Seed");
print '<br /><TABLE cellpadding=3 cellspacing=2 border=1>';
print '<TR><TH>Gene</TH><TH>Symbol</TH><TH>Description</TH></TR>';
foreach my $i(0..$#$gene_aref) {
	my $gene   = $gene_aref->[$i][0];
	my $symbol = $gene_aref->[$i][1];
	my $desc   = $gene_aref->[$i][2];
	
	print "<TR><TD>" . $gene . "</TD><TD>" . $symbol . "</TD><TD>" . $desc . "</TD></TR>\n";
}
print '</TABLE>';

print $QUERY->end_html();
