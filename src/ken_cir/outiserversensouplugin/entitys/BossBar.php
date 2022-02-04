<?php

namespace ken_cir\outiserversensouplugin\entitys;

use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
use pocketmine\entity\AttributeMap;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\Server;

final class BossBar
{
    private string $title;

    private string $subtitle;

    private AttributeMap $attributeMap;

    private EntityMetadataCollection $entityMetadataCollection;

    public function __construct()
    {
        $this->title = "戦争 TEAM A VS TEAM B";
        $this->subtitle = "残りのフラッグ n個";
        $this->attributeMap = new AttributeMap();
        $this->attributeMap->add(AttributeFactory::getInstance()->mustGet(Attribute::HEALTH)->setMaxValue(100.0)->setMinValue(0.0)->setDefaultValue(100.0));
        $this->entityMetadataCollection = new EntityMetadataCollection();
        $this->entityMetadataCollection->setLong(EntityMetadataProperties::FLAGS, 0
            ^ 1 << EntityMetadataFlags::SILENT
            ^ 1 << EntityMetadataFlags::INVISIBLE
            ^ 1 << EntityMetadataFlags::NO_AI
            ^ 1 << EntityMetadataFlags::FIRE_IMMUNE);
        $this->entityMetadataCollection->setShort(EntityMetadataProperties::MAX_AIR, 400);
        $this->entityMetadataCollection->setString(EntityMetadataProperties::NAMETAG, $this->getFullTitle());
        $this->entityMetadataCollection->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
        $this->entityMetadataCollection->setFloat(EntityMetadataProperties::SCALE, 0);
        $this->entityMetadataCollection->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
        $this->entityMetadataCollection->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
        $this->sendBossTextPacket();
        $this->sendBossPacket(Server::getInstance()->getOnlinePlayers());
    }

    public function getFullTitle(): string
    {
        $text = $this->title;
        if (!empty($this->subtitle)) {
            $text .= "\n\n" . $this->subtitle;
        }
        return mb_convert_encoding($text, 'UTF-8');
    }

    private function sendBossTextPacket(): void
    {
        $pk = new BossEventPacket();
        $pk->eventType = BossEventPacket::TYPE_TITLE;
        $pk->title = $this->getFullTitle();
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if (!$onlinePlayer->isConnected()) continue;
            $pk->bossActorUniqueId = $this->actorId ?? $onlinePlayer->getId();
            $onlinePlayer->getNetworkSession()->sendDataPacket($pk);
        }
    }

    private function sendBossPacket(array $players): void
    {
        $pk = new BossEventPacket();
        $pk->eventType = BossEventPacket::TYPE_SHOW;
        foreach ($players as $player) {
            if (!$player->isConnected()) continue;
            $pk->bossActorUniqueId = $this->actorId ?? $player->getId();
            $player->getNetworkSession()->sendDataPacket($this->addDefaults($pk));
        }
    }

    private function addDefaults(BossEventPacket $pk): BossEventPacket
    {
        $pk->title = $this->getFullTitle();
        $pk->healthPercent = $this->getPercentage();
        $pk->unknownShort = 1;
        $pk->color = 5;//Does not function anyways
        $pk->overlay = 0;//Neither. Typical for Mojang: Copy-pasted from Java edition
        return $pk;
    }

    public function getPercentage(): float
    {
        return $this->attributeMap->get(Attribute::HEALTH)->getValue() / 100;
    }
}