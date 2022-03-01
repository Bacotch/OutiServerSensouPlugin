<?php

namespace ken_cir\outiserversensouplugin\commands;

use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\Server;

class BanAllCOmmand extends Command
{
    public function __construct()
    {
        parent::__construct("banall");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        $playerData = PlayerDataManager::getInstance()->getName($args[0]);
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), new Language("jpn")), "ban {$playerData->getName()}");
        foreach ($playerData->getIp() as $ip) {
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), new Language("jpn")), "ban-ip $ip");
        }

        $sender->sendMessage("{$playerData->getName()}のBan処理が完了しました");
    }
}