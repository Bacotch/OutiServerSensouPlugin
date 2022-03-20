<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use CortexPE\Commando\BaseCommand;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\block\BaseSign;
use pocketmine\command\CommandSender;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use function file_exists;
use function mkdir;
use function zlib_encode;
use function file_put_contents;

class WorldBackupCommand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "worldbackup", "派閥の土地をバックアップする", []);
    }

    protected function prepare(): void
    {
        $this->setPermission("outiserver.op");
        $this->setPermissionMessage("このコマンドはOPのみ使用可能");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        foreach (FactionDataManager::getInstance()->getAll() as $factionData) {
            if (!file_exists(Main::getInstance()->getDataFolder() . "worldBackups/{$factionData->getId()}/")) {
                mkdir(Main::getInstance()->getDataFolder() . "worldBackups/{$factionData->getId()}/");
            }

            foreach (LandDataManager::getInstance()->getFactionLands($factionData->getId()) as $factionLand) {
                $world = Server::getInstance()->getWorldManager()->getWorldByName($factionLand->getWorld());
                $tag = new CompoundTag();
                $tag->setInt("version", 1);
                $tag->setString("worldName", $world->getFolderName());

                for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                    for ($x = ($factionLand->getX() << 4); $x < (($factionLand->getX() << 4) + 16); $x++) {
                        for ($z = ($factionLand->getZ() << 4); $z < (($factionLand->getZ() << 4) + 16); $z++) {
                            $block = $world->getBlockAt($x, $y, $z);

                            if ($block instanceof BaseSign) continue;
                            else {
                                $tag->setTag("{$world->getFolderName()}-$x-$y-$z", (new CompoundTag())
                                    ->setInt("x", $x)
                                    ->setInt("y", $y)
                                    ->setInt("z", $z)
                                    ->setInt("id", $block->getId())
                                    ->setInt("meta",  $block->getMeta())
                                );
                            }
                        }
                    }
                }

                $nbt = new BigEndianNbtSerializer();
                $buffer = zlib_encode($nbt->write(new TreeRoot(CompoundTag::create()->setTag("Data", $tag))), ZLIB_ENCODING_GZIP);
                file_put_contents(Main::getInstance()->getDataFolder() . "worldBackups/{$factionData->getId()}/{$factionLand->getId()}-{$factionLand->getWorld()}-{$factionLand->getX()}-{$factionLand->getZ()}.owb", $buffer);
            }
        }

        $sender->sendMessage("§a[システム] バックアップしました");
    }
}