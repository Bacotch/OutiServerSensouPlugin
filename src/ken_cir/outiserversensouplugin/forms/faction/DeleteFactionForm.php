<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use jojoe77777\FormAPI\ModalForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\pmmpoutiserverbot\PMMPOutiServerBot;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * 派閥削除フォーム
 */
class DeleteFactionForm
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
                        FactionDataManager::getInstance()->delete($factionId);
                        $player->sendMessage("§a[システム] 派閥 $faction_name を削除しました");
                        Server::getInstance()->broadcastMessage("§[システム] 派閥 $faction_name が崩壊しました");
                        PMMPOutiServerBot::getInstance()->getDiscordBotThread()->sendChatMessage("[システム] 派閥 $faction_name が崩壊しました");
                    }
                } catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("派閥 {$player_data->getFaction()} の削除");
            $form->setContent("§6 派閥 {$faction_data->getName()} を削除してもよろしいですか？\n削除してしまったら復元できません");
            $form->setButton1("はい");
            $form->setButton2("いいえ");
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
