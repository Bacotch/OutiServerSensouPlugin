<?php

namespace ken_cir\outiserversensouplugin\commands;

use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

final class ItemsCommand extends Command
{
    public function __construct()
    {
        parent::__construct("items");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $items = json_decode(file_get_contents(Main::getInstance()->getDataFolder() . "test.json"), true);
        $form = new SimpleForm(function (Player $player, $data) use ($items) {
            try {
                if ($data === null) return;
                $idmeta = explode(":", $items[$data]["key"]);
                $player->getInventory()->addItem(ItemFactory::getInstance()->get($idmeta[0], $idmeta[1]));
            }
            catch (\Exception | \Error $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        });
        $form->setTitle("ITEM");

        foreach ($items as $item) {
            $form->addButton($item["translator"]);
        }
        $sender->sendForm($form);
    }
}