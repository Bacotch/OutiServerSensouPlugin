<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;
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
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionData = FactionDataManager::getInstance()->get($playerData->getFaction());
            $form = new ModalForm(function (Player $player, $data) {
            });

            $roles = array_map(function (int $id) {
                $roleData = RoleDataManager::getInstance()->get($id);
                if (!$roleData) return "データが見つからない";
                $color = OutiServerPluginUtils::getChatColor($roleData->getColor());
                return "$color {$roleData->getName()}";
            }, $playerData->getRoles());
            $form->setTitle("自分の詳細");
            $form->setContent("所属派閥: {$factionData->getName()}\n\n所持役職:\n" . join("\n", $roles));
            $form->setButton1("閉じる");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}
