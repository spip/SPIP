<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Index of img_pack</title>
</head>
<body>
<h1>Index of img_pack</h1>
<table>
<?php
	$myDir = opendir('.');
	while($file = readdir($myDir)) {
		if (ereg("(.+)\.(png|gif)$", $file, $match))
			echo "<tr><td>$file<td><img src=$file>\n";
	}
?>
</table>
</body>
</html>