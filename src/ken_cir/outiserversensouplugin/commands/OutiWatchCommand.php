<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\commands;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

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
        catch (Error|Exception $error) {
            Main::getInstance()->getOutiServerLogger()->error($error, $sender);
        }
    }
}