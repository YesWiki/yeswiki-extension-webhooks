<?php

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

$GLOBALS['params'] = getAllParameters($this);

switch ($GLOBALS['params']['vue']) {
    case BAZ_VOIR_FORMULAIRE:
        if( !isset($GLOBALS['params']['action_formulaire']) ) {
            echo webhooks_formulaire();
        }
        break;

    case BAZ_VOIR_CONSULTER:
        switch($GLOBALS['params']['action']) {
            case BAZ_VOIR_FICHE:
                if( $_GET['message']==='ajout_ok' ) {
                    $data = baz_valeurs_fiche($_GET['id_fiche']);
                    webhooks_post_all($data, WEBHOOKS_ACTION_ADD);
                }
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
