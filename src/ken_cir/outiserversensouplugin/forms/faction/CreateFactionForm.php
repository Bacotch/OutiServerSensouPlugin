<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

/**
 * 派閥作成フォーム
 */
class CreateFactionForm
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
            // 既に派閥所属済みの場合は
            if ($player_data->getFaction() !== -1) {
                $player->sendMessage("§cあなたは既に派閥 {$player_data->getFaction()} に所属しています");
                return;
            }

            $form = new CustomForm(function (Player $player, $data) use ($player_data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0])) {
                        $player->sendMessage("§a[システム] 派閥名を空にすることはできません");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                    } else {
                        $id = FactionDataManager::getInstance()->create($data[0], $player->getXuid(), (int)$data[1]);
                        $player_data->setFaction($id);
                        $player->sendMessage("§a[システム]派閥 $data[0] を作成しました");
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥作成フォーム");
            $form->addInput("§a派閥名§c", "name");
            $form->addDropdown("§e派閥チャットカラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"]);
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
