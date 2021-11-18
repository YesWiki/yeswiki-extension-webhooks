<?php

namespace YesWiki\Webhooks;

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
