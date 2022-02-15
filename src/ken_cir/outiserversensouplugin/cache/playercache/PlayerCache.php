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
     * ワールドバックアップのワールド名
     *
     * @var string|null
     */
    private ?string $worldBackup_WorldName;

    /**
     * ワールドバックアップの開始X座標
     *
     * @var int|null
     */
    private ?int $worldBackup_StartX;

    /**
     * ワールドバックアップの開始Z座標
     *
     * @var int|null
     */
    private ?int $worldBackup_StartZ;

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

    public function __construct(string $xuid, string $name)
    {
        $this->xuid = $xuid;
        $this->name = strtolower($name);
        $this->lockOutiWatch = false;
        $this->landConfig_WorldName = null;
        $this->landConfig_StartX = null;
        $this->landConfig_StartZ = null;
        $this->worldBackup_WorldName = null;
        $this->worldBackup_StartX = null;
        $this->worldBackup_StartZ = null;
        $this->discordVerifyCode = null;
        $this->discordUserTag = null;
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
     * @return string|null
     */
    public function getWorldBackupWorldName(): ?string
    {
        return $this->worldBackup_WorldName;
    }

    /**
     * @param string|null $worldBackup_WorldName
     */
    public function setWorldBackupWorldName(?string $worldBackup_WorldName): void
    {
        $this->worldBackup_WorldName = $worldBackup_WorldName;
    }

    /**
     * @return int|null
     */
    public function getWorldBackupStartX(): ?int
    {
        return $this->worldBackup_StartX;
    }

    /**
     * @param int|null $worldBackup_StartX
     */
    public function setWorldBackupStartX(?int $worldBackup_StartX): void
    {
        $this->worldBackup_StartX = $worldBackup_StartX;
    }

    /**
     * @return int|null
     */
    public function getWorldBackupStartZ(): ?int
    {
        return $this->worldBackup_StartZ;
    }

    /**
     * @param int|null $worldBackup_StartZ
     */
    public function setWorldBackupStartZ(?int $worldBackup_StartZ): void
    {
        $this->worldBackup_StartZ = $worldBackup_StartZ;
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
}