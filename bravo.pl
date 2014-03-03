#!/usr/bin/env perl

use autodie;
use strict;
use warnings;

use JSON;
use List::Util qw( sum );
use Path::Class;
use Time::HiRes qw( sleep );

use constant DEV_LP => '/dev/usb/lp0';
use constant DEV_CD => '/dev/sr0';
use constant DIR    => '/nfs/data/media/DVD';

=for ref

05 Reset
80 move disk from left tray to drive
81 move disk from left tray to printer
82 move disk from left tray to right 
83 move disk from right tray to drive
84 move disk from right tray to printer
85 move disk from right tray to left
86 move disk from drive to printer
87 move disk from drive to right tray
88 move disk from drive to left tray

=cut

open my $lp, '+>', DEV_LP;
if (@ARGV) {
  command( map hex, @ARGV );
  exit;
}

system 'eject', DEV_CD;
while () {
  my @st = status();
  if ( $st[0] ) {
    print "Status: $st[0]\n";
    last;
  }

  print "Load disk\n";
  command(0x80);
  busy_wait();

  print "Close drive\n";
  system 'eject', -t => DEV_CD;

  sleep 4;
  wait_disk();

  my $title = get_title();
  print "Title: $title\n";
  my $dir = dir DIR, join ' ', $title, time;
  $dir->mkpath;
  system 'dvdbackup', -M => -i => DEV_CD, -o => $dir;

  print "Open drive\n";
  system 'eject', DEV_CD;
  sleep 4;

  print "Unload disk\n";
  command(0x87);
  busy_wait();
}

sub busy_wait {
  sleep 0.5;
  while () {
    my @st = status();
    last if $st[1] == ord('B');
    sleep 0.25;
  }
  while () {
    my @st = status();
    last if $st[1] == ord('I');
    sleep 0.25;
  }
}

sub command {
  my ( $cmd, @data ) = @_;
  push @data, 0 while @data < 4;
  my @cmd = ( 0x1b, scalar(@data), $cmd, @data );
  my $packet = pack 'C*', @cmd, sum(@cmd) & 0xff;
  syswrite $lp, $packet;
}

sub status {
  my $len = sysread $lp, my $buf, 90;
  unpack 'C*', substr $buf, 0x45, 4;
}

sub get_title {
  open my $ch, '-|', 'dvdbackup', -I => -i => DEV_CD;
  my $title;
  while (<$ch>) {
    $title = $1 if /^DVD-Video information of the DVD with title "(.+)"/;
  }
  close $ch;
  return $title;
}

sub wait_disk {
  for ( 1 .. 100 ) {
    eval {
      open my $fh, '<', DEV_CD;
      my $len = sysread $fh, my $buf, 1;
    };
    return unless $@;
    print "$@";
    sleep 1;
  }
  die "Disk not ready\n";
}

# vim:ts=2:sw=2:sts=2:et:ft=perl

