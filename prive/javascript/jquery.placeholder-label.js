/**
* Placeholder label
* https://github.com/AbleTech/jquery.placeholder-label
*
* Copyright (c) 2010 Able Technology Consulting Limited
* http://www.abletech.co.nz/
*/
(function($) {
  $.placeholderLabel = {
    placeholder_class: null,
    add_placeholder: function(){
      if($(this).val() == $(this).attr('placeholder')){
        $(this).val('').removeClass($.placeholderLabel.placeholder_class);
      }
    },
    remove_placeholder: function(){
      if($(this).val() == ''){
        $(this).val($(this).attr('placeholder')).addClass($.placeholderLabel.placeholder_class);
      }
    },
    disable_placeholder_fields: function(){
      $(this).find("input[placeholder]").each(function(){
        if($(this).val() == $(this).attr('placeholder')){
          $(this).val('');
        }
      });

      return true;
    }
  };

  $.fn.placeholderLabel = function(options) {
    // detect modern browsers
    var dummy = document.createElement('input');
    if(dummy.placeholder != undefined){
      return this;
    }

    var config = {
      placeholder_class : 'placeholder'
    };

    if(options) $.extend(config, options);

    $.placeholderLabel.placeholder_class = config.placeholder_class;

    this.each(function() {
      var input = $(this);

      input.focus($.placeholderLabel.add_placeholder);
      input.blur($.placeholderLabel.remove_placeholder);

      input.triggerHandler('focus');
      input.triggerHandler('blur');

      $(this.form).submit($.placeholderLabel.disable_placeholder_fields);
    });

    return this;
  }
})(jQuery);
