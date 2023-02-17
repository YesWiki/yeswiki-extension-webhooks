<?php

namespace YesWiki\Webhooks;

use BazarAction;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\YesWikiAction;
use YesWiki\Webhooks\Controller\WebhooksController;

class BazarAction__ extends YesWikiAction
{
    public function formatArguments($arg)
    {
        return([
            BazarAction::VARIABLE_ACTION => $_GET[BazarAction::VARIABLE_ACTION] ?? $arg[BazarAction::VARIABLE_ACTION] ?? null,
            BazarAction::VARIABLE_VOIR => $_GET[BazarAction::VARIABLE_VOIR] ?? $arg[BazarAction::VARIABLE_VOIR] ?? BazarAction::VOIR_DEFAUT,
            'redirecturl' => $arg['redirecturl'] ?? ''
        ]);
    }

    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        $tripleStore = $this->getService(TripleStore::class);
        $webhooksController = $this->getService(WebhooksController::class);

        $view = $this->arguments[BazarAction::VARIABLE_VOIR];
        $action = $this->arguments[BazarAction::VARIABLE_ACTION];

        switch ($view) {
            // Display webhooks form before the forms list
            case BazarAction::VOIR_FORMULAIRE:
                if (!isset($_GET['action'])) {
                    return $webhooksController->viewWebhooksForm();
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
