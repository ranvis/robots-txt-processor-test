# @author SATO Kentaro
# @license BSD 2-Clause License

use strict;
use warnings;

my $module = shift(@ARGV) or die;

eval "require $module" or die;

run(sub {
	my (%params) = @_;
	my $rules = $module->new($params{ua});
	$rules->parse('http://www.example.com/robots.txt', $params{text});
	return $rules->allowed('http://www.example.com' . $params{path});
});

sub run {
	my ($cb) = @_;
	local $|;
	binmode(STDIN, ':raw');
	binmode(STDOUT, ':raw');
	$| = 1;
	while (!eof(STDIN)) {
		read(STDIN, my $header, 4 * 3);
		my @header = unpack('N3', $header);
		my ($text, $ua, $path) = ('', '', '');
		read(STDIN, $text, $header[0]) if ($header[0]);
		read(STDIN, $ua, $header[1]) if ($header[1]);
		read(STDIN, $path, $header[2]) if ($header[2]);
		my $result = $cb->(text => $text, ua => $ua, path => $path);
		print int($result) . "\n";

		my $oldFp = select(STDERR);
		$| = 1;
		print STDERR "<<<END>>>\n";
		$| = 0;
		select($oldFp);
	}
}
