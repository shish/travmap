#!/usr/bin/perl

sub parse {
	@parts = m/(\d+),(-?\d+),(-?\d+),(\d+),(\d+),'(.*)',(\d+),'(.*)',(\d+),'(.*)',(\d+)/;

	if(@parts) {
		($loc, $x, $y, $race, 
		$town_id, $town_name, 
		$owner_id, $owner_name,
		$guild_id, $guild_name,
		$population) = @parts;

		$town_name =~ s/\t/ /g;
		$owner_name =~ s/\t/ /g;
		$guild_name =~ s/\t/ /g;

		print "$loc\t$x\t$y\t$race\t$town_id\t$town_name\t$owner_id\t$owner_name\t$guild_id\t$guild_name\t$population\n";
	}
	else {
		print STDERR "Error converting line to postgres syntax: $_\n";
	}
}

while(<>) {
	# cut the blob into CSV lines
	s/INSERT INTO \`x_world\` VALUES \(//;
	s/\),\(/\n/g;
	s/\);//;

	foreach (split /\n/) {
		parse;
	}
}

