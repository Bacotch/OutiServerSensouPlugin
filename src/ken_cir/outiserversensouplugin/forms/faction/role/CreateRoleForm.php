<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\role;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\threads\ReturnForm;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;

/**
 * 役職作成フォーム
 */
final class CreateRoleForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            $form = new CustomForm(function (Player $player, $data) use ($player_data) {
                try {
                    if ($data === null) return true;
                    elseif ($data[0] === true) {
                        $form = new RoleManagerForm();
                        $form->execute($player);
                        return true;
                    }
                    elseif (!isset($data[1])) return true;
                    RoleDataManager::getInstance()->create($player_data->getFaction(), $data[1], (int)$data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10]);
                    $player->sendMessage("§a[システム]役職 $data[1] を作成しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([$this, "execute"], [$player]), 10);
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥役職作成フォーム");
            $form->addToggle("キャンセルして戻る");
            $form->addInput("§a役職名§c", "rolename");
            $form->addDropdown("§e役職カラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"]);
            $form->addToggle("宣戦布告権限");
            $form->addToggle("派閥にプレイヤー招待権限");
            $form->addToggle("派閥プレイヤー全員に一括でメール送信権限");
            $form->addToggle("敵対派閥と友好派閥（制限あり）の設定権限");
            $form->addToggle("派閥からプレイヤーを追放権限");
            $form->addToggle("派閥の土地管理権限");
            $form->addToggle("派閥銀行管理権限");
            $form->addToggle("派閥ロール管理権限");
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error);
        }
    }
}
