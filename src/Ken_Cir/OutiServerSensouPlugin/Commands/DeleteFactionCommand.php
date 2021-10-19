<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Ken_Cir\OutiServerSensouPlugin\Forms\DeleteFactionForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\FactionData\FactionDataManager;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerData;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\command\CommandSender;
use pocketmine\Player;

final class DeleteFactionCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "deletefaction", "派閥を削除する", "/deletefaction", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $this->CommandNotPlayer($sender);
            return;
        }

        $player = $sender->getPlayer();
        $player_data = PlayerDataManager::getInstance()->get($player->getName());
        if ($player_data->getFaction() === "") {
            $player->sendMessage("§cこのコマンドは派閥のリーダーのみ使用できます");
            return;
        }
        $faction_data = FactionDataManager::getInstance()->get($player_data->getFaction());
        if ($faction_data->getOwner() !== $player_data->getName()) {
            $player->sendMessage("§cこのコマンドは派閥のリーダーのみ使用できます");
            return;
        }

        $form = new DeleteFactionForm();
        $form->execute($player);
    }
}