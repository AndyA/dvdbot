#!/usr/bin/env perl

use autodie;
use strict;
use warnings;

use Data::Dumper;
use File::Find;
use Path::Class;

use constant ISO => 'iso';
use constant TMP => 'tmp';
use constant MP4 => 'mp4';

my @vts = find_dvd(ISO);

for my $vt (@vts) {
  my $root  = dir($vt)->parent;
  my $tmp   = kind_dir( $root, TMP );
  my $mp4   = kind_dir( $root, MP4 );
  my $progs = find_prog( find_vob($vt) );
  for my $prog ( sort keys %$progs ) {
    my $tmpf = file( $tmp, "$prog.mp4" );
    my $logf = file( $tmp, "$prog.log" );
    my $mp4f = file( $mp4, "$prog.mp4" );
    if ( -f $mp4f ) {
      print "$mp4f exists\n";
      next;
    }

    my @vobs = by_index( @{ $progs->{$prog} } );
    my $map = eval { probe( $vobs[0] ) };
    if ($@) { print "$@"; next }

    my @maps = map { -map => $_ } sort @{ $map->{audio} || [] },
     @{ $map->{video} || [] };

    my $cmd = join ' ', 'cat', ( map { shell_escape($_) } @vobs ), '|',
     'ffmpeg', -y => -f => 'mpeg',
     -i => '-',
     @maps,
     '-c:a' => 'libfaac',
     '-b:a' => '256k',
     '-c:v' => 'libx264',
     '-b:v' => '3500k',
     shell_escape($tmpf), '>', shell_escape($logf), '2>&1';
    print "$cmd\n";
    my $rc = eval { system $cmd };
    if ($@) { print "$@"; next }
    if ($rc) { print "ffmpeg failed: $rc\n"; next }
    rename "$tmpf", "$mp4f";
  }
}

sub probe {
  my $vf  = shift;
  my $tmp = "/tmp/ffprobe.$$.log";
  my $cmd = join ' ', 'ffprobe',
   -i => shell_escape($vf),
   '>', shell_escape($tmp), '2>&1';
  print "$cmd\n";
  my $rc = system $cmd;
  die "ffprobe failed: $rc" if $rc;
  my $map = {};
  open my $tf, '<', $tmp;

  while (<$tf>) {
    chomp;
    push @{ $map->{ lc $2 } }, $1
     if /^\s*Stream\s+\#(\d+:\d+)\[0x[0-9a-f]+\]:\s+(\w+):/;
  }
  return $map;
}

sub get_index {
  my $fn = shift;
  return $1 if $fn =~ /_(\d+)\.VOB$/;
  return 0;
}

sub by_index {
  return map { $_->[0] }
   sort { $a->[1] <=> $b->[1] || $a->[0] cmp $b->[0] }
   map { [$_, get_index($_)] } @_;
}

sub shell_escape {
  ( my $word = shift ) =~ s/([!\s"'*?:(){};\$<>&\\])/\\$1/g;
  return $word;
}

sub kind_dir {
  my ( $dir, $root ) = @_;
  my $nd = rebase( $dir, $root );
  $nd->mkpath;
  return $nd;
}

sub rebase {
  my ( $dir,  $root ) = @_;
  my ( undef, @comp ) = dir($dir)->dir_list;
  return dir( $root, @comp );
}

sub find_prog {
  my @vobs = @_;
  my $prog = {};
  for my $vob (@vobs) {
    ( my $key = $vob->basename ) =~ s/\.VOB$//;
    $key =~ s/_[1-9]\d*$//;
    print "$vob -> $key\n";
    push @{ $prog->{$key} }, $vob;
  }
  return $prog;
}

sub find_vob {
  my $dir = shift;
  opendir my ($dh), $dir;
  return map { file $dir, $_ } sort grep { $_ =~ /\.VOB$/ } readdir $dh;
}

sub find_dvd {
  my $dir = shift;
  my @vts = ();
  find {
    wanted => sub {
      return unless -d $_;
      if ( $_ =~ m{/VIDEO_TS$} ) {
        $File::Find::prune = 1;
        push @vts, $_;
      }
    },
    no_chdir => 1,
  }, $dir;
  return sort @vts;
}

# vim:ts=2:sw=2:sts=2:et:ft=perl

