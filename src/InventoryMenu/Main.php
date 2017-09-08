<?php

declare(strict_types=1);

namespace InventoryMenu;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{

    private $isOpen;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $e): void
    {
        $packet = $e->getPacket();
        $player = $e->getPlayer();
        if ($packet instanceof ContainerSetSlotPacket) {
            if (!isset($this->isOpen[$player->getName()])) return;
            //todo: ids
            if ($packet->windowid == 10 && $this->isOpen[$player->getName()]) {
                $pk = new ContainerClosePacket();
                $pk->windowid = 10;
                $player->dataPacket($pk);
                switch ($packet->slot) {
                    case 0:
                        $player->sendMessage("You select stone block");
                        break;
                    case 1:
                        $player->sendMessage("You select grass block");
                        break;
                }
            }
        } elseif ($packet instanceof ContainerClosePacket) {
            $v3 = $player->getPosition()->asVector3();

            $pk = new UpdateBlockPacket;
            $pk->x = $v3->x;
            $pk->y = $v3->y - 3;
            $pk->z = $v3->z;
            $pk->blockId = $player->getLevel()->getBlock(new Vector3($v3->x, $v3->y - 3, $v3->z))->getId();
            $pk->blockData = 0xb << 4 | (0 & 0xf);
            $player->dataPacket($pk);
            //todo: need to remove the chest by 100%
        } elseif ($packet instanceof DropItemPacket) {
            if (!isset($this->isOpen[$player->getName()])) return;
            if ($this->isOpen[$player->getName()]) {
                $e->setCancelled();
                $this->isOpen[$player->getName()] = false;
            }
        }
    }

    public function onClick(PlayerInteractEvent $e): void
    {
        if ($e->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;

        $player = $e->getPlayer();
        if ($e->getItem()->getId() == Item::COMPASS) {
            $this->isOpen[$player->getName()] = true;
            $v3 = $player->getPosition()->asVector3();

            $pk = new UpdateBlockPacket;
            $pk->x = $v3->x;
            $pk->y = $v3->y - 3;
            $pk->z = $v3->z;
            $pk->blockId = 54;
            $pk->blockData = 0xb << 4 | (0 & 0xf);
            $player->dataPacket($pk);

            $pk1 = new ContainerOpenPacket;
            $pk1->windowid = 10;
            $pk1->type = 0;
            $pk1->x = $v3->x;
            $pk1->y = $v3->y - 3;
            $pk1->z = $v3->z;
            $player->dataPacket($pk1);

            $pk2 = new ContainerSetContentPacket;
            $pk2->windowid = 10;
            $pk2->slots = [Item::get(1, 0, 1), Item::get(2, 0, 1)];
            $player->dataPacket($pk2);
        }
    }

}