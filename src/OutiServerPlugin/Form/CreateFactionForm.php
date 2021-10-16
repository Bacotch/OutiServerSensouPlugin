<?php

namespace OutiServerPlugin\Form;

use Error;
use Exception;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;

/**
 * 派閥作成フォーム
 */
final class CreateFactionForm extends FormBase
{
    /**
     * @param Player $player
     * フォーム実行
     */
    public function execute(Player $player)
    {
        try {
            // 既に派閥所属済みの場合は
            if ($faction_id = $this->plugin->database->getPlayer($player->getName())["faction"]) {
                $faction = $this->plugin->database->getFactionById($faction_id);
                $player->sendMessage("§cあなたは既に派閥 {$faction["name"]} に所属しています");
                return;
            }

            $form = new CustomForm(function (Player $player, $data) {
                try {
                    if ($data === null) return true;
                    elseif (!isset($data[0]) or !isset($data[1])) return true;
                    $this->plugin->database->addFaction($data[0], $player->getName(), (int)$data[1]);
                    $player->sendMessage("§a[システム] 派閥 $data[0] を作成しました");
                } catch (Error | Exception $e) {
                    $this->plugin->logger->error($e, $player);
                }

                return true;
            });

            $form->setTitle("§d派閥作成フォーム");
            $form->addInput("§a派閥名", "name");
            $form->addDropdown("§e派閥チャットカラー", ["黒", "濃い青", "濃い緑", "濃い水色", "濃い赤色", "濃い紫", "金色", "灰色", "濃い灰色", "青", "緑", "水色", "赤", "ピンク", "黄色", "白色"]);
            $player->sendForm($form);
        } catch (Error | Exception $error) {
            $this->plugin->logger->error($error, $player);
        }
    }
}