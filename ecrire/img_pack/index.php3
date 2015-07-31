<table>
<?php
	$myDir = opendir('.');
	while($file = readdir($myDir)) {
		if (ereg("(.+)\.(png|gif)$", $file, $match))
			echo "<tr><td>$file<td><img src=$file>\n";
	}
?>
</table>
