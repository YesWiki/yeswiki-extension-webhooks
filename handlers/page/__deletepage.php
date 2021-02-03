<?php

use YesWiki\Bazar\Service\EntryManager;

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$entryManager = $this->services->get(EntryManager::class);

if ($this->HasAccess('write') && $entryManager->isEntry($this->GetPageTag()) && isset($_GET['confirme']) && $_GET['confirme']==='oui') {
    $data = $entryManager->getOne($this->GetPageTag());
    webhooks_post_all($data, WEBHOOKS_ACTION_DELETE);
}
