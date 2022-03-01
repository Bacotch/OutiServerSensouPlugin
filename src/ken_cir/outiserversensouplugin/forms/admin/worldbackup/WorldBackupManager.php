<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\worldbackup;


use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\forms\admin\AdminForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\BaseSign;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;

class WorldBackupManager
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            try {
                if ($data === null) return true;
                elseif ($data === 0) {
                    $form = new AdminForm();
                    $form->execute($player);
                } elseif ($data === 1 and PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getWorldBackupWorldName() === null) {
                    PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setWorldBackupWorldName($player->getWorld()->getFolderName());
                    PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setWorldBackupStartX($player->getPosition()->getFloorX());
                    PlayerCacheManager::getInstance()->getXuid($player->getXuid())->setWorldBackupStartZ($player->getPosition()->getFloorZ());
                    $player->sendMessage("§a[システム] 開始X座標を{$player->getPosition()->getFloorX()}\n開始Z座標を{$player->getPosition()->getFloorZ()}に設定しました");
                } elseif ($data === 1) {
                    $startX = PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getWorldBackupStartX();
                    $endX = $player->getPosition()->getFloorX();
                    $startZ = PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getWorldBackupStartZ();
                    $endZ = $player->getPosition()->getFloorZ();
                    if ($startX > $endX) {
                        $backup = $startX;
                        $startX = $endX;
                        $endX = $backup;
                    }
                    if ($startZ > $endZ) {
                        $backup = $startZ;
                        $startZ = $endZ;
                        $endZ = $backup;
                    }

                    $tag = new CompoundTag();
                    $tag->setInt("version", 1);
                    $tag->setString("worldName", $player->getWorld()->getFolderName());

                    for ($y = $player->getWorld()->getMinY(); $y < $player->getWorld()->getMaxY(); $y++) {

                        for ($x = $startX; $x < $endX; $x++) {
                            for ($z = $startZ; $z < $endZ; $z++) {
                                $oldBlock = $player->getWorld()->getBlockAt($x, $y, $z, addToCache: false);

                                //At the moment support sign conversion only from java to bedrock
                                if ($oldBlock instanceof BaseSign) {
                                    continue;
                                } else {
                                    $oldId = $oldBlock->getId();
                                    $oldMeta = $oldBlock->getMeta();
                                    $tag->setTag("{$player->getWorld()->getFolderName()}-$x-$y-$z", (new CompoundTag())
                                        ->setInt("x", $x)
                                        ->setInt("y", $y)
                                        ->setInt("z", $z)
                                        ->setInt("id", $oldId)
                                        ->setInt("meta", $oldMeta)
                                    );
                                }
                            }
                        }
                    }

                    $nbt = new BigEndianNbtSerializer();
                    $buffer = zlib_encode($nbt->write(new TreeRoot(CompoundTag::create()->setTag("Data", $tag))), ZLIB_ENCODING_GZIP);
                    file_put_contents(Main::getInstance()->getDataFolder() . "test.owb", $buffer);

                    $player->sendMessage("終了");
                }
            } catch (\Error|\Exception $e) {
                Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
            }

            return true;
        });
        $form->setTitle("ワールドバックアップの管理");
        $form->addButton("戻る");
        if (PlayerCacheManager::getInstance()->getXuid($player->getXuid())->getWorldBackupWorldName() === null) {
            $form->addButton("バックアップの開始座標の設定");
        } else {
            $form->addButton("バックアップの終了座標の設定");
        }
        $player->sendForm($form);
    }
}