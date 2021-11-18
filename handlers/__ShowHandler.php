<?php

namespace YesWiki\Webhooks;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Webhooks\Controller\WebhooksController;

class __ShowHandler extends YesWikiHandler
{
    public function run()
    {
        $webhooksController = $this->getService(WebhooksController::class);
        $webhooksController->sendEditWebhookIfNeeded();
    }
}
