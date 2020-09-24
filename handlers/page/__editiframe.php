<?php

// Vérification de sécurité
if (!defined("WIKINI_VERSION")) {
    die("acc&egrave;s direct interdit");
}

global $bazarFiche;

if ($this->HasAccess('write') && $bazarFiche->isFiche($this->GetPageTag()) && isset($_POST['bf_titre'])) {
    webhooks_post_all($_POST, WEBHOOKS_ACTION_EDIT);
}
