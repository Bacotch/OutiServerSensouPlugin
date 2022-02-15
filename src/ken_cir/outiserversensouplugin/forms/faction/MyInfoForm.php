<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use pocketmine\player\Player;
use jojoe77777\FormAPI\ModalForm;
use function array_map;
use function join;

class MyInfoForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
            $form = new ModalForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === true) {
                        $form = new FactionForm();
                        $form->execute($player);
                    }
                } catch (Error|Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });
            $roles = array_map(function (int $id) {
                $roleData = RoleDataManager::getInstance()->get($id);
                $color = OutiServerUtilitys::getChatColor($roleData->getColor());
                return "$color {$roleData->getName()}";
            }, $playerData->getRoles());
            $form->setTitle("自分の詳細");
            $form->setContent("所属派閥: {$factionData->getName()}\n\n所持役職:\n" . join("\n", $roles));
            $form->setButton1("戻る");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
