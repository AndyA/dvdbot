#!/usr/bin/env perl

use autodie;
use strict;
use warnings;

use Data::HexDump;
use List::Util qw( sum );

use constant DEV_LP => '/dev/usb/lp0';
use constant DEV_CD => '/dev/sr0';

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

open my $lp, '<', DEV_LP;
my $len = sysread $lp, my $buf, 90;
my $flag = ord substr $buf, 0x45, 1;
my $st = substr $buf, 0x46, 3;
print "$flag $st\n";

# vim:ts=2:sw=2:sts=2:et:ft=perl

