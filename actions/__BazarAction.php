<?php

use YesWiki\Core\YesWikiAction;

class __BazarAction extends YesWikiAction
{
    // const to synchronize manually with 'tools/bazar/actions/BazarAction.php'
    // because  BazarAction should be loaded by Performer not here (if BazarACtion is present in custom/actions folder)
    public const VARIABLE_VOIR = 'vue';
    public const VOIR_DEFAUT = 'formulaire';
    public const VOIR_SAISIR = 'saisir';

    public function formatArguments($arg)
    {
        return([
            self::VARIABLE_VOIR => $_GET[self::VARIABLE_VOIR] ?? $arg[self::VARIABLE_VOIR] ?? self::VOIR_DEFAUT,
            'redirecturl' => $arg['redirecturl'] ?? ''
        ]);
    }

    public function run()
    {
        $view = $this->arguments[self::VARIABLE_VOIR];

        switch ($view) {
            // Redefine redirecturl to be sure to call webhooks
            case self::VOIR_SAISIR:
                if (!empty($this->arguments['redirecturl'])) {
                    $this->wiki->setParameter('redirecturl', '');
                }
                break;
        }
    }
}
