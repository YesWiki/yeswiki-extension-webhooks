<?php

use YesWiki\Bazar\Service\EntryManager;

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$entryManager = $this->services->get(EntryManager::class);

if ($this->HasAccess('write') && $entryManager->isEntry($this->GetPageTag()) && $_GET['confirme']==='oui') {
    $data = json_decode($GLOBALS['wiki']->page['body'], true);
    webhooks_post_all($data, WEBHOOKS_ACTION_DELETE);
}
