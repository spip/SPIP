<?php

declare(strict_types=1);

/**
 * Test unitaire de la fonction spip_xml_parse du fichier inc/xml.php
 */

namespace Spip\Test\Format\Xml;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SpipXmlParseTest extends TestCase
{
	public static function setUpBeforeClass(): void {
		find_in_path('inc/xml.php', '', true);
	}

	#[DataProvider('providerXmlSpipXmlParse')]
	public function testXmlSpipXmlParse($expected, ...$args): void {
		$actual = serialize(spip_xml_parse(...$args));
		$this->assertSame($expected, $actual);
	}

	public static function providerXmlSpipXmlParse(): array {
		$essais = [];
		$xml1 = '<' . <<<CODE_SAMPLE
?xml version="1.0" encoding="utf-8"?>
<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">
<url><loc>http://localhost/_core/spip/spip.php?breve25</loc>
<lastmod>2003-12-31</lastmod>
<priority>0.8</priority>
</url>
<url>
<loc>http://localhost/_core/spip/spip.php?breve32</loc>
<lastmod>2004-02-10</lastmod>
<priority>0.8</priority>
</url>
<url>
<loc>http://localhost/_core/spip/spip.php?breve64</loc>
<lastmod>2005-01-31</lastmod>
<priority>0.8</priority>
</url>
</urlset>
CODE_SAMPLE;
		$tree1 = <<<CODE_SAMPLE
a:1:{s:57:"urlset xmlns="http://www.google.com/schemas/sitemap/0.84"";a:1:{i:0;a:1:{s:3:"url";a:3:{i:0;a:3:{s:3:"loc";a:1:{i:0;s:44:"http://localhost/_core/spip/spip.php?breve25";}s:7:"lastmod";a:1:{i:0;s:10:"2003-12-31";}s:8:"priority";a:1:{i:0;s:3:"0.8";}}i:1;a:3:{s:3:"loc";a:1:{i:0;s:44:"http://localhost/_core/spip/spip.php?breve32";}s:7:"lastmod";a:1:{i:0;s:10:"2004-02-10";}s:8:"priority";a:1:{i:0;s:3:"0.8";}}i:2;a:3:{s:3:"loc";a:1:{i:0;s:44:"http://localhost/_core/spip/spip.php?breve64";}s:7:"lastmod";a:1:{i:0;s:10:"2005-01-31";}s:8:"priority";a:1:{i:0;s:3:"0.8";}}}}}}
CODE_SAMPLE;
		$xml2 = '<' . <<<CODE_SAMPLE
?xml version="1.0" encoding="UTF-8"?>
<opml version="1.0">
  <head>
    <title>arbo_riec</title>
    <expansionState>0,9,14,24,28,30,31,35,41,43,44,46,48,55,58,61,66,71,74,77,78,82,87,89,90,92,96,98,102,110,112</expansionState>
  </head>
  <body>
    <outline text="Citoyenneté">
      <outline text="Page d'accueil" Contenu="Articles et brèves d'actualité + Vos questions en direct"/>
      <outline text="Le maire et les adjoints" Contenu="Nom, fonction, contact, horaires de permanence et photo de chaque élu" Fonctionnalité="Trombinoscope"/>
      <outline text="Les conseillers municipaux" Contenu="idem" Fonctionnalité="Trombinoscope"/>
      <outline text="Les commissions" Contenu="Présentation générale des commissions">
        <outline text="Une fiche par commission" Contenu="Domaine de compétence et membres"/>
      </outline>
      <outline text="Les conseils municipaux" Contenu="Présentation générale, téléchargement du dernier compte-rendu et agenda des prochains conseils" Fonctionnalité="Agenda">
        <outline text="Comptes-rendus" Contenu="Les comptes-rendus de conseils à télécharger" Fonctionnalité="Publications et archivage auto">
          <outline text="Un article par compte-rendu" Contenu="Texte ou doc à télécharger"/>
        </outline>
      </outline>
      <outline text="Les réunions de quartier" Contenu="Présentation et agenda des réunions" Fonctionnalité="Agenda"/>
      <outline text="L’Atelier municipal sur l’environnement" Contenu="Texte + photos"/>
      <outline text="L’intercommunalité" Contenu="Texte de présentation + photos">
        <outline text="Cocopaq" Contenu="Texte + photos"/>
        <outline text="Syndicat de Voirie de Rosporden" Contenu="Texte + photos"/>
        <outline text="SIVU de Riec sur Bélon" Contenu="Texte + photos"/>
        <outline text="Sicom" Contenu="Texte + photos"/>
        <outline text="Syndicat d’eau et d’électricification de Riec sur Belon" Contenu="Texte + photos"/>
      </outline>
    </outline>
    <outline text="Economie">
      <outline text="Page d'accueil" Contenu="Actualité + lien vers les marchés publics"/>
      <outline text="Marchés publics" Contenu="Texte de présentation + contact">
        <outline text="Avis d'attribution" Contenu="Texte accueil et affichage des derniers avis" Fonctionnalité="Publications + archivage">
          <outline text="Un article par avis" Contenu="Texte ou doc à télécharger"/>
        </outline>
        <outline text="Avis de publicité" Contenu="Texte + affichage des avis en cours" Fonctionnalité="Marchés public">
          <outline text="Un article par avis" Contenu="Texte + docs à télécharger" Fonctionnalité="Un formulaire invite le visiteur à fournir son adresse email. Un email lui est alors automatiquement expédié qui contient un lien. En cliquant sur ce lien, le visiteur revient sur la page mais cette fois il a la possibilité de télécharger les pièces jointes à l'avis. Les mails sont ainsi collectés et ceux qui ont téléchargé les pièces peuvent ainsi être contactés en cas de changement."/>
        </outline>
      </outline>
      <outline text="Les atouts de la ville">
        <outline text="Production ostréicole" Contenu="Texte + photos"/>
        <outline text="Situation géographique" Contenu="Même contenu que dans La Ville ?" Fonctionnalité="modèle de duplication d'article"/>
      </outline>
      <outline text="S’implanter" Contenu="Texte + photos"/>
      <outline text="Le GAER" Contenu="Texte + photos"/>
      <outline text="Les commerces" Contenu="Présentation + formulaire d'inscription à l'annuaire" Fonctionnalité="Fonctionnalité annuaire"/>
      <outline text="Les entreprises" Contenu="Présentation + formulaire d'inscription à l'annuaire" Fonctionnalité="Fonctionnalité annuaire"/>
      <outline text="Les ZA et ZI" Contenu="Présentation + carte des ZA-ZI" Fonctionnalité="Carte interactive">
        <outline text="Une page par zone" Contenu="Fiche de présentation + photo + plan accès"/>
      </outline>
      <outline text="Le marché" Contenu="Texte + photos"/>
    </outline>
    <outline text="Tourisme version anglaise"/>
  </body>
</opml>
CODE_SAMPLE;
		$tree2 = <<<CODE_SAMPLE
a:1:{s:18:"opml version="1.0"";a:1:{i:0;a:2:{s:4:"head";a:1:{i:0;a:2:{s:5:"title";a:1:{i:0;s:9:"arbo_riec";}s:14:"expansionState";a:1:{i:0;s:93:"0,9,14,24,28,30,31,35,41,43,44,46,48,55,58,61,66,71,74,77,78,82,87,89,90,92,96,98,102,110,112";}}}s:4:"body";a:1:{i:0;a:3:{s:27:"outline text="Citoyenneté"";a:1:{i:0;a:8:{s:98:"outline text="Page d'accueil" Contenu="Articles et brèves d'actualité + Vos questions en direct"";a:1:{i:0;s:0:"";}s:152:"outline text="Le maire et les adjoints" Contenu="Nom, fonction, contact, horaires de permanence et photo de chaque élu" Fonctionnalité="Trombinoscope"";a:1:{i:0;s:0:"";}s:88:"outline text="Les conseillers municipaux" Contenu="idem" Fonctionnalité="Trombinoscope"";a:1:{i:0;s:0:"";}s:81:"outline text="Les commissions" Contenu="Présentation générale des commissions"";a:1:{i:0;a:1:{s:83:"outline text="Une fiche par commission" Contenu="Domaine de compétence et membres"";a:1:{i:0;s:0:"";}}}s:173:"outline text="Les conseils municipaux" Contenu="Présentation générale, téléchargement du dernier compte-rendu et agenda des prochains conseils" Fonctionnalité="Agenda"";a:1:{i:0;a:1:{s:136:"outline text="Comptes-rendus" Contenu="Les comptes-rendus de conseils à télécharger" Fonctionnalité="Publications et archivage auto"";a:1:{i:0;a:1:{s:82:"outline text="Un article par compte-rendu" Contenu="Texte ou doc à télécharger"";a:1:{i:0;s:0:"";}}}}}s:113:"outline text="Les réunions de quartier" Contenu="Présentation et agenda des réunions" Fonctionnalité="Agenda"";a:1:{i:0;s:0:"";}s:83:"outline text="L’Atelier municipal sur l’environnement" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:78:"outline text="L’intercommunalité" Contenu="Texte de présentation + photos"";a:1:{i:0;a:5:{s:47:"outline text="Cocopaq" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:71:"outline text="Syndicat de Voirie de Rosporden" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:63:"outline text="SIVU de Riec sur Bélon" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:45:"outline text="Sicom" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:100:"outline text="Syndicat d’eau et d’électricification de Riec sur Belon" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}}}}}s:23:"outline text="Economie"";a:1:{i:0;a:9:{s:83:"outline text="Page d'accueil" Contenu="Actualité + lien vers les marchés publics"";a:1:{i:0;s:0:"";}s:74:"outline text="Marchés publics" Contenu="Texte de présentation + contact"";a:1:{i:0;a:2:{s:131:"outline text="Avis d'attribution" Contenu="Texte accueil et affichage des derniers avis" Fonctionnalité="Publications + archivage"";a:1:{i:0;a:1:{s:74:"outline text="Un article par avis" Contenu="Texte ou doc à télécharger"";a:1:{i:0;s:0:"";}}}s:113:"outline text="Avis de publicité" Contenu="Texte + affichage des avis en cours" Fonctionnalité="Marchés public"";a:1:{i:0;a:1:{s:495:"outline text="Un article par avis" Contenu="Texte + docs à télécharger" Fonctionnalité="Un formulaire invite le visiteur à fournir son adresse email. Un email lui est alors automatiquement expédié qui contient un lien. En cliquant sur ce lien, le visiteur revient sur la page mais cette fois il a la possibilité de télécharger les pièces jointes à l'avis. Les mails sont ainsi collectés et ceux qui ont téléchargé les pièces peuvent ainsi être contactés en cas de changement."";a:1:{i:0;s:0:"";}}}}}s:37:"outline text="Les atouts de la ville"";a:1:{i:0;a:2:{s:62:"outline text="Production ostréicole" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:133:"outline text="Situation géographique" Contenu="Même contenu que dans La Ville ?" Fonctionnalité="modèle de duplication d'article"";a:1:{i:0;s:0:"";}}}s:53:"outline text="S’implanter" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:47:"outline text="Le GAER" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}s:136:"outline text="Les commerces" Contenu="Présentation + formulaire d'inscription à l'annuaire" Fonctionnalité="Fonctionnalité annuaire"";a:1:{i:0;s:0:"";}s:138:"outline text="Les entreprises" Contenu="Présentation + formulaire d'inscription à l'annuaire" Fonctionnalité="Fonctionnalité annuaire"";a:1:{i:0;s:0:"";}s:105:"outline text="Les ZA et ZI" Contenu="Présentation + carte des ZA-ZI" Fonctionnalité="Carte interactive"";a:1:{i:0;a:1:{s:87:"outline text="Une page par zone" Contenu="Fiche de présentation + photo + plan accès"";a:1:{i:0;s:0:"";}}}s:50:"outline text="Le marché" Contenu="Texte + photos"";a:1:{i:0;s:0:"";}}}s:40:"outline text="Tourisme version anglaise"";a:1:{i:0;s:0:"";}}}}}}
CODE_SAMPLE;
		$essais['sitemap'] = [$tree1, $xml1];
		$essais['opml'] = [$tree2, $xml2];
		return $essais;
	}
}
