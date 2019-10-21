<?php

define('WEBHOOKS_PATH', 'tools/webhooks/');

require_once WEBHOOKS_PATH.'vendor/autoload.php';
require_once WEBHOOKS_PATH.'libs/functions.php';

define('WEBHOOKS_ACTION_ADD', 'add');
define('WEBHOOKS_ACTION_EDIT', 'edit');
define('WEBHOOKS_ACTION_DELETE', 'delete');

define('WEBHOOKS_FORMAT_RAW', 'raw');
define('WEBHOOKS_FORMAT_ACTIVITYPUB', 'activitypub');
define('WEBHOOKS_FORMAT_MATTERMOST', 'mattermost');
define('WEBHOOKS_FORMAT_SLACK', 'slack');

define('WEBHOOKS_VUE_TEST', 'test-webhook');

define('WEBHOOKS_VOCABULARY_WEBHOOK', "http://yeswiki.net/_vocabulary/webhook");
define('WEBHOOKS_VOCABULARY_TEST', "http://yeswiki.net/_vocabulary/webhook-test");

define('WEBHOOKS_ACTIVITYPUB_PUBLIC_URI', "https://www.w3.org/ns/activitystreams#Public");

//////////////////
// Configs
//////////////////

// Available JSON formatting to ease integration with other services
// If you add new formats, you will also need to modify format_json_data() and the lang file(s)
$wakkaConfig['webhooks_formats'] = getConfigValue('webhooks_formats', [
    WEBHOOKS_FORMAT_RAW => 'WEBHOOKS_FORMAT_RAW',
    WEBHOOKS_FORMAT_ACTIVITYPUB => 'WEBHOOKS_FORMAT_ACTIVITYPUB',
    WEBHOOKS_FORMAT_MATTERMOST => 'WEBHOOKS_FORMAT_MATTERMOST',
    WEBHOOKS_FORMAT_SLACK => 'WEBHOOKS_FORMAT_SLACK',
], $wakkaConfig);

// If no actor field is defined semantically, this value will be used
$wakkaConfig['webhooks_activitypub_default_actor'] = getConfigValue('webhooks_activitypub_default_actor', '', $wakkaConfig);

// If an actor is defined, we can override the base URL with this config
$wakkaConfig['webhooks_activitypub_actors_base_url'] = getConfigValue('webhooks_activitypub_actors_base_url', '', $wakkaConfig);

// Bot config (works for Mattermost if username and profile picture override is enabled)
// See https://docs.mattermost.com/developer/webhooks-incoming.html
$wakkaConfig['webhooks_bot_name'] = getConfigValue('webhooks_bot_name', "YesWiki Bot", $wakkaConfig);
$wakkaConfig['webhooks_bot_icon'] = getConfigValue('webhooks_bot_icon', "https://yeswiki.net/files/PageHeader_yeswikiprovisoire_vignette_97_97_20181206153605_20181206154004.png", $wakkaConfig);
