<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\forms\player;

use ken_cir\outiserversensouplugin\libs\Himbeer\LibSkin\SkinConverter;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

final class SkinManagerForm
{
    public function __construct()
    {
    }

    public function execute(Player $player): void
    {
        $form = new SimpleForm(function (Player $player, $data) {
            if ($data === null) return true;
            elseif ($data === 0) {
                $player->setSkin(new Skin($player->getSkin()->getSkinId(), SkinConverter::imageToSkinDataFromPngPath(Main::getInstance()->getDataFolder() . "skins/{$player->getName()}.default.png")));
            }
            elseif ($data === 1) {
                $player->setSkin(new Skin($player->getSkin()->getSkinId(), SkinConverter::imageToSkinDataFromPngPath(Main::getInstance()->getDataFolder() . "skins/test.png")));
            }
            elseif ($data === 2) {
                $player->setSkin(new Skin($player->getSkin()->getSkinId(), SkinConverter::imageToSkinDataFromPngPath(Main::getInstance()->getDataFolder() . "skins/shinmyoumaru.png")));
            }
            elseif ($data === 3) {
                $player->setSkin(new Skin($player->getSkin()->getSkinId(), SkinConverter::imageToSkinDataFromPngPath(Main::getInstance()->getDataFolder() . "skins/sirokumasan.png")));
            }

            $player->sendSkin();
            $player->sendMessage("§a[システム] スキンを変更しました");
            return true;
        });

        $form->setTitle("プレイヤー スキン編集");
        $form->addButton("デフォルトスキン");
        $form->addButton("鬼人 正邪");
        $form->addButton("少名 針妙丸");
        $form->addButton("しろくまパーカー");
        $player->sendForm($form);
    }
}