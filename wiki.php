<?php

define('WEBHOOKS_PATH', 'tools/webhooks/');

//principales fonctions de bazar
require_once WEBHOOKS_PATH.'libs/autoload.php';
require_once WEBHOOKS_PATH.'libs/functions.php';

define('WEBHOOKS_ACTION_ADD', 'add');
define('WEBHOOKS_ACTION_EDIT', 'edit');
define('WEBHOOKS_ACTION_DELETE', 'delete');

define('WEBHOOKS_FORMAT_RAW', 'raw');
define('WEBHOOKS_FORMAT_MATTERMOST', 'mattermost');
define('WEBHOOKS_FORMAT_SLACK', 'slack');

define('WEBHOOKS_VUE_TEST', 'test-webhook');

define('WEBHOOKS_VOCABULARY_WEBHOOK', "http://yeswiki.net/_vocabulary/webhook");
define('WEBHOOKS_VOCABULARY_TEST', "http://yeswiki.net/_vocabulary/webhook-test");

//////////////////
// Configs
//////////////////

// Available JSON formatting to ease integration with other services
// If you add new formats, you will also need to modify format_json_data() and the lang file(s)
$wakkaConfig['WEBHOOKS_FORMATS'] = getConfigValue('WEBHOOKS_FORMATS', [
    WEBHOOKS_FORMAT_RAW => 'WEBHOOKS_FORMAT_RAW',
    WEBHOOKS_FORMAT_MATTERMOST => 'WEBHOOKS_FORMAT_MATTERMOST',
    WEBHOOKS_FORMAT_SLACK => 'WEBHOOKS_FORMAT_SLACK',
], $wakkaConfig);