<?php

/**
 *  Copyright (c) 2022 hachkingtohach1
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

namespace hachkingtohach1\UHC;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use hachkingtohach1\UHC\data\PluginData;
use hachkingtohach1\UHC\task\ArenaTick;
use hachkingtohach1\UHC\recipe\Recipe;

/**
 * Base plugin class, contains onEnable and onDisable methods,
 * specific UHC commands like /lobby, /uhc, /uhcadmin
 */
class UHC extends PluginBase{
	/*@var array*/
	public array $arenas = [];
	/*@var array*/
	public array $players = [];
    /*@var bool*/
	private bool $testMode = false;	
	/*@var static*/
	private static $instance;

	public function onLoad() :void{
        self::$instance = $this;
	}
	
	/**
	 * @return UHC
	 */
    public static function getInstance(): UHC{
        return self::$instance;
    }

    /**
	 * calls when plugin enable
	 */
	public function onEnable() :void{
		//upload listener
		$listener = new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents($listener, $this);
		//setup all arenas
		$this->getLogger()->info("Setting up all arenas...");		
		foreach((new PluginData())->getData() as $nameArena => $data){
			if(!$this->getServer()->getWorldManager()->isWorldLoaded($data["world"])){
                $this->getServer()->getWorldManager()->loadWorld($data["world"]);
			}
			if(!$this->getServer()->getWorldManager()->isWorldLoaded($data["lobbywaiting"]["world"])){
                $this->getServer()->getWorldManager()->loadWorld($data["lobbywaiting"]["world"]);
			}
			$this->arenas[$nameArena] = new Arenas($this, $nameArena, $data["world"], $data["team"], $data["spawns"], $data["deathmatch"], $data["lobbywaiting"], $data["border"]);
		    $this->arenas[$nameArena]->updateMapData();
		}
		//add some recipes for crafting table
		(new Recipe($this))->registerItems();
		//register task
		$this->getScheduler()->scheduleRepeatingTask(new ArenaTick($this), 20);
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if($command->getName() == "lobby"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			$dataPlayer = $this->getPlayer($sender);
			if($dataPlayer->isInGame()){
				$dataArena = $this->arenas[$dataPlayer->getNameArena()];
				if($dataArena instanceof Arenas){	
				    $dataArena->removePlayer($sender, false, true);
				}
			}
			$sender->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			return true;
		}
		if($command->getName() == "uhc"){
			if(!$sender instanceof Player){
				$sender->sendMessage(TextFormat::RED."This is command for in-game!");
				return false;
			}
			$this->findArenas($sender);
			return true;
		}
		return false;
	}
	
	/**
	 * @return bool
	 */
	public function isTesting() :bool{
		return $this->testMode;
	}
	
	/**
	 * @return array
	 */
	public function getPlayer(Player $player){
		$playerData = null;
		if(isset($this->players[$player->getXuid()])){
			$playerData = $this->players[$player->getXuid()];
		}
		return $playerData;
	}
	
	/**
	 * @return int
	 */
	public function getTotalCountPlayers() :int{
	    $count = 0;
		foreach($this->arenas as $t => $k){
			if($t instanceof Arenas){
				$count += count($k->getPlayers());
			}
		}
		return $count;
	}
	
	/**
	 * @param Player $player
	 * @return bool
	 */
	public function findArenas(Player $player) :bool{
		foreach($this->arenas as $arena){
			if($arena instanceof Arenas){
				if(!$arena->isStarted()){
					if($arena->getPlayerCount() >= 1){
						$arena->addPlayer($player);
						return true;
					}
					$arena->addPlayer($player);
					return true;
				}				
			}
		}
		return false;
	}
}