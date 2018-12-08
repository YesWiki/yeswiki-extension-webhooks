<?php

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

$GLOBALS['params'] = getAllParameters($this);

switch ($GLOBALS['params']['vue']) {
    // Display webhooks form
    case BAZ_VOIR_FORMULAIRE:
        if( !isset($GLOBALS['params']['action_formulaire']) ) {
            echo webhooks_formulaire();
        }
        break;

    // Call webhook on addition
    case BAZ_VOIR_CONSULTER:
        switch($GLOBALS['params']['action']) {
            case BAZ_VOIR_FICHE:
                if( $_GET['message']==='ajout_ok' ) {
                    // We set this condition because otherwise the page is called twice and the webhook is sent twice
                    // TODO: Understand why the YesWiki core calls this kind of page twice
                    if( !isset($GLOBALS['add_webhook_already_called']) ) {
                        $data = baz_valeurs_fiche($_GET['id_fiche']);
                        webhooks_post_all($data, WEBHOOKS_ACTION_ADD);
                        $GLOBALS['add_webhook_already_called'] = true;
                    }
                }
        }
        break;

    // Incoming webhook for tests
    case WEBHOOKS_VUE_TEST:
        $GLOBALS['wiki']->InsertTriple(
            $GLOBALS['wiki']->GetPageTag(),
            WEBHOOKS_VOCABULARY_TEST,
            file_get_contents('php://input'),
            '',
            ''
        );
        break;
}
