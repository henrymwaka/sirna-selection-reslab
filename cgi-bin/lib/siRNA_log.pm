#!/usr/bin/perl -w
#
# siRNA_log.pm  –  Modernized ResLab Edition (2025)
# Based on original Whitehead Institute implementation (2001, Bingbing Yuan)
# Maintained and updated by Henry Mwaka
#
# Provides unified Log::Log4perl interface for all siRNA components.
# --------------------------------------------------------------------

package siRNA_log;
use strict;
use warnings;
use Log::Log4perl;
use CGI ();
use siRNA_env;

umask 000;

# --------------------------------------------------------------------
# Initialize Log4perl
# --------------------------------------------------------------------
my $conf_file = $ENV{'SIRNA_LOG_CONF'}
    || '/home/shaykins/Projects/siRNA/www/siRNA_log.conf';

Log::Log4perl->init($conf_file);
$Log::Log4perl::caller_depth = 1;

my $logger = Log::Log4perl->get_logger("siRNA");

# --------------------------------------------------------------------
# Core logging methods (original behaviour preserved)
# --------------------------------------------------------------------
sub mydebug { _log('debug', @_) }
sub myinfo  { _log('info',  @_) }
sub mywarn  { _log('warn',  @_) }
sub myerror { _log('error', @_) }
sub myfatal { _log('fatal', @_) }

# --------------------------------------------------------------------
# Public modern aliases (preferred in new scripts)
# --------------------------------------------------------------------
sub debug { mydebug(@_) }
sub info  { myinfo(@_)  }
sub warn  { mywarn(@_)  }
sub error { myerror(@_) }
sub fatal { myfatal(@_) }

# --------------------------------------------------------------------
# Internal helper
# --------------------------------------------------------------------
sub _log {
    my ($level, @msg) = @_;
    my $session = $SiRNA::MySessionID // $main::MySessionID // '';
    my $prefix  = $session ? "[$session] " : '';
    if ($logger->can($level)) {
        $logger->$level($prefix . join(' ', @msg));
    }
    # For error and fatal, echo to browser if a CGI object is passed
    if ($level =~ /error|fatal/) {
        _printToWWW(@msg);
    }
    exit 1 if $level eq 'fatal';
}

# --------------------------------------------------------------------
# Minimal HTML error output for web users
# --------------------------------------------------------------------
sub _printToWWW {
    my ($reason, $query) = @_;
    if (ref $query eq 'CGI') {
        print $query->header('text/html; charset=UTF-8');
        print $query->start_html('siRNA Error'),
              $query->h1('Error:'),
              $query->p($query->i($reason)),
              $query->end_html;
    }
}

1;
__END__

=head1 NAME
siRNA_log – unified logging wrapper for the ResLab siRNA system

=head1 SYNOPSIS
  use siRNA_log;
  siRNA_log::info("Starting job $id");
  siRNA_log::error("Invalid input");

=head1 DESCRIPTION
Wrapper around Log::Log4perl preserving backward-compatibility
with the Whitehead version while exposing modern aliases (info, warn, error).
=cut
