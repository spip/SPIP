var fx_done=null;
var formatfx_done=null;
function dofxform(){
	if (!window.jQuery) { // on passe ici apres ajax et un file upload ... :( bug pas resolu
		setTimeout(dofxform,1000);
		return;
	}
	fx_done = true;
  // Hide forms
  var f=jQuery('form').parents('div.formfx').find('form');
  f.css('opacity','50%').end();
  
  // Processing
  f.find( 'ol>li>label' ).not( '.nofx' ).each( function( i ){
    var labelContent = this.innerHTML;
    var labelWidth = document.defaultView.getComputedStyle( this, '' ).getPropertyValue( 'width' );
    var labelSpan = document.createElement( 'span' );
        labelSpan.style.display = 'block';
        labelSpan.style.width = labelWidth;
        labelSpan.innerHTML = labelContent;
    this.style.display = '-moz-inline-box';
    this.innerHTML = "";
    this.appendChild( labelSpan );
  } ).end();
  
  // Show forms
  f.css('opacity','100%').end();
}
function fxform(){
		dofxform();
		if (window.onAjaxLoad)
		  // s'ajouter dans la file des onajaxload
		  onAjaxLoad(dofxform);
}
function formate_formfx(){
	if (!formatfx_done && window.jQuery) {
		var formulaires = jQuery('form').parents('div.formfx');
		jQuery(formulaires).find('input, textarea')
		  .bind('focus',function(){jQuery(this).addClass('focus');})
		  .bind('blur',function(){jQuery(this).removeClass('focus');});
		formatfx_done = true;
	}
}
if( document.addEventListener ) document.addEventListener( 'DOMContentLoaded', fxform, false );
if (window.jQuery) {
	jQuery('document').ready(function(){
		formate_formfx();
	  onAjaxLoad(formate_formfx);
	});
}
