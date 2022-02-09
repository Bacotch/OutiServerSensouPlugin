<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands\subcommands;

use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function mt_rand;

final class DiscordCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct("discord", "Discordアカウントと連携します", []);
    }

    protected function prepare(): void
    {
        // TODO: Implement prepare() method.
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§a[システム] このコマンドはサーバー内で実行してください");
            return;
        }

        $code = mt_rand(10000, 99999);
        PlayerCacheManager::getInstance()->getXuid($sender->getXuid())->setDiscordVerifyCode($code);
        PlayerCacheManager::getInstance()->getXuid($sender->getXuid())->setDiscordverifycodeTime(time());

        $sender->sendMessage("§a[システム] あなたの一時認証コードは §b$code §aです、このコードは10分間有効です");
    }
}