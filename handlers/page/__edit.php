<?php

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

if ($this->HasAccess('write')) {
    $type = $this->GetTripleValue($this->GetPageTag(), 'http://outils-reseaux.org/_vocabulary/type', '', '');

    if ($type == 'fiche_bazar') {
        if (isset($_POST['bf_titre'])) {
            webhooks_post_all($_POST, WEBHOOKS_ACTION_EDIT);
        }
    }
}
