/*
  Author: Justin Whitford
  Source: www.evolt.org
*/
function filtery(pattern, list){
  /*
  if the dropdown list passed in hasn't
  already been backed up, we'll do that now
  */
  if (!list.bak){
    /*
    We're going to attach an array to the select object
    where we'll keep a backup of the original dropdown list
    */
    list.bak = new Array();
    for (n=0; n<list.length; n++){
      list.bak[list.bak.length] = new Array(list[n].value, list[n].text);
    }
    bakselected = list.selectedIndex;
  }

  /*
  We're going to iterate through the backed up dropdown
  list. If an item matches, it is added to the list of
  matches. If not, then it is added to the list of non matches.
  */
  match = new Array();
  nomatch = new Array();
  
  if (pattern.length != 0) {
	  for (n=0; n<list.bak.length; n++){
		if(list.bak[n][1].toLowerCase().indexOf(pattern.toLowerCase())!=-1 || list.bak[n][0] == pattern ){
		  match[match.length] = new Array(list.bak[n][0], list.bak[n][1]);
		}else{
		  nomatch[nomatch.length] = new Array(list.bak[n][0], list.bak[n][1]);
		}
	  }
  }

  /*
  Now we completely rewrite the dropdown list.
  First we write in the matches, then we write
  in the non matches
  */
  
  if (match.length > 0) {
	  list.options.length = match.length;
	  for (n=0; n<match.length; n++){
		list[n].value = match[n][0];
		list[n].text = match[n][1];
	  }

    list.selectedIndex=0;
  
  }
  else {
	  list.options.length = list.bak.length;
	  for (n=0; n<list.bak.length; n++){
		list[n].value =  list.bak[n][0];
		list[n].text =  list.bak[n][1];
	  }
  list.selectedIndex = bakselected;
  }

}
