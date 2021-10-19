<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData;

use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\PluginUtils;
use pocketmine\Player;

final class PlayerDataManager
{
    /**
     * @var PlayerDataManager $this
     */
    private static self $instance;

    /**
     * @var PlayerData[]
     */
    private array $player_datas;

    public function __construct()
    {
        self::$instance = $this;
        $this->player_datas = [];
        Main::getInstance()->getDatabase()->executeSelect("players.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->player_datas[$data["name"]] = new PlayerData($data["name"], $data["ip"], $data["faction"], $data["chatmode"], $data["drawscoreboard"]);
                }
            }, function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            });
    }

    /**
     * @return PlayerDataManager
     */
    public static function getInstance(): PlayerDataManager
    {
        return self::$instance;
    }

    /**
     * @return PlayerData[]
     */
    public function getPlayerDatas(): array
    {
        return $this->player_datas;
    }

    /**
     * @param string $name
     * @return bool|PlayerData
     * データを取得する
     */
    public function get(string $name): bool|PlayerData
    {
        if (!isset($this->player_datas[strtolower($name)])) return false;
        return $this->player_datas[strtolower($name)];
    }

    /**
     * @param Player $player
     * データを作成する
     */
    public function create(Player $player)
    {
        if ($this->get($player->getName())) return;
        Main::getInstance()->getDatabase()->executeInsert("players.create",
            [
                "name" => strtolower($player->getName()),
                "ip" => serialize([$player->getAddress()]),
                "drawscoreboard" => 1
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        $this->player_datas[strtolower($player->getName())] = new PlayerData(strtolower($player->getName()), serialize([$player->getAddress()]), -1, -1, 1);
        PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Plugin_Webhook", ""), "PlayerDataに {$player->getName()} のデータを作成しました");
    }

    /**
     * @param string $name
     * データを削除する
     */
    public function delete(string $name)
    {
        if (!$this->get($name)) return;
        Main::getInstance()->getDatabase()->executeGeneric("players.delete",
            [
                "name" => strtolower($name)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getPluginLogger()->error($error);
            }
        );
        unset($this->player_datas[strtolower($name)]);
        PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Plugin_Webhook", ""), "PlayerDataから $name のデータを削除しました");
    }

    /**
     * @param int $id
     * @return PlayerData[]
     * nameに所属している派閥メンバーを返す
     */
    public function getFactionPlayers(int $id): array
    {
        return array_filter($this->player_datas, function ($playerData) use ($id) {
            return $playerData->getFaction() === $id;
        });
    }
}