<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Index des icones de Spip</title>
	<link rel="up" href="../">
</head>
<body>
	<center>
		<h1>Index des icones de <a href='http://www.spip.net'>Spip</a></h1>
		<table>
<?php
	$myDir = opendir('.');
	while($file = readdir($myDir)) {
		if (ereg("\.(png|gif)$", $file))
			echo "		<tr><td>$file</td><td><img src='$file' alt='$file' /></td></tr>\n";
	}
?>

		</table>
	</center>
</body>
</html>