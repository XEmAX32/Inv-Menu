<?php

declare(strict_types=1);

namespace InventoryMenu\event;


use InventoryMenu\API;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class CloseChestEvent extends PluginEvent implements Cancellable
{
    public static $handlerList = null;
    /** @var  Player $player */
    private $player;
    /** @var  int $chestId */
    private $chestId;

    public function __construct(API $plugin, Player $player, int $chestId)
    {
        parent::__construct($plugin);
        $this->chestId = $chestId;
        $this->player = $player;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getChestId()
    {
        return $this->getChestId();
    }
}