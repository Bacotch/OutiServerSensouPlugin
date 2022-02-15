<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use DateTime;
use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use jojoe77777\FormAPI\ModalForm;

/**
 * 派閥脱退フォーム
 */
class LeaveFactionForm
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
            $form = new ModalForm(function (Player $player, $data) use ($player_data) {
                try {
                    if ($data === null) return true;
                    else if ($data === true) {
                        $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction());
                        $time = new DateTime('now');
                        foreach ($faction_players as $faction_player) {
                            if ($faction_player->getName() === $player_data->getName()) continue;
                            MailDataManager::getInstance()->create(
                                $faction_player->getName(),
                                "派閥脱退通知",
                                "所属派閥 {$player_data->getFaction()} から {$player->getName()} が脱退しました",
                                "システム",
                                $time->format("Y年m月d日 H時i分")
                            );
                        }

                        $player_data->setFaction(-1);
                        $player->sendMessage("§a[システム] 派閥 {$player_data->getFaction()} から脱退しました");
                    }
                } catch (Error|Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }

                return true;
            });

            $form->setTitle("派閥 {$player_data->getFaction()} から脱退");
            $form->setContent("§6 派閥 {$player_data->getFaction()} から脱退してもよろしいですか？\n脱退してしまったらもう1度招待されるまで入ることはできません");
            $form->setButton1("はい");
            $form->setButton2("いいえ");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
