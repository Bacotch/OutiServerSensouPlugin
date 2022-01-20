<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\database\chunk\ChunkDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\Air;
use pocketmine\block\BlockFactory;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class BackupLoadCommand extends Command
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

            foreach (ChunkDataManager::getInstance()->getChunkDatas() as $chunkData) {
                $block = BlockFactory::getInstance()->get($chunkData->getBlockid(), $chunkData->getMeta());
                $oldblock = $sender->getWorld()->getBlockAt($chunkData->getX(), $chunkData->getY(), $chunkData->getZ());
                if ($block instanceof Air and $oldblock instanceof Air) continue;
                $sender->getWorld()->setBlockAt($chunkData->getX(), $chunkData->getY(), $chunkData->getZ(), $block, false);
            }

            $sender->sendMessage("全ては何事もなかったかのように");
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $sender);
        }
    }
}