<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\forms\faction\land\LandManagerForm;
use ken_cir\outiserversensouplugin\forms\faction\role\RoleInfoForm;
use ken_cir\outiserversensouplugin\forms\faction\role\RoleManagerForm;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

/**
 * 派閥関係フォーム
 */
class FactionForm
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
            $form = new SimpleForm(function (Player $player, $data) use ($player_data, $faction_data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new OutiWatchForm();
                        $form->execute($player);
                    } elseif ($data === 1) {
                        // どこにも所属していない時は作成に飛ばす
                        if ($player_data->getFaction() === -1) {
                            $form = new CreateFactionForm();
                        } // 所属していてリーダーなら削除に飛ばす
                        elseif ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                            $form = new DeleteFactionForm();
                        } // それ以外は脱退に飛ばす
                        else {
                            $form = new LeaveFactionForm();
                        }
                        $form->execute($player);
                    } elseif ($data === 2) {
                        // どこかに所属しているなら詳細表示フォームに飛ばす
                        if ($player_data->getFaction() !== -1) {
                            $form = new FactionInfoForm();
                            $form->execute($player);
                        }
                    } elseif ($data === 3) {
                        // どこかに所属しているなら詳細表示フォームに飛ばす
                        if ($player_data->getFaction() !== -1) {
                            $form = new MyInfoForm();
                            $form->execute($player);
                        }
                    } elseif ($data === 4) {
                        // どこかに所属しているならチャットモード変更フォームに飛ばす
                        if ($player_data->getFaction() !== -1) {
                            $form = new ChangeChatModeForm();
                            $form->execute($player);
                        }
                    } elseif ($data === 5) {
                        // どこかに所属している
                        if ($player_data->getFaction() !== -1) {
                            $form = new RoleInfoForm();
                            $form->execute($player);
                        }
                    } elseif ($data === 6) {
                        // どこかに所属している
                        if ($player_data->getFaction() !== -1) {
                            // 役職管理権限があるなら役職管理フォームに飛ばす
                            if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                $form = new RoleManagerForm();
                                $form->execute($player);
                            } elseif ($player_data->isRoleManager()) {
                                $form = new RoleManagerForm();
                                $form->execute($player);
                            } elseif ($player_data->isRoleManager()) {
                                $form = new RoleManagerForm();
                                $form->execute($player);
                            }
                        }
                    } elseif ($data === 7) {
                        // どこかに所属している
                        if ($player_data->getFaction() !== -1) {
                            // 役職管理権限があるなら役職管理フォームに飛ばす
                            if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                $form = new LandManagerForm();
                                $form->execute($player);
                            } elseif ($player_data->isLandManager()) {
                                $form = new LandManagerForm();
                                $form->execute($player);
                            }
                        }
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("派閥");
            $form->addButton("戻る");
            // どこの派閥にも所属していないなら
            if ($player_data->getFaction() === -1) {
                $form->addButton("§b派閥の作成");
            } else {
                // 所属派閥所有者なら
                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("§c派閥の削除");
                } else {
                    $form->addButton("§e派閥から脱退");
                }
                $form->addButton("§d派閥の詳細表示");
                $form->addButton("自分の詳細表示");
                $form->addButton("§eチャットモード変更");
                $form->addButton("役職の詳細表示");
                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("§3役職の管理");
                } elseif ($player_data->isRoleManager()) {
                    $form->addButton("§3役職の管理");
                } elseif ($player_data->isRoleManager()) {
                    $form->addButton("§3役職の管理");
                }
                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("土地の管理");
                } elseif ($player_data->isLandManager()) {
                    $form->addButton("土地の管理");
                }
            }
            $player->sendForm($form);
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
