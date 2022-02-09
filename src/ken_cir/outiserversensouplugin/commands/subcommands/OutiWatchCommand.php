<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands\subcommands;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

/**
 * おうちウォッチフォームを出すコマンド
 */
final class OutiWatchCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct("outiwatch", "おうちウォッチフォームを出すコマンド", []);
    }

    protected function prepare(): void
    {
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
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