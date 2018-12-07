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

define('WEBHOOKS_VOCABULARY_WEBHOOK', "http://yeswiki.net/_vocabulary/webhook");