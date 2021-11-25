#!/usr/bin/env php
<?php
$i = 0;

$file = fopen('enterprise-numbers','r');
$output = fopen('ent.sql','w');
if ($file) {

	// skip few lines
	for ($f = 0; $f < 15; $f++) {
		$x = fgets($file);
	}

	while(!feof($file)) {
		$line1 = trim(fgets($file));
		$line2 = addslashes(trim(fgets($file)));
		$line3 = fgets($file);
		$line4 = fgets($file);
	
		if (!empty($line1) || !empty($line2)) {
			fwrite ($output, "INSERT INTO plugin_snver_organizations (id, organization) VALUES ('$line1','$line2');" . PHP_EOL);
			$i++;
		}
	}
}
fclose ($file);
fclose ($output);

echo 'rows: ' . $i . PHP_EOL;
?>