<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms\Faction\Role;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\ModalForm;
use Ken_Cir\OutiServerSensouPlugin\libs\jojoe77777\FormAPI\SimpleForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleData;
use Ken_Cir\OutiServerSensouPlugin\Managers\RoleData\RoleDataManager;
use Ken_Cir\OutiServerSensouPlugin\Utils\OutiServerPluginUtils;
use pocketmine\Player;

/**
 * ロール詳細表示フォーム
 */
class RoleInfoForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $factionRoles = array_values(RoleDataManager::getInstance()->getFactionRoles($playerData->getFaction()));
            $form = new SimpleForm(function (Player $player, $data) use ($factionRoles) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new RoleManagerForm();
                        $form->execute($player);
                    }
                    else {
                        $this->info($player, $factionRoles[$data - 1]);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職詳細表フォーム");
            $form->setContent("詳細を表示する役職を選択してください");
            $form->addButton("戻る");
            foreach ($factionRoles as $factionRole) {
                $form->addButton(OutiServerPluginUtils::getChatColor($factionRole->getColor()) . $factionRole->getName());
            }
            $player->sendForm($form);
        }
        catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }

    private function info(Player $player, RoleData $infoRoleData): void
    {
        try {
            $playerData = PlayerDataManager::getInstance()->get($player->getName());
            $form = new ModalForm(function(Player $player, $data){
                if ($data === true) {
                    $this->execute($player);
                }
            });

            $form->setTitle("役職 {$infoRoleData->getName()} の詳細");
            $form->setContent("役職名: {$infoRoleData->getName()}\n役職カラー: " . OutiServerPluginUtils::getChatColor($infoRoleData->getColor()) . OutiServerPluginUtils::getChatString($infoRoleData->getColor()) . "\n\n§f宣戦布告権限: " . ($infoRoleData->isSensenHukoku() ? '§bある' : '§cない') . "\n\n§f派閥にプレイヤー招待権限: " . ($infoRoleData->isInvitePlayer() ? '§bある' : '§cない') . "\n\n§f派閥プレイヤー全員に一括でメール送信権限: " . ($infoRoleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f敵対派閥と友好派閥（制限あり）の設定権限: " . ($infoRoleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥からプレイヤーを追放権限: " . ($infoRoleData->isKickFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥の土地管理権限: " . ($infoRoleData->isLandManager() ? '§bある' : '§cない') . "\n\n§f派閥銀行管理権限: " . ($infoRoleData->isBankManager() ? '§bある' : '§cない') . "\n\n§f派閥ロール管理権限: " . ($infoRoleData->isRoleManager() ? '§bある' : '§cない'));
            $form->setButton1("戻る");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}