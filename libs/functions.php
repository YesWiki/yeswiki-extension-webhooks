<?php

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ConnectException;

// Read all hooks from the triples table
function get_all_webhooks() {
    return array_map(function($webhook) {
        return json_decode($webhook['value'], true);
    }, $GLOBALS['wiki']->GetAllTriplesValues('BazaR', WEBHOOKS_VOCABULARY_WEBHOOK, '', ''));
}

function is_valid_url($url){
    if(preg_match( '/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*(\\.[_a-z]{2,5})?'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$url)){
        return $url;
    }
    else{
        return false;
    }
}

function get_notification_text($data, $action_type) {
    $formulaire = baz_valeurs_formulaire($data['id_typeannonce']);
    $loggedUser = $GLOBALS['wiki']->getUser();

    switch($action_type) {
        case WEBHOOKS_ACTION_ADD:
            return "**AJOUT** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" ajoutée par {$loggedUser['name']}\n{$GLOBALS['wiki']->config['base_url']}{$data['id_fiche']}";
        case WEBHOOKS_ACTION_EDIT:
            return "**MODIFICATION** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" mise à jour par {$loggedUser['name']}\n{$GLOBALS['wiki']->config['base_url']}{$data['id_fiche']}";
        case WEBHOOKS_ACTION_DELETE:
            return "**SUPPRESSION** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" supprimée par {$loggedUser['name']}";
    }
}

function format_json_data($format, $data) {
    switch($format) {
        case WEBHOOKS_FORMAT_RAW:
            return $data;
        case WEBHOOKS_FORMAT_MATTERMOST:
            return ["text" => $data['text']];
    }
}

function webhooks_post_all($data, $action_type) {

    $webhooks = get_all_webhooks();

    if( count($webhooks) > 0 ) {

        $client = new Client(['headers' => [
            'Connection' => 'Close'
        ]]);

        $data['action'] = $action_type;
        $data['text'] = get_notification_text($data, $action_type);

        $promises = array_map(function($webhook) use ($client, $data) {
            return $client->postAsync($webhook['url'], ['json' => format_json_data($webhook['format'], $data)]);
        }, $webhooks);

        try{
            // Wait on all of the requests to complete.
            // Throws a ConnectException if any of the requests fail
            Promise\unwrap($promises);
        } catch( ConnectException $connectException ) {
            // Do nothing on errors...
        }

        // Wait for the requests to complete, even if some of them fail
        Promise\settle($promises)->wait();
    }
}

function webhooks_formulaire() {

    if( $_POST['url'] ) {

        // First delete all existing triples for this resource
        $GLOBALS['wiki']->DeleteTriple($GLOBALS['wiki']->GetPageTag(), WEBHOOKS_VOCABULARY_WEBHOOK, null, '', '');

        $numFields = count($_POST['url']);

        for( $i=0; $i<$numFields; $i++ ) {
            if( is_valid_url(trim($_POST['url'][$i])) ) {
                $GLOBALS['wiki']->InsertTriple(
                    $GLOBALS['wiki']->GetPageTag(),
                    WEBHOOKS_VOCABULARY_WEBHOOK,
                    json_encode([
                        'format' => $_POST['format'][$i],
                        'url' => trim($_POST['url'][$i])
                    ]),
                    '',
                    ''
                );
            }
        }

        // Redirect so that we don't resubmit form on reload
        header('Location:' . $_SERVER['REQUEST_URI']);
    }

    $template_name = 'themes/tools/webhooks/templates/webhooks_form.tpl.html';
    if (!is_file($template_name)) $template_name = 'tools/webhooks/presentation/templates/webhooks_form.tpl.html';

    include_once 'includes/squelettephp.class.php';
    $templateforms = new SquelettePhp($template_name);
    $templateforms->set('webhooks', get_all_webhooks());
    return $templateforms->analyser();
}