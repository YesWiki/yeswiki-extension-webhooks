<?php

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

$ficheManager = $this->services->get('bazar.fiche.manager');

if ($this->HasAccess('write') && $ficheManager->isFiche($this->GetPageTag()) && $_GET['confirme']==='oui') {
    $data = json_decode($GLOBALS['wiki']->page['body'], true);
    webhooks_post_all($data, WEBHOOKS_ACTION_DELETE);
}
