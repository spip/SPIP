/**
 * SEhighlight plugin for jQuery
 * 
 * Thanks to Scott Yang <http://scott.yang.id.au/>
 * for the original idea and some code
 *    
 * @author Renato Formato <renatoformato@virgilio.it> 
 *  
 * @version 0.32
 *
 *  Options
 *  - exact (string, default:"exact") 
 *    "exact" : find and highlight the exact words.
 *    "whole" : find partial matches but highlight whole words
 *    "partial": find and highlight partial matches
 *     
 *  - style_name (string, default:'hilite')
 *    The class given to the span wrapping the matched words.
 *     
 *  - style_name_suffix (boolean, default:true)
 *    If true a different number is added to style_name for every different matched word.
 *     
 *  - debug_referrer (string, default:null)
 *    Set a referrer for debugging purpose.
 *     
 *  - engines (array of regex, default:null)
 *    Add a new search engine regex to highlight searches coming from new search engines.
 *    The first element is the regex to match the domain.
 *    The second element is the regex to match the query string. 
 *    Ex: [/^http:\/\/my\.site\.net/i,/search=([^&]+)/i]        
 *            
 *  - startHighlightComment (string, default:null)
 *    The text of a comment that starts a block enabled for highlight.
 *    If null all the document is enabled for highlight.
 *        
 *  - stopHighlightComment (string, default:null)  
 *    The text of a comment that ends a block enabled for highlight.
 *    If null all the document is enabled for highlight. 
 */

(function($){
  jQuery.fn.SEhighlight = function(options) {
    var ref = options.debug_referrer || document.referrer;
    if(!ref && options.keys==undefined) return this;
    
    SEhighlight.options = $.extend({exact:"exact",style_name:'hilite',style_name_suffix:true},options);
    
    if(options.engines) SEhighlight.engines.unshift(options.engines);  
    var q = options.keys!=undefined?options.keys.split(/[\s,\+\.]+/):SEhighlight.decodeURL(ref,SEhighlight.engines);
    if(q && q.join("")) {
      SEhighlight.buildReplaceTools(q);
      return this.each(function(){
        var el = this;
        if(el==document) el = $("body")[0];
        SEhighlight.hiliteElement(el, q); 
      })
    } else return this;
  }    

  var SEhighlight = {
    options: {},
    regex: [],
    engines: [
    [/^http:\/\/(www\.)?google\./i, /q=([^&]+)/i],                            // Google
    [/^http:\/\/(www\.)?search\.yahoo\./i, /p=([^&]+)/i],                     // Yahoo
    [/^http:\/\/(www\.)?search\.msn\./i, /q=([^&]+)/i],                       // MSN
    [/^http:\/\/(www\.)?search\.live\./i, /query=([^&]+)/i],                  // MSN Live
    [/^http:\/\/(www\.)?search\.aol\./i, /userQuery=([^&]+)/i],               // AOL
    [/^http:\/\/(www\.)?ask\.com/i, /q=([^&]+)/i],                            // Ask.com
    [/^http:\/\/(www\.)?altavista\./i, /q=([^&]+)/i],                         // AltaVista
    [/^http:\/\/(www\.)?feedster\./i, /q=([^&]+)/i],                          // Feedster
    [/^http:\/\/(www\.)?search\.lycos\./i, /q=([^&]+)/i],                     // Lycos
    [/^http:\/\/(www\.)?alltheweb\./i, /q=([^&]+)/i],                         // AllTheWeb
    [/^http:\/\/(www\.)?technorati\.com/i, /([^\?\/]+)(?:\?.*)$/i],           // Technorati
    ],
    subs: {},
    decodeURL: function(URL,reg) {
      URL = decodeURIComponent(URL);
      var query = null;
      $.each(reg,function(i,n){
        if(n[0].test(URL)) {
          var match = URL.match(n[1]);
          if(match) {
            query = match[1];
            return false;
          }
        }
      })
      
      if (query) {
      query = query.replace(/(\'|")/, '\$1');
      query = query.split(/[\s,\+\.]+/);
      }
      
      return query;
    },
		regexAccent : [
      [/[\xC0-\xC5]/ig,'a'],
      [/[\xD2-\xD6\xD8]/ig,'o'],
      [/[\xC8-\xCB]/ig,'e'],
      [/\xC7/ig,'c'],
      [/[\xCC-\xCF]/ig,'i'],
      [/[\xD9-\xDC]/ig,'u'],
      [/\xFF/ig,'y'],
      [/\xD1/ig,'n'],
      [/[\x91\x92\u2018\u2019]/ig,'\'']
    ],
    matchAccent : /[\x91\x92\u2018\u2019\xC0-\xC5\xC7-\xCF\xD1-\xD6\xD8-\xDC\xFF]/ig,  
		replaceAccent: function(q) {
		  SEhighlight.matchAccent.lastIndex = 0;
      if(SEhighlight.matchAccent.test(q)) {
        for(var i=0,l=SEhighlight.regexAccent.length;i<l;i++)
          q = q.replace(SEhighlight.regexAccent[i][0],SEhighlight.regexAccent[i][1]);
      }
      return q;
    },
    buildReplaceTools : function(query) {
        re = new Array();
        for (var i = 0, l=query.length; i < l; i ++) {
            query[i] = SEhighlight.replaceAccent(query[i].toLowerCase());
            re.push(query[i]);
        }
        
        var regex = re.join("|");
        switch(SEhighlight.options.exact) {
          case "exact":
            regex = '\\b(?:'+regex+')\\b';
            break;
          case "whole":
            regex = '\\b\\w*('+regex+')\\w*\\b';
            break;
        }    
        SEhighlight.regex = new RegExp(regex, "gi");
        
        for (var i = 0, l = query.length; i < l; i ++) {
            SEhighlight.subs[query[i]] = SEhighlight.options.style_name+
              (SEhighlight.options.style_name_suffix?i+1:''); 
        }        
    },
    nosearch: /s(?:cript|tyle)|textarea/i,
    hiliteElement: function(el, query) {
        var startIndex, endIndex, comment = false, opt = SEhighlight.options;
        if(!opt.startHighlightComment || !opt.stopHighlightComment)
          return SEhighlight.hiliteTree(0,el.childNodes.length,el,query);
        if($.browser.msie) {
          var item = el.firstChild, i = 0, parents = [], startComment = false;
          while(item) {
            if(item.nodeType==8) {
              if($.trim(item.data)==opt.startHighlightComment) {
                comment = startComment = true;
                startIndex= i+1;
              } else if($.trim(item.data)==opt.stopHighlightComment) {
                endIndex = i;
                SEhighlight.hiliteTree(startIndex,endIndex,item.parentNode,query);
                startComment = false;
              }
            }
            var next = item.nextSibling, back, child;
            if(!startComment && (child = item.firstChild)) {
              if(next)
                parents.push([next,i+1]);
              item = child;
              i = 0;
            } else {
              if(!(item = next)) {
                if(back = parents.pop()) {
                  item = back[0];
                  i =  back[1];
                }
              } else i++;
            }
          }
        } else {
          var walker = document.createTreeWalker(el,NodeFilter.SHOW_COMMENT,null,false), currComment;
          while(currComment = walker.nextNode()) {
            if($.trim(currComment.data)==opt.startHighlightComment) {
              comment = true;
              el = currComment.parentNode;
              startIndex = 0;
              endIndex = el.childNodes.length;
              while(el.childNodes[startIndex]!=currComment) startIndex++;
              startIndex++;
            } else if($.trim(currComment.data)==opt.stopHighlightComment) {
              while(el.childNodes[endIndex-1]!=currComment) endIndex--;
              SEhighlight.hiliteTree(startIndex,endIndex,el,query);
            }
          }
        }
        if(!comment) SEhighlight.hiliteTree(0,el.childNodes.length,el,query);
    },
    hiliteTree : function(startIndex,endIndex,el,query) {
        var matchIndex = SEhighlight.options.exact=="whole"?1:0;
        for(;startIndex<endIndex;startIndex++) {
          var item = el.childNodes[startIndex];
          if ( item.nodeType != 8 ) {//comment node
  				  //text node
            if(item.nodeType==3) {
              var text = item.data, textNoAcc = SEhighlight.replaceAccent(text);
              var newtext="",match,index=0;
              SEhighlight.regex.lastIndex = 0;
              while(match = SEhighlight.regex.exec(textNoAcc)) {
                newtext += text.substr(index,match.index-index)+'<span class="'+
                SEhighlight.subs[match[matchIndex].toLowerCase()]+'">'+text.substr(match.index,match[0].length)+"</span>";
                index = match.index+match[0].length;
              }
              if(newtext) {
                //add ther last part of the text
                newtext += text.substring(index);
                var repl = $.merge([],$("<span>"+newtext+"</span>")[0].childNodes);
                endIndex += repl.length-1;
                startIndex += repl.length-1;
                $(item).before(repl).remove();
              }                
            } else {
              if(item.nodeType==1 && item.nodeName.search(SEhighlight.nosearch)==-1)
                SEhighlight.hiliteTree(0,item.childNodes.length,item,query);
            }	
          }
        }    
    }
    
  };
})(jQuery)
