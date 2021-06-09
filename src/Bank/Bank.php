<?php 

namespace Bank;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\Server;
use onebone\economyapi\EconomyAPI;
use Bank\Event\EventListener;
use Bank\Forms\Forms;

class Bank extends PluginBase {
  
  private $bank;
  
  public function onEnable(){
    
    if($this->getServer()->getPluginManager()->getPlugin("FormAPI") === null || $this->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null){
      $this->getServer()->getPluginManager()->disablePlugin($this);
      $this->getLogger()->critical("Missing dependencies!");
      $this->getLogger()->critical("Disabling plugin...");
      return;
    }
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getLogger()->info("Bank activated");
    $this->bank = new Config($this->getDataFolder() . "bank.yml", Config::YAML, array());
    $this->bank->save();
  }
  public function getForms(){
    return new Forms($this);
  }
  public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool {
    if($sender instanceof Player){
      switch($cmd->getName()){
      
        case "bank":
          $this->getForms()->Bank($sender);
        break;
      
      }
    }
    return true;
  }
}
