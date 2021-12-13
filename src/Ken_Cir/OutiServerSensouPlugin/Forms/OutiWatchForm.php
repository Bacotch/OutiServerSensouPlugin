<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Forms;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\EventListener;
use Ken_Cir\OutiServerSensouPlugin\Forms\Faction\FactionForm;
use Ken_Cir\OutiServerSensouPlugin\Forms\Mail\MailForm;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

/**
 * おうちウォッチ
 */
class OutiWatchForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player, ?EventListener $eventListener = null)
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) use ($eventListener) {
                try {
                    if ($eventListener instanceof EventListener) {
                        $eventListener->unsetCheck($player->getName());
                    }

                    if ($data === null) return true;
                    elseif ($data === 1) {
                        $form = new FactionForm();
                        $form->execute($player);
                    }
                    elseif ($data === 2) {
                        $form = new MailForm();
                        $form->execute($player);
                    }
                    elseif ($data === 3) {
                        $form = new ReportForm();
                        $form->execute($player);
                    }
                    elseif ($data === 4) {
                        $form = new RequestForm();
                        $form->execute($player);
                    }
                } catch (Error | Exception $e) {
                    Main::getInstance()->getPluginLogger()->error($e, $player);
                }

                return true;
            });

            $form->setTitle("おうちウォッチ");
            $form->addButton("§c閉じる");
            $form->addButton("§d派閥");
            $form->addButton("§eメール");
            $form->addButton("§4レポート");
            $form->addButton("§6要望");
            $player->sendForm($form);
        } catch (Error | Exception $e) {
            Main::getInstance()->getPluginLogger()->error($e, $player);
        }
    }
}