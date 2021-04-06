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

    public static $features = [];
    public static $pics = [];

    public static $levels = [Block::COBBLESTONE, Block::COAL_ORE, Block::IRON_ORE, Block::GOLD_ORE, Block::LAPIS_ORE, Block::REDSTONE_ORE, Block::DIAMOND_ORE, Block::EMERALD_ORE];

    public function onEnable()
    {
        $this->skyblock = SkyBlock::getInstance();
        $this->saveDefaultConfig();
        $this->reloadConfig();
        
        for($i = 0; $i <= 8; $i++)
        {
            $level = $i + 1;
            self::$features[$level] = $this->getConfig()->get("f$level");
            self::$pics[$level] = $this->getConfig()->get("p$level");
        }
        
        self::$levelsDB = new Config($this->getDataFolder() . "levels.json", Config::JSON);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        parent::onEnable();
    }
    
     public function onLoad()
    {
        for($i = 0; $i <= 8; $i++)
        {
            $level = $i + 1;
            self::$features[$level] = $this->getConfig()->get("f$level");
            self::$pics[$level] = $this->getConfig()->get("p$level");
        }
        parent::onLoad();
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
                       if ($this->getGenlevel($player) != 8) {
                           $nxtlevel = $this->getGenlevel($player) + 1;
                           $price  = $this->getConfig()->get($nxtlevel);
                           if ($this->takeUpgradeMoney($player, $price)) {
                               $levelName = $session->getIsland()->getLevel()->getName();
                               self::$levelsDB->set($levelName, $nextlevel);
                               self::$levelsDB->save();
                               $msg = str_replace(["{name}", "{line}", "{level}", "{cost}"], [$player->getName(), "\n", $this->getGenlevel($player), $price], $this->getConfig()->get('upgrade-msg'));
                               $player->sendMessage( $msg );
                           } else return false;
                       } else return false;
                   } else {
                       return true;
                   }
                   break;
           }
        });
        $form->setTitle( $this->getConfig()->get('title') );
        if( (!is_null($session)) and ($session->hasIsland()) )
        {
            $levelName = $session->getIsland()->getLevel()->getName();
            if (!isset(self::$levelsDB->getAll()[$levelName])) {
                self::$levelsDB->set($levelName, 1);
                self::$levelsDB->save();
            }
            
            $feature = str_replace( "{line}", "\n", self::$features[$this->getGenlevel($player)] );
            $cost    = $this->getConfig()->get($this->getGenlevel($player) + 1);
            $price   = str_replace( ["{line}", "{price}"], ["\n", $cost], $this->getConfig()->get('price-format') );
            
            if ($this->getGenlevel($player) != 8) {
                
                $content = str_replace(["{line}", "{level}", "{name}"], ["\n", $this->getGenlevel($player), $player->getName()], $this->getConfig()->get('have-is-content') );
                
                $form->setContent($content . $feature . $price);
                
                $button = str_replace(["{line}", "{level}", "{name}", "{nextLevel}"], ["\n", $this->getGenlevel($player), $player->getName(), $this->getGenlevel($player) + 1], $this->getConfig()->get('have-is-button') );
                
                $form->addButton($button, 0, self::$pics[$this->getGenlevel($player)]);
            } else {
                $content = str_replace(["{line}", "{level}", "{name}"], ["\n", $this->getGenlevel($player), $player->getName()], $this->getConfig()->get('max-level-gen-content'));
                
                $form->setContent( $content . $feature);
                $form->addButton( $this->getConfig()->get('max-level-gen-button') );
            }
        } else {
            $content = str_replace(["{line}", "{name}"], ["\n", $player->getName()], $this->getConfig()->get('have-no-is-content'));
            
            $form->setContent($content);
            $form->addButton( $this->getConfig()->get('have-no-is-button') );
        }
        $form->sendToPlayer($player);
        return $form;
    }

    public function takeUpgradeMoney(Player $player, $price) : bool
    {
        $api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        if ($api->myMoney($player) >= $price)
        {
            $api->reduceMoney($player, $price);
            return true;
        } else {
            $msg = str_replace(["{name}", "{line}", "{level}", "{cost}", "{nextLevel}"], [$player->getName(), "\n", $this->getGenlevel($player), $price, $this->getGenlevel($player) + 1], $this->getConfig()->get('not-enough-money-msg'));
            $player->sendMessage($msg);
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
