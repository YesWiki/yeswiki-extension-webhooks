<?php

use YesWiki\Bazar\Service\EntryManager;

if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$entryManager = $this->services->get(EntryManager::class);

if ($this->HasAccess('write') && $entryManager->isEntry($this->GetPageTag()) && isset($_POST['bf_titre'])) {
    webhooks_post_all($_POST, WEBHOOKS_ACTION_EDIT);
}
