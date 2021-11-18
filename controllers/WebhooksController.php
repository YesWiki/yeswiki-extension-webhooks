<?php

namespace YesWiki\Webhooks\Controller;

use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\Service\AclService;
use YesWiki\Core\YesWikiController;
use YesWiki\Wiki;
use Throwable;

class WebhooksController extends YesWikiController
{
    protected $entryManager;
    protected $aclService;
    protected $debugMode;

    public function __construct(
        EntryManager $entryManager,
        AclService $aclService
    ) {
        $this->entryManager = $entryManager;
        $this->aclService = $aclService;
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
                    $this->securedExecution('webhooks_post_all', $entry, WEBHOOKS_ACTION_EDIT);
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
}
