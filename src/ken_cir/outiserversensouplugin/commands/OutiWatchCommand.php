<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * おうちウォッチフォームを出すコマンド
 */
final class OutiWatchCommand extends Command
{
    public function __construct()
    {
        parent::__construct("outiwatch", "おうちウォッチフォームを出すコマンド", "/outiwatch", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        try {
            if (!$sender instanceof Player) {
                $sender->sendMessage("§a[システム] このコマンドはサーバー内で実行してください");
                return;
            }

            $form = new OutiWatchForm();
            $form->execute($sender);
            PlayerCacheManager::getInstance()->getXuid($sender->getXuid())->setLockOutiWatch(true);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, true, $sender);
        }
    }
}