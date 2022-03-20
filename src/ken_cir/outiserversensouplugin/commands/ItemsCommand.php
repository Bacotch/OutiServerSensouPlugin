<?php

namespace ken_cir\outiserversensouplugin\commands;


use CortexPE\Commando\BaseCommand;
use jojoe77777\FormAPI\SimpleForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

class ItemsCommand extends BaseCommand
{
    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin, "items");
    }

    protected function prepare(): void
    {
        $this->setPermission("outiserver.op");
        $this->setPermissionMessage("このコマンドはOPのみ使用可能");
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) return;
        $items = json_decode(file_get_contents(Main::getInstance()->getDataFolder() . "test.json"), true);
        $form = new SimpleForm(function (Player $player, $data) use ($items) {
            try {
                if ($data === null) return;
                $idmeta = explode(":", $items[$data]["key"]);
                $player->getInventory()->addItem(ItemFactory::getInstance()->get($idmeta[0], $idmeta[1]));
            } catch (\Error|\Exception $e) {
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