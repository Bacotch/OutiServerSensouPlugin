<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\role;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleData;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\forms\faction\FactionForm;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\utilitys\OutiServerPluginUtils;
use pocketmine\player\Player;
use Vecnavium\FormsUI\ModalForm;
use Vecnavium\FormsUI\SimpleForm;

/**
 * ロール詳細表示フォーム
 */
final class RoleInfoForm
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
                        $form = new FactionForm();
                        $form->execute($player);
                    }
                    else {
                        $this->info($player, $factionRoles[$data - 1]);
                    }
                }
                catch (Error | Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
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
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }

    private function info(Player $player, RoleData $infoRoleData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === true) {
                        $this->execute($player);
                    }
                }
                catch (Error | Exception $error) {
                    Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
                }

                return true;
            });

            $form->setTitle("役職 {$infoRoleData->getName()} の詳細");
            $form->setContent("役職名: {$infoRoleData->getName()}\n役職カラー: " . OutiServerPluginUtils::getChatColor($infoRoleData->getColor()) . OutiServerPluginUtils::getChatString($infoRoleData->getColor()) . "\n\n§f宣戦布告権限: " . ($infoRoleData->isSensenHukoku() ? '§bある' : '§cない') . "\n\n§f派閥にプレイヤー招待権限: " . ($infoRoleData->isInvitePlayer() ? '§bある' : '§cない') . "\n\n§f派閥プレイヤー全員に一括でメール送信権限: " . ($infoRoleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f敵対派閥と友好派閥（制限あり）の設定権限: " . ($infoRoleData->isSendmailAllFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥からプレイヤーを追放権限: " . ($infoRoleData->isKickFactionPlayer() ? '§bある' : '§cない') . "\n\n§f派閥の土地管理権限: " . ($infoRoleData->isLandManager() ? '§bある' : '§cない') . "\n\n§f派閥銀行管理権限: " . ($infoRoleData->isBankManager() ? '§bある' : '§cない') . "\n\n§f派閥ロール管理権限: " . ($infoRoleData->isRoleManager() ? '§bある' : '§cない'));
            $form->setButton1("戻る");
            $form->setButton2("閉じる");
            $player->sendForm($form);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
