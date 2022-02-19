<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionData;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandData;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\Main;
use ken_cir\outiserversensouplugin\tasks\ReturnForm;
use pocketmine\player\Player;

class LandDatabaseForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $landData = LandDataManager::getInstance()->getChunk($player->getPosition()->getFloorX() >> 4, $player->getPosition()->getFloorZ() >> 4, $player->getWorld()->getFolderName());
            if ($landData) {
                $this->viewLandData($player, $landData);
                return;
            }

            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        (new DatabaseManagerForm())->execute($player);
                        return;
                    }

                    LandDataManager::getInstance()->create(FactionDataManager::getInstance()->getAll(true)[$data[2]]->getId(),
                    $player->getPosition()->getFloorX()  >> 4,
                        $player->getPosition()->getFloorZ()  >> 4,
                    $player->getWorld()->getFolderName());
                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new DatabaseManagerForm(), "execute"], [$player]), 20);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $factionDatas = array_map(function (FactionData $factionData) {
                return $factionData->getName();
            }, FactionDataManager::getInstance()->getAll(true));

            $form->setTitle("土地データ 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addLabel("この土地はどの派閥も所有してないようです、所有させる派閥を選択してください");
            $form->addDropdown("所有させる派閥", $factionDatas);
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function viewLandData(Player $player, LandData $landData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) use ($landData) {
                try {
                    if ($data === null) return;
                    elseif ($data === true) {
                        (new DatabaseManagerForm())->execute($player);
                    }
                    elseif ($data === false) {
                        $this->editLandData($player, $landData);
                    }
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("土地データ #{$landData->getId()}");
            $form->setContent("土地ID: {$landData->getId()}\nワールド名: {$landData->getWorld()}\n開始X座標: " . $landData->getX() << 4 . "\n開始Z座標: " . $landData->getZ() << 4 . "\n終了X座標: " . ($landData->getX() << 4) + 15 . "\n終了Z座標" . ($landData->getZ() << 4) + 15);
            $form->setButton1("戻る");
            $form->setButton2("編集");
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }

    private function editLandData(Player $player, LandData $landData): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) use ($landData) {
                try {
                    if ($data === null) return;
                    elseif ($data[0]) {
                        $this->viewLandData($player, $landData);
                        return;
                    }
                    elseif ($data[1]) {
                        LandDataManager::getInstance()->delete($landData->getId());
                        $player->sendMessage("§a[システム] 削除しました");
                        Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new DatabaseManagerForm(), "execute"], [$player]), 20);
                        return;
                    }

                    $landData->setFactionId(FactionDataManager::getInstance()->getAll(true)[$data[2]]->getId());
                    $player->sendMessage("§a[システム] 変更しました");
                    Main::getInstance()->getScheduler()->scheduleDelayedTask(new ReturnForm([new DatabaseManagerForm(), "execute"], [$player]), 20);
                }
                catch (\Error | \Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $factionDefault = 0;
            $factionDatas = FactionDataManager::getInstance()->getAll(true);
            foreach ($factionDatas as $key => $factionData) {
                if ($factionData->getId() === $landData->getFactionId()) {
                    $factionDefault = $key;
                    break;
                }
            }
            $factionDatas = array_map(function (FactionData $factionData) {
                return $factionData->getName();
            }, FactionDataManager::getInstance()->getAll(true));

            $form->setTitle("土地データ #{$landData->getId()} 編集");
            $form->addToggle("キャンセルして戻る");
            $form->addToggle("削除して戻る");
            $form->addDropdown("所有派閥", $factionDatas, $factionDefault);
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}