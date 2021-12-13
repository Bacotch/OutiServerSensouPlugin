<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionData;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerData;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;

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
    public function execute(Player $player)
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());

            $form = new SimpleForm(function (Player $player, $data) use ($faction_data) {
                try {
                    if ($data === null) return true;
                    $this->Info($player, $faction_data);
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e);
                }

                return true;
            });

            $form->setTitle("§b派閥の詳細表示フォーム");
            $form->addButton(OutiServerPluginUtils::getChatColor($faction_data->getColor()) . "{$faction_data->getName()}");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    private function Info(Player $player, FactionData $faction_data)
    {
        try {
            $form = new ModalForm(function(Player $player, $data){
            });

            $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($faction_data->getId());
            $faction_players_name = array_map(function (PlayerData $playerData) {
                return $playerData->getName();
            }, $faction_players);
            $color = OutiServerPluginUtils::getChatColor($faction_data->getColor());
            $form->setTitle("派閥 $color {$faction_data->getName()} の詳細");
            $form->setContent("§6 派閥名: {$faction_data->getName()}\n§aリーダー: {$faction_data->getOwner()}\n§d総人数: " . count($faction_players) . "人\n§b派閥所属プレイヤー§f\n" . join("\n", $faction_players_name));
            $form->setButton1("閉じる");
            $form->setButton1("閉じる");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}