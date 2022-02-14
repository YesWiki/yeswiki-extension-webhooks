<?php

namespace YesWiki\Webhooks;

use Exception;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Throwable;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Webhooks\Controller\WebhooksController;

class __DeletePageHandler extends YesWikiHandler
{
    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        if (($this->wiki->UserIsOwner() || $this->wiki->UserIsAdmin())
                && (
                    $this->wiki->IsOrphanedPage($this->wiki->GetPageTag())
                    ||
                    isset($_GET['eraselink']) && $_GET['eraselink'] === 'oui'
                )
                && $entryManager->isEntry($this->wiki->GetPageTag())
                && isset($_GET['confirme']) && $_GET['confirme'] === 'oui'
                && !isset($_GET['webhooks_force_delete'])) {
            $data = $entryManager->getOne($this->wiki->GetPageTag());
            if (!empty($data['id_typeannonce'])) {
                $csrfModeAvailable = $this->wiki->services->has(CsrfTokenManager::class);
                if ($csrfModeAvailable) {
                    $inputToken = filter_input(INPUT_POST, 'crsf-token', FILTER_SANITIZE_STRING);
                    if (!is_null($inputToken) && $inputToken !== false) {
                        $tag = $this->wiki->GetPageTag();
                        $token = new CsrfToken("handler\deletepage\\$tag", $inputToken);
                        $csrfTokenManager = $this->getService(CsrfTokenManager::class);
                        if ($csrfTokenManager->isTokenValid($token)) {
                            $webhooksController = $this->getService(WebhooksController::class);
                            try {
                                $webhooksController->securedExecution('webhooks_post_all', $data, WEBHOOKS_ACTION_DELETE);
                            } catch (Throwable $th) {
                                throw new Exception($th->getMessage() .
                                    ' <form action="' .$this->wiki->Href('deletepage', '', [
                                            'confirme' => 'oui',
                                            'webhooks_force_delete' => '1'
                                        ] + (
                                            empty($_GET['incomingurl']) ? [] : ['incomingurl' => $_GET['incomingurl']]
                                        ) + (
                                            empty($_GET['eraselink']) ? [] : ['eraselink' => $_GET['eraselink']]
                                        ), false) .'" method="post" style="display: inline">'.
                                    '<input type="hidden" name="crsf-token" value="'. htmlentities($csrfTokenManager->refreshToken("handler\deletepage\\$tag")) .'">'.
                                    '<input type="submit" class="btn btn-primary btn-xs" value="'._t('WEBHOOKS_FORCE_DELETE').'"/></form>');
                            }
                        }
                    }
                } else {
                    // TODO remove this part for ectoplasme (v5), kept only for backward-compatibility in doryphore
                    $webhooksController = $this->getService(WebhooksController::class);
                    try {
                        $webhooksController->securedExecution('webhooks_post_all', $data, WEBHOOKS_ACTION_DELETE);
                    } catch (Throwable $th) {
                        throw new Exception($th->getMessage() .
                            '<a href="'.$this->wiki->Href('deletepage', '', [
                                'confirme' => 'oui',
                                'webhooks_force_delete' => '1'
                            ] + (
                                empty($_GET['incomingurl']) ? [] : ['incomingurl' => $_GET['incomingurl']]
                            ), false).'" class="btn btn-primary btn-xs">'._t('WEBHOOKS_FORCE_DELETE').'</a><br>', );
                    }
                }
            }
        }
    }
}
