<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Index des vignettes de SPIP</title>
	<link rel="up" href="../">
</head>
<body style="background: #fff; text-align: center;">
	<h1>Index des vignettes de SPIP</h1>
	<table>
		<tr><th colspan='10'>Format png &amp; gif</th>
		<?php
			$myDir = opendir('.');
			$i= 0;
			while($file = readdir($myDir)) {
			  if (preg_match(",\.(png|gif)$,i", $file)) {
				$r = "\n\t<td style='text-align:center; padding:10px'>$file<br /><img src='$file' alt='$file' /></td>";
				if ($i%10) echo $r; else echo "</tr>\n<tr>", $r;
				$i++;
			  }
			}
		?>
		</tr>
	</table>
</body>
</html>