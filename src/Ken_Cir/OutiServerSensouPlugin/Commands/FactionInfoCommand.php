<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Commands;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Forms\CreateFactionForm;
use Ken_Cir\OutiServerSensouPlugin\Forms\FactionInfoForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData\PlayerDataManager;
use pocketmine\command\CommandSender;
use pocketmine\Player;

/**
 * 派閥詳細表示コマンド
 */
class FactionInfoCommand extends CommandBase
{
    public function __construct(Main $plugin)
    {
        parent::__construct($plugin, "factioninfo", "派閥の詳細を表示する", "/factioninfo", []);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        try {
            if (!$sender instanceof Player) {
                $this->CommandNotPlayer($sender);
                return;
            }

            $player = $sender->getPlayer();
            $player_data = PlayerDataManager::getInstance()->get($player->getName());
            if ($player_data->getFaction() === "") {
                $player->sendMessage("§cこのコマンドはどこかの派閥に所属していないと使用できません");
                return;
            }

            $form = new FactionInfoForm();
            $form->execute($player);
        } catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }
}