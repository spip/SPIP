var vis = new Array();

function MM_findObj(n, d) { //v4.0
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && document.getElementById) x=document.getElementById(n); return x;
}

function swap_couche(couche){
	if (!(triangle = MM_findObj('triangle' + couche))) return;
	if (!(layer = MM_findObj('Layer' + couche))) return;
	if (vis[couche] == 'hide'){
		triangle.src = 'img_pack/deplierbas.gif';
		layer.style.display = 'block';
		vis[couche] = 'show';
	} else {
		triangle.src = 'img_pack/deplierhaut.gif';
		layer.style.display = 'none';
		vis[couche] = 'hide';
	}
}
