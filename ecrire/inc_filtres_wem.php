<?php

/*   WeM - Web editeur Mathematique

   Copyright 2002 Stephan Semirat

    Contact : admin@mathosphere.net

    

    This file is part of WeM.



    WeM is free software; you can redistribute it and/or modify

    it under the terms of the GNU General Public License as published by

    the Free Software Foundation; either version 2 of the License, or

    (at your option) any later version.



    Foobar is distributed in the hope that it will be useful,

    but WITHOUT ANY WARRANTY; without even the implied warranty of

    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

    GNU General Public License for more details.



    You should have received a copy of the GNU General Public License

    along with Foobar; if not, write to the Free Software

    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/



//

// Ce fichier ne sera execute qu'une fois

if (defined("_ECRIRE_INC_FILTRES_WEM")) return;

define("_ECRIRE_INC_FILTRES_WEM", "1");



$GLOBALS["latex2mathml"] = array (

	// correspondances selon http://www.w3.org/TR/MathML2/byalpha.html

	'angle' => 'ang',

	'approx' => 'ap',

	'approxeq' => 'ape',

	'ast' => 'midast',

	'backcong' => 'bcong',

	'backepsilon' => 'bepsi',

	'backprime' => 'bprime',

	'backslash' => 'bsol',

	'because' => 'becaus',

	'between' => 'twixt',

	'bigcap' => 'xcap',

	'bigcirc' => 'xcirc',

	'bigcup' => 'xcup',

	'bigodot' => 'xodot',

	'bigoplus' => 'xoplus',

	'bigotimes' => 'xotime',

	'bigtriangledown' => 'xdtri',

	'bigsqcup' => 'xsqcup',

	'biguplus' => 'xuplus',

	'bigtriangleup' => 'xutri',

	'bigstar' => 'starf',

	'bigvee' => 'xvee',

	'bigwedge' => 'xwedge',

	'blacktriangle' => 'utrif',

	'blacktriangleleft' => 'ltrif',

	'blacktriangleright' => 'rtrif',

	'blacksquare' => 'squarf',

	'blacklozenge' => 'lozf',

	'blacktriangledown' => 'dtrif',

	'bot' => 'bottom',

	'boxminus' => 'minusb',

	'boxplus' => 'plusb',

	'boxdot' => 'sdotb',

	'boxtimes' => 'timesb',

	'dotsquare' => 'sdotb',

	'bullet' => 'bull',

	'Bumpeq' => 'bump',

	'bumpeq' => 'bumpe',

	'cdot' => 'sdot',

	'cdots' => 'ctdot',

	'checkmark' => 'check',

	'circledast' => 'oast',

	'circledcirc' => 'ocir',

	'circleddash' => 'odash',

	'circlearrowleft' => 'olarr',

	'circlearrowright' => 'orarr',

	'circledR' => 'reg',

	'circledS' => 'oS',

	'dotplus' => 'plusdo',

	'oslash' => 'osol',

	'centerdot' => 'middot',

	'circ' => 'cir',

	'circeq' => 'cire',

	'clubsuit' => 'clubs',

	'coloneq' => 'colone',

	'complement' => 'comp',

	'curlyeqprec' => 'cuepr',

	'curlyeqsucc' => 'cuesc',

	'curvearrowleft' => 'cularr',

	'curvearrowright' => 'curarr',

	'curlyvee' => 'cuvee',

	'curlywedge' => 'cuwed',

	'ddagger' => 'Dagger',

	'ddots' => 'dtdot',

	'ddotseq' => 'eDDot',

	'diamond' => 'diam',

	'diamondsuit' => 'diams',

	'digamma' => 'gammad',

	'div' => 'divide',

	'divideontimes' => 'divonx',

	'doteq' => 'esdot',

	'Doteq' => 'eDot',

	'doteqdot' => 'eDot',

	'dotminus' => 'minusd',

	'doublecap' => 'Cap',

	'doublecup' => 'Cup',

	'Downarrow' => 'dArr',

	'downarrow' => 'darr',

	'downdownarrows' => 'ddarr',

	'downharpoonleft' => 'dhart',

	'downharpoonright' => 'dharr',

	'dbkarow' => 'rBarr',

	'bkarow' => 'rbarr',

	'drbkarow' => 'RBarr',

	'emptyset' => 'empty',

	'eqcirc' => 'ecir',

	'eqcolon' => 'ecolon',

	'eqslantgtr' => 'egs',

	'eqslantless' => 'els',

	'exists' => 'exist',

	'fallingdotseq' => 'efDot',

	'geq' => 'ge',

	'geqq' => 'gE',

	'geqslant' => 'ges',

	'gets' => 'larr',

	'gg' => 'Gt',

	'ggg' => 'Gg',

	'gggtr' => 'Gg',

	'gnapprox' => 'gnap',

	'gneq' => 'gne',

	'gneqq' => 'gnE',

	'gtrapprox' => 'gap',

	'gtrdot' => 'gtdot',

	'gtreqqless' => 'gEl',

	'gtreqless' => 'gel',

	'gtrless' => 'gl',

	'gtrsim' => 'gsim',

	'gvertneqq' => 'gvnE',

	'hbar' => 'planck',

	'hslash' => 'plankv',

	'heartsuit' => 'hearts',

	'hkswarow' => 'swarhk',

	'hksearow' => 'searhk',

	'hookrightarrow' => 'rarrhk',

	'hookleftarrow' => 'larrhk',

	'iiiint' => 'qint',

	'iiint' => 'tint',

	'Im' => 'image',

	'infty' => 'infin',

	'intercal' => 'intcal',

	'intprod' => 'iprod',

	'langle' => 'lang',

	'lbrace' => 'lcub',

	'lbrack' => 'lsqb',

	'Leftarrow' => 'lArr',

	'leftarrow' => 'larr',

	'leftarrowtail' => 'larrtl',

	'Leftrightarrow' => 'hArr',

	'leftrightarrow' => 'harr',

	'leftrightsquigarrow' => 'harrw',

	'leftthreetimes' => 'lthree',

	'leq' => 'le',

	'leqq' => 'lE',

	'leqslant' => 'les',

	'lessapprox' => 'lap',

	'lessdot' => 'ltdot',

	'lesseqgtr' => 'leg',

	'lesseqqgtr' => 'lEg',

	'lessgtr' => 'lg',

	'leftharpoondown' => 'lhard',

	'leftharpoonup' => 'lharu',

	'leftleftarrows' => 'llarr',

	'leftrightarrows' => 'lrarr',

	'leftrightharpoons' => 'lrhar',

	'lesssim' => 'lsim',

	'llcorner' => 'dlcorn',

	'Lleftarrow' => 'lAarr',

	'll' => 'Lt',

	'lll' => 'Ll',

	'llless' => 'Ll',

	'lmoustache' => 'lmoust',

	'lnapprox' => 'lnap',

	'lneq' => 'lne',

	'lneqq' => 'lnE',

	'lnot' => 'not',

	'Longleftrightarrow' => 'xhArr',

	'longleftrightarrow' => 'xharr',

	'Longleftarrow' => 'xlArr',

	'longleftarrow' => 'xlarr',

	'longmapsto' => 'xmap',

	'Longrightarrow' => 'xrArr',

	'longrightarrow' => 'xrarr',

	'looparrowleft' => 'larrlp',

	'looparrowright' => 'rarrlp',

	'lor' => 'or',

	'lozenge' => 'loz',

	'nparallel' => 'npar',

	'nprec' => 'npr',

	'npreceq' => 'npre',

	'nRightarrow' => 'nrArr',

	'nrightarrow' => 'nrarr',

	'nsubset' => 'vnsub',

	'nsupset' => 'vnsup',

	'ntriangleright' => 'nrtri',

	'ntrianglerighteq' => 'nrtrie',

	'nsucc' => 'nsc',

	'nsucceq' => 'nsce',

	'nsimeq' => 'nsime',

	'nshortmid' => 'nsmid',

	'nshortparallel' => 'nspar',

	'nsubseteqq' => 'nsubE',

	'nsubseteq' => 'nsube',

	'nsupseteqq' => 'nsupE',

	'nsupseteq' => 'nsupe',

	'nwarrow' => 'nwarr',

	'lrcorner' => 'drcorn',

	'lvertneqq' => 'lvnE',

	'Lsh' => 'lsh',

	'maltese' => 'malt',

	'mapsto' => 'map',

	'measuredangle' => 'angmsd',

	'mp' => 'mnplus',

	'multimap' => 'mumap',

	'napprox' => 'nap',

	'natural' => 'natur',

	'nearrow' => 'nearr',

	'nexists' => 'nexist',

	'neq' => 'ne',

	'neg' => 'not',

	'ngeq' => 'nge',

	'ngeqq' => 'ngE',

	'ngeqslant' => 'nges',

	'ngtr' => 'ngt',

	'nLeftarrow' => 'nlArr',

	'nleftarrow' => 'nlarr',

	'nleqq' => 'nlE',

	'nleq' => 'nle',

	'nleqslant' => 'nles',

	'nless' => 'nlt',

	'ntriangleleft' => 'nltri',

	'ntrianglelefteq' => 'nltrie',

	'nLeftrightarrow' => 'nhArr',

	'nleftrightarrow' => 'nharr',

	'oint' => 'conint',

	'owns' => 'ni',

	'parallel' => 'par',

	'partial' => 'part',

	'pitchfork' => 'fork',

	'pm' => 'plusmn',

	'prec' => 'pr',

	'precapprox' => 'prap',

	'preccurlyeq' => 'prcue',

	'preceq' => 'pre',

	'precnapprox' => 'prnap',

	'precneqq' => 'prnE',

	'precnsim' => 'prnsim',

	'propto' => 'prop',

	'precsim' => 'prsim',

	'quad' => 'ensp',

	'qquad' => 'emsp',

	'questeq' => 'equest',

	'rangle' => 'rang',

	'rbrace' => 'rcub',

	'rbrack' => 'rsqb',

	'Re' => 'real',

	'risingdotseq' => 'erDot',

	'rightharpoondown' => 'rhard',

	'rightharpoonup' => 'rharu',

	'Rightarrow' => 'rArr',

	'rightarrow' => 'rarr',

	'rightarrowtail' => 'rarrtl',

	'rightleftarrows' => 'rlarr',

	'rightleftharpoons' => 'rlhar',

	'rightsquigarrow' => 'rarrw',

	'rightrightarrows' => 'rrarr',

	'rightthreetimes' => 'rthree',

	'rmoustache' => 'rmoust',

	'Rrightarrow' => 'rAarr',

	'Rsh' => 'rsh',

	'searrow' => 'searr',

	'setminus' => 'setmn',

	'shortmid' => 'smid',

	'shortparallel' => 'spar',

	'simeq' => 'sime',

	'smallfrown' => 'sfrown',

	'smallsetminus' => 'ssetmn',

	'smallsmile' => 'ssmile',

	'spadesuit' => 'spades',

	'sphericalangle' => 'angsph',

	'sqsubset' => 'sqsub',

	'sqsubseteq' => 'sqsube',

	'sqsupset' => 'sqsup',

	'sqsupseteq' => 'sqsupe',

	'star' => 'sstarf',

	'straightepsilon' => 'epsi',

	'straightphi' => 'phi',

	'Subset' => 'Sub',

	'subset' => 'sub',

	'subseteqq' => 'subE',

	'subseteq' => 'sube',

	'subsetneqq' => 'subnE',

	'subsetneq' => 'subne',

	'Supset' => 'Sup',

	'supset' => 'sup',

	'supseteqq' => 'supE',

	'supseteq' => 'supe',

	'supsetneqq' => 'supnE',

	'supsetneq' => 'supne',

	'succ' => 'sc',

	'succapprox' => 'scap',

	'succcurlyeq' => 'sccue',

	'succeq' => 'sce',

	'succnapprox' => 'scnap',

	'succneqq' => 'scnE',

	'succnsim' => 'scnsim',

	'succsim' => 'scsim',

	'surd' => 'radic',

	'swarrow' => 'swarr',

	'therefore' => 'there4',

	'thickapprox' => 'thkap',

	'thicksim' => 'thksim',

	'to' => 'rarr',

	'toea' => 'nesear',

	'tosa' => 'seswar',

	'twoheadrightarrow' => 'Rarr',

	'triangle' => 'utri',

	'triangledown' => 'dtri',

	'triangleleft' => 'ltri',

	'trianglelefteq' => 'ltrie',

	'triangleq' => 'trie',

	'triangleright' => 'rtri',

	'trianglerighteq' => 'rtrie',

	'twoheadleftarrow' => 'Larr',

	'ulcorner' => 'ulcorn',

	'Uparrow' => 'uArr',

	'uparrow' => 'uarr',

	'upuparrows' => 'uuarr',

	'Updownarrow' => 'vArr',

	'updownarrow' => 'varr',

	'upharpoonleft' => 'uharl',

	'upharpoonright' => 'uharr',

	'Upsilon' => 'Upsi',

	'upsilon' => 'upsi',

	'urcorner' => 'urcorn',

	'restriction' => 'uharr',

	'varepsilon' => 'epsiv',

	'varkappa' => 'kappav',

	'varphi' => 'phiv',

	'varpi' => 'piv',

	'varnothing' => 'emptyv',

	'varpropto' => 'vprop',

	'vartriangleright' => 'vrtri',

	'varsubsetneqq' => 'vsubnE',

	'varsubsetneq' => 'vsubne',

	'varsupsetneqq' => 'vsupnE',

	'varsupsetneq' => 'vsupne',

	'varrho' => 'rhov',

	'varsigma' => 'sigmav',

	'vartheta' => 'thetav',

	'vartriangleleft' => 'vltri',

	'vee' => 'or',

	'Vert' => 'Verbar',

	'vert' => 'verbar',

	'wedge' => 'and',

	'wp' => 'weierp',

	'wr' => 'wreath'

);





function chercherbf($bo,$bf,$expression) {

	$expression=str_replace($bo,"(",$expression);

	$expression=str_replace($bf,")",$expression);

	$expr=$expression;

	$k=0;

	$flag=true;

	while ($flag==true){

		$j=strpos($expr,"(");

		$i=strpos($expr,")");

		if ($i===false) $i=100000;

		if ($j===false) $j=100000;

		if ($i<$j) {

			$k++;

			if ($k==1) $flag=false; else

			$expr=substr($expr,$i+1,strlen($expr));

		}

		if ($i>$j) {

			$k--;

			$expr=substr($expr,$j+1,strlen($expr));

		}

		if ($i==$j) {$flag=false;die("i=j");}

	}

	$expression=substr($expression,0,strlen($expression)-strlen($expr)+$i);

	$expression=str_replace("(",$bo,$expression);

	$expression=str_replace(")",$bf,$expression);

	return $expression;

}

function chercherbo($bo,$bf,$expression) {

	$expression=str_replace($bo,"(",$expression);

	$expression=str_replace($bf,")",$expression);

	$expr=$expression;

	$k=0;

	$flag=true;

	while ($flag==true){

		$i=strrpos($expr,"(");

		$j=strrpos($expr,")");

		if ($i===false) $i=-1;

		if ($j===false) $j=-1;

		if ($i>$j) {

			$k++;

			if ($k==1) $flag=false; else

			$expr=substr($expr,0,$i);

		}

		if ($i<$j) {

			$k--;

			$expr=substr($expr,0,$j);

		}

		if ($i==$j) {$flag=false;die("probleme de format du texte");}

	}

	$expression=substr($expression,$i+1,strlen($expression));

	$expression=str_replace("(",$bo,$expression);

	$expression=str_replace(")",$bf,$expression);

	return $expression;

}

function indiceexposant($expr) {

	$l=strlen($expr);

	$exprr="";

	$i=$l-1;

	while ($i>=0){

		$a=substr($expr,$i,1);

		$i--;

		$ok=false; $j=$i;

		if ((ereg("\^",$a)) || (ereg("\_",$a))) {

			$b=substr($expr,$i,1);

			$i--;

			if ((ereg(">",$b)) || ($b=="}")){

				if (substr($expr,$i,1)=="/"){

					$numerateur=strrchr(substr($expr,0,$i+2),"<");

					$i=$i-strlen($numerateur)+1;

				} else {

					if ($b==">") $b=strrchr(substr($expr,0,$i+2),"<");

					$i=$i-strlen($b)+1;

					$balisefin=$b;

					if ($b!="}") $balisedebut=str_replace("/","",$b); else $balisedebut="{";

					$c=chercherbo($balisedebut,$balisefin,substr($expr,0,$i+1));

					$i=$i-strlen($c.$balisedebut);

					$c=indiceexposant($c);

					if ($balisedebut!="{") $balisedebutN=$balisedebut; else $balisedebutN="<mrow>";

					if ($balisefin!="}") $balisefinN=$balisefin; else $balisefinN="</mrow>";

					$numerateur=$c;

				}

				$ok=true;

			}

			$b=substr($exprr,0,1);

			if (ereg("<",$b)) {

				$j=strpos($exprr,">");

				$b=substr($exprr,0,$j+1);

				$balisedebut=$b;

				$balisefin=substr($b,0,1)."/".substr($b,1,strlen($b)-1);

				$c=indiceexposant(chercherbf($balisedebut,$balisefin,substr($exprr,strlen($balisedebut),strlen($exprr))));

				$exprr=substr($exprr,strlen($balisedebut.$c.$balisefin),strlen($exprr));

				if (($balisedebut=="<msup>") && (ereg("\_",$a))){

					$baliseO="<msubsup>";

					$baliseF="</msubsup>";

					$balisedebut="";

					$balisefin="";

				} else {

					if (ereg("\_",$a)) {

						$baliseO="<msub>";

						$baliseF="</msub>";

					} else {

						$baliseO="<msup>";

						$baliseF="</msup>";

					}

				}				

				$denominateur=$balisedebut.$c.$balisefin;

			} else $ok=false;

			if ($ok) {

					$exprr=$baliseO.$balisedebutN.$numerateur.$balisefinN.$denominateur.$baliseF.$exprr;

				$a="";

			} else $i=$j;

		}

		$exprr=$a.$exprr;

	}

	return $exprr;

}





function editestring($expr) {

/*	while(ereg("\(([^()]*)\)",$expr)){

		$expr=preg_replace("/\(([^()]*)\)/","<mrow><mo><parenthese></mo>$1<mo></parenthese></mo></mrow>",$expr);

	}*/

	$l=strlen($expr);

	//echo "expr=$expr\n ";

	$exprr="";

	$i=$l-1;

	while ($i>=0){

		$a=substr($expr,$i,1);

		//echo " a=$a ";

		$i--;

		if ($a==";") {

			$texte=false;

			$b=strrchr(substr($expr,0,$i+1),"&");

			if (ereg("(&#)(.*)([^a-zA-Z]+)",$b)) { $texte=true;}

			if ($b=="") {$texte=true;}

			if ($texte==true){

				$b=strrchr(substr($expr,0,$i+2),"<");

				if (!(ereg("<mo",$b))) $a="<mo>".$a."</mo>";

				$exprr=$a.$exprr;

				$a="";

			} else {

				$b=strrchr(substr($expr,0,$i+2),"<");

				$exprr=$b.$exprr;

				$i=$i-strlen($b)+1;

				$a="";

			}

		}

		if (ereg("(\+|\-|\(|\)|=|\[|\]|\*|,|\.|:|%|!|')",$a)) {

			$b=strrchr(substr($expr,0,$i+2),"<");

			if (!(ereg("<mo",$b))) $a="<mo>".$a."</mo>";

			$exprr=$a.$exprr;

			$a="";

		}

		if ($a=="|") {

			$b=strrchr(substr($expr,0,$i+2),"<");

			if (!(ereg("<mo",$b))) $a="<mo>".$a."</mo>";

			$exprr="<mo>&#mid;</mo>".$exprr;

			$a="";

		}

/*		if (ereg("\}",$a)) {

			$b=chercherbo("{","}",substr($expr,0,$i+1));

			$i=$i-strlen($b)-1;

			if (($test=="^") || ($test=="_")) $exprr="<mrow>".$b."</mrow>".$exprr; else $exprr="{".$b."}".$exprr;

			$a="";

		}*/

		if (ereg(">",$a)) {

			$b=substr($expr,$i,1);

			if ($b!="\\") {

				$b=strrchr(substr($expr,0,$i+2),"<");

				$exprr=$b.$exprr;

				$i=$i-strlen($b)+1;

				$a="";

			}

		}

		if (ereg("[0-9]",$a)) {		

			while(ereg("[0-9]|\.|,",substr($expr,$i,1)) && ($i>=0)){

				$a=substr($expr,$i,1).$a;

				$i--;

			}

			$b=strrchr(substr($expr,0,$i+2),"<");

			if (!(ereg("<mn",$b))) $a="<mn>".$a."</mn>";

			$exprr=$a.$exprr;

			$a="";

		}

		if (ereg("[a-zA-Z]",$a)){

			$texte=false;

			$b=strrchr(substr($expr,0,$i+2),"\\");

			if (ereg("(\\\)(.*)([^a-zA-Z]+)",$b)) {			 $texte=true;}

			if ($b=="") {$texte=true;}

			if ($texte==true){

				$b=strrchr(substr($expr,0,$i+2),"<");

				// echo "//b=$b//";

				if ((!(ereg("<mi",$b))  && (!(ereg("<mtext",$b)))) || (!($b))) $a="<mi>".$a."</mi>";

				$exprr=$a.$exprr;

				$a="";

			} else {

				$commande=substr($b,1,strlen($b));

				if (strlen($GLOBALS["latex2mathml"][$commande]) > 0) $commande = $GLOBALS["latex2mathml"][$commande];

				$c=strrchr(substr($expr,0,$i+2),"<");

				if ((!(ereg("<mo",$c))) || (!($c))) {

					$i=$i-strlen($b)+1;

					if (($commande!="sqrt") && ($commande!="frac")) {

						$exprr="<mo>&#".$commande.";</mo>".$exprr;

					} else

						$exprr="\\".$commande."".$exprr;

				} else {

					 $exprr=str_replace("\\".$commande,"&#".$commande.";",$c).$exprr;

					// echo "c=$c\n";

					 //echo "exprr=$exprr\n";

					 $i=$i-strlen($c)+1;

					 //$a=substr($expr,$i,1);

					 //echo "a=$a\n";

					 //$a="";

				}

				$a="";

			}

		}

		if ($a==" "){

			$b=substr($expr,$i,1);

			if ($b=="\\") {

				$exprr="<mspace width=\"1em\"/>".$exprr;

				$i--;

			} else {

				$b=strrchr(substr($expr,0,$i+2),"<");

				if (ereg("<mtext",$b)) $exprr=" ".$exprr;

			}





			$a="";

		}

		if ($a=="\'"){

			$exprr="<mi>\'</mi>".$exprr;

			$a="";

		}

		$exprr=$a.$exprr;

//		echo "\n exprr=$exprr\n";

	}

	return $exprr;

}

function editermaths($message){

//echo $message."... \n";

	$flag=0;

	$message=str_replace("&#lt;","<mo>&#lt;</mo>",$message);

	$message=str_replace("&#gt;","<mo>&#gt;</mo>",$message);

	$message=str_replace("<mo><mo>","<mo>",$message);

	$message=str_replace("</mo></mo>","</mo>",$message);

	$message=str_replace("<quote/>","<mo>'</mo>",$message);

	$message=preg_replace("/(\\\){1}(begin)\{(array)\}/","<mtable><mtr columnalign=\"left\"><mtd>",$message);

	$message=preg_replace("/(\\\){1}(end)\{(array)\}/","</mtd></mtr>\n</mtable>",$message);

	$message=preg_replace("/((\\\){2})(<br \/>)?/","</mtd></mtr>\n<mtr columnalign=\"left\"><mtd>",$message);

	$message=preg_replace("/(&)([^#])/","</mtd><mtd>$2",$message);

	$message=str_replace("<mtr columnalign=\"left\"><mtd>\n</mtd></mtr>\n</mtable>","</mtable>",$message);

	while($flag<2) {

	$message=preg_replace("/(\\\){1}(displaystyle)\{([^}{]*)\}/","<mstyle displaystyle=\"true\">$3</mstyle>",$message);

	$message=preg_replace("/(\\\){1}(to)/","<mo>\\RightArrow</mo>",$message);

	$temp=preg_replace("/(\\\){1}(vec)\{([^}{]*)\}/","<mover accent=\"true\"><mrow>$3</mrow> <mo stretchy=\"true\">\\RightArrow</mo></mover>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(bar|overbar)\{([^}{]*)\}/","<mover accent=\"true\"><mrow>$3</mrow> <mo stretchy=\"true\">\\OverBar</mo></mover>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(overbrace)\{([^}{]*)\}/","<mover accent=\"true\"><mrow>$3</mrow> <mo stretchy=\"true\">\\OverBrace</mo></mover>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(underbrace)\{([^}{]*)\}/","<munder accentunder=\"true\"><mrow>$3</mrow> <mo stretchy=\"true\">\\UnderBrace</mo></munder>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(over)\{([^}{]*)\}\{([^}{]*)\}/","<mover><mrow>$3</mrow> <mrow>$4</mrow></mover>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(under)\{([^}{]*)\}\{([^}{]*)\}/","<munder><mrow>$3</mrow> <mrow>$4</mrow></munder>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}	$temp=preg_replace("/(\\\){1}(binomial)\{([^}{]*)\}\{([^}{]*)\}/","<mrow><mo><parenthese></mo><mfrac linethickness=\"0\"><mrow>$3</mrow><mrow>$4</mrow></mfrac><mo></parenthese></mo></mrow>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(text)\{([^}{]*)\}/","<mtext>$3</mtext>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(sqrt)\{([^}{]*)\}/","<msqrt>$3</msqrt>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$temp=preg_replace("/(\\\){1}(frac)\{([^}{]*)\}\{([^}{]*)\}/","<mfrac><mrow>$3</mrow> <mrow>$4</mrow></mfrac>",$message);

	if ($message!=$temp) {$message=editermaths($temp);}

	$message=preg_replace("/(\^\{[^}{]*\})(\_\{[^}{]*\})/","$2$1",$message);

	$message=preg_replace("/(\^|\_)\{([^}{]*)\}/","$1<mrow>$2</mrow>",$message);

	$message=preg_replace("/(\\\){1}(mathfrak)\{([^}{]{1})\}/","\\\\$3fr ",$message);				

	$message=preg_replace("/(\\\){1}(mathbb)\{([^}{]{1})\}/","\\\\$3opf ",$message);				

	$message=preg_replace("/(\\\){1}(mathcal)\{([^}{]{1})\}/","\\\\$3scr ",$message);

	$message=preg_replace("/(\^.{1})(\_.{1})/","$2$1",$message);

	$flag++;

	}

	$message=preg_replace("/(\{){1}([^{}]+)(\}){1}/e","'{'.editermaths(\"$2\").'}'",$message);

	$message=editestring($message);

	$message=indiceexposant($message);

//	$message=preg_replace("/<msubsup><mo>(&#sum;|&#prod;)<\/mo>([^{}]*)<\/msubsup>/","<munderover><mo>$1</mo>$2</munderover>",$message);

	$message=preg_replace("/<mo>&#(sin|arcsin|cos|arccos|tan|arctan|ln|exp|log|lim|min|max|inf|sup|pgcd|ppcm|gcd|lcm);<\/mo>/","<mi>$1</mi>",$message);

	$message=preg_replace("/<mo>&#([a-zA-Z]+);<\/mo>/","<mo>&$1;</mo>",$message);

//	$message=preg_replace("/<msub><mi>(lim|min|max|inf|sup)<\/mi>([^{}]*)<\/msub>/e","'<munder><mi>$1</mi>'.chercherbf('<msub>','</msub>',\"$2\").'</munder>'.substr(\"$2\",strlen(chercherbf('<msub>','</msub>',\"$2\"))+7,strlen(\"$2\")).'</msub>'",$message);

//	echo "...\n\r\n";

	$message=str_replace("<br />","",$message);

	

	

	return $message;

}

function editerlatex($message) {

	$message=stripslashes($message);

	$message=str_replace("<","&#lt;",$message);

	$message=str_replace(">","&#gt;",$message);



	$message=ereg_replace("(\n|\r){2}","\\1",$message);

	$message=preg_replace("/(\\\){1}(\{)/","<mo><accolade></mo>",$message);

	$message=preg_replace("/(\\\){1}(\})/","<mo></accolade></mo>",$message);

	$message=preg_replace("/(\')/","<quote/>",$message);

	$message = nl2br($message);

	$flag=0;

	while($flag<10) {

	$fflag=0;

	while((ereg("$",$message)) && ($fflag<5)) {

		$message=preg_replace("/(\\$){2}([^$]+)(\\$){2}/e","'<html><div align=\"center\"><math xmlns=\"http://www.w3.org/1998/Math/MathML\"><mstyle displaystyle=\"true\">'.editermaths(\"$2\").'</mstyle></math></div></html>'",$message);

		$message=preg_replace("/(\\$){1}([^$]+)(\\$){1}/e","'<html><math xmlns=\"http://www.w3.org/1998/Math/MathML\">'.editermaths(\"$2\").'</math></html>'",$message);

		$fflag++;

	}

	$message=preg_replace("/(\\\){1}(begin)\{(ltabular)\}(\[([0-9]*)?\])?(<br \/>)?/","\n<table border=\"$5\" width=\"100%\">\n<tr valign=\"center\"><td align=\"center\">",$message);

	$message=preg_replace("/(\\\){1}(end)\{(ltabular)\}/","</td></tr>\n</table>",$message);

	//$message=preg_replace("/(\\\){1}(begin)\{(ltabular)\}(\[([0-9]*)?\])?(<br \/>)?([^}{]*)(\\\){1}(end)\{(ltabular)\}/","\n<table border=\"$5\" width=\"100%\">\n<tr valign=\"center\"><td align=\"center\">\n$7\n</td></tr>\n</table>",$message);

	$message=preg_replace("/(\\\){1}(begin)\{(tabular)\}(\[([0-9]*)?\])?(<br \/>)?([^}{]*)(\\\){1}(end)\{(tabular)\}/","<table border=\"$5\"><tr valign=\"center\"><td align=\"center\">$7</td></tr>\n</table>",$message);

	$message=preg_replace("/((\\\){2})(<br \/>)?/","</td></tr>\n<tr valign=\"center\"><td align=\"center\">",$message);

	$message=preg_replace("/(&)([^#])/","</td><td align=\"center\">$2",$message);

	$message=str_replace("<tr valign=\"center\"><td align=\"center\">\n</td></tr>\n</table>","</table>",$message);

	$message=preg_replace("/(\\\){1}(begin|debut)\{(théorème|définition|exemple|exemples|proposition|propriété|propriétés|exercice|exercices|démonstration|preuve)( ?[0-9]*)\}([^}{]*)(\\\){1}(end|fin)\{(théorème|définition|exemple|exemples|proposition|propriété|propriétés|exercice|exercices|démonstration|preuve)\}/e","'<b>'.ucwords(\"$3\").\"$4.\".'</b><i>'.\"$5\".'</i>'",$message);

	$message=preg_replace("/(\\\){1}(color)\[([^}{]*)\]\{([^}{]*)\}/","<font color=\"$3\">$4</font>",$message);				

	$message=preg_replace("/(\\\){1}(left)\{([^}{]*)\}/","<div align=\"left\">$3</div>",$message);				

	$message=preg_replace("/(\\\){1}(right)\{([^}{]*)\}/","<div align=\"right\">$3</div>",$message);				

	$message=preg_replace("/(\\\){1}(center)\{([^}{]*)\}/","<div align=\"center\">$3</div>",$message);				

	$message=preg_replace("/(\\\){1}(bf)\{([^}{]*)\}/","<b>$3</b>",$message);				

	$message=preg_replace("/(\\\){1}(it)\{([^}{]*)\}/","<i>$3</i>",$message);				

	$message=preg_replace("/(\\\){1}(ul)\{([^}{]*)\}/","<u>$3</u>",$message);				

	$message=preg_replace("/(\\\){1}(chapter)\{([^}{]*)\}/","<h1>$3</h1>",$message);				

	$message=preg_replace("/(\\\){1}(section)\{([^}{]*)\}/","<h2>$3</h2>",$message);				

	$message=preg_replace("/(\\\){1}(subsection)\{([^}{]*)\}/","<h3>$3</h3>",$message);

	$message=preg_replace("/(\\\){1}(subsubsection)\{([^}{]*)\}/","<h4>$3</h4>",$message);

	$message=preg_replace("/(\\\){1}(end)\{(itemize)\}/","</li></ul>",$message);

	$message=preg_replace("/(\\\){1}(begin)\{(itemize)\}/","<ul>",$message);

	$message=preg_replace("/(\\\){1}(end)\{(enumerate)\}/","</li></ol>",$message);

	$message=preg_replace("/(\\\){1}(begin)\{(enumerate|enumerer)\}\[([1|a|A|i|I]?)\]/","<ol type=\"$4\">",$message);

	$message=str_replace("\\item","</li><li>",$message);

		$flag++;

	}



	$message=ereg_replace("(<ol type=\")(a|A|1|i|I)(\"><br />\n</li>)","<ol type=\"\\2\"><br />\n",$message);

	$message=str_replace("<ul><br />\n</li>","<ul><br />\n",$message);

	$message=str_replace("\\quad","&nbsp;",$message);

	$message=str_replace("\\qquad","&nbsp;&nbsp;",$message);

	$message=str_replace("&#","&",$message);

	$message=str_replace("<parenthese>","(",$message);

	$message=str_replace("</parenthese>",")",$message);

	$message=str_replace("<accolade>","{",$message);

	$message=str_replace("</accolade>","}",$message);

	$message=str_replace("<quote/>","'",$message);

	$message=preg_replace("/([éèëàçêûïâîôùÀÈÎÔÛÏÂ]{1})/e","htmlentities(\"$1\")",$message);

return $message;

}

?>