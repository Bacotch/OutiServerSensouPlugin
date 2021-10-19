<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\CustomForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\Player;

/**
 * チャットモード変更フォーム
 */
final class ChangeChatModeForm
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
            $form = new CustomForm(function (Player $player, $data) {
                try {
                    $player_data = PlayerDataManager::getInstance()->get($player->getName());
                    if ($data === null) return true;
                    elseif ($data[0] === 0) {
                        $player_data->setChatmode(-1);
                        $player->sendMessage("§a[システム] チャットモードを§f全体§aに変更しました");
                    }
                    elseif ($data[0] === 1) {
                        $player_data->setChatmode($player_data->getFaction());
                        $player->sendMessage("§a[システム] チャットモードを§f所属派閥と友好関係派閥§aに変更しました");
                    }
                    $player_data->save();
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e);
                }

                return true;
            });

            $form->setTitle("チャットモード変更");
            $form->addDropdown("モード", ["全体", "所属派閥と友好関係派閥"]);
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}