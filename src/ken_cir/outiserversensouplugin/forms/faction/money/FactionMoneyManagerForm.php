<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\money;

use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\forms\faction\FactionForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

class FactionMoneyManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());

            $form = new ModalForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        (new FactionForm())->execute($player);
                    }
                    elseif ($data === false) {
                        (new FactionMoneyOperationForm())->execute($player, $factionData);
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $form->setTitle("派閥金庫");
            $form->setContent("金庫にあるお金: {$factionData->getSafe()}\n派閥資金: {$factionData->getMoney()}");
            $form->setButton1("戻る");
            $form->setButton2("金庫操作");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}