<?php
/*
 * KillBounty (v1.0.1.0) by CyberTech++
 * Developer: CyeberTech++ (Yungtechboy1)
 * Website: http://www.cybertechpp.com
 * Date: 2/3/2015 11:47 PM (UTC)
 * Copyright & License: (C) 2015 CyberTech++
 */

namespace CyberTech;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use onebone\economyapi\EconomyAPI;

class Main extends PluginBase implements Listener{
    
    	public $db;
        public $api;
    
        public function onEnable() {
         @mkdir($this->getDataFolder());
         $this->getLogger()->info("Boutny Plugin Has Been Enabled!");
         $this->loadYml();
         $this->db = new \SQLite3($this->getDataFolder() . "Boutny.db");
         $this->db->exec("CREATE TABLE IF NOT EXISTS bounty (id INTEGER PRIMARY KEY AUTOINCREMENT, player TEXT, amount INTEGER, setby TEXT);");
         $this->db->exec("CREATE TABLE IF NOT EXISTS settings (id INTEGER PRIMARY KEY AUTOINCREMENT , name TEXT, val TEXT);");
         $this->getServer()->getPluginManager()->registerEvents($this, $this);
         $this->api = EconomyAPI::getInstance();
         return true;
        }
        
         public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        switch($command->getName()){
			case "bounty":
                            if ($args[0] === "set" && isset($args[1]) && isset($args[2])){
                                $this->getLogger()->info("Boutny Plugin Has Been Set Command!");
                                $this->SetBounty($sender, $args[1] ,$args);
                            }elseif($args[0] === "del"){
                                if ($args[1]){
                                    //Delete Offer
                                    $this->DeleteBounty($sender,$args[1]);
                                }else{
                                    //List Their Hits
                                }
                            }elseif($args[0] === "list"){
                                if (!isset($args[1])){
                                    $page = 1;
                                }else{
                                    $page = $args[1];
                                }
                                $this->ListBounties($sender,$page);
                            }
                            return true;
                        default : 
                            return false;
         }
         
        }
        
        public function ListBounties(Player $sender,$page){
            $page = $page*1;
            $sender->sendMessage("----Current  Bounties----");
            $startnum = $page * 5;
            $endnum = $startnum - 5;
            for ($x = $endnum; $x<$startnum; $x++){
            $xx = $x + 1;
            $sqlr = $this->db->query("SELECT * FROM bounty ORDER BY `id` DESC LIMIT $x,1");
            $eslf = $sqlr->fetchArray(SQLITE3_ASSOC);
            $sender->sendMessage("#".$xx."-".$eslf['player']." -> $". $eslf['amount']);
            }
            
            $this->api->addMoney ( $sender->getName(), $eslf['amount'] );
            $sender->sendMessage("-------Page $page--------");
        }

        public function DeleteBounty(Player $sender, $player) {
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            $sendern = $sender->getName();
            $sqlr = $this->db>query("SELECT * FROM bounty WHERE setby='$sendern'AND player LIKE '%$player%' ORDER BY `id` DESC LIMIT 0,1");
            $eslf = $sqlr->fetchArray(SQLITE3_ASSOC);
            $this->api->addMoney ( $sender->getName(), $eslf['amount'] );
            $message = "Bounty Removed From ".$eslf['player']."'s Head.";
            $this->getServer()->broadcastMessage($message);
        }
        
        public function SetBounty($sendplayer, $setplayer, $args) {
            $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
            $temp = $yml->getAll();
            $this->getLogger()->info("Came To Funtion");
            if ($temp['Minimum-Bounty']< $args[2]){
            if (($this->api->myMoney($sendplayer->getName())) >= $args[2]){
                $q = 1;
                if ($setplayer instanceof Player || $q == 1){
                    $this->api->reduceMoney($sendplayer->getName(), $args[2]);
                    $player1 = $sendplayer->getName();
                    $player2 = $setplayer;
                    $a2 = $args[2];
                    $stmt = $this->db->prepare("INSERT OR REPLACE INTO bounty (player, amount, setby) VALUES (:bounty, :amount, :setby);");
                    $stmt->bindValue(":bounty", $setplayer);
                    $stmt->bindValue(":amount", $a2);
                    $stmt->bindValue(":setby", $sendplayer->getName());
                    $result = $stmt->execute();
                    $m = $player1." Has set a bounty of ". $a2 . " On " . $player2."'s Head!";
                    $this->getServer()->broadcastMessage($m);
                    return true;
                }
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
            $killer = $death->getEntity()->getLastDamageCause()->getDamager();
            if ($this->CheckIfPlayerHasBounty($player) === TRUE && $player instanceof Player && $killer instanceof Player){
                    $yml = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array()));
                    $temp = $yml->getAll();

                    $message = $player->getName()."'s Bounty was collected by ".$killer->getName();
                    $this->getServer()->broadcastMessage($message);
                    $money = $this->PlayerBountyAmount($player);
                    $this->api->addMoney ( $killer->getName(), $money );
                    if ($temp['Death-Fine']){
                        $fee = ((($money)*1)*((($temp['Death-Fine'])*1)/100));
                        $force = true;
                        $this->api->reduceMoney($player->getName(), $fee, $force);
                    }
                    $this->RemovePlayerBounty($player);
            }
        }
        
        public function CheckIfPlayerHasBounty(Player $player){
            $playern = $player->getName();
            $playerresuts = $this->db->query("SELECT * FROM bounty WHERE player='$playern';");
            $result = $playerresuts->fetchArray(SQLITE3_ASSOC);
            if ($result['player'] !== ""){
                return TRUE;
            }else{
                return FALSE;
            }
        }
        
        public function PlayerBountyAmount(Player $player){
            $playern = $player->getName();
            $playerresuts = $this->db->query("SELECT COUNT(*) as count FROM bounty WHERE player='$playern';");
            $multis = $playerresuts->fetchArray();
            $multi = $multis['count'];
            if ($multi > 1){
                $newval = 0;
                for ($x=0;$x<=$multi;$x++){
                    $message = $x;
                    $playerresuts = $this->db->query("SELECT * FROM bounty WHERE player='$playern' Limit 1,$x;");
                    $result = $playerresuts->fetchArray(SQLITE3_ASSOC);
                    $newval = $newval + ($result['amount']*1);
                    $message=$newval;
                    return $newval;
                }
            }else{
                $playerresuts = $this->db->query("SELECT * FROM bounty WHERE player='$playern';");
                $result = $playerresuts->fetchArray(SQLITE3_ASSOC);
                if ($result['amount'] !== ""){
                    return $result['amount'];
                }else{
                    $message = "Uh Oh! Thier was an error! ERROR ID 'N-227'! Please Notify Developer!";
                    $this->getLogger()->info($message);
                }
            }    
        }
        
        public function RemovePlayerBounty(Player $player){
            $playern = $player->getName();
            $playerresuts = $this->db->query("DELETE FROM bounty WHERE player='$playern';");
        }


        public function loadYml(){
        @mkdir($this->getServer()->getDataPath() . "/plugins/Bounty/");
        $this->bounty = (new Config($this->getServer()->getDataPath() . "/plugins/Bounty/" . "Bounty.yml", Config::YAML ,array(
            'Minimum-Bounty'=>"50",
            'Death-Fine'=>'50',
            'allow-multi-bountys'=>true,
            'Current-Bounties' => array(),
        )))->getAll();
        return true;
    }
}
