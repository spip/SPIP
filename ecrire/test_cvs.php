<?  // test cvs version auto 

	// on est appele via un tag ?
	if ($tag = "$Name$") {
		$version = $tag;
	} else {
		$version = "$Id$";
	}
?>