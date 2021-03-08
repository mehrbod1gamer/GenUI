<?php

namespace mehrbod1ganer\GenUI\event;

use mehrbod1gamer\GenUI\main;
use pocketmine\block\Block;
use pocketmine\block\Cobblestone;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\Listener;
use pocketmine\level\sound\FizzSound;
use room17\SkyBlock\event\island\IslandCreateEvent;
use room17\SkyBlock\event\island\IslandDisbandEvent;

class EventListener implements Listener
{
    public static $levelsDB;
    public static $levels;
    
    public function __construct()
    {
        self::$levels = main::$levels;
        self::$levelsDB = main::$levelsDB;
    }

    public function onCobblestoneForm(BlockFormEvent $event): void
    {
        $block = $event->getBlock();
        $levelName = $block->getLevel()->getName();
        if (!$event->getNewState() instanceof Cobblestone) return;
        if (isset(self::$levelsDB->getAll()[$levelName])) {
            $level = self::$levelsDB->get($levelName);
            $max = $level - 1;
            $blockID = self::$levels[mt_rand(0, $max)];
            $choice = Block::get($blockID);
            $event->setCancelled();
            $block->getLevel()->setBlock($block, $choice, true, true);
            $block->getLevel()->addSound(new FizzSound($block->add(0.5, 0.5, 0.5), 2.6 + (lcg_value() - lcg_value()) * 0.8));
        }
    }

    public function onCreateIs(IslandCreateEvent $event)
    {
        $island = $event->getIsland();
        $levelName = $island->getLevel()->getName();
        self::$levelsDB->set($levelName, 1);
        self::$levelsDB->save();
    }

    public function onDisbandIs(IslandDisbandEvent $event)
    {
        $island = $event->getIsland();
        $levelName = $island->getLevel()->getName();
        self::$levelsDB->remove($levelName);
        self::$levelsDB->save();
    }
}
