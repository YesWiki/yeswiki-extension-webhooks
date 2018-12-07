<?php

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

// recuperation des parametres
$GLOBALS['params'] = getAllParameters($this);


// +------------------------------------------------------------------------------------------------------+
// |                                            CORPS du PROGRAMME                                        |
// +------------------------------------------------------------------------------------------------------+

// on affiche les infos correspondantes à la vue
switch ($GLOBALS['params']['vue']) {
    case BAZ_VOIR_FORMULAIRE:
        if( !isset($GLOBALS['params']['action_formulaire']) ) {
            echo webhooks_formulaire();
        }
        break;

    case 'test-webhook':
        $GLOBALS['wiki']->InsertTriple(
            $GLOBALS['wiki']->GetPageTag(),
            'http://yeswiki.net/_vocabulary/test',
            file_get_contents('php://input'),
            '',
            ''
        );
        break;
}
