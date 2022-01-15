<?php

declare(strict_types=1);

namespace Ken_Cir\OutiServerSensouPlugin\Database\RoleData;

use Ken_Cir\OutiServerSensouPlugin\Exception\InstanceOverwriteException;
use Ken_Cir\OutiServerSensouPlugin\Main;
use poggit\libasynql\SqlError;
use function count;
use function array_filter;
use function ksort;

/**
 * 派閥のロール系管理クラス
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
                    $this->faction_role_datas[$data["id"]] = new RoleData($data["id"], $data["faction_id"], $data["name"], $data["color"], $data["position"], $data["sensen_hukoku"], $data["invite_player"], $data["sendmail_all_faction_player"], $data["freand_faction_manager"], $data["kick_faction_player"], $data["land_manager"], $data["bank_manager"], $data["role_manager"]);
                }
            },
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    public static function createInstance(): void
    {
        if (isset(self::$instance))  throw new InstanceOverwriteException(RoleDataManager::class);
        self::$instance = new RoleDataManager();
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
     * @param int $faction_id
     * @param string $name
     * @param int $color
     * @param bool $sensen_hukoku
     * @param bool $invite_player
     * @param bool $sendmail_all_faction_player
     * @param bool $freand_faction_manager
     * @param bool $kick_faction_player
     * @param bool $land_manager
     * @param bool $bank_manager
     * @param bool $role_manager
     * ロール作成
     */
    public function create(int $faction_id, string $name, int $color, bool $sensen_hukoku, bool $invite_player, bool $sendmail_all_faction_player, bool $freand_faction_manager, bool $kick_faction_player, bool $land_manager, bool $bank_manager, bool $role_manager)
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
                "invite_player" => (int)$invite_player,
                "sendmail_all_faction_player" => (int)$sendmail_all_faction_player,
                "freand_faction_manager" => (int)$freand_faction_manager,
                "kick_faction_player" => (int)$kick_faction_player,
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
            })) + 1, (int)$sensen_hukoku, (int)$invite_player, (int)$sendmail_all_faction_player, (int)$freand_faction_manager, (int)$kick_faction_player, (int)$land_manager, (int)$bank_manager, (int)$role_manager);
    }

    /**
     * @param int $id
     * ロール削除
     */
    public function delete(int $id): void
    {
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
        }
        else {
            return array_filter($this->faction_role_datas, function ($roleData) use ($factionId) {
                return $roleData->getFactionId() === $factionId;
            });
        }
    }
}
