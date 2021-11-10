<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Managers\PlayerData;

use Error;
use Exception;
use Ken_Cir\OutiServerSensouPlugin\libs\poggit\libasynql\SqlError;
use Ken_Cir\OutiServerSensouPlugin\Main;
use Ken_Cir\OutiServerSensouPlugin\Utils\PluginUtils;
use pocketmine\Player;
use function strtolower;
use function serialize;
use function array_filter;
use function in_array;

class PlayerDataManager
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
                    $this->player_datas[$data["name"]] = new PlayerData($data["name"], $data["ip"], $data["faction"], $data["chatmode"], $data["drawscoreboard"], $data["roles"]);
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
        $this->player_datas[strtolower($player->getName())] = new PlayerData($player->getName(), serialize([$player->getAddress()]), -1, -1, 1, serialize([]));
        PluginUtils::sendDiscordLog(Main::getInstance()->getPluginConfig()->get("Discord_Plugin_Webhook", ""), "PlayerDataに {$player->getName()} のデータを作成しました");
    }

    /**
     * @param string $name
     * データを削除する
     */
    public function delete(string $name)
    {
        try {
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
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
    }

    public function save(): void
    {
        try {
            foreach ($this->player_datas as $player_data) {
                Main::getInstance()->getDatabase()->executeChange("players.update",
                    [
                        "ip" => serialize($player_data->getIp()),
                        "faction" => $player_data->getFaction(),
                        "chatmode" => $player_data->getChatmode(),
                        "drawscoreboard" => $player_data->getDrawscoreboard(),
                        "roles" => serialize($player_data->getRoles()),
                        "name" => $player_data->getName()
                    ],
                    null,
                    function (SqlError $error) {
                        Main::getInstance()->getPluginLogger()->error($error);
                    }
                );
            }

        }
        catch (Error | Exception $error) {
            Main::getInstance()->getPluginLogger()->error($error);
        }
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

    /**
     * @return PlayerData[]
     * 指定したロールIDを所持しているプレイヤーを返す
     */
    public function getRolePlayers(int $id): array
    {
        return array_filter($this->player_datas, function ($playerData) use ($id) {
            return in_array($id, $playerData->getRoles(), true);
        });
    }
}