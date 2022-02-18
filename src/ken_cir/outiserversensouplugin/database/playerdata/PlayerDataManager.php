<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\playerdata;

use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_values;
use function in_array;
use function serialize;
use function strtolower;

class PlayerDataManager
{
    /**
     * @var PlayerDataManager $this
     */
    private static self $instance;

    /**
     * @var PlayerData[]
     */
    private array $playerDatas;

    public function __construct()
    {
        $this->playerDatas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.players.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->playerDatas[$data["xuid"]] = new PlayerData($data["xuid"], $data["name"], $data["ip"], $data["faction"], $data["chatmode"], $data["drawscoreboard"], $data["roles"], $data["punishment"], $data["money"], $data["discord_userid"]);
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
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = new self();
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
        return $this->playerDatas;
    }

    /**
     * @return PlayerData[]
     */
    public function getAll(?bool $keyValue = false): array
    {
        if ($keyValue) return array_values($this->playerDatas);
        return $this->playerDatas;
    }

    /**
     * プレイヤーデータをXUIDで取得する
     *
     * @param string $xuid
     * @return false|PlayerData
     */
    public function getXuid(string $xuid): false|PlayerData
    {
        if (!isset($this->playerDatas[$xuid])) return false;
        return $this->playerDatas[$xuid];
    }

    public function getName(string $name): false|PlayerData
    {
        $playerData = array_filter($this->playerDatas, function ($playerData) use ($name) {
            return $playerData->getName() === strtolower($name);
        });

        if (count($playerData) < 1) return false;
        return array_shift($playerData);
    }

    /**
     * @param Player $player
     * データを作成する
     */
    public function create(Player $player): PlayerData
    {
        $data = $this->getXuid($player->getXuid());
        if ($data) return $data;

        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.players.create",
            [
                "xuid" => $player->getXuid(),
                "name" => strtolower($player->getName()),
                "ip" => serialize([$player->getNetworkSession()->getIp()]),
                "money" => 0
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $data = new PlayerData($player->getXuid(), $player->getName(), serialize([$player->getNetworkSession()->getIp()]), -1, -1, 1, serialize([]), 0, 0, null);
        $this->playerDatas[$player->getXuid()] = $data;

        return $data;
    }

    public function deleteXuid(string $xuid): void
    {
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.players.delete_xuid",
            [
                "xuid" => $xuid
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        unset($this->playerDatas[$xuid]);
    }

    /**
     * @param string $name
     * データを削除する
     */
    public function deleteName(string $name): void
    {
        if (!$this->getName($name)) return;
        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.players.delete_name",
            [
                "name" => strtolower($name)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->playerDatas = array_filter($this->playerDatas, function ($playerData) use ($name) {
            return $playerData->getName() !== strtolower($name);
        });
    }

    /**
     * @param int $id
     * @return PlayerData[]
     * nameに所属している派閥メンバーを返す
     */
    public function getFactionPlayers(int $id): array
    {
        return array_values(array_filter($this->playerDatas, function ($playerData) use ($id) {
            return $playerData->getFaction() === $id;
        }));
    }

    /**
     * @return PlayerData[]
     * 指定したロールIDを所持しているプレイヤーを返す
     */
    public function getRolePlayers(int $id): array
    {
        return array_filter($this->playerDatas, function ($playerData) use ($id) {
            return in_array($id, $playerData->getRoles(), true);
        });
    }
}
