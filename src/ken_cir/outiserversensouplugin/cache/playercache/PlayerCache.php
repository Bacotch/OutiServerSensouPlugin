<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\cache\playercache;

use function strtolower;

/**
 * プレイヤーキャッシュ
 */
class PlayerCache
{
    /**
     * プレイヤーXUID
     *
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
     * おうちウォッチのロック状態
     *
     * @var bool
     */
    private bool $lockOutiWatch;

    /**
     * 土地保護設定のワールド名
     *
     * @var string|null
     */
    private ?string $landConfig_WorldName;

    /**
     * 土地保護設定の開始X座標
     *
     * @var int|null
     */
    private ?int $landConfig_StartX;

    /**
     * 土地保護設定の開始Z座標
     *
     * @var int|null
     */
    private ?int $landConfig_StartZ;

    /**
     * Discord認証一時コード
     *
     * @var int|null
     */
    private ?int $discordVerifyCode;

    /**
     * Discord認証一時コード発行時刻
     *
     * @var int|null
     */
    private ?int $discordverifycodeTime;

    /**
     * Discordと連携していた場合はDiscordのユーザータグ
     *
     * @var string|null
     */
    private ?string $discordUserTag;

    /**
     * 土地保護設定のワールド名
     *
     * @var string|null
     */
    private ?string $landConfigDatabase_WorldName;

    /**
     * 土地保護設定の開始X座標
     *
     * @var int|null
     */
    private ?int $landConfigDatabase_StartX;

    /**
     * 土地保護設定の開始Z座標
     *
     * @var int|null
     */
    private ?int $landConfigDatabase_StartZ;

    public function __construct(string $xuid, string $name)
    {
        $this->xuid = $xuid;
        $this->name = strtolower($name);
        $this->lockOutiWatch = false;
        $this->landConfig_WorldName = null;
        $this->landConfig_StartX = null;
        $this->landConfig_StartZ = null;
        $this->discordVerifyCode = null;
        $this->discordUserTag = null;
        $this->landConfigDatabase_WorldName = null;
        $this->landConfigDatabase_StartX = null;
        $this->landConfigDatabase_StartZ = null;
    }

    /**
     * @return string
     */
    public function getXuid(): string
    {
        return $this->xuid;
    }

    /**
     * プレイヤー名の取得
     *
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
        $this->name = $name;
    }

    /**
     * おうちウォッチがロック状態か
     *
     * @return bool
     */
    public function isLockOutiWatch(): bool
    {
        return $this->lockOutiWatch;
    }

    /**
     * おうちウォッチのロック状態を設定
     *
     * @param bool $lockOutiWatch
     */
    public function setLockOutiWatch(bool $lockOutiWatch): void
    {
        $this->lockOutiWatch = $lockOutiWatch;
    }

    /**
     * 土地保護設定をリセット
     *
     * @return void
     */
    public function resetLandConfig(): void
    {
        $this->landConfig_WorldName = null;
        $this->landConfig_StartX = null;
        $this->landConfig_StartZ = null;
    }

    /**
     * 土地保護設定のワールド名を返す
     * @return string|null
     */
    public function getLandConfigWorldName(): ?string
    {
        return $this->landConfig_WorldName;
    }

    /**
     * 土地保護設定のワールド名を設定する
     *
     * @param string|null $landConfig_WorldName
     */
    public function setLandConfigWorldName(?string $landConfig_WorldName): void
    {
        $this->landConfig_WorldName = $landConfig_WorldName;
    }

    /**
     * 土地保護設定の開始X座標を返す
     *
     * @return int|null
     */
    public function getLandConfigStartX(): ?int
    {
        return $this->landConfig_StartX;
    }

    /**
     * 土地保護設定の開始X座標を設定する
     *
     * @param int|null $landConfig_StartX
     */
    public function setLandConfigStartX(?int $landConfig_StartX): void
    {
        $this->landConfig_StartX = $landConfig_StartX;
    }

    /**
     * 土地保護設定の開始Z座標を返す
     *
     * @return int|null
     */
    public function getLandConfigStartZ(): ?int
    {
        return $this->landConfig_StartZ;
    }

    /**
     * 土地保護設定の開始Z座標を設定する
     *
     * @param int|null $landConfig_SrartZ
     */
    public function setLandConfigStartZ(?int $landConfig_SrartZ): void
    {
        $this->landConfig_StartZ = $landConfig_SrartZ;
    }

    /**
     * @return int|null
     */
    public function getDiscordVerifyCode(): ?int
    {
        return $this->discordVerifyCode;
    }

    /**
     * @param int|null $discordVerifyCode
     */
    public function setDiscordVerifyCode(?int $discordVerifyCode): void
    {
        $this->discordVerifyCode = $discordVerifyCode;
    }

    /**
     * @return int|null
     */
    public function getDiscordverifycodeTime(): ?int
    {
        return $this->discordverifycodeTime;
    }

    /**
     * @param int|null $discordverifycodeTime
     */
    public function setDiscordverifycodeTime(?int $discordverifycodeTime): void
    {
        $this->discordverifycodeTime = $discordverifycodeTime;
    }

    /**
     * @return string|null
     */
    public function getDiscordUserTag(): ?string
    {
        return $this->discordUserTag;
    }

    /**
     * @param string|null $discordUserTag
     */
    public function setDiscordUserTag(?string $discordUserTag): void
    {
        $this->discordUserTag = $discordUserTag;
    }

    /**
     * @return string|null
     */
    public function getLandConfigDatabaseWorldName(): ?string
    {
        return $this->landConfigDatabase_WorldName;
    }

    /**
     * @param string|null $landConfigDatabase_WorldName
     */
    public function setLandConfigDatabaseWorldName(?string $landConfigDatabase_WorldName): void
    {
        $this->landConfigDatabase_WorldName = $landConfigDatabase_WorldName;
    }

    /**
     * @return int|null
     */
    public function getLandConfigDatabaseStartX(): ?int
    {
        return $this->landConfigDatabase_StartX;
    }

    /**
     * @param int|null $landConfigDatabase_StartX
     */
    public function setLandConfigDatabaseStartX(?int $landConfigDatabase_StartX): void
    {
        $this->landConfigDatabase_StartX = $landConfigDatabase_StartX;
    }

    /**
     * @return int|null
     */
    public function getLandConfigDatabaseStartZ(): ?int
    {
        return $this->landConfigDatabase_StartZ;
    }

    /**
     * @param int|null $landConfigDatabase_StartZ
     */
    public function setLandConfigDatabaseStartZ(?int $landConfigDatabase_StartZ): void
    {
        $this->landConfigDatabase_StartZ = $landConfigDatabase_StartZ;
    }

    /**
     * 土地保護データベース操作リセット
     * @return void
     */
    public function resetLandConfigDatabase(): void
    {
        $this->landConfigDatabase_WorldName = null;
        $this->landConfigDatabase_StartX = null;
        $this->landConfigDatabase_StartZ = null;
    }
}