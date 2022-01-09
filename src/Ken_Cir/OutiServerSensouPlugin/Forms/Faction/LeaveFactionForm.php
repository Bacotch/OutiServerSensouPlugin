<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use DateTime;
use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Database\MailData\MailManager;
use Ken_Cir\OutiServerSensouPlugin\Database\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;

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
    public function execute(Player $player)
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $form = new ModalForm(function (Player $player, $data) use ($player_data) {
                if ($data === true) {
                    $faction_players = PlayerDataManager::getInstance()->getFactionPlayers($player_data->getFaction());
                    $time = new DateTime('now');
                    foreach ($faction_players as $faction_player) {
                        if ($faction_player->getName() === $player_data->getName()) continue;
                        MailManager::getInstance()->create(
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
            });

            $form->setTitle("派閥 {$player_data->getFaction()} から脱退");
            $form->setContent("§6 派閥 {$player_data->getFaction()} から脱退してもよろしいですか？\n脱退してしまったらもう1度招待されるまで入ることはできません");
            $form->setButton1("はい");
            $form->setButton2("いいえ");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }
}
