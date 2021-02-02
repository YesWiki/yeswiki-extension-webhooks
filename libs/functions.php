<?php

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;

function get_all_webhooks($form_id=0)
{
    // Select all webhooks
    $all_webhooks = array_map(function ($webhook) {
        return json_decode($webhook['value'], true);
    }, $GLOBALS['wiki']->GetAllTriplesValues('BazaR', WEBHOOKS_VOCABULARY_WEBHOOK, '', ''));

    if ($form_id === 0) {
        return $all_webhooks;
    } else {
        // Return only webhooks which must be called for this form_id
        return(array_filter($all_webhooks, function ($webhook) use ($form_id) {
            return(!isset($webhook['form']) || $webhook['form']===0 || $webhook['form']===$form_id);
        }));
    }
}

function is_valid_url($url)
{
    if (preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*(\\.[_a-z]{2,5})?'.'((:[0-9]{1,5})?\\/.*)?$/i', $url)) {
        return $url;
    } else {
        return false;
    }
}

function get_notification_text($data, $action_type, $user_name)
{
    $formulaire = baz_valeurs_formulaire($data['id_typeannonce']);

    switch ($action_type) {
        case WEBHOOKS_ACTION_ADD:
            return "**AJOUT** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" ajoutée par {$user_name}\n{$GLOBALS['wiki']->config['base_url']}{$data['id_fiche']}";
        case WEBHOOKS_ACTION_EDIT:
            return "**MODIFICATION** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" mise à jour par {$user_name}\n{$GLOBALS['wiki']->config['base_url']}{$data['id_fiche']}";
        case WEBHOOKS_ACTION_DELETE:
            return "**SUPPRESSION** Fiche \"{$data['bf_titre']}\" de type \"{$formulaire['bn_label_nature']}\" supprimée par {$user_name}";
    }
}

function format_date_xsd($date)
{
    $date_array = explode(" ", $date);
    return $date_array[0] . "T" . $date_array[1] . "Z";
}

function get_actor_uri($actor)
{
    if ($GLOBALS['wiki']->config['webhooks_activitypub_default_actor'] !== '') {
        // If a default global-wide actor is defined, use it
        return $GLOBALS['wiki']->config['webhooks_activitypub_default_actor'];
    } else {
        // If no field is marked as an actor, take the current logged-in user
        if (!$actor) {
            $actor = $GLOBALS['wiki']->href('', $GLOBALS['wiki']->getUser() ? $GLOBALS['wiki']->getUser()['name'] : _t('WEBHOOKS_ANONYMOUS_USER'));
        }
        // If a base URL is defined in the configs, replace the yeswiki base URL with it
        if ($GLOBALS['wiki']->config['webhooks_activitypub_actors_base_url'] !== '') {
            $actor = str_replace($GLOBALS['wiki']->config['base_url'], '', $actor);
            return $GLOBALS['wiki']->config['webhooks_activitypub_actors_base_url'] . $actor;
        } else {
            return $actor;
        }
    }
}

function format_json_data($format, $data)
{
    switch ($format) {
        case WEBHOOKS_FORMAT_RAW:
            return $data;

        case WEBHOOKS_FORMAT_ACTIVITYPUB:
            $semanticData = $data['data']['semantic'];
            if (!$semanticData) {
                throw new Exception("Webhook error: unable to format data for activitypub (no semantic data defined)");
            };

            $actorUri = get_actor_uri($semanticData['actor']);
            $to = [
                $actorUri . "/followers",
                WEBHOOKS_ACTIVITYPUB_PUBLIC_URI
            ];
            $activityPubActions = [
                WEBHOOKS_ACTION_ADD => "Create",
                WEBHOOKS_ACTION_EDIT => "Update",
                WEBHOOKS_ACTION_DELETE => "Delete"
            ];

            if ($data['action'] === WEBHOOKS_ACTION_DELETE) {
                $object = $semanticData['@id'];
            } else {
                $object = array_merge(
                    [
                        // In ActivityPub, IDs and types are defined without the @ prefix (go figure ?)
                        'id' => $semanticData['@id'],
                        'type' => $semanticData['@type'],
                        'attributedTo' => $actorUri,
                        'to' => $to,
                        // If published or updated are defined as a semantic field, this will be overwritten
                        'published' => format_date_xsd($data['data']['date_creation_fiche']),
                        'updated' => format_date_xsd($data['data']['date_maj_fiche'])
                    ],
                    $data['data']['semantic']
                );

                // Remove unused keys
                unset($object['@context']);
                unset($object['@type']);
                unset($object['@id']);
                unset($object['actor']);
            }

            return [
                "@context" => $semanticData['@context'],
                "type" => $activityPubActions[$data['action']],
                "to" => $to,
                "actor" => $actorUri,
                "object" => $object
            ];

        case WEBHOOKS_FORMAT_MATTERMOST:
            return [
                "username" => $GLOBALS['wiki']->config['webhooks_bot_name'],
                "icon_url" => $GLOBALS['wiki']->config['webhooks_bot_icon'],
                "text" => $data['text']
            ];

        case WEBHOOKS_FORMAT_SLACK:
            return ["text" => $data['text']];
    }
}

function webhooks_post_all($data, $action_type)
{
    if (!isset($data['id_typeannonce'])) {
        throw new Exception("Webhook error: unable to determine the form ID (id_typeannonce is not defined)");
    }

    $form_id = intval($data['id_typeannonce']);

    $webhooks = get_all_webhooks($form_id);

    if (count($webhooks) > 0) {

        // Add the semantic data if they don't already exist

        if (!$data['semantic']) {
            // If one of the webhook is using ActivityPub
            $activityPubWebhooks = array_filter($webhooks, function ($webhook) {
                return $webhook['format'] === WEBHOOKS_FORMAT_ACTIVITYPUB;
            });

            if (count($activityPubWebhooks) > 0) {
                $data['semantic'] = $GLOBALS['wiki']->services->get(EntryManager::class)->convertToSemanticData($data['id_typeannonce'], $data);
            }
        }

        // Prepare data to send

        $logged_user = $GLOBALS['wiki']->getUser();
        $logged_user_name = $logged_user === '' ? _t('WEBHOOKS_ANONYMOUS_USER') : $logged_user['name'];

        $data_to_send = [
            'action' => $action_type,
            'user' => $logged_user_name,
            'text' => get_notification_text($data, $action_type, $logged_user_name),
            'data_type' => 'bazar',
            'bazar_form_id' => $form_id,
            'data' => $data
        ];

        // Send data to all webhooks

        $client = new Client(['headers' => [
            'Connection' => 'Close'
        ]]);

        $promises = array_map(function ($webhook) use ($client, $data_to_send) {
            return $client->postAsync($webhook['url'], ['json' => format_json_data($webhook['format'], $data_to_send)]);
        }, $webhooks);

        try {
            // Wait on all of the requests to complete.
            // Throws a ConnectException if any of the requests fail
            Promise\unwrap($promises);
        } catch (ConnectException $connectException) {
            // Do nothing on errors...
        } catch (ServerException $serverException) {
            // Do nothing on errors...
        }

        // Wait for the requests to complete, otherwise the code may end before the request is sent
        // TODO: try to make it work without this command, so that webhooks can be sent asyncronously
        Promise\settle($promises)->wait();
    }
}

function webhooks_formulaire()
{
    if (!empty($_POST['url'])) {

        // First delete all existing triples for this resource
        $GLOBALS['wiki']->DeleteTriple($GLOBALS['wiki']->GetPageTag(), WEBHOOKS_VOCABULARY_WEBHOOK, null, '', '');

        $numFields = count($_POST['url']);

        for ($i=0; $i<$numFields; $i++) {
            if ($_POST['url'][$i]) {
                // Check that URL is valid
                if (!is_valid_url(trim($_POST['url'][$i]))) {
                    exit(_t('WEBHOOKS_ERROR_INVALID_URL'));
                }

                // If ActivityPub is selected, check that the selected form(s) are semantic
                if ($_POST['format'][$i] === WEBHOOKS_FORMAT_ACTIVITYPUB) {
                    $formId = intval($_POST['form'][$i]);

                    if ($formId === 0) {
                        // Check that all forms are semantic
                        foreach ($GLOBALS['wiki']->services->get(FormManager::class)->getAll() as $form) {
                            if (!$form['bn_sem_type']) {
                                exit(_t('WEBHOOKS_ERROR_FORM_NOT_SEMANTIC'));
                            }
                        }
                    } else {
                        // Check that the selected form is semantic
                        $form = baz_valeurs_formulaire($formId);
                        if (!$form['bn_sem_type']) {
                            exit(_t('WEBHOOKS_ERROR_FORM_NOT_SEMANTIC'));
                        }
                    }
                }

                // All good, save webhook
                $GLOBALS['wiki']->InsertTriple(
                    $GLOBALS['wiki']->GetPageTag(),
                    WEBHOOKS_VOCABULARY_WEBHOOK,
                    json_encode([
                        'format' => $_POST['format'][$i],
                        'form' => intval($_POST['form'][$i]),
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

    include_once 'includes/squelettephp.class.php';
    $templateforms = new SquelettePhp('webhooks_form.tpl.html', 'webhooks');
    return $templateforms->render([
        'url' => getAbsoluteUrl(),
        'webhooks' => get_all_webhooks(),
        'forms' => $GLOBALS['wiki']->services->get(FormManager::class)->getAll(),
        'formats' => $GLOBALS['wiki']->config['webhooks_formats']
    ]);
}
