<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;

use Ken_Cir\OutiServerSensouPlugin\Forms\MailForm;
use Ken_Cir\OutiServerSensouPlugin\Main;

use pocketmine\command\CommandSender;
use pocketmine\Player;

class MailCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "/mail", "メール確認", "/mail", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            $player = $sender->getPlayer();
            $form = new MailForm();
            $form->execute($player);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}