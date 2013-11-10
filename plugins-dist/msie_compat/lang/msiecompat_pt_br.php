<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de http://www.spip.net/trad-lang/
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// C
	'choix_explication' => '<p>Esta configuração permite-lhe melhorar a compatibilidade do site público com o navegador Internet Explorer.</p>
  <lu>
    <li><a href=\'http://jquery.khurshid.com/ifixpng.php\'>iFixPng</a> (<b>padrão</b>) restabelece a semi-transparência das imagens em formato PNG, em MSIE 5 e 6.</li>
   <li><a href=\'http://code.google.com/p/ie7-js/\'>IE7.js</a> corrige as imagens PNG e inclui seletores CSS2 para MSIE 5 et 6 (<a href=\'http://ie7-js.googlecode.com/svn/test/index.html\'>você pode consultar a lista de seletores compatíveis introduzidos pelo IE7.js e IE8.js</a>).</li>
   <li>IE8.js complementa o IE7.js enriquecendo os comportamentos do CSS do MSIE 5 a 7. </li>
   <li>IE7-squish corrige três bugs do MSIE 6 (notadamente a dupla margem dos elemento flutuantes), mas efeitos indesejáveis podem aparecer (o webmaster deverá verificar a compatibilidade).</li>
   </ul>', # MODIF
	'choix_ie7' => '<a href=\'http://code.google.com/p/ie7-js/\'>IE7.js</a> corrige les images PNG et ajoute des sélecteurs CSS2 pour MSIE 5 et 6 (<a href=\'http://ie7-js.googlecode.com/svn/test/index.html\'>vous pouvez consulter la liste des sélecteurs compatibles introduits par IE7.js et IE8.js</a>).', # NEW
	'choix_ie7squish' => 'IE7-squish corrige trois bugs de MSIE 6 (notamment la double marge des éléments flottants), mais des effets indésirables peuvent apparaître (le webmestre doit vérifier la compatibilité).', # NEW
	'choix_ie8' => 'IE8.js complète IE7.js en enrichissant les comportements des CSS de MSIE 5 à 7.', # NEW
	'choix_ifixpng' => 'Choix par défaut, <a href=\'http://jquery.khurshid.com/ifixpng.php\'>iFixPng</a> rétablit la semi-transparence les images au format PNG sous MSIE 5 et 6.', # NEW
	'choix_non' => 'Não ativar: não incluir nada nos meus templates',
	'choix_titre' => 'Compatibilidade com Microsoft Internet Explorer'
);

?>
