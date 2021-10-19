<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use DateTime;
use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\ModalForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\Player;
use pocketmine\Server;

/**
 * 派閥削除フォーム
 */
final class DeleteFactionForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player)
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $form = new ModalForm(function(Player $player, $data) use ($player_data) {
                if ($data === true) {
                    $faction_name = $player_data->getFaction();
                    $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($faction_name);
                    $time = new DateTime('now');
                    foreach ($faction_players as $faction_player) {
                        MailManager::getInstance()->create(
                            $faction_player->getName(),
                            "派閥崩壊通知",
                            "所属派閥 $faction_name が {$time->format("Y年m月d日 H時i分")} に崩壊しました",
                            "システム",
                            $time->format("Y年m月d日 H時i分")
                        );
                        $faction_player->setFaction("");
                        $faction_player->save();
                    }
                    FactionDataManager::getInstance()->delete($player_data->getFaction());
                    $player->sendMessage("§a[システム] 派閥 $faction_name を削除しました");
                    Server::getInstance()->broadcastMessage("§a[システム] 派閥 $faction_name が崩壊しました");
                    Main::getInstance()->getDiscordClient()->sendChatMessage("[システム] 派閥 $faction_name が崩壊しました");
                }
            });

            $form->setTitle("派閥 {$player_data->getFaction()} の削除");
            $form->setContent("§6 派閥 {$player_data->getFaction()} を削除してもよろしいですか？\n削除してしまったら復元できません");
            $form->setButton1("はい");
            $form->setButton2("いいえ");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}