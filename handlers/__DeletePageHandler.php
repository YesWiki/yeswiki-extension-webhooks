<?php

namespace YesWiki\Webhooks;

use Exception;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Webhooks\Controller\WebhooksController;
use Throwable;

class __DeletePageHandler extends YesWikiHandler
{
    public function run()
    {
        $entryManager = $this->getService(EntryManager::class);
        if (($this->wiki->UserIsOwner() || $this->wiki->UserIsAdmin())
                && $this->wiki->IsOrphanedPage($this->wiki->GetPageTag())
                && $entryManager->isEntry($this->wiki->GetPageTag())
                && isset($_GET['confirme']) && $_GET['confirme'] === 'oui'
                && !isset($_GET['webhooks_force_delete'])) {
            $data = $entryManager->getOne($this->wiki->GetPageTag());
            if (!empty($data['id_typeannonce'])) {
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
