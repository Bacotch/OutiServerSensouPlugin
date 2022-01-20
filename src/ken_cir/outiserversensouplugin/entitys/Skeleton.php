<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\entitys;

use JetBrains\PhpStorm\Pure;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\World;

final class Skeleton extends Living
{
    private $target = null;
    private bool $isNeutral = true;

    private float $speed = 0.28;
    private int $coolTime = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $nbt);

        // HPを設定する
        // $this->setHealth(1.0);

        $this->getArmorInventory()->addItem(
            ItemFactory::getInstance()->get(310),
            ItemFactory::getInstance()->get(261)
        );
    }

    public static function getNetworkTypeId() : string
    {
        return EntityIds::SKELETON;
    }

    #[Pure] protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.9, 0.6); //TODO: eye height ??
    }

    public function getName() : string{
        return "Skeleton";
    }

    public function findClosestPlayer(int $distance) : ?Player {
        $result = null;
        foreach ($this->getWorld()->getPlayers() as $player) {
            //[$playerとこのエンティティの距離 < 前の$playerの距離]なら、$resultに$playerを代入
            if ($player->location->distance($this->location) < $distance) {
                $result = $player;//結果に代入
                $distance = $player->location->distance($this->location);//距離を更新
            }
        }

        return $result;
    }

    public function entityBaseTick(int $tickDiff = 1): bool
    {
        $world = $this->getWorld();
        $time = $world->getTimeOfDay();
        if(0 <= $time && $time < World::TIME_NIGHT){
            $this->kill();
        }
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $this->attackTime -= $tickDiff;
        $this->coolTime -= $tickDiff;

        if($this->attackTime > 0)
            return false;
        else
            $this->attackTime = 0;

        if($this->getTarget() == null) {
            if ($this->isNeutral) return $hasUpdate;//中立の状態なら処理を終了

            $preTarget = $this->findClosestPlayer(10);
            if ($preTarget === null) {
                $this->isNeutral = true;//中立状態に設定
                return $hasUpdate;//プレイヤーが近くにいなければ処理を終了
            } else {
                $this->isNeutral = false;//中立状態を解除
                $this->target = $preTarget;
            }
        }

        $target = $this->getTarget();
        if(!($target instanceof Player))
            return $hasUpdate;

        $speed = $this->getSpeed();
        $this->lookAt($target->location);

        if($this->location->distance($target->location) <= 1){
            if($this->coolTime < 0){
                $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 3);
                $target->attack($ev);
                $this->coolTime = 23;
            }
            return $hasUpdate;
        } else if ($this->location->distance($target->location) >= 5) {//5ブロックより遠ければ
            $preTarget = $this->findClosestPlayer(10);//10ブロック以内の一番近いプレイヤーを取得
            if ($preTarget === null) {//プレイヤーが近くにいなければ
                $this->target = null;//ターゲットを空にして、処理をやめる。
                return $hasUpdate;
            } else {//プレイヤーが存在すれば
                $this->target = $preTarget;//ターゲットを設定
            }
        }


        $moveX = sin(-deg2rad($this->location->yaw)) * $speed;
        $moveZ = cos(-deg2rad($this->location->yaw)) * $speed;
        $this->checkFront();
        $this->motion->x = $moveX;
        $this->motion->z = $moveZ;

        return true;
    }


    public function attack(EntityDamageEvent $source): void
    {
        if($source instanceof EntityDamageByEntityEvent)
            $source->setKnockBack(0.5);

        parent::attack($source);
        $this->attackTime = 17;
    }

    public function jump(): void
    {
        if($this->onGround)
            $this->motion->y = 0.5;
    }


    public function checkFront(): void
    {
        $dv = $this->getDirectionVector()->multiply(1);
        $checkPos = $this->location->add($dv->x, 0, $dv->z)->floor();
        if($this->getWorld()->getBlockAt($checkPos->getFloorX(), $this->location->getFloorY()+1, $checkPos->getFloorZ())->isSolid())
        {
            return;
        }
        if($this->getWorld()->getBlockAt($checkPos->getFloorX(), $this->location->getFloorY(), $checkPos->getFloorZ())->isSolid())
        {
            $this->jump();
        }
    }


    public function setTarget(Player $player)
    {

        $this->isNeutral = false;
        $this->target = $player;
    }


    public function getTarget()
    {
        return $this->target;
    }


    public function getSpeed(): float
    {
        return $this->speed;
    }


    #[Pure] public function hasTarget(): bool
    {
        return !is_null($this->getTarget());
    }

    public function getXpDropAmount() : int{
        return 0;
    }
}