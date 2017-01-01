<?php

/**
 *  __  __          _____  ______   ______     __
 * |  \/  |   /\   |  __ \|  ____| |  _ \ \   / /
 * | \  / |  /  \  | |  | | |__    | |_) \ \_/ /
 * | |\/| | / /\ \ | |  | |  __|   |  _ < \   /
 * | |  | |/ ____ \| |__| | |____  | |_) | | |
 * |_|_ |_/_/    \_\_____/|______| |____/__|_|__ _____ _  _  ____ ___  __  ___
 *  / _|                    / _ \ / _ \____  / /| ____| || ||___ \__ \/_ |/ _ \
 * | |_ _ __ __ _  __ _  __| (_) | (_) |  / / /_| |__ | || |_ __) | ) || | | | |
 * |  _| '__/ _` |/ _` |/ _ \__, |> _ <  / / '_ \___ \|__   _|__ < / / | | | | |
 * | | | | | (_| | (_| | (_) |/ /| (_) |/ /| (_) |__) |  | | ___) / /_ | | |_| |
 * |_| |_|  \__,_|\__, |\___//_/  \___//_/  \___/____/   |_||____/____||_|\___/
 *                 __/ |
 *               |___/
 * @link http://vk.com/frago9876543210
 * @link http://github.com/Frago9876543210
 **/

namespace menu;

use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\level\format\FullChunk;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\scheduler\CallbackTask;
use pocketmine\item\enchantment\Enchantment;

class main extends PluginBase implements Listener {

	public $item;
	public $items;
	public $y;

	public function onEnable() {
		//server menu item
		$this->item = array(
			'item' => array(
				'name' => 'server menu',
				'id' => 345,
				'damage' => 0,
				'count' => 1,
			)
		);
		//fake chest Y
		$this->y = 4;
		//slot[parameter]
		$this->items = array(
			'0' => array(
				'name' => 'item',
				'id' => 1,
				'damage' => 0,
				'count' => 1,
				'enchantment' => true,
				'code' => '$p->sendMessage("lol");',
			),
		);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @param PlayerJoinEvent $e
     */
	public function give(PlayerJoinEvent $e) {
		$p = $e->getPlayer();
		$inv = $p->getInventory();
		for($i = 0; $i <= 35; $i++) {
			if (@$inv->getItem($i)->getCustomName() == $this->item['item']['name'] and @$inv->getItem($i)->getId() == $this->item['item']['id'] and @$inv->getItem($i)->getDamage() == $this->item['item']['damage'] and @$inv->getItem($i)->getCount() == $this->item['item']['count']) {
				$inv->clear($i);
			}
		}
		$item = Item::get($this->item['item']['id'], $this->item['item']['damage'], $this->item['item']['count']);
		$item->setCustomName($this->item['item']['name']);
		$inv->addItem($item);
		$inv->setHotbarSlotIndex(345, 1);
	}

	/**
	 * @param PlayerDropItemEvent $e
     */
	public function drop(PlayerDropItemEvent $e) {
		$e->setCancelled();
	}

	/**
	 * @param PlayerInteractEvent $e
     */
	public function openMenu(PlayerInteractEvent $e) {
		$p = $e->getPlayer();
		if($e->getItem()->getCustomName() == $this->item['item']['name'] and $e->getItem()->getId() == $this->item['item']['id'] and $e->getItem()->getDamage() == $this->item['item']['damage'] and $e->getItem()->getCount() == $this->item['item']['count']){
			$block = $p->getLevel()->getBlock(new Vector3($p->getX(), $p->getY() - $this->y, $p->getZ()))->getID();
			$p->getLevel()->setBlock(new Vector3($p->getX(), $p->getY() - $this->y, $p->getZ()), new \pocketmine\block\Chest(), true, true);
			$nbt = new CompoundTag( "", [new ListTag("Items", []),new StringTag("id", Tile::CHEST),new IntTag("x",$p->getX()),new IntTag("y", $p->getY() - $this->y),new IntTag("z", $p->getZ())]);
			$nbt->Items->setTagType(NBT::TAG_Compound);
			$tile = Tile::createTile("Chest", $p->getLevel()->getChunk($p->getX() >> 4, $p->getZ() >> 4), $nbt);
			if($tile instanceof Chest){
				for($i = 0; $i <= 26; $i++){
					if(@$this->items[$i] !== null){
						$item = Item::get($this->items[$i]['id'], $this->items[$i]['damage'], $this->items[$i]['count']);
						$item->setCustomName($this->items[$i]['name']);
						if($this->items[$i]['enchantment'] == true){
							$enchant = Enchantment::getEnchantment(1);
							$enchant->setLevel(1);
							$item->addEnchantment($enchant);
						}
						$tile->getInventory()->setItem($i, $item);
					}
				}
				$p->addWindow($tile->getInventory());
				$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"chestTask" ], [$p, $tile, $block]), 1 * 40 );
			}
		}
	}

	/**
	 * @param Player $p
	 * @param Tile $tile
	 * @param Block $block
     */
	public function ChestTask($p, $tile, $block) {
		if(count($tile->getInventory()->getViewers()) == 0){
			$p->getLevel()->setBlock(new Vector3($p->getX(), $p->getY() - $this->y, $p->getZ()), new Block($block));
		}
		for($i = 0; $i <= 26; $i++) {
			if (@$this->items[$i] !== null) {
				if ($tile->getInventory()->getItem($i)->getId() !== $this->items[$i]['id']) {
					$tile->close();
					$p->getLevel()->setBlock(new Vector3($p->getX(), $p->getY() - $this->y, $p->getZ()), new Block($block));
					eval($this->items[$i]['code']);
					$p->getInventory()->removeItem(Item::get($this->items[$i]['id'], $this->items[$i]['damage'], $this->items[$i]['count']));
				} else {
					$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "chestTask"], [$p, $tile, $block]), 1 * 40);
				}
			}
		}
	}

}
