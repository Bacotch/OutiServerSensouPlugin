<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use DateTime;
use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
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
            $player_data = PlayerDataManager::getInstance()->getXuid($player->getXuid());
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
