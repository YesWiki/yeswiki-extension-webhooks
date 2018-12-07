<?php

if (!defined('WIKINI_VERSION')) {
    die('acc&egrave;s direct interdit');
}

// recuperation des parametres
$GLOBALS['params'] = getAllParameters($this);

// on affiche les infos correspondantes à la vue
switch ($GLOBALS['params']['vue']) {
    case BAZ_VOIR_CONSULTER:
        switch($GLOBALS['params']['action']) {
            case BAZ_VOIR_FICHE:
                if( $_GET['message']==='ajout_ok' ) {
                    $data = baz_valeurs_fiche($_GET['id_fiche']);
                    webhooks_post_all($data, WEBHOOKS_ACTION_ADD);
                }
        }
}

