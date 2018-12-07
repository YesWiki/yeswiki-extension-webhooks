<?php

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

if ($this->HasAccess('write')) {
    $type = $this->GetTripleValue($this->GetPageTag(), 'http://outils-reseaux.org/_vocabulary/type', '', '');

    if ($type == 'fiche_bazar' && $_GET['confirme']==='oui') {
        $data = json_decode($GLOBALS['wiki']->page['body'], true);
        webhooks_post_all($data, WEBHOOKS_ACTION_DELETE);
    }
}
