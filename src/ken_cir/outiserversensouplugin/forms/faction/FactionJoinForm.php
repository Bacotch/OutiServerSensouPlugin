<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerData;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use function count;
use function join;

class FactionJoinForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, FactionData $factionData): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($factionData) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new FactionForm())->execute($player);
                    }
                    // 承認
                    elseif ($data === 1) {
                        // 招待全部キャンセルする
                        foreach (FactionDataManager::getInstance()->getInvite($player->getXuid()) as $factionData_) {
                            $factionData_->removeInvite($player->getXuid());
                        }

                        $playerData = PlayerDataManager::getInstance()->getXuid($player->getXuid());
                        $playerData->setFaction($factionData->getId());
                        $player->sendMessage("§a[システム] {$factionData->getName()}からの招待を承諾し、加入しました");
                    }
                    // 拒否
                    elseif ($data === 2) {
                        $factionData->removeInvite($player->getXuid());
                        $player->sendMessage("§a[システム] {$factionData->getName()}からの招待を拒否しました");
                    }
                }
                catch (\Error|\Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }
            });

            $factionPlayers = array_map(function (PlayerData $playerData) {
                return $playerData->getName();
            }, PlayerDataManager::getInstance()->getFactionPlayers($factionData->getId()));
            $form->setTitle("派閥招待 {$factionData->getName()}");
            $form->setContent("{$factionData->getName()}から招待されました、この招待を承諾しますか？\n\n派閥の情報\n派閥主: " . PlayerDataManager::getInstance()->getXuid($factionData->getOwnerXuid())->getName() . "\n資金: {$factionData->getMoney()}\n派閥に所属しているメンバー: " . count($factionPlayers) . "\n" . join("\n", $factionPlayers));
            $form->addButton("戻る");
            $form->addButton("§a承諾");
            $form->addButton("§c拒否");
            $player->sendForm($form);
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}