<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\mail;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\forms\OutiWatchForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

/**
 * メール関係フォーム
 */
class MailForm
{
    public function __construct()
    {
    }

    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new OutiWatchForm();
                        $form->execute($player);
                    } elseif ($data === 1) {
                        $form = new CreateMailForm();
                        $form->execute($player);
                    } elseif ($data === 2) {
                        $form = new MailInfoForm();
                        $form->execute($player);
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });

            $form->setTitle("メール");
            $form->addButton("戻る");
            $form->addButton("§aメールを作成");
            $form->addButton("§bメールを閲覧・削除");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}