<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin;

use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\Air;
use pocketmine\block\BlockFactory;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;
use function glob;
use function file_get_contents;
use function zlib_decode;

class BackupLoadForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return;
                    elseif ($data === 0) {
                        (new AdminForm())->execute($player);
                        return;
                    }

                    $factionData = FactionDataManager::getInstance()->getAll(true)[$data - 1];
                    $files = glob(Main::getInstance()->getDataFolder() . "worldBackups/{$factionData->getId()}/*.owb");
                    foreach ($files as $file) {
                        $rawData = file_get_contents($file);
                        $nbt = new BigEndianNbtSerializer();
                        $decompressed = zlib_decode($rawData);
                        $Data = $nbt->read($decompressed)->mustGetCompoundTag();
                        /** @var CompoundTag $dataTag */
                        $dataTag = $Data->getTag("Data");
                        $world = Server::getInstance()->getWorldManager()->getWorldByName($dataTag->getString("worldName"));
                        foreach ($dataTag->getValue() as $value) {
                            if ($value instanceof CompoundTag) {
                                $block = BlockFactory::getInstance()->get($value->getInt("id"), $value->getInt("meta"));
                                $oldblock = $world->getBlockAt($value->getInt("x"), $value->getInt("y"), $value->getInt("z"));
                                if ($block instanceof Air and $oldblock instanceof Air) continue;
                                $world->setBlockAt($value->getInt("x"), $value->getInt("y"), $value->getInt("z"), $block);
                            }
                        }
                    }

                    $player->sendMessage("§a[システム] バックアップを復元しました");
                }
                catch (\Error|\Exception $exception) {
                    Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
                }
            });

            $form->setTitle("ワールドバックアップ復元");
            $form->addButton("戻る");
            foreach (FactionDataManager::getInstance()->getAll() as $factionData) {
                $form->addButton($factionData->getName());
            }
            $player->sendForm($form);
        }
        catch (\Error|\Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}