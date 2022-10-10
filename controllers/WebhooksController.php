<?php

namespace YesWiki\Webhooks\Controller;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\SemanticTransformer;
use YesWiki\Core\Entity\Event;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\Service\TripleStore;
use YesWiki\Core\Service\UserManager;
use YesWiki\Core\YesWikiController;
use YesWiki\Wiki;
use Throwable;

// Check the interface exists before trying to use it
if (interface_exists(EventSubscriberInterface::class)) {
    class WebhooksController extends WebhooksControllerCommons implements EventSubscriberInterface
    {
        public static function getSubscribedEvents()
        {
            return [
                WebhooksControllerCommons::WEBHOOKS_ACTION_CREATE_COMMENT => 'sendCommentCreatedWebHook',
                WebhooksControllerCommons::WEBHOOKS_ACTION_MODIFY_COMMENT => 'sendCommentModifiedWebHook',
                WebhooksControllerCommons::WEBHOOKS_ACTION_DELETE_COMMENT => 'sendCommentDeletedWebHook'
            ];
        }

        /**
         * @param Event $event
         */
        public function sendCommentCreatedWebHook($event)
        {
            // array
            $data = $event->getData();
            $this->securedExecution([$this,'webhooks_post_all'], $data, self::WEBHOOKS_ACTION_CREATE_COMMENT);
        }

        /**
         * @param Event $event
         */
        public function sendCommentModifiedWebHook($event)
        {
            // array
            $data = $event->getData();
            $this->securedExecution([$this,'webhooks_post_all'], $data, self::WEBHOOKS_ACTION_MODIFY_COMMENT);
        }

        /**
         * @param Event $event
         */
        public function sendCommentDeletedWebHook($event)
        {
            // array
            $data = $event->getData();
            $this->securedExecution([$this,'webhooks_post_all'], $data, self::WEBHOOKS_ACTION_DELETE_COMMENT);
        }
    }
} else {
    class WebhooksController extends WebhooksControllerCommons
    {
    }
}

class WebhooksControllerCommons extends YesWikiController
{
    public const WEBHOOKS_ACTION_CREATE_COMMENT = 'comments.create';
    public const WEBHOOKS_ACTION_MODIFY_COMMENT = 'comments.modify';
    public const WEBHOOKS_ACTION_DELETE_COMMENT = 'comments.delete';

    protected $aclService;
    protected $debugMode;
    protected $entryManager;
    protected $formManager;
    protected $params;
    protected $semanticTransformer;
    protected $tripleStore;
    protected $userManager;

    public function __construct(
        AclService $aclService,
        EntryManager $entryManager,
        FormManager $formManager,
        ParameterBagInterface $params,
        SemanticTransformer $semanticTransformer,
        TripleStore $tripleStore,
        UserManager $userManager,
        Wiki $wiki
    ) {
        $this->aclService = $aclService;
        $this->entryManager = $entryManager;
        $this->formManager = $formManager;
        $this->params = $params;
        $this->semanticTransformer = $semanticTransformer;
        $this->tripleStore = $tripleStore;
        $this->userManager = $userManager;
        $this->wiki = $wiki;
        $this->debugMode = null;
    }

    private function getDebugMode(): bool
    {
        if (is_null($this->debugMode)) {
            $this->debugMode = ($this->wiki->GetConfigValue('debug')=='yes');
        }
        return $this->debugMode ;
    }

    public function sendEditWebhookIfNeeded()
    {
        if (isset($_GET['vue']) && $_GET['vue'] === 'consulter'
            && isset($_GET['action']) && $_GET['action'] === 'voir_fiche'
            && !empty($_GET['id_fiche']) && preg_match("/^". WN_CAMEL_CASE_EVOLVED ."$/m", $_GET['id_fiche'])
            && isset($_GET['message']) && $_GET['message'] === 'modif_ok'
        ) {
            if ($this->aclService->hasAccess('write', $_GET['id_fiche'])
                && $this->entryManager->isEntry($_GET['id_fiche'])) {
                $entry = $this->entryManager->getOne($_GET['id_fiche']);
                if (!empty($entry['id_typeannonce'])) {
                    $this->securedExecution([$this,'webhooks_post_all'], $entry, WEBHOOKS_ACTION_EDIT);
                }
            }
        }
    }

    /**
     * execution of $function with catch of errors
     * @param string|array $function string = a function , otherwise [className, Method]
     * @param mixed $param1
     * @param mixed $param2
     * @param mixed $param3
     * @return mixed
     */
    public function securedExecution($function, $param1 = null, $param2 = null, $param3 = null)
    {
        try {
            $isMethod = (is_array($function) && count($function) == 2);
            if (!$isMethod) {
                return $function($param1, $param2, $param3) ;
            } else {
                $object = $function[0];
                $method = $function[1];
                return $object->$method($param1, $param2, $param3);
            }
        } catch (Throwable $th) {
            if ($this->getDebugMode() && $this->wiki->UserIsAdmin()) {
                throw $th;
            } else {
                $functionName = $isMethod
                    ? (
                        is_string(get_class($function[0])) ? get_class($function[0]).'->' : ''
                    ).(
                        is_string($function[1]) ? $function[1] : ''
                    )
                    : $function;

                $_SESSION['message'] = ($_SESSION['message'] ?? '') . str_replace(
                    ['{method}','{function}',"\n"],
                    [__METHOD__,$functionName,''],
                    nl2br(_t('WEBHOOKS_POST_ERROR'))
                );

                // TODO find a way not to change config
                $this->wiki->config['toast_class'] = 'alert alert-warning';
                $this->wiki->config['toast_duration'] = 10000;
            }
        }
    }

    public function viewWebhooksForm(): string
    {
        if (!empty($_POST['url']) && $this->wiki->UserIsAdmin()) {
            $this->registerWebhooks();
        }

        return $this->render('@webhooks/webhooks_form.twig', [
            'url' => getAbsoluteUrl(),
            'webhooks' => $this->get_all_webhooks(),
            'forms' => $this->formManager->getAll(),
            'formats' => $this->params->get('webhooks_formats')
        ]);
    }

    protected function registerWebhooks()
    {
        // First delete all existing triples for this resource
        $this->tripleStore->delete($this->wiki->GetPageTag(), WEBHOOKS_VOCABULARY_WEBHOOK, null, '', '');

        $numFields = count($_POST['url']);

        for ($i=0; $i<$numFields; $i++) {
            if ($_POST['url'][$i]) {
                // Check that URL is valid
                if (!$this->is_valid_url(trim($_POST['url'][$i]))) {
                    $this->wiki->exit(_t('WEBHOOKS_ERROR_INVALID_URL'));
                }

                $formId = ($_POST['form'][$i] !== "comments") ? intval($_POST['form'][$i]) : "comments";
                // If ActivityPub is selected, check that the selected form(s) are semantic
                if ($_POST['format'][$i] === WEBHOOKS_FORMAT_ACTIVITYPUB) {
                    if ($formId === 0) {
                        // Check that all forms are semantic
                        foreach ($this->formManager->getAll() as $form) {
                            if (!$form['bn_sem_type']) {
                                $this->wiki->exit(_t('WEBHOOKS_ERROR_FORM_NOT_SEMANTIC'));
                            }
                        }
                    } elseif ($formId !== "comments") {
                        // Check that the selected form is semantic
                        $form = $this->formManager->getOne($formId);
                        if (!$form['bn_sem_type']) {
                            $this->wiki->exit(_t('WEBHOOKS_ERROR_FORM_NOT_SEMANTIC'));
                        }
                    }
                }

                // All good, save webhook
                $this->tripleStore->create(
                    $this->wiki->GetPageTag(),
                    WEBHOOKS_VOCABULARY_WEBHOOK,
                    json_encode([
                        'format' => $_POST['format'][$i],
                        'form' => $formId,
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

    public function get_all_webhooks($form_id=0)
    {
        // Select all webhooks
        $all_webhooks = array_map(function ($webhook) {
            return json_decode($webhook['value'], true);
        }, $this->tripleStore->getAll('BazaR', WEBHOOKS_VOCABULARY_WEBHOOK, '', ''));

        if ($form_id === 0) {
            return $all_webhooks;
        } else {
            // Return only webhooks which must be called for this form_id
            return(array_filter($all_webhooks, function ($webhook) use ($form_id) {
                return(!isset($webhook['form']) || ($form_id != "comments" && $webhook['form']===0) || $webhook['form']===$form_id);
            }));
        }
    }

    protected function is_valid_url($url)
    {
        if (preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*(\\.[_a-z]{2,5})?'.'((:[0-9]{1,5})?\\/.*)?$/i', $url)) {
            return $url;
        } else {
            return false;
        }
    }

    protected function get_notification_text($data, $action_type, $user_name)
    {
        switch ($action_type) {
            case self::WEBHOOKS_ACTION_CREATE_COMMENT:
            case self::WEBHOOKS_ACTION_MODIFY_COMMENT:
            case self::WEBHOOKS_ACTION_DELETE_COMMENT:
                $formulaire = "";
                break;
            default:
                $idformulaire = $data['id_typeannonce'] ?? '';
                if (is_array($idformulaire) and count($idformulaire) > 0) {
                    $idformulaire = $idformulaire[0];
                }
                if (!empty($idformulaire) && strval($idformulaire) == strval(intval($idformulaire))) {
                    $formulaire = $this->formManager->getOne($idformulaire);
                } else {
                    $formulaire = ($this->formManager->getAll())[0];
                }
                break;
        }
        $tabData = [
            'data' => $data,
            'form'=> $formulaire,
            'user'=> $user_name,
            'url' => $this->params->get('base_url')
        ];
        switch ($action_type) {
            case WEBHOOKS_ACTION_ADD:
                return $this->render('@webhooks/message-add.twig', $tabData);
            case WEBHOOKS_ACTION_EDIT:
                return $this->render('@webhooks/message-edit.twig', $tabData);
            case WEBHOOKS_ACTION_DELETE:
                return $this->render('@webhooks/message-delete.twig', $tabData);
            case self::WEBHOOKS_ACTION_CREATE_COMMENT:
                return $this->render('@webhooks/message-create-comment.twig', $tabData);
            case self::WEBHOOKS_ACTION_MODIFY_COMMENT:
                return $this->render('@webhooks/message-modify-comment.twig', $tabData);
            case self::WEBHOOKS_ACTION_DELETE_COMMENT:
                return $this->render('@webhooks/message-delete-comment.twig', $tabData);
        }
    }

    protected function format_date_xsd($date)
    {
        $date_array = explode(" ", $date);
        return $date_array[0] . "T" . $date_array[1] . "Z";
    }

    protected function get_actor_uri($actor)
    {
        if ($this->params->has('webhooks_activitypub_default_actor')
            && !empty($this->params->get('webhooks_activitypub_default_actor'))) {
            // If a default global-wide actor is defined, use it
            return $this->params->has('webhooks_activitypub_default_actor');
        } else {
            // If no field is marked as an actor, take the current logged-in user
            if (!$actor) {
                $user = $this->userManager->getLoggedUser();
                $actor = $this->wiki->href('', !empty($user['name']) ? $user['name'] : _t('WEBHOOKS_ANONYMOUS_USER'));
            }
            // If a base URL is defined in the configs, replace the yeswiki base URL with it
            if ($this->params->has('webhooks_activitypub_actors_base_url')
                && !empty($this->params->get('webhooks_activitypub_actors_base_url'))) {
                $actor = str_replace($this->params->get('base_url'), '', $actor);
                return $this->params->get('webhooks_activitypub_actors_base_url') . $actor;
            } else {
                return $actor;
            }
        }
    }

    protected function format_json_data($format, $data)
    {
        switch ($format) {
            case WEBHOOKS_FORMAT_RAW:
                return $data;

            case WEBHOOKS_FORMAT_ACTIVITYPUB:
                $semanticData = $data['data']['semantic'];
                if (!$semanticData) {
                    throw new Exception("Webhook error: unable to format data for activitypub (no semantic data defined)");
                };

                $actorUri = $this->get_actor_uri($semanticData['actor']);
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
                            'published' => $this->format_date_xsd($data['data']['date_creation_fiche']),
                        'updated' => $this->format_date_xsd($data['data']['date_maj_fiche'])
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
                    "username" => $this->params->get('webhooks_bot_name'),
                    "icon_url" => $this->params->get('webhooks_bot_icon'),
                    "text" => $data['text']
                ];

            case WEBHOOKS_FORMAT_SLACK:
                return ["text" => $data['text']];

            case WEBHOOKS_FORMAT_YESWIKI:
                // remove not used fields
                foreach ($data['data'] as $key => $value) {
                    if (!in_array($key, ['id_fiche','bf_titre','id_typeannonce','url','date_maj_fiche'], true)) {
                        unset($data['data'][$key]);
                    }
                }
                $data['base_url'] = $this->params->get('base_url');
                return $data;
        }
    }

    /**
     * update $webhook['url'], $data and options according to $webhook['format']
     * @param $webhook
     * @param array $data
     * @return array [$url, $options (to merge to current options))]
     */
    protected function extract_url_options($webhook, $data)
    {
        $options = [];
        switch ($webhook['format']) {
            case WEBHOOKS_FORMAT_YESWIKI:
                $query = parse_url($webhook['url'], PHP_URL_QUERY);
                if (!empty($query)) {
                    parse_str($query, $queries);

                    // get bearer
                    if (isset($queries['bearer'])) {
                        if (!empty($queries['bearer'])) {
                            $options['headers'] = ['Authorization' => 'Bearer '. $queries['bearer']];
                        }
                        unset($queries['bearer']);
                    }

                    // refresh url
                    array_walk($queries, function (&$item, $key) {
                        $item = empty($item)
                            ? $key
                            : (
                                is_array($item)
                                ? $key.'='.implode(',', $item)
                                : $key.'='.$item
                            );
                    });
                    $newQuery = implode('&', $queries);
                    $url = str_replace($query, $newQuery, $webhook['url']);
                }
                break;

            default:
                $url = $webhook['url'];
                break;
        }
        return [$url, $options];
    }

    public function webhooks_post_all($data, $action_type)
    {
        switch ($action_type) {
            case self::WEBHOOKS_ACTION_CREATE_COMMENT:
            case self::WEBHOOKS_ACTION_MODIFY_COMMENT:
            case self::WEBHOOKS_ACTION_DELETE_COMMENT:
                $form_id = "comments";
                break;
            default:
                if (!isset($data['id_typeannonce'])) {
                    throw new Exception("Webhook error: unable to determine the form ID (id_typeannonce is not defined)");
                }

                $form_id = intval($data['id_typeannonce']);
                break;
        }

        $webhooks = $this->get_all_webhooks($form_id);

        if (count($webhooks) > 0) {
            // Add the semantic data if they don't already exist

            if (!isset($data['semantic'])) {
                // If one of the webhook is using ActivityPub
                $activityPubWebhooks = array_filter($webhooks, function ($webhook) {
                    return $webhook['format'] === WEBHOOKS_FORMAT_ACTIVITYPUB;
                });

                if (count($activityPubWebhooks) > 0) {
                    $data['semantic'] = $this->semanticTransformer->convertToSemanticData($data['id_typeannonce'], $data);
                }
            }

            // Prepare data to send

            $logged_user = $this->userManager->getLoggedUser();
            $logged_user_name = empty($logged_user) ? _t('WEBHOOKS_ANONYMOUS_USER') : $logged_user['name'];

            $data_to_send = [
                'action' => $action_type,
                'user' => $logged_user_name,
                'text' => $this->get_notification_text($data, $action_type, $logged_user_name),
                'data_type' => 'bazar',
                'bazar_form_id' => $form_id,
                'data' => $data
            ];

            // Send data to all webhooks

            $client = new Client([
                'headers' => [
                    'Connection' => 'Close'
                ],
                'timeout' => 4 // to prevent 504 error if webhooks is not reachable : timeout 3s
            ]);

            $promises = array_map(function ($webhook) use ($client, $data_to_send) {
                list($url, $options) = $this->extract_url_options($webhook, $data_to_send);
                return $client->postAsync(
                    $url,
                    $options + ['json' => $this->format_json_data($webhook['format'], $data_to_send)]
                );
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
}
