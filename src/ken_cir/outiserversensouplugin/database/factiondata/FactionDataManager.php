<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\factiondata;

use DateTime;
use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\database\chestshopdata\ChestShopDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\database\maildata\MailDataManager;
use ken_cir\outiserversensouplugin\database\playerdata\PlayerDataManager;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\database\wardata\WarDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\DataConnector;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_shift;
use function array_values;
use function count;

/**
 * 派閥データマネージャー
 *
 * 依存関係:
 * FactionData <- RoleData
 * FactionData <- LandData
 * FactionData <- ChestShopData
 * FactionData <-> PlayerData
 */
class FactionDataManager
{
    private DataConnector $connector;

    /**
     * @var FactionDataManager $this
     */
    private static self $instance;

    /**
     * @var FactionData[]
     */
    private array $factionDatas;

    /**
     * @var int
     */
    private int $seq;

    public function __construct(DataConnector $connector)
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(self::class);
        self::$instance = $this;

        $this->connector = $connector;
        $this->factionDatas = [];

        $this->connector->executeSelect("outiserver.factions.seq",
            [],
            function (array $row) {
                if (count($row) < 1) {
                    $this->seq = 0;
                    return;
                }
                foreach ($row as $data) {
                    $this->seq = $data["seq"];
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
        $this->connector->executeSelect("outiserver.factions.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->factionDatas[$data["id"]] = new FactionData($data["id"],
                        $data["name"],
                        $data["owner_xuid"],
                        $data["color"],
                        $data["money"],
                        $data["safe"],
                        $data["invites"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            });
    }

    /**
     * @return FactionDataManager
     */
    public static function getInstance(): FactionDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return bool|FactionData
     * 派閥データの取得
     */
    public function get(int $id): bool|FactionData
    {
        if (!isset($this->factionDatas[$id])) return false;
        return $this->factionDatas[$id];
    }

    /**
     * 派閥名でデータの取得
     *
     * @param string $name
     * @return false|FactionData
     */
    public function getName(string $name): false|FactionData
    {
        $factionData = array_filter($this->factionDatas, function (FactionData $factionData) use ($name) {
            return $factionData->getName() === $name;
        });

        if (count($factionData) < 1) return false;
        return array_shift($factionData);
    }

    /**
     * @return FactionData[]
     */
    public function getAll(?bool $keyValue = false): array
    {
        if ($keyValue) return array_values($this->factionDatas);
        return $this->factionDatas;
    }

    /**
     * @param string $name
     * @param string $owner_xuid
     * @param int $color
     * 派閥データを作成する
     * @return int
     */
    public function create(string $name, string $owner_xuid, int $color): int
    {
        $this->connector->executeInsert("outiserver.factions.create",
            [
                "name" => $name,
                "owner_xuid" => $owner_xuid,
                "color" => $color,
                "money" => 0,
                "safe" => (int)Main::getInstance()->getConfig()->get("safe", 11000)
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );

        $this->seq++;
        $this->factionDatas[$this->seq] = new FactionData($this->seq,
            $name,
            $owner_xuid,
            $color,
            0,
            (int)Main::getInstance()->getConfig()->get("safe", 11000),
        "a:0:{}");

        return $this->seq;
    }

    /**
     * @param int $id
     */
    public function delete(int $id): void
    {
        if (!$deleteFactionData = $this->get($id)) return;

        // 派閥崩壊通知を送る
        $factionPlayers = PlayerDataManager::getInstance()->getFactionPlayers($deleteFactionData->getId());
        $time = new DateTime("now");
        foreach ($factionPlayers as $factionPlayer) {
            MailDataManager::getInstance()->create($factionPlayer->getXuid(),
                "派閥崩壊通知",
                "所属派閥{$deleteFactionData->getName()}が{$time->format("Y年m月d日 H時i分")}}に崩壊しました",
                "システム",
                $time->format("Y年m月d日 H時i分"));
            $factionPlayer->setFaction(-1);
            $factionPlayer->setRoles([]);
        }

        // 派閥役職を全て削除する
        foreach (RoleDataManager::getInstance()->getFactionRoles($deleteFactionData->getId()) as $factionRole) {
            RoleDataManager::getInstance()->delete($factionRole->getId());
        }

        // 派閥の土地を全て削除する
        foreach (LandDataManager::getInstance()->getFactionLands($deleteFactionData->getId()) as $factionLand) {
            LandDataManager::getInstance()->delete($factionLand->getId());
        }

        // 派閥のチェストショップを全て削除する
        foreach (ChestShopDataManager::getInstance()->getFactionChestShops($deleteFactionData->getId()) as $chestShopData) {
            ChestShopDataManager::getInstance()->delete($chestShopData->getId());
        }

        // 戦争データを全て削除する
        foreach (WarDataManager::getInstance()->getFaction($deleteFactionData->getId()) as $warData) {
            WarDataManager::getInstance()->delete($warData->getId());
        }

        $this->connector->executeGeneric("outiserver.factions.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->factionDatas[$id]);
    }

    /**
     * @param string $xuid
     * @return FactionData[]
     */
    #[Pure] public function getInvite(string $xuid): array
    {
        $inviteFactions = [];
        foreach ($this->factionDatas as $faction_data) {
            if ($faction_data->hasInvite($xuid)) {
                $inviteFactions[] = $faction_data;
            }
        }

        return $inviteFactions;
    }
}