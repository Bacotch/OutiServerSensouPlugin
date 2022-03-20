<?php

namespace ken_cir\outiserversensouplugin\commands;

use CortexPE\Commando\BaseCommand;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\lang\Language;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class BanAllCOmmand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "banall", "IP含めBan", []);
    }

    protected function prepare(): void
    {
        $this->setPermission("outiserver.op");
        $this->setPermissionMessage("このコマンドはOPのみ使用可能");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $playerData = PlayerDataManager::getInstance()->getName($args[0]);
        if (!$playerData) {
            $sender->sendMessage("§a[システム] その名前のプレイヤーは存在しません");
            return;
        }

        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), new Language("jpn")), "ban {$playerData->getName()}");
        foreach ($playerData->getIp() as $ip) {
            Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), new Language("jpn")), "ban-ip $ip");
        }

        $sender->sendMessage("§a[システム]  {$playerData->getName()}のBan処理が完了しました");
    }
}