<?php

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiAction;

class BazarAction__ extends YesWikiAction
{
    public function formatArguments($arg)
    {
        return([
            BazarAction::VARIABLE_ACTION => $arg[BazarAction::VARIABLE_ACTION] ?? $_GET[BazarAction::VARIABLE_ACTION] ?? null,
            BazarAction::VARIABLE_VOIR => $arg[BazarAction::VARIABLE_VOIR] ?? $_GET[BazarAction::VARIABLE_VOIR] ?? BazarAction::VOIR_DEFAUT,
        ]);
    }

    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        $tripleStore = $this->getService(TripleStore::class);

        $view = $this->arguments[BazarAction::VARIABLE_VOIR];
        $action = $this->arguments[BazarAction::VARIABLE_ACTION];

        switch ($view) {
            // Display webhooks form before the forms list
            case BazarAction::VOIR_FORMULAIRE:
                if (!isset($_GET['action'])) {
                    return webhooks_formulaire();
                }
                break;

            // Call webhook on addition
            case BazarAction::VOIR_CONSULTER:
                switch ($action) {
                    case BazarAction::ACTION_ENTRY_VIEW:
                        if (isset($_GET['message']) && $_GET['message']==='ajout_ok') {
                            // We set this condition because otherwise the page is called twice and the webhook is sent twice
                            // TODO: Understand why the YesWiki core calls this kind of page twice
                            if (!isset($GLOBALS['add_webhook_already_called'])) {
                                $fiche = $entryManager->getOne($_GET['id_fiche']);
                                webhooks_post_all($fiche, WEBHOOKS_ACTION_ADD);
                                $GLOBALS['add_webhook_already_called'] = true;
                            }
                        }
                }
                break;

            // Incoming webhook for tests
            case WEBHOOKS_VUE_TEST:
                $tripleStore->create(
                    $this->wiki->GetPageTag(),
                    WEBHOOKS_VOCABULARY_TEST,
                    file_get_contents('php://input'),
                    '',
                    ''
                );
                break;
        }
    }
}
