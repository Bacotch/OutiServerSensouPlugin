<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\Forms\OutiWatchForm;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\SimpleForm;
use Ken_Cir\OutiServerSensouPlugin\Main;

use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\Player;

/**
 * 派閥関係フォーム
 */
final class FactionForm
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
            $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());
            $form = new SimpleForm(function (Player $player, $data) use ($player_data, $faction_data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new OutiWatchForm();
                        $form->execute($player);
                    }
                    elseif ($data === 1) {
                        // どこにも所属していない時は作成に飛ばす
                        if ($player_data->getFaction() === -1) {
                            $form = new CreateFactionForm();
                        }
                        // 所属していてリーダーなら削除に飛ばす
                        elseif ($faction_data->getOwner() === $player_data->getName()) {
                           $form = new DeleteFactionForm();
                        }
                        // それ以外は脱退に飛ばす
                        else {
                            $form = new LeaveFactionForm();
                        }
                        $form->execute($player);
                    }
                    elseif ($data === 2) {
                        // どこかに所属しているなら詳細表示フォームに飛ばす
                        if ($player_data->getFaction() !== -1) {
                            $form = new FactionInfoForm();
                            $form->execute($player);
                        }
                    }
                    elseif ($data === 3) {
                        // どこかに所属しているならチャットモード変更フォームに飛ばす
                        if ($player_data->getFaction() !== -1) {
                            $form = new ChangeChatModeForm();
                            $form->execute($player);
                        }
                    }
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("派閥");
            $form->addButton("戻る");
            if ($player_data->getFaction() === -1) {
                $form->addButton("§b派閥の作成");
            }
            else {
                if ($faction_data->getOwner() === $player_data->getName()) {
                    $form->addButton("§c派閥の削除");
                } else {
                    $form->addButton("§e派閥から脱退");
                }
                $form->addButton("§d派閥の詳細表示");
                $form->addButton("§eチャットモード変更");
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error, $player);
        }
    }
}