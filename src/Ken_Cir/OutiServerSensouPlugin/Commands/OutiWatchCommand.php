<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Forms\OutiWatchForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\command\CommandSender;
use pocketmine\Player\Player;

/**
 * おうちウォッチフォームを出すコマンド
 */
class OutiWatchCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "outiwatch", "おうちウォッチフォームを出すコマンド", "/outiwatch", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            $form = new OutiWatchForm();
            $form->execute($sender);
        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}