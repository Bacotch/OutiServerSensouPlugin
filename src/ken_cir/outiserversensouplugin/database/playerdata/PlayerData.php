<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\database\playerdata;

use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\database\roledata\RoleDataManager;
use ken_cir\outiserversensouplugin\Main;
use poggit\libasynql\SqlError;
use function array_values;
use function in_array;
use function serialize;
use function strtolower;
use function unserialize;

class PlayerData
{
    /**
     * プレイヤーXUID
     * @var string
     */
    private string $xuid;

    /**
     * プレイヤー名
     *
     * @var string
     */
    private string $name;

    /**
     * @var array|mixed
     * PlayerのログインIPアドレス配列
     */
    private array $ip;

    /**
     * @var int
     * 所属派閥名
     */
    private int $faction;

    /**
     * @var int
     * チャットモード
     */
    private int $chatmode;

    /**
     * @var int
     * スコアボードを描写するか
     */
    private int $drawscoreboard;

    /**
     * @var int[]
     * 所持ロールID配列
     */
    private array $roles;

    /**
     * 処罰段階
     *
     * @var int
     */
    private int $punishment;

    /**
     * 所持金
     *
     * @var int
     */
    private int $money;

    /**
     * Discordアカウントと連携している場合はDiscordユーザーID
     *
     * @var string|null
     */
    private ?string $discord_userid;

    /**
     * @param string $xuid
     * @param string $name
     * @param string $ip
     * @param int $faction
     * @param int $chatmode
     * @param int $drawscoreboard
     * @param string $roles
     * @param int $punishment
     * @param int $money
     * @param string|null $discord_userid
     */
    public function __construct(string $xuid, string $name, string $ip, int $faction, int $chatmode, int $drawscoreboard, string $roles, int $punishment, int $money, ?string $discord_userid = null)
    {
        $this->xuid = $xuid;
        $this->name = strtolower($name);
        $this->ip = unserialize($ip);
        $this->faction = $faction;
        $this->chatmode = $chatmode;
        $this->drawscoreboard = $drawscoreboard;
        $this->roles = unserialize($roles);
        $this->punishment = $punishment;
        $this->money = $money;
        $this->discord_userid = $discord_userid;
    }

    private function update(): void
    {
        Main::getInstance()->getDatabase()->executeChange(
            "outiserver.players.update",
            [
                "name" => $this->name,
                "ip" => serialize($this->ip),
                "faction" => $this->faction,
                "chatmode" => $this->chatmode,
                "drawscoreboard" => $this->drawscoreboard,
                "roles" => serialize($this->roles),
                "punishment" => $this->punishment,
                "money" => $this->money,
                "discord_userid" => $this->discord_userid,
                "xuid" => $this->xuid
            ],
            null,
            function (SqlError $error) {
                Main::getInstance()->getOutiServerLogger()->error($error);
            }
        );
    }

    /**
     * @return string
     */
    public function getXuid(): string
    {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = strtolower($name);
    }

    /**
     * @return string[]
     */
    public function getIp(): array
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return bool
     */
    #[Pure] public function hasIp(string $ip): bool
    {
        if (in_array($ip, $this->getIp(), true)) return true;
        return false;
    }

    /**
     * @param string
     * IPを追加
     */
    public function addIp(string $ip): void
    {
        if (in_array($ip, $this->getIp(), true)) return;
        $this->ip[] = $ip;
        $this->update();
    }

    /**
     * @return int
     */
    public function getFaction(): int
    {
        return $this->faction;
    }

    /**
     * @param int $faction
     */
    public function setFaction(int $faction): void
    {
        $this->faction = $faction;
        $this->update();
    }

    /**
     * @return int
     */
    public function getChatmode(): int
    {
        return $this->chatmode;
    }

    /**
     * @param int $chatmode
     */
    public function setChatmode(int $chatmode): void
    {
        $this->chatmode = $chatmode;
        $this->update();
    }

    /**
     * @return int
     */
    public function getDrawscoreboard(): int
    {
        return $this->drawscoreboard;
    }

    /**
     * @param int $drawscoreboard
     */
    public function setDrawscoreboard(int $drawscoreboard): void
    {
        $this->drawscoreboard = $drawscoreboard;
        $this->update();
    }

    /**
     * @param int $id
     * ロールを追加する
     */
    public function addRole(int $id): void
    {
        if (in_array($id, $this->roles, true)) return;
        $this->roles[] = $id;
        $this->update();
    }

    /**
     * @param array $ids
     * ロールを追加する
     */
    public function addRoles(array $ids): void
    {
        foreach ($ids as $id) {
            if (in_array($id, $this->roles, true)) return;
            $this->roles[] = $id;
        }
        $this->update();
    }

    /**
     * @return int[]
     * 所持しているロールを取得する
     */
    public function getRoles(bool $sorted = true): array
    {
        if ($sorted) {
            $sort = [];
            foreach ($this->roles as $role) {
                $roleData = RoleDataManager::getInstance()->get($role);
                $sort[$roleData->getPosition()] = $role;
            }
            ksort($sort);
            return $sort;
        } else {
            return $this->roles;
        }
    }

    /**
     * @param int $id
     * @return bool
     * 指定したロールを所持しているか確認する
     */
    public function hasRole(int $id): bool
    {
        return in_array($id, $this->roles, true);
    }

    /**
     * @param int $id
     * ロールを剥奪する
     */
    public function removeRole(int $id): void
    {
        foreach ($this->roles as $key => $role) {
            if ($role === $id) {
                unset($this->roles[$key]);
            }
        }

        $this->roles = array_values($this->roles);
        $this->update();
    }

    /**
     * @param int[] $ids
     * ロールを剥奪する
     */
    public function removeRoles(array $ids): void
    {
        foreach ($this->roles as $key => $role) {
            if (in_array($role, $ids, true)) {
                unset($this->roles[$key]);
            }
        }

        $this->roles = array_values($this->roles);
        $this->update();
    }

    /**
     * @return int
     */
    public function getPunishment(): int
    {
        return $this->punishment;
    }

    /**
     * @param int $punishment
     */
    public function setPunishment(int $punishment): void
    {
        $this->punishment = $punishment;
        $this->update();
    }

    /**
     * @return int
     */
    public function getMoney(): int
    {
        return $this->money;
    }

    /**
     * @param int $money
     */
    public function setMoney(int $money): void
    {
        $this->money = $money;
        $this->update();
    }

    /**
     * @return string|null
     */
    public function getDiscordUserid(): ?string
    {
        return $this->discord_userid;
    }

    /**
     * @param string|null $discord_userid
     */
    public function setDiscordUserid(?string $discord_userid): void
    {
        $this->discord_userid = $discord_userid;
        $this->update();
    }

    // --------------------------------

    /**
     * @return bool
     * 宣戦布告権限があるかどうか
     */
    public function isSensenHukoku(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isSensenHukoku()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥にプレイヤー招待権限があるかどうか
     */
    public function isInvitePlayer(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isInvitePlayer()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥プレイヤー全員に一括でメール送信権限があるかどうか
     */
    public function isSendmailAllFactionPlayer(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isSendmailAllFactionPlayer()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 敵対派閥と友好派閥（制限あり）の設定権限
     */
    public function isFreandFactionManager(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isFreandFactionManager()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥からプレイヤーを追放権限
     */
    public function isKickFactionPlayer(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isKickFactionPlayer()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥の土地管理権限
     */
    public function isLandManager(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isLandManager()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥銀行管理権限があるかどうか
     */
    public function isBankManager(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isBankManager()) return true;
        }

        return false;
    }

    /**
     * @return bool
     * 派閥ロール管理権限があるかどうか
     */
    public function isRoleManager(): bool
    {
        foreach ($this->roles as $role) {
            $roleData = RoleDataManager::getInstance()->get($role);
            if ($roleData->isRoleManager()) return true;
        }

        return false;
    }
}
