<?php

namespace mehrbod1gamer\GenUI;

use mehrbod1gamer\GenUI\Lib\SimpleForm;
use mehrbod1gamer\GenUI\event\EventListener;
use pocketmine\block\Block;
use pocketmine\block\Cobblestone;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\Listener;
use pocketmine\level\sound\FizzSound;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use room17\SkyBlock\SkyBlock;
use room17\SkyBlock\event\island\IslandCreateEvent;
use room17\SkyBlock\event\island\IslandDisbandEvent;

class main extends PluginBase implements  Listener
{
    public static $levelsDB;
    public $skyblock;

    public static $features = [
        1 => '  §c* §aFeatures : §emine §8coal §ewith generator',
        2 => '  §c* §aFeatures : §emine §7iron §ewith generator',
        3 => '  §c* §aFeatures : §emine §6gold §ewith generator',
        4 => '  §c* §aFeatures : §emine §9lapis §ewith generator',
        5 => '  §c* §aFeatures : §emine §4redstone §ewith generator',
        6 => '  §c* §aFeatures : §emine §aemerald §ewith generator',
        7 => '  §c* Your generator is MAX level',
    ];

    public static $pics = [
        1 => "textures/blocks/coal_ore",
        2 => "textures/blocks/iron_ore",
        3 => "textures/blocks/gold_ore",
        4 => "textures/blocks/lapis_ore",
        5 => "textures/blocks/redstone_ore",
        6 => "textures/blocks/emerald_ore",
        7 => "textures/gui/newgui/X",
    ];

    public static $levels = [Block::COBBLESTONE, Block::COAL_ORE, Block::IRON_ORE, Block::GOLD_ORE, Block::LAPIS_ORE, Block::REDSTONE_ORE, Block::EMERALD_ORE];

    public function onEnable()
    {
        $this->skyblock = SkyBlock::getInstance();
        $this->saveDefaultConfig();
        $this->reloadConfig();
        self::$levelsDB = new Config($this->getDataFolder() . "levels.json", Config::JSON);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        parent::onEnable();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()){
            case "gen":
                if (!$sender instanceof Player){
                    $sender->sendMessage(TextFormat::RED . "Use this cmd in game!");
                    return false;
                }

                $this->genForm($sender);
        }
        return parent::onCommand($sender, $command, $label, $args);
    }

    public function genForm(Player $player)
    {
        $session = $this->skyblock->getSessionManager()->getSession($player);
        if( (!is_null($session)) and ($session->hasIsland()) ) {
            $nextlevel = $this->getGenlevel($player) + 1;
        } else $nextlevel = null;
        $form = new SimpleForm(function (Player $player, $data) use ($nextlevel, $session) {
           if ($data === null){
               return true;
           }
           switch ($data) {
               case 0:
                   if ((!is_null($session)) and ($session->hasIsland())) {
                       if ($this->getGenlevel($player) != 7) {
                           if ($this->takeUpgradeMoney($player)) {
                               $levelName = $session->getIsland()->getLevel()->getName();
                               self::$levelsDB->set($levelName, $nextlevel);
                               self::$levelsDB->save();
                               $player->sendMessage(TextFormat::GREEN . "Your Generator is LeveL " . $this->getGenlevel($player) . " Now");
                           } else return false;
                       } else return false;
                   } else {
                       return true;
                   }
                   break;
           }
        });
        if( (!is_null($session)) and ($session->hasIsland()) )
        {
            $levelName = $session->getIsland()->getLevel()->getName();
            if (!isset(self::$levelsDB->getAll()[$levelName])) {
                self::$levelsDB->set($levelName, 1);
                self::$levelsDB->save();
            }
            
            $form->setTitle(TextFormat::AQUA . "GenUI");
            $price = $this->getConfig()->get($this->getGenlevel($player) + 1);
            if ($this->getGenlevel($player) != 7) {
                $form->setContent(TextFormat::YELLOW . "Your Genlevel : " . $this->getGenlevel($player) . "\n\n\n" . self::$features[$this->getGenlevel($player)] . "\n" . "  §c* §aPrice : §e$price$");
                $form->addButton(TextFormat::GREEN . "Upgrade Gen To " . TextFormat::DARK_RED . $nextlevel, 0, self::$pics[$this->getGenlevel($player)]);
            } else {
                $form->setContent(TextFormat::YELLOW . "Your Genlevel : " . $this->getGenlevel($player) . "\n\n\n" . self::$features[$this->getGenlevel($player)]);
                $form->addButton(TextFormat::RED . "close form");
            }
        } else {
            $form->setTitle(TextFormat::AQUA . "GenUI");
            $form->setContent(TextFormat::RED . "* You dont have IsLand");
            $form->addButton(TextFormat::RED . "Close Form");
        }
        $form->sendToPlayer($player);
        return $form;
    }

    public function takeUpgradeMoney(Player $player) : bool
    {
        $nxtlevel = $this->getGenlevel($player) + 1;
        $price  = $this->getConfig()->get($nxtlevel);
        $api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        if ($api->myMoney($player) >= $price)
        {
            $api->reduceMoney($player, $price);
            return true;
        } else {
            $player->sendMessage(TextFormat::RED . "Your Money is not enough");
            return false;
        }
    }

    public function getGenlevel(Player $player) : int
    {
        $session   = $this->skyblock->getSessionManager()->getSession($player);
        $levelName = $session->getIsland()->getLevel()->getName();
        return self::$levelsDB->get($levelName);
    }
}
