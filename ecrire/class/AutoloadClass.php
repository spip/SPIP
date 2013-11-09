<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2012                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

if (!defined('_ECRIRE_INC_VERSION')) return;

Class Autoload
{
    /**
	 *
     */
    public static function load($name)
    {
        $map = self::loadCoreConfig();
        $lname = strtolower($name);
	
        if (isset($map[$lname])) {
            require $map[$lname];
		}
        if (!class_exists($name,false)) {
			trigger_error(
				'Class ' . $name . ' not found in file ' . __FILE__ . 'at line ' . __LINE__,
				E_USER_ERROR
			);
			return false;
        }
        return true;
    }
	
    protected static function loadCoreConfig()
    {
        return array(
            'texte' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'inclure' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'boucle' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'critere' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'champ' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'idiome' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'polyglotte' => _DIR_RESTREINT_ABS . '/class/ASTClass.php',
            'bouton' => _DIR_RESTREINT_ABS . '/class/BoutonClass.php',
            'dtc' => _DIR_RESTREINT_ABS . '/class/DTCClass.php',
            'indenterxml' => _DIR_RESTREINT_ABS . '/class/IndenterXmlClass.php',
            'iterateurcondition' => _DIR_RESTREINT_ABS . '/class/IterateurConditionClass.php',
            'iterateurdata' => _DIR_RESTREINT_ABS . '/class/IterateurDataClass.php',
            'iterfactory' => _DIR_RESTREINT_ABS . '/class/IterateurPatronsClass.php',
            'iterdecorateur' => _DIR_RESTREINT_ABS . '/class/IterateurPatronsClass.php',
            'pclzip' => _DIR_RESTREINT_ABS . '/class/PclzipClass.php',
            'phpthrumb' => _DIR_RESTREINT_ABS . '/class/PhpThrumbClass.php',
            'sql' => _DIR_RESTREINT_ABS . '/class/SqlClass.php',
            'sqlite' => _DIR_RESTREINT_ABS . '/class/SqliteClass.php',
            'valideurxml' => _DIR_RESTREINT_ABS . '/class/ValideurXmlClass.php',
		);
	}
}