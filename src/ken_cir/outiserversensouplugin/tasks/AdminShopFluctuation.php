<?php

declare(strict_types=1);

namespace ken_cir\outiserversensouplugin\tasks;

use JetBrains\PhpStorm\Pure;
use ken_cir\outiserversensouplugin\database\adminshopdata\AdminShopDataManager;
use ken_cir\outiserversensouplugin\Main;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use function floor;

/**
 * AdminShopの値段変動計算Task
 */
class AdminShopFluctuation extends Task
{
    private array $adminshopDatas;

    private int $subtract;

    private FloatingTextParticle $ftp;

    #[Pure] public function __construct(int $subtract)
    {
        $this->adminshopDatas = [];
        $this->subtract = $subtract;
        $this->ftp =  new FloatingTextParticle("取得中...", "アドミンショップ");
        // Server::getInstance()->getWorldManager()->getDefaultWorld()->addParticle(new Vector3(410, 68, 150), $this->ftp);
    }

    public function onRun(): void
    {
        $this->adminshopDatas = AdminShopDataManager::getInstance()->getAll();
        $text = "";

        foreach ($this->adminshopDatas as $adminshopData) {
            $item = ItemFactory::getInstance()->get($adminshopData->getItemId(), $adminshopData->getItemMeta());

            // もし値段が最大値に達していたら
            if ($adminshopData->getPrice() >= $adminshopData->getMaxPrice()) {
                $adminshopData->setPrice($adminshopData->getMaxPrice());
                $text = $text . "\n§f {$item->getName()} {$adminshopData->getPrice()}円 " . "§aUP " . ($adminshopData->getPrice() - $adminshopData->getDefaultPrice()) / $adminshopData->getDefaultPrice() * 100 . "パーセント";
            }
            // もし値段が最低値に達していたら
            elseif ($adminshopData->getPrice() <= $adminshopData->getMinPrice()) {
                $adminshopData->setPrice($adminshopData->getMinPrice());
                $text = $text . "\n§f {$item->getName()} {$adminshopData->getPrice()}円 " . "§cDOWN " . (1 - ($adminshopData->getPrice() / $adminshopData->getDefaultPrice())) * 100 . "パーセント";
            }
            else {
                $adminshopData->setSellCount($adminshopData->getSellCount()  - $this->subtract);

                // 値段を下げる
                if ($adminshopData->getSellCount() > 0) {
                    $discount = floor($adminshopData->getSellCount() / $adminshopData->getRateCount());
                    $adminshopData->setPrice((int)floor($adminshopData->getDefaultPrice() * ((100 - ($adminshopData->getRateFluctuation() + $discount)) / 100)));
                    $text = $text . "\n§f {$item->getName()} {$adminshopData->getPrice()}円 " . "§cDOWN " . (1 - ($adminshopData->getPrice() / $adminshopData->getDefaultPrice())) * 100 . "パーセント";
                }
                // 値段を上げる
                elseif ($adminshopData->getSellCount() < 0) {
                    $premium = floor($adminshopData->getSellCount() / $adminshopData->getRateCount());
                    $adminshopData->setPrice((int)floor($adminshopData->getDefaultPrice() + $adminshopData->getDefaultPrice() * (($adminshopData->getRateFluctuation() + $premium) / 100)));
                    $text = $text . "\n§f {$item->getName()} {$adminshopData->getPrice()}円 " . "§aUP " . ($adminshopData->getPrice() - $adminshopData->getDefaultPrice()) / $adminshopData->getDefaultPrice() * 100 . "パーセント";
                }
                else {
                    $text = $text . "\n§7 {$item->getName()} {$adminshopData->getPrice()}円 - 0.0";
                }
            }
        }

        /*
        $this->ftp->setInvisible();
        $this->ftp = new FloatingTextParticle($text, "アドミンショップ");
        Server::getInstance()->getWorldManager()->getDefaultWorld()->addParticle(new Vector3(410, 68, 150), $this->ftp, Server::getInstance()->getOnlinePlayers());
        */
    }
}