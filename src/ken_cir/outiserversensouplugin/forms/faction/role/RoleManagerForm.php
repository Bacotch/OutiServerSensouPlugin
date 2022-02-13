<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\faction\role;

use Error;
use Exception;
use ken_cir\outiserversensouplugin\forms\faction\FactionForm;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

/**
 * 役職管理フォーム
 */
class RoleManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        try {
            $form = new SimpleForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif ($data === 0) {
                        $form = new FactionForm();
                        $form->execute($player);
                    } elseif ($data === 1) {
                        $form = new CreateRoleForm();
                        $form->execute($player);
                    } elseif ($data === 2) {
                        $form = new EditRoleForm();
                        $form->execute($player);
                    } elseif ($data === 3) {
                        $form = new EditMemberRole();
                        $form->execute($player);
                    }
                } catch (Error|Exception $e) {
                    Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
                }

                return true;
            });
            $form->setTitle("§3派閥役職管理フォーム");
            $form->addButton("戻る");
            $form->addButton("§d役職の作成");
            $form->addButton("役職の編集");
            $form->addButton("派閥メンバー役職操作");
            $player->sendForm($form);
        } catch (Error|Exception $e) {
            Main::getInstance()->getOutiServerLogger()->error($e, true, $player);
        }
    }
}