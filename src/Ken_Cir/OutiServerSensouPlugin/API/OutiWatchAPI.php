<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\API;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\Form\CreateFactionForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\Player;

class OutiWatchAPI extends APIBase
{
    /**
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param Player $player
     * 実行
     */
    public function execute(Player $player)
    {
        try {
            $form = new CreateFactionForm($this->plugin);
            $form->execute($player);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error, $player);
        }
    }
}