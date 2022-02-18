<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\admin\database;

use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\database\landdata\LandData;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

class LandDatabaseForm
{
    public function __construct()
    {
    }

    public function execute(Player $player, LandData $landData): void
    {
        try {
            $form = new ModalForm(function (Player $player, $data) {

            });

            $form->setTitle("土地データ #{$landData->getId()}");
            $form->setContent("土地ID: {$landData->getId()}\nワールド名: {$landData->getWorld()}\n開始X座標: " . $landData->getX() << 4 . "\n開始Z座標: " . $landData->getZ() << 4 . "\n終了X座標: " . ($landData->getX() << 4) + 15 . "\n終了Z座標" . ($landData->getZ() << 4) + 15);
            $player->sendForm($form);
        }
        catch (\Error | \Exception $exception) {
            Main::getInstance()->getOutiServerLogger()->error($exception, true, $player);
        }
    }
}