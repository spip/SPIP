var agt=navigator.userAgent.toLowerCase();
var ie = (agt.indexOf("msie") != -1);
var ns = (navigator.appName.indexOf("Netscape") != -1);
var win = ((agt.indexOf("win")!=-1) || (agt.indexOf("32bit")!=-1));
var mac = (agt.indexOf("mac")!=-1);

function detectIE(ClassID,name) { 
	result = false; 
	document.write('<SCRIPT LANGUAGE=VBScript>\n on error resume next \n result = IsObject(CreateObject("' + ClassID + '"))</SCRIPT>\n'); 
	if (result) return name+','; 
	else return ''; 
}

function detectNS(ClassID,name) { 
	n = ""; 
	if (nse.indexOf(ClassID) != -1) 
		if (navigator.mimeTypes[ClassID].enabledPlugin != null) 
			n = name+","; 
	return n; 
}

if (ie && win) { 
	pluginlist = detectIE("Adobe.SVGCtl","SVG Viewer") 
	+ detectIE("SWCtl.SWCtl.1","Shockwave Director") 
	+ detectIE("ShockwaveFlash.ShockwaveFlash.1","Shockwave Flash") 
	+ detectIE("rmocx.RealPlayer G2 Control.1","RealPlayer") 
	+ detectIE("QuickTimeCheckObject.QuickTimeCheck.1","QuickTime") 
	+ detectIE("MediaPlayer.MediaPlayer.1","Windows Media Player") 
	+ detectIE("PDF.PdfCtrl.5","Acrobat Reader"); }

if (ns || !win) {
	nse = ""; 
	for (var i=0; i<navigator.mimeTypes.length; i++) 
		nse += navigator.mimeTypes[i].type.toLowerCase();
		
	pluginlist = detectNS("image/svg-xml","SVG Viewer") 
		+ detectNS("application/x-director","Shockwave Director") 
		+ detectNS("application/x-shockwave-flash","Shockwave Flash") 
		+ detectNS("audio/x-pn-realaudio-plugin","RealPlayer") 
		+ detectNS("video/quicktime","QuickTime") 
		+ detectNS("application/x-mplayer2","Windows Media Player") 
		+ detectNS("application/pdf","Acrobat Reader");

}




pluginlist += navigator.javaEnabled() ? "Java," : "";
if (pluginlist.length > 0) pluginlist = pluginlist.substring(0,pluginlist.length-1);


//SAMPLE USAGE- detect "Flash"
//if (pluginlist.indexOf("SVG")!=-1)
	//alert(pluginlist) ;