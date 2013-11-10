<?php
/*
 * Plugin Porte Plume pour SPIP 2
 * Licence GPL
 * Auteur Matthieu Marcillaud
 */
require_once('lanceur_spip.php');

class Test_barre_outil_markitup extends SpipTest{
	
	var $baseParamsBarre = array();
	var $baseParamsBarreEtendue = array();
	
	function Test_barre_outil_markitup() {

		$this->SpipTest("Test de la classe Barre_outils");

		// instancier une barre d'outil
		include_spip('porte_plume_fonctions');
		$this->baseParamsBarre = array(
			'nameSpace'    => 'spip',
			'markupSet'    => array(
				// H1 - {{{
				array(
					"id"        => 'header1',
					"name"      => _T('barreoutils:barre_intertitre'),
					"key"       => "H",
					"className" => "outil_header1",
					"openWith" => "{{{",
					"closeWith" => "}}}",
					"display"   => true,
				)));
		$p = $this->baseParamsBarre;
		$p['markupSet'][1] = array(
			"id"        => 'couleurs',
			"name"      => _T('barreoutils:barre_couleur'),
			"key"       => "C",
			"className" => "outil_couleur",
			"openWith"  => '[color=[![Color]!]]',
			"closeWith" => '[/color]',
			"display"   => true,
			"dropMenu"  => array(
				array(
					"id"  => "couleur_jaune",
					"name"  => 'Yellow',
					"openWith" => '[color=yellow]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display"   => true,
				),
				array(
					"id"  => "couleur_orange",
					"name"  => 'Orange',
					"openWith" => '[color=orange]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display"   => true,
				),
				array(
					"id"  => "couleur_rouge",
					"name"  => 'Red',
					"openWith" => '[color=red]',
					"closeWith" => '[/color]',
					"className" => "outil_couleur",
					"display"   => true,
				),
			),	
		);
		$this->baseParamsBarreEtendue = $p;
	}
	
	// avant chaque appel de fonction test
	function setUp() {

    }
    // apres chaque appel de fonction test
    function tearDown() {
        
    }

	
	function testInitialisationBarre(){
		// parametres inseres a leur bonne place
		$b = new Barre_outils($this->baseParamsBarre);
		$this->assertEqual('spip', $b->nameSpace);
		$this->assertEqual('header1', $b->markupSet[0]['id']);
		$this->assertEqual(7, count($b->markupSet[0]));
	}
	
	function testInitialisationBarreEtendue(){	
		// parametres inseres a leur bonne place,
		// meme quand il y a des sous-menu d'icones
		$b = new Barre_outils($this->baseParamsBarreEtendue);
		$this->assertEqual('spip', $b->nameSpace);
		$this->assertEqual('header1', $b->markupSet[0]['id']);
		$this->assertEqual(7, count($b->markupSet[0]));
		$this->assertEqual('couleurs', $b->markupSet[1]['id']);	
		$this->assertEqual(3, count($b->markupSet[1]['dropMenu']));	
	}
	
	function testOptionsIncorrectesNonIncluses(){
		$p = $this->baseParamsBarre;
		$p['fausseVariable'] = "je ne dois pas m'installer";
		$p['markupSet'][0]['fauxParam'] = "je ne dois pas m'installer";
		$b = new Barre_outils($p);
		$this->assertEqual('spip',$b->nameSpace);
		$this->assertNull($b->fausseVariable);
		$this->assertNull($b->markupSet[0]['fauxParam']);
		$this->assertEqual(7, count($b->markupSet[0]));
	}
	
	function testRecuperationDeParametreAvecGet(){
		// trouver des id de premier niveau
		$p = $this->baseParamsBarre;
		$b = new Barre_outils($p);
		$this->assertEqual($b->get('header1'), $p['markupSet'][0]);
		
		// trouver des id de second niveau
		$p = $this->baseParamsBarreEtendue;
		$b = new Barre_outils($p);
		$this->assertEqual($b->get('header1'), $p['markupSet'][0]);
		$this->assertEqual($b->get('couleurs'), $p['markupSet'][1]);
		$this->assertEqual($b->get('couleur_jaune'), $p['markupSet'][1]['dropMenu'][0]);		
		$this->assertEqual($b->get('couleur_orange'), $p['markupSet'][1]['dropMenu'][1]);		
		$this->assertEqual($b->get('couleur_rouge'), $p['markupSet'][1]['dropMenu'][2]);
		
		// ne pas trouver d'id inconnu
		$this->assertFalse($b->get('je_nexiste_pas'));
	}
	
	function testModificationDeParametresAvecSet(){
		$p = $this->baseParamsBarre;
		$b = new Barre_outils($p);
		$p['markupSet'][0]['name'] = 'New';
		$r = $p['markupSet'][0];
		$x = $b->set('header1', array("name"=>"New"));
		
		$this->assertEqual($r, $x); // set retourne la chaine modifiee complete
		$this->assertEqual($r, $b->get('header1'));
		
		// on ne peut ajouter de mauvais parametres
		$x = $b->set('header1', array("Je Suis Pas Bon"=>"No no no"));
		$this->assertEqual($r, $x); // set retourne la chaine modifiee complete
		$this->assertEqual($r, $b->get('header1'));		
	}
	
	function testAjoutDeParametresApres(){
		$b = new Barre_outils($this->baseParamsBarre);
		$p = $this->baseParamsBarreEtendue;
		
		// ajoutons la couleur apres
		$b->ajouterApres('header1',$p['markupSet'][1]);
		$this->assertEqual(2, count($b->markupSet)); // 2 boutons de premier niveau maintenant
		$this->assertEqual($b->get('couleurs'), $p['markupSet'][1]); // get renvoie bien le bon ajout
		$this->assertEqual($b->markupSet[1], $p['markupSet'][1]); // et l'ajout est au bon endroit
		
			// ajoutons une couleur dans l'ajout
			$coul = $p['markupSet'][1]['dropMenu'][0];
			$coul['id'] = 'couleur_violette';
			$b->ajouterApres('couleur_orange',$coul);
			$this->assertEqual(4, count($b->markupSet[1]['dropMenu'])); // sous boutons
			$this->assertEqual($b->get('couleur_violette'), $coul);
			$this->assertEqual($b->markupSet[1]['dropMenu'][2], $coul); // insertion au bon endroit
			
		// ajoutons un header2 encore apres
		$p['markupSet'][0]['id'] = 'header2';
		$b->ajouterApres('couleurs',$p['markupSet'][0]);
		$this->assertEqual(3, count($b->markupSet));
		$this->assertEqual($b->get('header2'), $p['markupSet'][0]);
		$this->assertEqual($b->markupSet[2], $p['markupSet'][0]);
	}
	
	function testAjoutDeParametresAvant(){
		$b = new Barre_outils($this->baseParamsBarre);
		$p = $this->baseParamsBarreEtendue;
		
		// ajoutons la couleur apres
		$b->ajouterAvant('header1',$p['markupSet'][1]);
		$this->assertEqual(2, count($b->markupSet)); // 2 boutons de premier niveau maintenant
		$this->assertEqual($b->get('couleurs'), $p['markupSet'][1]); // get renvoie bien le bon ajout
		$this->assertEqual($b->markupSet[0], $p['markupSet'][1]); // et l'ajout est au bon endroit
		
			// ajoutons une couleur dans l'ajout
			$coul = $p['markupSet'][1]['dropMenu'][0];
			$coul['id'] = 'couleur_violette';
			$b->ajouterAvant('couleur_orange',$coul);
			$this->assertEqual(4, count($b->markupSet[0]['dropMenu'])); // sous boutons
			$this->assertEqual($b->get('couleur_violette'), $coul);
			$this->assertEqual($b->markupSet[0]['dropMenu'][1], $coul); // insertion au bon endroit
					
		// ajoutons un header2 avant le 1
		$p['markupSet'][0]['id'] = 'header2';
		$b->ajouterAvant('header1',$p['markupSet'][0]);
		$this->assertEqual(3, count($b->markupSet));
		$this->assertEqual($b->get('header2'), $p['markupSet'][0]);
		$this->assertEqual($b->markupSet[1], $p['markupSet'][0]);
	}
	
	function testAfficherEtCacher(){
		$b = new Barre_outils($this->baseParamsBarre);
		$b->cacher('header1');
		$this->assertFalse($b->markupSet[0]['display']);
		$b->afficher('header1');
		$this->assertTrue($b->markupSet[0]['display']);
	}
	
	function testAfficherEtCacherTout(){
		$b = new Barre_outils($this->baseParamsBarreEtendue);
		$b->cacherTout();
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);
		
		$b->afficherTout();
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);
	}
	
	function testAfficherEtCacherPlusieursBoutons(){
		$b = new Barre_outils($this->baseParamsBarreEtendue);
		$b->cacher(array('header1','couleur_jaune'));
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][1]['display']);
		
		$b->cacherTout();
		$b->afficher(array('header1','couleur_jaune'));
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][1]['display']);
	}
	
	function testSetAvecIdVideNeDoitRienModifier(){
		$b = new Barre_outils($this->baseParamsBarreEtendue);
		$b->set(array(),array('display'=>false));	
		$this->assertTrue($b->markupSet[0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][0]['display']);		
	}
	
	function testSetAvecIdArrayDoitModifTousLesIds(){
		$b = new Barre_outils($this->baseParamsBarreEtendue);
		$b->set(array('header1','couleur_jaune'),array('display'=>false));	
		$this->assertFalse($b->markupSet[0]['display']);
		$this->assertFalse($b->markupSet[1]['dropMenu'][0]['display']);
		$this->assertTrue($b->markupSet[1]['dropMenu'][1]['display']);			
	}	
	
	function testCreerJson(){
		$b = new Barre_outils($this->baseParamsBarre);
		$b->ajouterApres('header1', array(
				"id"        => 'Caracteres decodes',
				"name"      => "&eacute;trange",
				"className" => "outil_fr",
				"openWith"  => "[fr]",
				"display"   => true,
		));
		$json = $b->creer_json();
		$this->assertPattern(',barre_outils_spip = {,',$json);	
		$this->assertPattern(',\[{"name":",',$json);
		$this->assertNoPattern(',eacute;,',$json);
	}
	
	function testBoutonsDUneLangue(){
		$b = new Barre_outils($this->baseParamsBarre);
		$ico2 = $ico1 = array(
				"id"        => 'ico_fr1',
				"name"      => "test apparaissant si langue est le francais",
				"className" => "outil_fr",
				"openWith"  => "[fr]",
				"lang"      => array("fr"),
				"display"   => true,
		);
		$ico2['id'] = 'ico_fr2';
		$ico2['lang'] = array("fr","en","es");

		$b->ajouterApres('header1', $ico1);
		$b->ajouterApres('ico_fr1', $ico2);
		$this->assertEqual($ico1, $b->get('ico_fr1'));
		$this->assertEqual($ico2, $b->get('ico_fr2'));
		
		// verifier que ces nouveaux array()
		// ne posent pas de problemes dans les recursions
		$b->cacherTout();
		$this->assertFalse($b->markupSet[1]['display']);
		$b->afficher('ico_fr1');
		$this->assertTrue($b->markupSet[1]['display']);
		$b->cacherTout();
		$b->afficher(array('ico_fr1','ico_fr2'));
		$this->assertTrue($b->markupSet[1]['display']);	
		
		// la langue est bien transmise au json
		$json = $b->creer_json();
		$this->assertPattern(',"lang":\[,', $json);
	}
	
	
	function testFonctionsJavacriptDansParametreNeDoitPasEtreEntreguillemetsDansJson(){
		$b = new Barre_outils($this->baseParamsBarre);
		$clean = array(
				"id"          => 'clean',
				"name"        => _T('barreoutils:barre_clean'), 
				"className"   => "outil_clean",
				// function doit etre echappe
				"replaceWith" => 'function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") }',
				"display"     => true,
			);
		$b->ajouterApres('header1', $clean);
		$json = $b->creer_json();
		// pas de :"function(... ..."
		$this->assertPattern('/:function\(/',$json);
	}
	
	function testParametreFunctionsDansJson(){
		$b = new Barre_outils($this->baseParamsBarre);
		$b->functions = "function dido(){}";
		$json = $b->creer_json();
		// function n'est plus dans la barre
		$this->assertFalse($b->functions);
		// mais uniquement en fin du fichier json
		$this->assertPattern('/function dido\(/', $json);		
	}
	
	function testAjouterFonctions(){
		$b = new Barre_outils($this->baseParamsBarre);
		$b->ajouterFonction("function dido(){}");
		$this->assertPattern('/function dido\(/', $b->functions);		
	}
	
	/*	
	function squeletteTest(){
		$sq = new SqueletteTest("SimpleTest");
		$sq->addInsertHead();
		$sq->addToBody("
			<div class='formulaire_spip'>
			<form>
			<textarea name='texte' class='texte'></textarea>
			</form>
			</div>
		");	
		return $sq;	
	}
	
	function testPresenceBarreOutilPublique(){
		include_spip('simpletest/browser');
		include_spip('simpletest/web_tester');
		
		$sq = $this->squeletteTest();

		$browser = &new SimpleBrowser();
		$browser->get($f=$this->urlTestCode($sq->code()));
		$browser->setField('texte', 'me');
		$this->dump($browser->getField('texte'));
		$this->dump($browser->getContent());
		#$this->dump($c);
		#$this->assertPattern('/jquery\.markitup_pour_spip\.js/', $c);
	}*/
}


?>
