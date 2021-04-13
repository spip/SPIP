<?php

/**
 * Collection de variables CSS
 * @internal
 */
class Spip_Css_Vars_Collection {
	private $vars = [];

	public function add(string $var, string $value) {
		$this->vars[$var] = $value;
	}

	public function getString() : string {
		$string = '';
		foreach ($this->vars as $key => $value) {
			$string .= "$key: $value;\n";
		}
		return $string;
	}

	public function __toString() : string {
		return $this->getString();
	}
}

/**
 * Génère les variables CSS relatif à la typo et langue pour l'espace privé
 *
 * @param Pile $pile Pile
 */
function spip_generer_variables_css_typo(array $Pile) : \Spip_Css_Vars_Collection {
		$vars = new \Spip_Css_Vars_Collection();

	$vars->add('--spip-css-dir', $Pile[0]['dir']);
	$vars->add('--spip-css-left', $Pile[0]['left']);
	$vars->add('--spip-css-right', $Pile[0]['right']);

	$vars->add('--spip-css-font-size', $Pile[0]['font-size']);
	$vars->add('--spip-css-line-height',  $Pile[0]['line-height']);
	$vars->add('--spip-css-margin-bottom', $Pile[0]['margin-bottom']);

	$vars->add('--spip-css-text-indent', $Pile[0]['text-indent']);
	$vars->add('--spip-css-font-family', $Pile[0]['font-family']);
	$vars->add('--spip-css-background-color', $Pile[0]['background-color']);
	$vars->add('--spip-css-color', $Pile[0]['color']);

	$vars->add('--spip-css-border-radius-mini', '0.2em');
	$vars->add('--spip-css-border-radius', '0.33em');
	$vars->add('--spip-css-border-radius-large', '0.66em');

	$vars->add('--spip-css-box-shadow', '0px 2px 1px -1px hsla(0,0%,0%,0.2), 0px 1px 1px 0px hsla(0,0%,0%,0.1), 0px 1px 3px 0px hsla(0,0%,0%,0.12)');

	return $vars;
}

/**
 * Génère les variables CSS d'un thème de couleur pour l'espace privé
 *
 * @param string $couleur Couleur hex
 */
function spip_generer_variables_css_couleurs_theme(string $couleur) : \Spip_Css_Vars_Collection {
	$vars = new \Spip_Css_Vars_Collection();

	#$vars->add('--spip-color-theme--hsl', couleur_hex_to_hsl($couleur, 'h, s, l')); // redéfini ensuite
	$vars->add('--spip-color-theme--h', couleur_hex_to_hsl($couleur, 'h'));
	$vars->add('--spip-color-theme--s', couleur_hex_to_hsl($couleur, 's'));
	$vars->add('--spip-color-theme--l', couleur_hex_to_hsl($couleur, 'l'));

	// un joli dégradé coloré de presque blanc à presque noir…
	$vars->add('--spip-color-theme--100', couleur_hex_to_hsl(couleur_eclaircir($couleur, .99), 'h, s, l'));
	$vars->add('--spip-color-theme--98', couleur_hex_to_hsl(couleur_eclaircir($couleur, .95), 'h, s, l'));
	$vars->add('--spip-color-theme--95', couleur_hex_to_hsl(couleur_eclaircir($couleur, .90), 'h, s, l'));
	$vars->add('--spip-color-theme--90', couleur_hex_to_hsl(couleur_eclaircir($couleur, .75), 'h, s, l'));
	$vars->add('--spip-color-theme--80', couleur_hex_to_hsl(couleur_eclaircir($couleur, .50), 'h, s, l'));
	$vars->add('--spip-color-theme--70', couleur_hex_to_hsl(couleur_eclaircir($couleur, .25), 'h, s, l'));
	$vars->add('--spip-color-theme--60', couleur_hex_to_hsl($couleur, 'h, s, l'));
	$vars->add('--spip-color-theme--50', couleur_hex_to_hsl(couleur_foncer($couleur, .125), 'h, s, l'));
	$vars->add('--spip-color-theme--40', couleur_hex_to_hsl(couleur_foncer($couleur, .25), 'h, s, l'));
	$vars->add('--spip-color-theme--30', couleur_hex_to_hsl(couleur_foncer($couleur, .375), 'h, s, l'));
	$vars->add('--spip-color-theme--20', couleur_hex_to_hsl(couleur_foncer($couleur, .50), 'h, s, l'));
	$vars->add('--spip-color-theme--10', couleur_hex_to_hsl(couleur_foncer($couleur, .75), 'h, s, l'));
	$vars->add('--spip-color-theme--00', couleur_hex_to_hsl(couleur_foncer($couleur, .98), 'h, s, l'));

	return $vars;
}

/**
 * Génère les variables CSS de couleurs, dont celles dépendantes des couleurs du thème actif.
 */
function spip_generer_variables_css_couleurs() : \Spip_Css_Vars_Collection {
	$vars = new \Spip_Css_Vars_Collection();

	// nos déclinaisons de couleur (basées sur le dégradé précedent, où 60 est là couleur du thème)
	$vars->add('--spip-color-theme-white--hsl', 'var(--spip-color-theme--100)');
	$vars->add('--spip-color-theme-lightest--hsl', 'var(--spip-color-theme--95)');
	$vars->add('--spip-color-theme-lighter--hsl', 'var(--spip-color-theme--90)');
	$vars->add('--spip-color-theme-light--hsl', 'var(--spip-color-theme--80)');
	$vars->add('--spip-color-theme--hsl', 'var(--spip-color-theme--60)');
	$vars->add('--spip-color-theme-dark--hsl', 'var(--spip-color-theme--40)');
	$vars->add('--spip-color-theme-darker--hsl', 'var(--spip-color-theme--20)');
	$vars->add('--spip-color-theme-darkest--hsl', 'var(--spip-color-theme--10)');
	$vars->add('--spip-color-theme-black--hsl', 'var(--spip-color-theme--00)');

	$vars->add('--spip-color-theme-white', 'hsl(var(--spip-color-theme-white--hsl))');
	$vars->add('--spip-color-theme-lightest', 'hsl(var(--spip-color-theme-lightest--hsl))');
	$vars->add('--spip-color-theme-lighter', 'hsl(var(--spip-color-theme-lighter--hsl))');
	$vars->add('--spip-color-theme-light', 'hsl(var(--spip-color-theme-light--hsl))');
	$vars->add('--spip-color-theme', 'hsl(var(--spip-color-theme--hsl))');
	$vars->add('--spip-color-theme-dark', 'hsl(var(--spip-color-theme-dark--hsl))');
	$vars->add('--spip-color-theme-darker', 'hsl(var(--spip-color-theme-darker--hsl))');
	$vars->add('--spip-color-theme-darkest', 'hsl(var(--spip-color-theme-darkest--hsl))');
	$vars->add('--spip-color-theme-black', 'hsl(var(--spip-color-theme-black--hsl))');

	// déclinaisons de gris (luminosité calquée sur le dégradé de couleur)
	$vars->add('--spip-color-white--hsl', '0, 0%, 100%');
	$vars->add('--spip-color-gray-lightest--hsl', '0, 0%, 96%');
	$vars->add('--spip-color-gray-lighter--hsl', '0, 0%, 90%');
	$vars->add('--spip-color-gray-light--hsl', '0, 0%, 80%');
	$vars->add('--spip-color-gray--hsl', '0, 0%, 60%');
	$vars->add('--spip-color-gray-dark--hsl', '0, 0%, 40%');
	$vars->add('--spip-color-gray-darker--hsl', '0, 0%, 20%');
	$vars->add('--spip-color-gray-darkest--hsl', '0, 0%, 10%');
	$vars->add('--spip-color-black--hsl', '0, 0%, 0%');

	$vars->add('--spip-color-white', 'hsl(var(--spip-color-white--hsl))');
	$vars->add('--spip-color-gray-lightest', 'hsl(var(--spip-color-gray-lightest--hsl))');
	$vars->add('--spip-color-gray-lighter', 'hsl(var(--spip-color-gray-lighter--hsl))');
	$vars->add('--spip-color-gray-light', 'hsl(var(--spip-color-gray-light--hsl))');
	$vars->add('--spip-color-gray', 'hsl(var(--spip-color-gray--hsl))');
	$vars->add('--spip-color-gray-dark', 'hsl(var(--spip-color-gray-dark--hsl))');
	$vars->add('--spip-color-gray-darker', 'hsl(var(--spip-color-gray-darker--hsl))');
	$vars->add('--spip-color-gray-darkest', 'hsl(var(--spip-color-gray-darkest--hsl))');
	$vars->add('--spip-color-black', 'hsl(var(--spip-color-black--hsl))');

	return $vars;
}
