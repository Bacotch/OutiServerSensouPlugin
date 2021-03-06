<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;


use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\utilitys\OutiServerUtilitys;
use pocketmine\player\Player;

/**
 * 派閥詳細表示フォーム
 */
class FactionInfoForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * 実行
     */
    public function execute(Player $player): void
    {
        try {
            $player_data = PlayerDataManager::getInstance()->getXuid($player->getXuid());
            $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());

            $form = new SimpleForm(function (Player $player, $data) use ($faction_data) {
                try {
                    if ($data === null) return true;
                    $this->Info($player, $faction_data);
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§b派閥の詳細表示フォーム");
            $form->addButton(OutiServerUtilitys::getChatColor($faction_data->getColor()) . "{$faction_data->getName()}");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }

    private function Info(Player $player, FactionData $faction_data): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                } catch (\Error|\Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($faction_data->getId());
            $faction_players_name = array_map(function (PlayerData $playerData) {
                return $playerData->getName();
            }, $faction_players);
            $color = OutiServerUtilitys::getChatColor($faction_data->getColor());
            $form->setTitle("派閥 $color {$faction_data->getName()} の詳細");
            $form->setContent("§6 派閥名: {$faction_data->getName()}\n金庫: {$faction_data->getSafe()}円\n資金: {$faction_data->getMoney()}円\n§aリーダー: " . PlayerDataManager::getInstance()->getXuid($faction_data->getOwnerXuid())->getName() . "\n§d総人数: " . count($faction_players) . "人\n§b派閥所属プレイヤー§f\n" . join("\n", $faction_players_name));
            $form->setButton1("戻る");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
