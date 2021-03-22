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
        $string = "";
        foreach ($this->vars as $key => $value) {
            $string .= "$key: $value;\n";
        }
        return $string;
    }

    public function __toString() {
        return $this->getString();
    }
}

/**
 * Génère les variables CSS d'une couleur pour l'espace privé
 *
 * @param string $couleur Couleur hex
 * @return string
 */
function spip_couleur_theme_generer_variables_css($couleur) : \Spip_Css_Vars_Collection {

    $vars = new \Spip_Css_Vars_Collection();

    $vars->add("--spip-color-theme--hsl", couleur_hex_to_hsl($couleur, "h, s, l"));
    $vars->add("--spip-color-theme--rgb", couleur_hex_to_rgb($couleur, "r, g, b"));

    $vars->add("--spip-color-theme--h", couleur_hex_to_hsl($couleur, "h"));
    $vars->add("--spip-color-theme--s", couleur_hex_to_hsl($couleur, "s"));
    $vars->add("--spip-color-theme--l", couleur_hex_to_hsl($couleur, "l"));
    $vars->add("--spip-color-theme--r", couleur_hex_to_rgb($couleur, "r")); // utile ?
    $vars->add("--spip-color-theme--g", couleur_hex_to_rgb($couleur, "g")); // utile ?
    $vars->add("--spip-color-theme--b", couleur_hex_to_rgb($couleur, "b")); // utile ?

    // un joli dégradé de presque blanc à presque noir…
    $vars->add("--spip-color-theme--100", '#' . couleur_eclaircir($couleur, .99));
    $vars->add("--spip-color-theme--98", '#' . couleur_eclaircir($couleur, .95));
    $vars->add("--spip-color-theme--95", '#' . couleur_eclaircir($couleur, .90));
    $vars->add("--spip-color-theme--90", '#' . couleur_eclaircir($couleur, .75));
    $vars->add("--spip-color-theme--80", '#' . couleur_eclaircir($couleur, .50));
    $vars->add("--spip-color-theme--70", '#' . couleur_eclaircir($couleur, .25));
    $vars->add("--spip-color-theme--60", $couleur);
    $vars->add("--spip-color-theme--50", '#' . couleur_foncer($couleur, .125));
    $vars->add("--spip-color-theme--40", '#' . couleur_foncer($couleur, .25));
    $vars->add("--spip-color-theme--30", '#' . couleur_foncer($couleur, .375));
    $vars->add("--spip-color-theme--20", '#' . couleur_foncer($couleur, .50));
    $vars->add("--spip-color-theme--10", '#' . couleur_foncer($couleur, .75));
    $vars->add("--spip-color-theme--00", '#' . couleur_foncer($couleur, .98));

    // nos déclinaisons (basées sur le dégradé précedent, où 60 est là couleur du thème)
    $vars->add("--spip-color-theme-white", "var(--spip-color-theme--100)");
    $vars->add("--spip-color-theme-lightest", "var(--spip-color-theme--95)");
    $vars->add("--spip-color-theme-lighter", "var(--spip-color-theme--90)");
    $vars->add("--spip-color-theme-light", "var(--spip-color-theme--80)");
    $vars->add("--spip-color-theme", "var(--spip-color-theme--60)");
    $vars->add("--spip-color-theme-dark", "var(--spip-color-theme--40)");
    $vars->add("--spip-color-theme-darker", "var(--spip-color-theme--20)");
    $vars->add("--spip-color-theme-darkest", "var(--spip-color-theme--10)");
    $vars->add("--spip-color-theme-black", "var(--spip-color-theme--00)");

    return $vars;
}