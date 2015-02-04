<?php
/*
 * KillBounty (v1.0.0.1) by CyberTech++
 * Developer: CyeberTech++ (Yungtechboy1)
 * Website: http://www.cybertechpp.com
 * Date: 1/13/2014 11:58 PM (UTC)
 * Copyright & License: (C) 2014 CyberTech++
 */

namespace CyberTech;

use pocketmine\Player;
//use pocketmine\Server;
use pocketmine\event\Listener;
/*use pocketmine\event\player\PlayerEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;*/
use pocketmine\event\player\PlayerDeathEvent;
/*use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;*/
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
//use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use onebone\economyapi\EconomyAPI;
//use pocketmine\event\player\PlayerQuitEvent;
/*use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;*/

class Main extends PluginBase implements Listener{
    
        public function onEnable() {
         $this->getLogger()->info("Boutny Plugin Has Been Enabled!");
         $this->loadYml();
         //$this->getServer()->getPluginManager()->registerEvents(new Main($this), $this);
         $this->getServer()->getPluginManager()->registerEvents($this, $this);
         $this->api = EconomyAPI::getInstance ();
         return true;
        }
        
         public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        switch($command->getName()){
			case "bounty":
                            if ($args[0] === "set" && isset($args[1]) && isset($args[2])){
                                //Set Bounty
                                //Command Ex
                                //bounty set yungtech 1000
                                $this->getLogger()->info("Boutny Plugin Has Been Set Command!");
                                $this->SetBounty($sender, $args[1] ,$args);
                            }elseif($args[0] === "del"){
                                if ($args[1]){
                                    //Delete Offer
                                    $this->DeleteBounty($args[1],$sender);
                                }else{
                                    //List Their Hits
                                }
                            }elseif($args === "test"){
                                $this->ListBounties($sender);
                            }
                            return true;
                        default : 
                            return false;
         }
         
        }
        
        public function ListBounties(Player $player){
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            foreach ($temp['Current-Bounties'] as $key=> $value){
               //foreach ($key as $k => $val){
                    $message = $key." _ ".$value;
                    $this->getServer()->broadcastMessage($message);
               //}
                        
                
            }
            
        }

        public function DeleteBounty(Player $player, $sender) {
            //Delets Bounty On That Player
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            if ($temp['Current-Bounties'][$player->getName()]['set-by'] === $sender){
                $amount = $temp['Current-Bounties'][$player->getName()]['bounty'];
                $temp['Current-Bounties'][$player->getName()] = NULL;
                $yml->setAll($temp);
                $yml->save();
                $this->api->addMoney ( $sender->getName(), $amount );
                $message = "Bounty Removed From ".$player."'s Head.";
                $player->sendMessage($message);
            }
            
        }
        
        public function SetBounty($sendplayer, $setplayer, $args) {
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            $this->getLogger()->info("Came To Funtion");
            if ($temp['Minimum-Bounty']< $args[2]){
            if (($this->api->myMoney($sendplayer)) >= $args[2]){
            $this->api->reduceMoney($sendplayer, $args[2]);
            $player1 = $this->getPlayerName($sendplayer);
            $player2 = $setplayer;
            //$a1 = $args[1];
            $a2 = $args[2];
            //$a3 = $args[3];
            $temp['Current-Bounties'][$player2] = array();
            $temp['Current-Bounties'][$player2]['bounty'] = $a2;
            $temp['Current-Bounties'][$player2]['set-by'] = $player1;
            $yml->setAll($temp);
            $yml->save();
            $m = $player1." Has set a bounty of ". $a2 . " On " . $player2."'s Head!";
            $this->getServer()->broadcastMessage($m);
            return true;
           }else{
               $m = "You Don't Have Enough Money!";
               $sendplayer->sendMessage($m);
           }
        }else{
            $m = "Oh No! A Minimum Bounty of". $temp['Minimum-Bounty'] . " is requred!";
            $sendplayer->sendMessage($m);
        }
        }
        
        public function getPlayerName(Player $player){
           return $player->getName();
        }


        public function onPlayerDeath(PlayerDeathEvent $death) {
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            $player = $death->getEntity();
            if (isset($temp['Current-Bounties'][$player->getName()])){
            if($player instanceof Player){
               //$cause = $death->getEntity()->getLastDamageCause()->getCause();
                $killer = $death->getEntity()->getLastDamageCause()->getDamager();
                if($killer instanceof Player){
                    $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
                    $temp = $yml->getAll();
                    
                        $message = $player->getName()."'s Bounty was collected by ".$killer->getName();
                        $this->getServer()->broadcastMessage($message);
                        $money = $temp['Current-Bounties'][$player->getName()]['bounty'];
                        $this->api->addMoney ( $killer->getName(), $money );
                        if ($temp['Death-Fine']){
                            $fee = ((($money)*1)/((($temp['Death-Fine'])*1)/100));
                            $force = true;
                            $this->api->reduceMoney($player->getName(), $fee, $force);
                        }
                        $temp['Current-Bounties'][$player->getName()] = NULL;
                        $yml->setAll($temp);
                        $yml->save();
                    }
                }
            }
        }
    
        public function loadYml(){
        @mkdir($this->getServer()->getDataPath() . "/plugins/Bounty/");
        $this->bounty = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array(
            'Minimum-Bounty'=>"50",
            'Death-Fine'=>'15',
            'allow-multi-bountys'=>true,
            'Current-Bounties' => array(),
        )))->getAll();
        return true;
    }
}