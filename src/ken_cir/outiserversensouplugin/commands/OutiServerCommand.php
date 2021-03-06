<?php

namespace ken_cir\outiserversensouplugin\commands;

use CortexPE\Commando\BaseCommand;
use ken_cir\outiserversensouplugin\commands\subcommands\DiscordCommand;
use ken_cir\outiserversensouplugin\commands\subcommands\OutiWatchCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;

class OutiServerCommand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "outiserver", "おうち鯖コマンド", ["ouc", "outi"]);
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new OutiWatchCommand());
        $this->registerSubCommand(new DiscordCommand());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        // TODO: Implement onRun() method.
    }
}