<?php

namespace ken_cir\outiserversensouplugin\commands;

use ken_cir\outiserversensouplugin\cache\playercache\PlayerCacheManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class DiscordCommand extends Command
{
    public function __construct()
    {
        parent::__construct("discord", "Discordのアカウントと連携する", "/discord", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
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