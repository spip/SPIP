<?  // test cvs version auto 

	// dernier tag connu ou date si cvs
	if (! ereg("Name: v(.*) ","$Name$", $regs))
		ereg("(20../../.. ..:..:..)", "$Id$", $regs);
	$spip_version_affichee = $regs[1];

?>