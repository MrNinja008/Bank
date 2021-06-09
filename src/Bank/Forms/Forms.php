<?php

namespace Bank\Forms;

use Bank\Bank;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ByteTag;

class Forms {
  
  private $bank;
  
  private $economy;
  
  private $plugin;
  
  private $count;
  
  public function __construct(Bank $plugin){
    
    $this->plugin = $plugin;
    $this->bank = new Config($this->plugin->getDataFolder(). "bank.yml", Config::YAML, array());
    $this->economy = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
    $this->count = new Config($this->plugin->getDataFolder(). "count.yml", Config::YAML, array());
  }
  public function Bank($player){
    $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function(Player $player, int $data = null){
      $result = $data;
      if($result === null){
        return true;
      }
      switch($result){
        
        case 0:
          $this->Withdraw($player);
        break;
        
        case 1:
          $this->Deposit($player);
        break;
        
        case 2:
          $this->Pouch($player);
        break;
        
        case 3:
          $this->Send($player);
        break;
      }
    });
    $form->setTitle("Bank");
    $form->addButton("Withdraw",0,"textures/ui/MCoin");
    $form->addButton("Deposit",0,"textures/ui/MCoin");
    $form->addButton("Create Pouch",0,"textures/items/paper");
    $form->addButton("Send Money",0,"textures/ui/FriendsIcon");
    $form->addButton("§c§lExit",0,"textures/blocks/barrier");
    $form->sendToPlayer($player);
    return $form;
  }
  public function Withdraw($player){
    $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createCustomForm(function(Player $player, $data){
      $result = $data;
      if($result === null){
        return true;
      }
      if($result[1] < 1){
        $player->sendMessage("§cAmount must be atleast $"."1");
        return true;
      }
      if(trim($result[1]) === ""){
        $this->Bank($player);
        return true;
      }
      if(is_numeric($result[1])){
        $money = $this->economy->myMoney($player);
        $mb = $this->getMoneyInBank($player);
        $final = $result[1] + 0;
        if($mb >= $result[1]){
          EconomyAPI::getInstance()->addMoney($player, $result[1]);
          $player->sendMessage("§6Successfully withdrawn §b". "$". $final ." §6from your bank!");
          $this->reduceMoney($player, $result[1]);
        } else {
          $player->sendMessage("§cYou don't have ". "$". $final ." in your bank");
          return true;
        }
      } else {
        $player->sendMessage("§cAmount must be numeric");
        return true;
      }
    });
    $mib = $this->getMoneyInBank($player);
    $money = $this->economy->myMoney($player);
    $form->setTitle("Withdraw");
    $form->addLabel("§aBank: §r". "$". $mib ."\n§aPurse: §r". "$". $money);
    $form->addInput("Amount");
    $form->sendToPlayer($player);
    return $form;
  }
  public function Deposit($player){
    $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createCustomForm(function(Player $player, $data){
      $result = $data;
      if($result === null){
        return true;
      }
      if($result[1] < 1){
        $player->sendMessage("§cAmount must be atleast $"."1");
        return true;
      }
      if(trim($result[1]) === ""){
        $this->Bank($player);
        return true;
      }
      $money = $this->economy->myMoney($player);
      $mib = $this->getMoneyInBank($player);
      $final = $result[1] + 0;
      if(is_numeric($result[1])){
        if($money >= $result[1]){
          if($result[1] + $mib < 1000000000){
            EconomyAPI::getInstance()->reduceMoney($player, $final);
            $this->addMoney($player, $result[1]);
            $player->sendMessage("§6Successfully deposited §b". "$". $final ."§6 in to your bank");
          } else {
            $player->sendMessage("§cYou can only deposit "."$". "999999999 at max in your bank");
            return true;
          }
        } else {
          $player->sendMessage("§cYou don't have enough money in your purse");
          return true;
        }
      } else {
        $player->sendMessage("§cAmount must be numeric");
        return true;
      }
    });
    $mib = $this->getMoneyInBank($player);
    $money = $this->economy->myMoney($player);
    $form->setTitle("Deposit");
    $form->addLabel("§aBank: §r". "$". $mib ."\n§aPurse §r"."$". $money);
    $form->addInput("Amount");
    $form->sendToPlayer($player);
    return $form;
  }
  public function Pouch($player){
    $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createCustomForm(function(Player $player, $data){
      $result = $data;
      if($result === null){
        return true;
      }
      if($result[1] < 1){
        $player->sendMessage("§cAmount must be atleast $"."1");
        return true;
      }
      if(trim($result[1]) === ""){
        $this->Bank($player);
        return true;
      }
      $money = $this->economy->myMoney($player);
      $mib = $this->getMoneyInBank($player);
      $final = $result[1] + 0;
      if(is_numeric($result[1])){
        if($money >= $result[1]){
          if($result[1] < 1000000000){
            $pouch = Item::get(339,0,1);
            $pouch->setCustomName("§d§lMoney Pouch§r");
            $pouch->setLore([
              "§6Amount: $$final",
              "",
              "§7Interact with this pouch to redeem"]);
            //Pouch NBT
            $nbt = $pouch->getNamedTag();
            $nbt->setInt("Ammount", $result[1]);
            $nbt->setByte("isValid", true);
            $nbt->setInt("ValidationID", $this->count->get("pouchcount") + 1);
            $pouch->setCompoundTag($nbt);
            $player->getInventory()->addItem($pouch);
            $this->count->set(strtolower("pouchcount"), $this->count->get("pouchcount") + 1);
            $this->count->save();
            $player->sendMessage("§aYou created a money pouch worth §6"."$".$final);
            EconomyAPI::getInstance()->reduceMoney($player, $result[1]);
          } else {
            $player->sendMessage("§cYou can only create pouch worth "."$"."999999999 at max");
            return true;
          }
        } else {
          $player->sendMessage("§cYou don't have enough money to make this pouch");
          return true;
        }
      } else {
        $player->sendMessage("§cAmount must be numeric");
        return true;
      }
    });
    $mib = $this->getMoneyInBank($player);
    $money = $this->economy->myMoney($player);
    $form->setTitle("Create Money Pouch");
    $form->addLabel("§aBank: §r"."$". $mib ."\n§aPurse §r"."$". $money);
    $form->addInput("Amount");
    $form->sendToPlayer($player);
    return $form;
  }
  public function Send($player){
    $api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createCustomForm(function(Player $player, $data){
      $result = $data;
      if($result === null){
        return true;
      }
      if($result[2] < 1){
        $player->sendMessage("§cAmount must be atleast $"."1");
        return true;
      }
      if(trim($result[2]) === ""){
        $player->sendMessage("§cPlease enter amount");
        return true;
      }
      if(trim($result[1]) === ""){
        $player->sendMessage("§cPlease enter the recipient");
        return true;
      }
      $final = $result[2] + 0;
      $target = $this->plugin->getServer()->getPlayer($result[1]);
      $money = $this->economy->myMoney($player);
      if(is_numeric($result[2])){
        if($money >= $result[2]){
          if($target instanceof Player){
            EconomyAPI::getInstance()->reduceMoney($player, $result[2]);
            EconomyAPI::getInstance()->addMoney($target, $result[2]);
            $player->sendMessage("§aSuccessfully sent §6"."$". $final ." §ato§b ". $target->getName());
            $target->sendMessage("§a". $player->getName() ." sent you §6$". $final);
          } else {
            $player->sendMessage("§cRecipient could not be found");
            return true;
          }
        } else {
          $player->sendMessage("§cYou don't have enough money to transfer");
          return true;
        }
      } else {
        $player->sendMessage("§cAmount must be numeric");
        return true;
      }
    });
    $mib = $this->getMoneyInBank($player);
    $money = $this->economy->myMoney($player);
    $form->setTitle("Send Money");
    $form->addLabel("§aBank: §r". "$". $mib ."\n§aPurse §r". "$". $money);
    $form->addInput("Recipient");
    $form->addInput("Amount");
    $form->sendToPlayer($player);
    return $form;
  }
  //Functions
  public function addMoney($player, $amount){
     
    $this->bank->set(strtolower($player->getName()), $this->bank->get(strtolower($player->getName())) + $amount);
    $this->bank->save();
  }
    
  public function reduceMoney($player, $amount){
      
    $this->bank->set(strtolower($player->getName()), $this->bank->get(strtolower($player->getName())) - $amount);
    $this->bank->save();
  }
    
  public function getMoneyInBank($player){
       
    return $this->bank->get(strtolower($player->getName()));
   }
}
