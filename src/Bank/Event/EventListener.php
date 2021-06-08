<?php

namespace Bank\Event;

use Bank\Bank;
use pocketmine\utils\TextFormat as C;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\utils\Config;

class EventListener implements Listener {
  
  public function __construct(Bank $plugin){
    
    $this->plugin = $plugin;
    $this->bank = new Config($this->plugin->getDataFolder(). "bank.yml", Config::YAML, array());
    $this->count = new Config($this->plugin->getDataFolder(). "count.yml", Config::YAML, array());
    
  }
  public function onInteract(PlayerInteractEvent $event){
    
    $player = $event->getPlayer();
    $name = $player->getName();
    $inv = $player->getInventory();
    $hand = $inv->getItemInHand();
    $lore = $hand->getLore();

	  
    if(!empty($lore)){
      if(C::clean($lore[2]) === "Interact with this pouch to redeem"){
        
        $nbt = $hand->getNamedTag();
        $value = $nbt->getInt("Ammount");
      	$count = $hand->getCount();
      	$amount = $value;
        $hand->setCount($hand->getCount() - 1);
        $inv->setItemInHand($hand);
        EconomyAPI::getInstance()->addMoney($name, $amount);
     	  $player->sendMessage("§6You redeemed §b"."$"."$amount"." §6from the pouch");
      }
    }
  }
  public function onJoin(PlayerJoinEvent $event){
    
    $player = $event->getPlayer();
    
    if($player->hasPlayedBefore() === true){
      return;
    }
    $this->bank->set(strtolower($player->getName()), 0);
      $this->bank->save();
  }
}
