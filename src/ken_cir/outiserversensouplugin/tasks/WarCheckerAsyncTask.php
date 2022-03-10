<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\scheduler\AsyncTask;

/**
 *
 */
class WarCheckerAsyncTask extends AsyncTask
{
    public function __construct()
    {
    }

    public function onRun(): void
    {
        // 開始時間を過ぎているWarDataのID
        $startWarIds = [];
        foreach (WarDataManager::getInstance()->getAll() as $warData) {
            // 開始時間を過ぎていたら
            if ($warData->getStartTime() <= time()) {
                $startWarIds[] = $warData->getId();
                $warData->setStarted(true);
            }
        }

        $this->setResult($startWarIds);
    }

    public function onCompletion(): void
    {
        foreach ($this->getResult() as $warId) {
            $warData = WarDataManager::getInstance()->get($warId);
            $factionData = FactionDataManager::getInstance()->get($warData->getDeclarationFactionId());
           Main::getInstance()->getLogger()->debug("{$factionData->getName()}の戦争が開始");
        }
    }
}