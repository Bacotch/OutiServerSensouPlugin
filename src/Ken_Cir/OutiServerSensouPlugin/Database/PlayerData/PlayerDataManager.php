<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\PlayerData;

use Ken_Cir\OutiServerSensouPlugin\Exception\InstanceOverwriteException;
use Ken_Cir\OutiServerSensouPlugin\Main;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;
use function array_filter;
use function in_array;
use function serialize;
use function strtolower;
use function array_values;

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
        $this->player_datas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.players.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->player_datas[$data["name"]] = new PlayerData($data["name"], $data["ip"], $data["faction"], $data["chatmode"], $data["drawscoreboard"], $data["roles"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * クラスインスタンスを作成する
     * @return void
     */
    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(PlayerDataManager::class);
        self::$instance = new PlayerDataManager();
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
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.players.create",
            [
                "name" => strtolower($player->getName()),
                "ip" => serialize([$player->getNetworkSession()->getIp()]),
                "drawscoreboard" => 1
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->player_datas[strtolower($player->getName())] = new PlayerData($player->getName(), serialize([$player->getNetworkSession()->getIp()]), -1, -1, 1, serialize([]));
    }

    /**
     * @param string $name
     * データを削除する
     */
    public function delete(string $name)
    {
        if (!$this->get($name)) return;
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.players.delete",
            [
                "name" => strtolower($name)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->player_datas[strtolower($name)]);
    }

    /**
     * @param int $id
     * @return PlayerData[]
     * nameに所属している派閥メンバーを返す
     */
    public function getFactionPlayers(int $id): array
    {
        return array_values(
            array_filter($this->player_datas, function ($playerData) use ($id) {
                return $playerData->getFaction() === $id;
            })
        );
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
