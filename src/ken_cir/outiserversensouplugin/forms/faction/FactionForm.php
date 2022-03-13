<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;


use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\forms\faction\land\LandManagerForm;
use ken_cir\outiserversensouplugin\forms\faction\money\FactionMoneyManagerForm;
use ken_cir\outiserversensouplugin\forms\faction\role\RoleInfoForm;
use ken_cir\outiserversensouplugin\forms\faction\role\RoleManagerForm;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

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
                    }
                    else {
                        if ($player_data->getFaction() === -1) {
                            if ($data === 1) {
                                $form = new CreateFactionForm();
                                $form->execute($player);
                                return true;
                            }

                            (new FactionJoinForm())->execute($player, FactionDataManager::getInstance()->getInvite($player->getXuid())[$data - 2]);
                        } else {
                            if ($data === 1) {
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    $form = new DeleteFactionForm();
                                } // それ以外は脱退に飛ばす
                                else {
                                    $form = new LeaveFactionForm();
                                }
                                $form->execute($player);
                            }
                            elseif ($data === 2) {
                                $form = new FactionInfoForm();
                                $form->execute($player);
                            }
                            elseif ($data === 3) {
                                $form = new MyInfoForm();
                                $form->execute($player);
                            }
                            elseif ($data === 4) {
                                $form = new ChangeChatModeForm();
                                $form->execute($player);
                            }
                            elseif ($data === 5) {
                                $form = new RoleInfoForm();
                                $form->execute($player);
                            }
                            elseif ($data === 6) {
                                // 役職管理権限があるなら役職管理フォームに飛ばす
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    $form = new RoleManagerForm();
                                    $form->execute($player);
                                } elseif ($player_data->isRoleManager()) {
                                    $form = new RoleManagerForm();
                                    $form->execute($player);
                                }
                            }
                            elseif ($data === 7) {
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    $form = new LandManagerForm();
                                    $form->execute($player);
                                } elseif ($player_data->isLandManager()) {
                                    $form = new LandManagerForm();
                                    $form->execute($player);
                                }
                            }
                            elseif ($data === 8) {
                                // 派閥メンバーをkick・派閥メンバーを招待権限があるなら管理フォームに
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    (new MemberManagerForm())->execute($player);
                                } elseif ($player_data->isFactionMenmerManager()) {
                                    (new MemberManagerForm())->execute($player);
                                }
                            }
                            elseif ($data === 9) {
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    (new FactionMoneyManagerForm())->execute($player);
                                } elseif ($player_data->isBankManager()) {
                                    (new FactionMoneyManagerForm())->execute($player);
                                }
                            }
                            elseif ($data === 10) {
                                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                                    (new DeclarationWarForm())->execute($player);
                                } elseif ($player_data->isSensenHukoku()) {
                                    (new DeclarationWarForm())->execute($player);
                                }
                            }
                        }
                    }
                } catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("派閥");
            $form->addButton("戻る");
            // どこの派閥にも所属していないなら
            if ($player_data->getFaction() === -1) {
                $form->addButton("§b派閥の作成");
                foreach (FactionDataManager::getInstance()->getInvite($player->getXuid()) as $factionData) {
                    $form->addButton("§a[招待] §f{$factionData->getName()}");
                }
            }
            else {
                // 所属派閥所有者なら
                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("§c派閥の削除");
                }
                else {
                    $form->addButton("§e派閥から脱退");
                }
                $form->addButton("§d派閥の詳細表示");
                $form->addButton("自分の詳細表示");
                $form->addButton("§eチャットモード変更");
                $form->addButton("役職の詳細表示");

                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("§3役職の管理");
                }
                elseif ($player_data->isRoleManager()) {
                    $form->addButton("§3役職の管理");
                }

                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("土地の管理");
                }
                elseif ($player_data->isLandManager()) {
                    $form->addButton("土地の管理");
                }

                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("派閥メンバーの管理");
                }
                elseif ($player_data->isFactionMenmerManager()) {
                    $form->addButton("派閥メンバーの管理");
                }

                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("派閥金庫");
                }
                elseif ($player_data->isBankManager()) {
                    $form->addButton("派閥金庫");
                }

                if ($faction_data->getOwnerXuid() === $player_data->getXuid()) {
                    $form->addButton("宣戦布告");
                }
                elseif ($player_data->isSensenHukoku()) {
                    $form->addButton("宣戦布告");
                }
            }
            $player->sendForm($form);
        } catch (\Error|\Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $player);
        }
    }
}
