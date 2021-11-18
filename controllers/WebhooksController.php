<?php

namespace YesWiki\Webhooks\Controller;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\YesWikiController;

class WebhooksController extends YesWikiController
{
    protected $entryManager;
    protected $aclService;

    public function __construct(
        EntryManager $entryManager,
        AclService $aclService
    ) {
        $this->entryManager = $entryManager;
        $this->aclService = $aclService;
    }

    public function sendEditWebhookIfNeeded()
    {
        if (isset($_GET['vue']) && $_GET['vue'] === 'consulter'
            && isset($_GET['action']) && $_GET['action'] === 'voir_fiche'
            && !empty($_GET['id_fiche']) && preg_match("/^". WN_CAMEL_CASE_EVOLVED ."$/m", $_GET['id_fiche'])
            && isset($_GET['message']) && $_GET['message'] === 'modif_ok'
            ) {
            if ($this->aclService->hasAccess('write', $_GET['id_fiche'])
                && $this->entryManager->isEntry($_GET['id_fiche'])) {
                $entry = $this->entryManager->getOne($_GET['id_fiche']);
                if (!empty($entry['id_typeannonce'])) {
                    webhooks_post_all($entry, WEBHOOKS_ACTION_EDIT);
                }
            }
        }
    }
}
