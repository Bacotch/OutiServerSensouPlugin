<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use DateTime;
use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\LandData\LandDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailDataManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use pocketmine\Server;
use Vecnavium\FormsUI\ModalForm;

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
    public function execute(Player $player): void
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());
            $form = new ModalForm(function (Player $player, $data) use ($faction_data, $player_data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === true) {
                        $faction_name = $faction_data->getName();
                        $factionId = $player_data->getFaction();
                        $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction());
                        $time = new DateTime('now');
                        foreach ($faction_players as $faction_player) {
                            MailDataManager::getInstance()->create(
                                $faction_player->getName(),
                                "派閥崩壊通知",
                                "所属派閥 $faction_name が {$time->format("Y年m月d日 H時i分")} に崩壊しました",
                                "システム",
                                $time->format("Y年m月d日 H時i分")
                            );
                            $faction_player->setFaction(-1);
                        }
                        LandDataManager::getInstance()->deleteFaction($factionId);
                        FactionDataManager::getInstance()->delete($factionId);
                        $player->sendMessage("§a[システム] 派閥 $faction_name を削除しました");
                        Server::getInstance()->broadcastMessage("§a[システム] 派閥 $faction_name が崩壊しました");
                        Main::getInstance()->getDiscordClient()->sendChatMessage("[システム] 派閥 $faction_name が崩壊しました");
                    }
                }
                catch (Error | Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("派閥 {$player_data->getFaction()} の削除");
            $form->setContent("§6 派閥 {$faction_data->getName()} を削除してもよろしいですか？\n削除してしまったら復元できません");
            $form->setButton1("はい");
            $form->setButton2("いいえ");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
