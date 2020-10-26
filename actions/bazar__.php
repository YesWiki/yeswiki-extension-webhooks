<?php

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

$ficheManager = $this->services->get('bazar.fiche.manager');

$GLOBALS['params'] = getAllParameters($this);

switch ($GLOBALS['params']['vue']) {
    // Display webhooks form
    case BAZ_VOIR_FORMULAIRE:
        if( !isset($_GET['action_formulaire']) ) {
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
                        $fiche = $ficheManager->getOne($_GET['id_fiche']);
                        webhooks_post_all($fiche, WEBHOOKS_ACTION_ADD);
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
