<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\Air;
use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class BackupLoadCommand extends Command
{
    public function __construct()
    {
        parent::__construct("backupload", "バックアップをロードする", "/backupload", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        try {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§a[システム] このコマンドはサーバー内で実行してください");
                return;
            }

            $rawData = file_get_contents(Main::getInstance()->getDataFolder() . "test.owb");
            $nbt = new BigEndianNbtSerializer();
            $decompressed = zlib_decode($rawData);
            $Data = $nbt->read($decompressed)->mustGetCompoundTag();
            /** @var CompoundTag $dataTag */
            $dataTag = $Data->getTag("Data");
            foreach ($dataTag->getValue() as $value) {
                if ($value instanceof CompoundTag) {
                    $block = BlockFactory::getInstance()->get($value->getInt("id"), $value->getInt("meta"));
                    $oldblock = $sender->getWorld()->getBlockAt($value->getInt("x"), $value->getInt("y"), $value->getInt("z"));
                    if ($block instanceof Air and $oldblock instanceof Air) continue;
                    $sender->getWorld()->setBlockAt($value->getInt("x"), $value->getInt("y"), $value->getInt("z"), $block);
                }
            }

            /*
            foreach (ChunkDataManager::getInstance()->getChunkDatas() as $chunkData) {
                $block = BlockFactory::getInstance()->get($chunkData->getBlockid(), $chunkData->getMeta());
                $oldblock = $sender->getWorld()->getBlockAt($chunkData->getX(), $chunkData->getY(), $chunkData->getZ());
                if ($block instanceof Air and $oldblock instanceof Air) continue;
                $sender->getWorld()->setBlockAt($chunkData->getX(), $chunkData->getY(), $chunkData->getZ(), $block);
            }
            */

            $sender->sendMessage("全ては何事もなかったかのように");
        } catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $sender);
        }
    }
}