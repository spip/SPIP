<?php
function saisie_url_syndic($url_syndic,$name='url_syndic',$id='url_syndic'){
	$res = "";
	if (strlen($url_syndic) < 8) $url_syndic = "http://";

	// cas d'une liste de flux detectee par feedfinder : menu
	if (preg_match(',^select: (.+),', $url_syndic, $regs)) {
		$feeds = explode(' ',$regs[1]);
		$res .= "<select name='$name' id='$id'>\n";
		foreach ($feeds as $feed) {
			$res .= '<option value="'.entites_html($feed).'">'.$feed."</option>\n";
		}
		$res .= "</select>\n";
	} else {
		$res .= "<input type='text' class='formo' name='$name' id='$id' value=\"$url_syndic\" size='40' />\n";
	}
	return $res;
}

?>