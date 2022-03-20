<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use CortexPE\Commando\BaseCommand;
use ken_cir\pmmpoutiserverbot\PMMPOutiServerBot;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

class ReConnectCommand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "reconnect", "DiscordBotを再接続する", []);
    }

    protected function prepare(): void
    {
        $this->setPermission("outiserver.op");
        $this->setPermissionMessage("このコマンドはOPのみ使用可能");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        PMMPOutiServerBot::getInstance()->getDiscordBotThread()->reConnect();
        $sender->sendMessage("§a[システム] Botの再接続リクエストを送信しました");
    }
}