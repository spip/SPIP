var vis = new Array();

function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}

function swap_couche(couche, rtl) {
	triangle = MM_findObj('triangle' + couche);
	if (!(layer = MM_findObj('Layer' + couche))) return;
	if (vis[couche] == 'hide'){
		if (triangle) triangle.src = 'img_pack/deplierbas' + rtl + '.gif';
		layer.style.display = 'block';
		vis[couche] = 'show';
	} else {
		if (triangle) triangle.src = 'img_pack/deplierhaut' + rtl + '.gif';
		layer.style.display = 'none';
		vis[couche] = 'hide';
	}
}
