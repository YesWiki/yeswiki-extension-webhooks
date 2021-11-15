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
define('WEBHOOKS_FORMAT_YESWIKI', 'yeswiki');

define('WEBHOOKS_VUE_TEST', 'test-webhook');

define('WEBHOOKS_VOCABULARY_WEBHOOK', "http://yeswiki.net/_vocabulary/webhook");
define('WEBHOOKS_VOCABULARY_TEST', "http://yeswiki.net/_vocabulary/webhook-test");

define('WEBHOOKS_ACTIVITYPUB_PUBLIC_URI', "https://www.w3.org/ns/activitystreams#Public");
