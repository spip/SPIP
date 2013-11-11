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
        if (!class_exists($name,false) AND !interface_exists($name,false)) {
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
            'texte' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'inclure' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'boucle' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'critere' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'champ' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'idiome' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'polyglotte' => _ROOT_RESTREINT . 'class/ASTClass.php',
            'bouton' => _ROOT_RESTREINT . 'class/BoutonClass.php',
            'dtc' => _ROOT_RESTREINT . 'class/DTCClass.php',
            'indenterxml' => _ROOT_RESTREINT . 'class/IndenterXmlClass.php',
            'iterateurcondition' => _ROOT_RESTREINT . 'class/IterateurConditionClass.php',
            'iterateurdata' => _ROOT_RESTREINT . 'class/IterateurDataClass.php',
            'iterateursql' => _ROOT_RESTREINT . 'class/IterateurSqlClass.php',
            'iterfactory' => _ROOT_RESTREINT . 'class/IterateurPatronsClass.php',
            'iterdecorateur' => _ROOT_RESTREINT . 'class/IterateurPatronsClass.php',
            'pclzip' => _ROOT_RESTREINT . 'class/PclzipClass.php',
            'phpthrumb' => _ROOT_RESTREINT . 'class/PhpThrumbClass.php',
            'validateurxml' => _ROOT_RESTREINT . 'class/ValidateurXmlClass.php',
            'sql' => _ROOT_RESTREINT . 'class/SqlClass.php',
            'isql' => _ROOT_RESTREINT . 'class/ISqlClass.php',
            'mysql' => _ROOT_RESTREINT . 'class/drivers/MysqlClass.php',
            //'sqlite' => _ROOT_RESTREINT . 'class/drivers/SqliteClass.php',
		);
	}
}