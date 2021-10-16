<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Form\CreateFactionForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class CreateFactionCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "createfaction", "派閥を作る、既にどこかの派閥に入っている場合は使えない", "/createfaction");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            $player = $sender->getPlayer();
            $form = new CreateFactionForm($this->plugin);
            $form->execute($player);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error, $sender);
        }
    }
}