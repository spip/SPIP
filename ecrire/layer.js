var vis = new Array();


function swap_couche(couche, rtl) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (vis[couche] == 'hide'){
		if (triangle) triangle.src = 'img_pack/deplierbas.gif';
		layer.style.display = 'block';
		vis[couche] = 'show';
	} else {
		if (triangle) triangle.src = 'img_pack/deplierhaut' + rtl + '.gif';
		layer.style.display = 'none';
		vis[couche] = 'hide';
	}
}
function ouvrir_couche(couche, rtl) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = 'img_pack/deplierbas.gif';
	layer.style.display = 'block';
	vis[couche] = 'show';
}
function fermer_couche(couche, rtl) {
	triangle = findObj('triangle' + couche);
	if (!(layer = findObj('Layer' + couche))) return;
	if (triangle) triangle.src = 'img_pack/deplierhaut' + rtl + '.gif';
	layer.style.display = 'none';
	vis[couche] = 'hide';
}
function manipuler_couches(action,rtl,first,last) {
	if (action=='ouvrir') {
		for (j=first; j<=last; j+=1) {
			ouvrir_couche(j,rtl);
		}
	} else {
		for (j=first; j<=last; j+=1) {
			fermer_couche(j,rtl);
		}
	}
}
