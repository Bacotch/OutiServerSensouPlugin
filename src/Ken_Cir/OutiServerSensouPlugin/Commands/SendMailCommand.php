<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Form\SendMailForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SendMailCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "/sendmail", "プレイヤーにメールを送信する", "/sendmail");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            $form = new SendMailForm($this->plugin);
            $form->execute($sender);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error, $sender);
        }
    }
}