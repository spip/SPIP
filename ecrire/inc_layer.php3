<?
//
// Ce fichier ne sera execute qu'une fois
if (defined("_INC_LAYER")) return;
define("_INC_LAYER", "1");


//
// Le contenu de cette fonction est a mettre dans inc_version
//

function init_layer() {
	global $HTTP_USER_AGENT, $browser_name, $browser_version, $browser_description;
	ereg("^([A-Za-z]+)/([0-9]+\.[0-9]+) (.*)$", $HTTP_USER_AGENT, $match);
	$browser_name = $match[1];
	$browser_version = $match[2];
	$browser_description = $match[3];
	
	if (eregi("opera", $browser_description)) {
		eregi("Opera ([^\ ]*)", $browser_description, $match);
		$browser_name = "Opera";
		$browser_version = $match[1];
	}
	else if (eregi("msie", $browser_description)) {
		eregi("MSIE ([^;]*)", $browser_description, $match);
		$browser_name = "MSIE";
		$browser_version = $match[1];
	}
}

function test_layer(){
	global $browser_name;
	global $browser_version;

	if (eregi("msie", $browser_name) AND $browser_version >= 5) {
		return true;
	} else if (eregi("mozilla", $browser_name) AND $browser_version >= 5){
		return true;
	}
	else {
		return false;
	}
}


function afficher_script_layer(){

	if (test_layer()){
?>

<script language="JavaScript">
<!--
function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
    document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}
MM_reloadPage(true);

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}


var vis = new Array();


function swap_couche(couche){
	if (vis[couche] == 'show'){
		MM_swapImage('triangle'+couche,'','IMG2/deplierhaut.gif?'+couche,1)
		MM_showHideLayers('Layer'+couche,'','hide');
		vis[couche] = 'hide';
	} else {
		MM_swapImage('triangle'+couche,'','IMG2/deplierbas.gif?'+couche,1)
		MM_showHideLayers('Layer'+couche,'','show');
		vis[couche] = 'show';
	}
}

function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}

function MM_showHideLayers() { //v3.0
  var i,p,v,obj,args=MM_showHideLayers.arguments;
  for (i=0; i<(args.length-2); i+=3) if ((obj=MM_findObj(args[i]))!=null) { v=args[i+2];
    if (obj.style) { obj=obj.style; v=(v=='show')?'block':(v='hide')?'none':v; }
    obj.display=v; }
}
//-->
</script>

<?
	}
}


function debut_block_visible($nom_block){
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}
		
		/*$retour = "\n<script language='JavaScript'>\n";
		$retour .= "<!--\n";
		$retour .= "vis['".$numero_block["$nom_block"]."'] = 'show';\n";
		$retour .= "//-->\n";
		$retour .= "</script>\n";
		*/
		$retour .= "<div id='Layer".$numero_block["$nom_block"]."' style='display: block'>";
	}
	return $retour;
}

function debut_block_invisible($nom_block){
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}
		
		$retour = "\n<script language='JavaScript'>\n";
		$retour .= "<!--\n";
		$retour .= "vis['".$numero_block["$nom_block"]."'] = 'hide';\n";
		$retour .= "//-->\n";
		$retour .= "</script>\n";
		
		$retour .= "<div id='Layer".$numero_block["$nom_block"]."' style='display: none; margin-top: 1;'>";
	}
	return $retour;
}

function fin_block() {
	if (test_layer()) {
		return "</div>";
	}
}

function bouton_block_invisible($nom_block) {
	if (test_layer()) {
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		return "<a href=\"javascript:swap_couche('".$numero_block["$nom_block"]."')\"><IMG name='triangle".$numero_block["$nom_block"]."' SRC='IMG2/deplierhaut.gif?".$numero_block["$nom_block"]."' WIDTH=16 HEIGHT=14 border=0></a> ";
	}
}

function bouton_block_visible($nom_block){
	if (test_layer()){
		global $numero_block;
		global $compteur_block;

		if (!$numero_block["$nom_block"] > 0){
			$compteur_block++;
			$numero_block["$nom_block"] = $compteur_block;
		}

		return "<a href=\"javascript:swap_couche('".$numero_block["$nom_block"]."')\"><IMG name='triangle".$numero_block["$nom_block"]."' SRC='IMG2/deplierbas.gif?".$numero_block["$nom_block"]."' WIDTH=16 HEIGHT=14 border=0></a> ";
	}
}

init_layer();
 	
?>