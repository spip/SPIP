// JavaScript Document
jQuery.async_upload_count = 0;
jQuery.fn.async_upload = function(add_function) {
  return this.submit(function(){
    return do_async_upload(this);
  });
  
  function do_async_upload(form) {
    jQuery.async_upload_count++;
    var num = jQuery.async_upload_count;
    var jForm = $(form);
    var par = $(jForm).parent();
    $("div.upload_message",par)
    .remove();
    if(!form.async_init) {
      form.async_init = true
      jForm
      .append("<input type='hidden' name='iframe' value='iframe'>")
      .find("input[@name='redirect']")
        .val("")
      .end();
    }
    
		jForm.attr("target","upload_frame"+num);
    var jFrame = $("<iframe id='upload_frame"+num+"' name='upload_frame"+num+"' frameborder='0' marginwidth='0' marginheight='0' scrolling='no' style='position:absolute;width:1px;height:1px;' onload='this.iframeload("+num+")'></iframe>")
      .appendTo("body");
    
    //IE apparently do not write anything in an iframe onload event handler 
    jFrame[0].iframeload = function(num) {
        //remove the previous message
        $("div.upload_message",par).remove();
        var res = $(".upload_answer",this.contentDocument || document.frames(this.name).document.body);
        //possible classes 
        //upload_document_added
        if(res.is(".upload_document_added")) {
          return add_function(res,jForm);
        }
        //upload_error
        if(res.is(".upload_error")) {
          var msg = $("<div class='upload_message'>")
          .append(res.html())
          jForm.after(msg[0]);
          return true;
        } 
        //upload_zip_list
        if(res.is(".upload_zip_list")) {
          var zip_form = $("<div class='upload_message'>").append(res.html());
          zip_form
          .find("form")
            .attr("target","upload_frame"+num)
            .append("<input type='hidden' name='iframe' value='iframe'>")
          .end();
          jForm.after(zip_form[0]);
          return true;  
        }
    };
    
    jForm.before($("<div class='upload_message' style='height:1%'>").append(ajax_image_searching)[0]);
    return true;
  }
}

function async_upload_article_edit(res,jForm){
      var cont;
      //add a class to new documents
      res.
      find(">div[@class]")
        .addClass("documents_added")
        .css("display","none")
      .end();
      if (jForm.find("input[@name='arg']").val().search("vignette")!=-1)
        cont = $("#liste_images");
      else
        cont = $("#liste_documents");
      cont
      .prepend(res.html());
      //find added documents, remove label and show them nicely
      cont.
      find("div.documents_added")
        .removeClass("documents_added")
        .show("slow",function(){
            var anim = $(this).css("height","");
            //bug explorer-opera-safari
            if(!jQuery.browser.mozilla) anim.width(this.orig.width-2);
            $(anim).find("img[@onclick]").get(0).onclick();
        })
        .overflow("");
      verifForm(cont);
      return true;
}

function async_upload_icon(res) {
  res.find(">div").each(function(){
    var cont = $("#"+this.id);
    verifForm(cont.html($(this).html()));
    $("form.form_upload_icon",cont).async_upload(async_upload_icon);
		cont.find("img[@onclick]").each(function(){this.onclick();});
  });
  return true;                     
}

function async_upload_portfolio_documents(res){
  res.find(">div").each(function(){
    var cont = $("#"+this.id);
    var self = $(this);
    if(!cont.size()) {
      cont = $(this.id.search(/--/)!=-1 ? "#portfolio":"#documents")
      .append(self.clone().get());
    }
    verifForm(cont.html(self.html()));
    $("form.form_upload",cont).async_upload(async_upload_portfolio_documents);
  });
  return true;             
}
