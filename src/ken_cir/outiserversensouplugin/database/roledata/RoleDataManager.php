<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\roledata;

use ken_cir\outiserversensouplugin\database\landconfigdata\LandConfigDataManager;
use ken_cir\outiserversensouplugin\database\landdata\LandDataManager;
use ken_cir\outiserversensouplugin\exception\InstanceOverwriteException;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function array_filter;
use function count;
use function ksort;

/**
 * 派閥のロール系管理クラス
 *
 * RoleData -> FactionData
 * RoleData <- PlayerData
 */
class RoleDataManager
{
    /**
     * @var RoleDataManager $this
     */
    private static self $instance;

    /**
     * @var RoleData[]
     */
    private array $faction_role_datas;

    /**
     * 管理用ID
     * @var int
     */
    private int $seq;

    /**
     */
    public function __construct()
    {
        $this->faction_role_datas = [];
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.roles.seq",
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
            }
        );
        Main::getInstance()->getDatabase()->executeSelect(
            "outiserver.roles.load",
            [],
            function (array $row) {
                foreach ($row as $data) {
                    $this->faction_role_datas[$data["id"]] = new RoleData($data["id"],
                        $data["faction_id"],
                        $data["name"],
                        $data["color"],
                        $data["position"],
                        $data["sensen_hukoku"],
                        $data["sendmail_all_faction_player"],
                        $data["freand_faction_manager"],
                        $data["member_manager"],
                        $data["land_manager"],
                        $data["bank_manager"],
                        $data["role_manager"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    public static function createInstance(): void
    {
        if (isset(self::$instance)) throw new InstanceOverwriteException(RoleDataManager::class);
        self::$instance = new self();
    }

    /**
     * @return RoleDataManager
     */
    public static function getInstance(): RoleDataManager
    {
        return self::$instance;
    }

    /**
     * @param int $id
     * @return false|RoleData
     */
    public function get(int $id): bool|RoleData
    {
        if (!isset($this->faction_role_datas[$id])) return false;
        return $this->faction_role_datas[$id];
    }

    /**
     * ロール作成
     *
     * @param int $faction_id
     * @param string $name
     * @param int $color
     * @param bool $sensen_hukoku
     * @param bool $sendmail_all_faction_player
     * @param bool $freand_faction_manager
     * @param bool $member_manager
     * @param bool $land_manager
     * @param bool $bank_manager
     * @param bool $role_manager
     */
    public function create(int $faction_id, string $name, int $color, bool $sensen_hukoku, bool $sendmail_all_faction_player, bool $freand_faction_manager, bool $member_manager, bool $land_manager, bool $bank_manager, bool $role_manager)
    {
        Main::getInstance()->getDatabase()->executeInsert(
            "outiserver.roles.create",
            [
                "faction_id" => $faction_id,
                "name" => $name,
                "color" => $color,
                "position" => count(array_filter($this->faction_role_datas, function ($factionRoleData) use ($faction_id) {
                        return $factionRoleData->getFactionId() === $faction_id;
                    })) + 1,
                "sensen_hukoku" => (int)$sensen_hukoku,
                "sendmail_all_faction_player" => (int)$sendmail_all_faction_player,
                "freand_faction_manager" => (int)$freand_faction_manager,
                "member_manager" => (int)$member_manager,
                "land_manager" => (int)$land_manager,
                "bank_manager" => (int)$bank_manager,
                "role_manager" => (int)$role_manager,
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        $this->seq++;
        $this->faction_role_datas[$this->seq] = new RoleData($this->seq, $faction_id, $name, $color, count(array_filter($this->faction_role_datas, function ($factionRoleData) use ($faction_id) {
                return $factionRoleData->getFactionId() === $faction_id;
            })) + 1, (int)$sensen_hukoku, (int)$sendmail_all_faction_player, (int)$freand_faction_manager, (int)$member_manager, (int)$land_manager, (int)$bank_manager, (int)$role_manager);
    }

    /**
     * @param int $id
     * ロール削除
     */
    public function delete(int $id): void
    {
        if (!$deleteRoleData = $this->get($id)) return;
        foreach ($this->getFactionRoles($deleteRoleData->getFactionId()) as $factionRole) {
            // 削除する役職より下の位置にある役職は
            if ($factionRole->getPosition() > $deleteRoleData->getPosition()) {
                // 1ずらす
                $factionRole->setPosition($factionRole->getPosition() - 1);
            }
        }

        // 土地保護データ(RolePerms)を削除する
        foreach (LandDataManager::getInstance()->getFactionLands($deleteRoleData->getFactionId()) as $factionLand) {
            foreach (LandConfigDataManager::getInstance()->getLandConfigs($factionLand->getId()) as $landConfigData) {
                $landConfigData->getLandPermsManager()->deleteRoleLandPerms($deleteRoleData->getId());
                $landConfigData->update();
            }
        }

        Main::getInstance()->getDatabase()->executeGeneric(
            "outiserver.roles.delete",
            [
                "id" => $id
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
        unset($this->faction_role_datas[$id]);
    }

    /**
     * @param int $factionId
     * @param bool $sorted
     * @return RoleData[]
     * 指定した派閥の役職を取得する
     */
    public function getFactionRoles(int $factionId, bool $sorted = true): array
    {
        if ($sorted) {
            $sort = array_filter($this->faction_role_datas, function ($roleData) use ($factionId) {
                return $roleData->getFactionId() === $factionId;
            });
            $sort2 = [];
            foreach ($sort as $value) {
                $sort2[$value->getPosition()] = $value;
            }
            ksort($sort2);
            return $sort2;
        } else {
            return array_filter($this->faction_role_datas, function ($roleData) use ($factionId) {
                return $roleData->getFactionId() === $factionId;
            });
        }
    }
}
