<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\command\CommandSender;

class DeleteFactionCommand extends CommandBase
{
    public function __construct(Main $plugin, string $name, string $description = "", ?string $usageMessage = null, array $aliases = [])
    {
        parent::__construct($plugin, $name, $description, $usageMessage, $aliases);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        // TODO: Implement execute() method.
    }
}