<?php

declare(strict_types=1);

namespace InventoryMenu;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ContainerSetSlotPacket;
use pocketmine\network\mcpe\protocol\DropItemPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{

    /** @var  array */
    private $chest;
    /** @var  array */
    private $inv;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $e)
    {
        $packet = $e->getPacket();
        $player = $e->getPlayer();
        if ($packet instanceof ContainerSetSlotPacket) {
            if (!isset($this->chest[$player->getName()])) return;
            if ($packet->windowid == 10 && $this->chest[$player->getName()] && $packet->item->getId() == 0) {
                $pk = new ContainerClosePacket();
                $pk->windowid = 10;
                $player->dataPacket($pk);
                switch ($this->chest[$player->getName()][0]) { //chest id
                    case 1:
                        switch ($packet->slot) {
                            case 0:
                                $player->sendMessage("You select stone block");
                                break;
                            case 1:
                                $player->sendMessage("You select grass block");
                                break;
                        }
                        break;
                    case 2:
                        switch ($packet->slot) {
                            case 0:
                                $player->sendMessage("You select sky wars");
                                break;
                            case 1:
                                $player->sendMessage("You select hunger games");
                                break;
                        }
                        break;
                }
            }
        } elseif ($packet instanceof ContainerClosePacket) {
            if (!isset($this->chest[$player->getName()]) or !isset($this->inv[$player->getName()])) return;
            /** @var Vector3 $v3 */
            $v3 = $this->chest[$player->getName()][1];
            $this->updateBlock($player, $player->getLevel()->getBlock($v3)->getId(), $v3);
            $player->getInventory()->setContents($this->inv[$player->getName()]);
            $this->clearData($player);
        } elseif ($packet instanceof DropItemPacket) {
            if (!isset($this->chest[$player->getName()])) return;
            $e->setCancelled();
        }
        /*
         * without shift:
         *  1. ContainerSetSlotPacket
         *  2. DropItemPacket
         *  3. ContainerClosePacket
         *
         * with shift
         *  1. ContainerSetSlotPacket
         *  2. ContainerClosePacket
         *
         * I hope this will not be in MCPE 1.2
         */
    }

    public function onClick(PlayerInteractEvent $e)
    {
        if ($e->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $player = $e->getPlayer();
            switch ($e->getItem()->getId()) {
                case Item::COMPASS:
                    $this->createChest($player, [Item::get(1, 0, 1), Item::get(2, 0, 1)], 1);
                    break;
                case Item::CLOCK:
                    $this->createChest($player, [Item::get(267, 0, 1)->setCustomName("§eSkyWars"), Item::get(276, 0, 1)->setCustomName("§aHungerGames")], 2);
                    break;
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $e)
    {
        $inv = $e->getPlayer()->getInventory();
        $inv->clearAll();
        $inv->setItem(0, Item::get(Item::COMPASS, 0, 1)->setCustomName("Select block"));
        $inv->setItem(1, Item::get(Item::CLOCK, 0, 1)->setCustomName("Select mini game"));
    }

    public function onPlayerQuit(PlayerQuitEvent $e)
    {
        $this->clearData($e->getPlayer());
    }

    public function createChest(Player $player, array $items, int $id)
    {
        $this->clearData($player);

        $this->inv[$player->getName()] = $player->getInventory()->getContents();

        $v3 = $this->getVector($player);
        $this->chest[$player->getName()] = [$id, $v3];
        $this->updateBlock($player, 54, $v3);

        $pk = new ContainerOpenPacket;
        $pk->windowid = 10;
        $pk->type = 0;
        $pk->x = $v3->x;
        $pk->y = $v3->y;
        $pk->z = $v3->z;
        $player->dataPacket($pk);

        $pk1 = new ContainerSetContentPacket;
        $pk1->windowid = 10;
        $pk1->slots = $items;
        $player->dataPacket($pk1);
    }

    public function updateBlock(Player $player, int $id, Vector3 $v3)
    {
        $pk = new UpdateBlockPacket;
        $pk->x = $v3->x;
        $pk->y = $v3->y;
        $pk->z = $v3->z;
        $pk->blockId = $id;
        $pk->blockData = 0xb << 4 | (0 & 0xf);
        $player->dataPacket($pk);
    }

    public function getVector(Player $player): Vector3
    {
        return new Vector3($player->x, $player->y - 3, $player->z);
    }

    public function clearData(Player $player)
    {
        if (isset($this->chest[$player->getName()]))
            unset($this->chest[$player->getName()]);
        if (isset($this->inv[$player->getName()]))
            unset($this->inv[$player->getName()]);
    }
}