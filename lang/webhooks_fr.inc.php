<?php

$GLOBALS['translations'] = array_merge(
    $GLOBALS['translations'],
    array(
        'WEBHOOKS_CONFIG_TITLE' => 'Webhooks sortants',
        'WEBHOOKS_CONFIG_DESC' => 'Les URLs que vous définissez ci-dessous seront appelés à chaque ajout / modification / suppression de fiche BazaR.',
        'WEBHOOKS_FORMAT_RAW' => 'Brut',
        'WEBHOOKS_FORMAT_ACTIVITYPUB' => 'ActivityPub',
        'WEBHOOKS_FORMAT_MATTERMOST' => 'Mattermost',
        'WEBHOOKS_FORMAT_SLACK' => 'Slack',
        'WEBHOOKS_URL_PLACEHOLDER' => 'Adresse URL',
        'WEBHOOKS_UPDATE' => 'Mettre à jour',
        'WEBHOOKS_ANONYMOUS_USER' => 'Anonyme',
        'WEBHOOKS_FORMS_ALL' => 'Tous les éléments du Bazar',
        'WEBHOOKS_ERROR_INVALID_URL' => 'Le lien fourni n\'est pas valide',
        'WEBHOOKS_ERROR_FORM_NOT_SEMANTIC' => 'Un ou plusieurs formulaires sélectionnés n\'est pas défini sémantiquement, le format ActivityPub ne peut être utilisé',
        'WEBHOOKS_VISIBLE_ONLY_FOR_ADMINS' => 'Visible uniquement pour les administrateurs',
        'WEBHOOKS_POST_ERROR' => "Une action d'arrière-plan ne s'est pas déroulée comme prévue.\nVous pouvez prévenir les administrateurs pour les aider à maintenir ce site en leur donnant cette information :\n erreur exécutant '{function}' dans '{method}'.",
    )
);
