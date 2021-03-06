<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\playerdata;

use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopDataManager;
use ken_cir\outiserversensouplugin\database\factiondata\FactionDataManager;
use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\player\Player;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_values;
use function in_array;
use function serialize;
use function strtolower;

/**
 * プレイヤーデータマネージャー
 *
 * 依存関係:
 * PlayerData <-> FactionData
 * PlayerData <- ChestShopData
 * PlayerData <- MailData
 * PlayerData -> RoleData
 */
class PlayerDataManager
{
    /**
     * db接続オブジェクト
     *
     * @var DataConnector
     */
    private DataConnector $connector;

    /**
     * @var PlayerDataManager $this
     */
    private static self $instance;

    /**
     * @var PlayerData[]
     */
    private array $playerDatas;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->playerDatas = [];

        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.players.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->playerDatas[$data["xuid"]] = new PlayerData($data["xuid"], $data["name"], $data["ip"], $data["faction"], $data["chatmode"], $data["drawscoreboard"], $data["roles"], $data["punishment"], $data["discord_userid"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
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

        $this->connector->executeInsert(
            "outiserver.players.create",
            [
                "xuid" => $player->getXuid(),
                "name" => strtolower($player->getName()),
                "ip" => serialize([$player->getNetworkSession()->getIp()])
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $data = new PlayerData($player->getXuid(), $player->getName(), serialize([$player->getNetworkSession()->getIp()]), -1, -1, 1, serialize([]), 0,null);
        $this->playerDatas[$player->getXuid()] = $data;

        return $data;
    }

    public function deleteXuid(string $xuid): void
    {
        if (!$deletePlayerData = $this->getXuid($xuid)) return;

        if ($deletePlayerData->getFaction() !== -1) {
            // もし所有してる派閥があるなら
            if (FactionDataManager::getInstance()->get($deletePlayerData->getFaction())->getOwnerXuid() === $deletePlayerData->getXuid()) {
                FactionDataManager::getInstance()->delete($deletePlayerData->getFaction());
            }
            // 所有してないならチェストショップデータと土地保護データ(MemberPerms)を削除する、
            // 所有してた場合は派閥削除と同時に消されるのでここの処理は要りません
            else {
                // チェストショップデータ
                foreach (ChestShopDataManager::getInstance()->getPlayerChestShops($deletePlayerData->getXuid()) as $playerChestShop) {
                    ChestShopDataManager::getInstance()->delete($playerChestShop->getId());
                }

                // 土地保護データ(MemberPerms)
                foreach (LandDataManager::getInstance()->getFactionLands($deletePlayerData->getFaction()) as $factionLand) {
                    foreach (LandConfigDataManager::getInstance()->getLandConfigs($factionLand->getId()) as $landConfig) {
                        $landConfig->getLandPermsManager()->deleteMemberLandPerms($deletePlayerData->getName());
                        $landConfig->update();
                    }
                }
            }
        }

        // メールデータを削除する
        foreach (MailDataManager::getInstance()->getPlayerMailDatas($xuid) as $mailData) {
            MailDataManager::getInstance()->delete($mailData->getId());
        }

        $this->connector->executeGeneric(
            "outiserver.players.delete",
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
        if (!$deletePlayerData = $this->getName($name)) return;
        $this->deleteXuid($deletePlayerData->getXuid());
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
