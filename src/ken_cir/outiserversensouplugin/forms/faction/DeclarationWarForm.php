<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction;

use jojoe77777\FormAPI\CustomForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;

class DeclarationWarForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new CustomForm(function (Player $player, $data) {

            });
        }
        catch (\Error|\Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}