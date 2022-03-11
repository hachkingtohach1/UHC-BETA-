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

use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\player\GameMode;
use pocketmine\entity\Location;
use pocketmine\world\Position;
use pocketmine\world\World;
use pocketmine\world\sound\ClickSound;
use pocketmine\math\Vector3 as Vector3PMMP;
use hachkingtohach1\UHC\UHC;
use hachkingtohach1\UHC\player\UHCPlayer;
use hachkingtohach1\UHC\math\Vector3;
use hachkingtohach1\UHC\data\PluginData;
use hachkingtohach1\UHC\utils\ScoreBoardAPI;

class Arenas{
	/*@var UHC*/
	private ?UHC $plugin;
	/*@var bool*/
	private bool $started = false;	
	/*@var bool*/
	private bool $invincible = false;
	/*@var bool*/
	private bool $teamMode = false;
	/*@var bool*/
	private bool $deathMatch = false;
	/*@var World*/
	private ?World $world;
	/*@var string - name of current arena*/
	private string $nameArena;
	/*@var array*/
	private array $spawnDeathmatch = [];
	/*@var array*/
	private array $lobbywaiting = [];
	/*@var array*/
	private array $kills = [];
	/*@var array*/
	private array $players = [];
	/*@var array*/
	private array $teams = [];
	/*@var array*/
	private array $spawns = [];
	/*@var int*/
	private int $border;	
	/*@var int*/
	private int $borderMove;
	/*@var int*/
	private int $humanReadableTime;	
	/*@var array*/
	private static $teamColors = [
	    "black",
		"gold",
		"gray",
		"blue",
		"green",
		"aqua",
		"red",
		"purple",
		"yellow",
		"white"
	];
	
	const MIN_PLAYER_COUNT = 1;
	const MAX_PLAYER_COUNT = 70;
	const MAX_PLAYER_INTEAM_COUNT = 7;
	const MATCH_START_IN_SECONDS = 180;
	const INVINCIBILITY_DISABLE_IN_SECONDS = 480;
	const DEATHMATCH_START_IN_SECONDS = 1000;
	const MATCH_FINAL_IN_SECONDS = 2000;
	
	/**
	 * construct of UHC class, 
	 * creates arena for it
	 * 
	 * @param UHC $plugin
	 * @param string $nameArena
	 * @param string $world
	 * @param bool $team
	 * @param array $spawns
	 * @param string $spawnDeathmatch
	 * @param array $lobbywaiting
	 * @param int $border
	 */
    public function __construct(UHC $plugin, string $nameArena, string $world, bool $team, array $spawns, array $spawnDeathmatch, array $lobbywaiting, int $border){
		$this->plugin = $plugin;
		$this->nameArena = $nameArena;
		$this->world = $this->plugin->getServer()->getWorldManager()->getWorldByName($world);
        if($team){
			$this->teamMode = true;
		}
		$this->setDefaultTeams();
		$this->spawns = $spawns;
		$this->spawnDeathmatch = $spawnDeathmatch;
		$this->lobbywaiting = $lobbywaiting;		
		$this->border = $border;
		$this->borderMove = $border;
		$this->humanReadableTime = (int)microtime(true);
	}
	
	/**
	 * @return bool
	 */
	public function isStarted() :bool{
		return $this->started;
	}
	
	/**
	 * @return bool
	 */
	public function isInvincible() :bool{
		return $this->invincible;
	}
	
	/**
	 * @return bool
	 */
	public function isDeathMatch() :bool{
		return $this->deathMatch;
	}
	
	/**
	 * @return bool
	 */
	public function isTeamMode() :bool{
		return $this->teamMode;
	}

	/**
	 * @return int
	 */
	public function getPlayerCount() :int{
		return count($this->players);
	}
	
	/**
	 * @return array
	 */
	public function getPlayers() :array{
		return $this->players;
	}
	
	/**
	 * @return World
	 */
	public function getWorld() :?World{
		return $this->world;
	}
	
	/**
	 * @return string
	 */
	public function getNameArena() :string{
		return $this->nameArena;
	}
	
	/**
	 * create default teams array
	 */
	private function setDefaultTeams(){
		if($this->isTeamMode()){
			$this->teams = [];
			foreach(self::$teamColors as $color){
				$this->teams[$color] = [];
			}
		}
	}
	
	/**
	 * @return array
	 */
	public function getTeams() :array{
		return $this->teams;
	}
	
	/**
	 * @return int
	 */
	public function getBorder() :int{
		return $this->border;
	}
	
	/**
	 * set border for arena
	 */
	public function setBorder(int $border){
		$this->border = $border;
	}
	
	/**
	 * @return int
	 */
	public function getBorderMove() :int{
		return $this->borderMove;
	}
	
	/**
	 * set border need move for arena
	 */
	public function setBorderMove(int $borderMove){
		$this->borderMove = $borderMove;
	}
	
	/**
	 * @return int
	 */
	public function getHumanReadableTime() :int{
		return $this->humanReadableTime;
	}
	
	/**
	 * unload and load map saved
	 */
	public function updateMapData() :?World{	
		$folderName = $this->getWorld()->getFolderName();		
		$path = $this->plugin->getServer()->getDataPath();		
        if(!file_exists($path . "worlds". DIRECTORY_SEPARATOR . $folderName)){
			return null;    
		}
		//get world manager
		$worldManager = $this->plugin->getServer()->getWorldManager();		
		//check world is generated
        if(!$worldManager->isWorldGenerated($folderName)) return null;
        //unload world      
        if($worldManager->isWorldLoaded($folderName)) {
            $worldManager->unloadWorld($worldManager->getWorldByName($folderName));
        }		
		//extract file world
        $tarPath = $this->plugin->getDataFolder(). "saves" . DIRECTORY_SEPARATOR . $folderName. ".tar.gz";
		$tar = new \PharData($tarPath);
        $tar->extractTo($path."worlds/".$folderName, null, true);
		//load world
		$worldManager->loadWorld($folderName);
        $worldManager->getWorldByName($folderName)->setAutoSave(false);	
		//return world
        return $worldManager->getWorldByName($folderName);
    }
	
	/**
	 * calls when arena restart, removes all players and chunks,
	 * clear arena data
	 */
	private function restart(){
		$this->pushPlayersToLobby();
		$this->updateMapData();	
		$this->setDefaultArenaData();
	}
	
	/**
	 * teleport players to lobby, 
	 * remove them from arena object
	 */
	private function pushPlayersToLobby(){
		foreach($this->players as $player){
			$player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
			$this->removePlayer($player);
		}
	}
	
	/**
	 * reset all arena data on restart
	 */
	private function setDefaultArenaData(){
		$this->invincible = false;
		$this->deathMatch = false;
		$this->started = false;
		$this->humanReadableTime = (int)microtime(true);
		$this->players = [];
		$this->kills = [];		
		$this->setDefaultTeams();
	}
	
	/**
	 * calls to inform player about current arena countdown
	 */
	private function sayTime(){
		$tick = (int)microtime(true) - $this->getHumanReadableTime();
		$timeLeft = gmdate("i:s", (int)(180 - $tick));
		$this->broadcastMessageLocalized("STARTING_IN", ["#time"], [$timeLeft]);
	}
	
	/**
	 * Calls when countdown is finished and fighting start
	 * update player's data, send start messages, update arena data
	 */
	private function start(){
		//check count player enought 3
		if(!$this->plugin->isTesting() and count($this->players) < self::MIN_PLAYER_COUNT){
			$this->humanReadableTime = (int)microtime(true);
			$this->broadcastMessageLocalized("NOT_ENOUGHT_PLAYER", [], []);
			return;
		}
		foreach($this->players as $player){
			//teleport
			$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->getWorld()->getFolderName());
		    $position = Vector3::fromString($this->spawns[array_rand($this->spawns, 1)]);
			$player->teleport(Position::fromObject($position, $world));
			//options
			$player->getHungerManager()->setEnabled(true);			
		}
		$this->sendStartInfo();
		$this->started = true;
		$this->invincible = true;
		if($this->plugin->isTesting()){
			$this->won();//also check if we have a winner
		}
	}
	
	/**
	 * Send message about arena start, invincibility
	 */
	private function sendStartInfo(){
		$this->broadcastMessage(TextFormat::BLUE."> --------------------------------");
		$this->broadcastMessageLocalized("STARTED", [], []);
		$this->broadcastMessageLocalized("INVINCIBILITY", [], []);
		$this->broadcastMessage(TextFormat::BLUE."> --------------------------------");
	}
	
	/**
	 * send scoreboard <>
	 * first 3 minutes are countdown, 
	 * 8 minutes playing on arena, 
	 * 5 minutes to start deathmatch, 
	 * then restart
	 */
	public function tick(){
        //send scoreboard
		$this->sendScoreBoard();
		//border move
		$this->updateBorder();
		//check tick
        $tick = (int)microtime(true) - $this->getHumanReadableTime();
		switch($tick){
			case 15:
			case 30:
			case 45:
			case 60:
			case 75:
			case 90:
			case 105:
			case 120:
			case 135:
			case 150:
			case 165:
				$this->updateCountdownTime();
				break;
			case self::MATCH_START_IN_SECONDS - 5:
			case self::MATCH_START_IN_SECONDS - 4:
			case self::MATCH_START_IN_SECONDS - 3:
			case self::MATCH_START_IN_SECONDS - 2:
			case self::MATCH_START_IN_SECONDS - 1:
				$this->almostStartNotify();
				break;
			case self::MATCH_START_IN_SECONDS:
				$this->start();
				break;
			case 480:
				$this->finishInvincibility();
				break;
			case 500:
				$this->setBorderMove(0);
				break;
			case 650:
				$this->setBorderMove(0);
				break;	
			case self::DEATHMATCH_START_IN_SECONDS - 300:
			case self::DEATHMATCH_START_IN_SECONDS - 200:
            case self::DEATHMATCH_START_IN_SECONDS - 5:
			case self::DEATHMATCH_START_IN_SECONDS - 4:
			case self::DEATHMATCH_START_IN_SECONDS - 3:
			case self::DEATHMATCH_START_IN_SECONDS - 2:
			case self::DEATHMATCH_START_IN_SECONDS - 1:
				$this->deathMatchSoonNotify();
				break;				
			case self::DEATHMATCH_START_IN_SECONDS:
				$this->startDeathmatch();
				break;
			case self::MATCH_FINAL_IN_SECONDS:
				$this->restart();
				break;
		}
	}
	
	/**
	 * @return array
	 */
	private function getStatus() :array{
		if(!$this->isStarted()){
			$time = self::MATCH_START_IN_SECONDS - ((int)microtime(true) - $this->getHumanReadableTime());
			return [$this->getMessageLocalized("STARTING_FORMAT", [], []), gmdate("i:s", $time)];
		}
		if($this->isInvincible()){
			$time = self::INVINCIBILITY_DISABLE_IN_SECONDS - ((int)microtime(true) - $this->getHumanReadableTime());
			return [$this->getMessageLocalized("INVINCIBILITY_FORMAT", [], []), gmdate("i:s", $time)];
		}
		if(!$this->isDeathMatch()){
			$time = self::DEATHMATCH_START_IN_SECONDS - ((int)microtime(true) - $this->getHumanReadableTime());
			return [$this->getMessageLocalized("DEATHMATCH_FORMAT", [], []), gmdate("i:s", $time)];
		}
		if($this->isDeathMatch()){
			$time = self::MATCH_FINAL_IN_SECONDS - ((int)microtime(true) - $this->getHumanReadableTime());
			return [$this->getMessageLocalized("ENDGAME_FORMAT", [], []), gmdate("i:s", $time)];
		}
        return ["Not found status!", 0];
	}
	
	/**
	 * say time left to start game by current tick
	 */
	private function updateCountdownTime(){	
        foreach($this->players as $player){
			$player->getWorld()->addSound($player->getLocation()->asVector3(), new ClickSound(), $player->getWorld()->getPlayers());
		}	
		$tick = (int)microtime(true) - $this->getHumanReadableTime();
		$timeLeft = gmdate("i:s", (int)(self::MATCH_START_IN_SECONDS - $tick));
		$this->broadcastMessageLocalized("STARTING_IN", ["#time"], [$timeLeft]);
	}

	/**
	 * calls in 5 seconds to start, send messages and sounds to players
	 */
	private function almostStartNotify(){
		foreach($this->players as $player){
			$player->getWorld()->addSound($player->getLocation()->asVector3(), new ClickSound(), $player->getWorld()->getPlayers());
		}
		$tick = (int)microtime(true) - $this->getHumanReadableTime();
		$timeLeft = (int)(self::MATCH_START_IN_SECONDS - $tick);
		$this->broadcastMessageLocalized("STARTING_IN", ["#time"], [$timeLeft]);
	}

	/**
	 * cancel invincibility, inform players
	 */
	private function finishInvincibility(){
		$this->invincible = false;
		$this->broadcastMessageLocalized("END_INVINCIBILITY", [], []);
	}
	
	/**
	 * inform players about deathmatch countdown every minute
	 */
	private function deathMatchSoonNotify() {
		foreach($this->players as $player){
			$player->getWorld()->addSound($player->getLocation()->asVector3(), new ClickSound(), $player->getWorld()->getPlayers());
		}
		$tick = (int)microtime(true) - $this->getHumanReadableTime();
		$timeLeft = (int)(self::DEATHMATCH_START_IN_SECONDS - $tick);
		$this->broadcastMessageLocalized("DEATHMATCH", ["#time"], [$timeLeft]);
	}
	
	/**
	 * Calls on adding player to arena, some checks before allow player
	 * 
	 * @param Player $player
	 */
	public function addPlayer(Player $player) :bool{
		//can't add player - game was started
		if($this->isStarted() || $this->isInvincible()){
			return false;
		}
		//full arena
		if(count($this->players) >= self::MAX_PLAYER_COUNT){
			return false;
		}
		//in-game
		$dataPlayer = $this->plugin->getPlayer($player);
	    if($dataPlayer->isInGame()){
			return false;
		}
		//accept player
		$this->acceptPlayer($player);
        return true;
	}
	
	/**
	 * Calls on adding player to spectator mode
	 * 
	 * @param Player $player
	 */
    public function setSpectator(Player $player){
		$this->removePlayer($player);
        $player->setHealth(20);
        $player->setGamemode(GameMode::SPECTATOR());		
	}
	
	/**
	 * remove player from arena, 
	 * unset him from arena data,
	 * clear player's data
	 * 
	 * @param Player $player
	 * @param bool $fromWon
	 * @param bool $leftGame
	 */
	public function removePlayer(Player $player, bool $fromWon = false, bool $leftGame = false){
		if(isset($this->players[$player->getXuid()])){
			unset($this->players[$player->getXuid()]);
			//clear inventory
			$player->getInventory()->clearAll();
			$this->removeFromTeam($player);
			$this->removeDataPlayer($player);
			//set winner if needs
			if(!$fromWon && $this->started){
				$this->won();
			}
			if($leftGame){
				$this->broadcastMessageLocalized("LEFT_GAME", ["#player"], [$player->getName()]);
			}
		}
	}

	/**
	 * Remove player info from team on current arena
	 * 
	 * @param Player $player
	 */
	private function removeFromTeam(Player $player){
		if($this->isTeamMode()){
			foreach($this->teams as $teamName => $teamPlayers){
				if(isset($teamPlayers[$player->getXuid()])){
					unset($this->teams[$teamName][$player->getXuid()]);
					return;
				}
			}
		}
	}
	
	/**
	 * Update data for player
	 * 
	 * @param Player $player
	 */
	private function removeDataPlayer(Player $player){
		if(isset($this->kills[$player->getXuid()])){
			unset($this->kills[$player->getXuid()]);
		}
		$this->plugin->getPlayer($player)->setInGame(false);
		$this->plugin->getPlayer($player)->setNameArena("");
		$this->plugin->getPlayer($player)->setTeam("");
	}
	
	/**
	 * Update data for player
	 * 
	 * @param Player $player
	 */
	private function addDataPlayer(Player $player){
		$player->getInventory()->clearAll();
		$player->getHungerManager()->setEnabled(false);	
		$this->updateKillsCounter($player);
		$this->plugin->getPlayer($player)->setInGame(true);
		$this->plugin->getPlayer($player)->setNameArena($this->getNameArena());
	}

	/**
	 * Update kills counter for player
	 * 
	 * @param Player $player
	 */
	public function updateKillsCounter(Player $player){
		if(isset($this->kills[$player->getXuid()])){
			$this->kills[$player->getXuid()] += 1;
		}else{
			$this->kills[$player->getXuid()] = 0;
		}
	}
	
	/**
	 * Update border for arena
	 * - 20% per update
	 * @param Player $player
	 */
	private function updateBorder(){
		if($this->getBorderMove() == 0){
		    $calculate = (int)($this->getBorder() - ($this->getBorder() * (35/100)));
		    $this->setBorderMove($calculate);
			$this->broadcastMessageLocalized("BORDER_UPDATE", ["#border"], [$calculate]);
		}else{
			if($this->getBorder() > $this->getBorderMove()){
			    $this->setBorder($this->getBorder() - 2);	
			}				
		}
	}
	
	/**
	 * Teleport players to deathmatch arena,
	 * inform them
	 */
	private function startDeathmatch(){
		$position = Vector3::fromString($this->spawnDeathmatch["spawn"]);
		$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->spawnDeathmatch["world"]);
		foreach($this->players as $player){	
		    $player->teleport(Position::fromObject($position, $world));
		}
		$this->broadcastMessageLocalized("DEATHMATCH_START", [], []);
		$this->deathMatch = true;
	}
	
	/**
	 * broadcast message without translate for all players in arena
	 * 
	 * @param string $text
	 */
	public function broadcastMessage(string $text){
		foreach($this->players as $player){
			$player->sendMessage($text);
		}
	}
	
	/**
	 * @param string $subject
	 * @param array $search
	 * @param array $replace
	 */
	public function broadcastMessageLocalized(string $subject, array $search, array $replace){
		$message = (new PluginData())->getMessage($subject);
		foreach($this->players as $player){
		    $player->sendMessage(str_replace($search, $replace, $message));
		}
	}
	
	/**
	 * Send scoreboard for all players in arena
	 */
	private function sendScoreBoard(){
		$status = $this->getStatus();
		foreach($this->players as $player){
			ScoreBoardAPI::setScore($player, TextFormat::BOLD.TextFormat::RED."FOXPVP UHC");
		    ScoreBoardAPI::setScoreLine($player, 1, TextFormat::GRAY. date("d/m/Y").TextFormat::BLACK." M101");
			ScoreBoardAPI::setScoreLine($player, 2, TextFormat::GRAY."");
			ScoreBoardAPI::setScoreLine($player, 3, $status[0]);
			ScoreBoardAPI::setScoreLine($player, 4, TextFormat::RED.$status[1]);
			ScoreBoardAPI::setScoreLine($player, 5, TextFormat::RED."");
			ScoreBoardAPI::setScoreLine($player, 6, TextFormat::WHITE."Players");
			ScoreBoardAPI::setScoreLine($player, 7, TextFormat::RED. count($this->players).TextFormat::GRAY."/70");
			ScoreBoardAPI::setScoreLine($player, 8, TextFormat::WHITE."");
			ScoreBoardAPI::setScoreLine($player, 9, TextFormat::WHITE."Kills: ".TextFormat::RED.$this->kills[$player->getXuid()]);
			ScoreBoardAPI::setScoreLine($player, 10, TextFormat::GREEN."");
			ScoreBoardAPI::setScoreLine($player, 11, $this->getMessageLocalized("BORDER_FORMAT", [], []));
		    ScoreBoardAPI::setScoreLine($player, 12, TextFormat::GREEN."-".$this->getBorder().", +".$this->getBorder());
		    ScoreBoardAPI::setScoreLine($player, 13, TextFormat::BOLD."");
			ScoreBoardAPI::setScoreLine($player, 14, $this->getMessageLocalized("IP_SERVER", [], []));
		}
	}

    /**
     * @param string $subject
     * @param array $search
     * @param array $replace
     * @return string
     */
    private function getMessageLocalized(string $subject, array $search, array $replace) :string{
        $message = (new PluginData())->getMessage($subject);
        return str_replace($search, $replace, $message);
    }
	
	/**
     * @param Player $player
	 * @param string $subject
	 * @param string $search
	 * @param string $replace
	 */
	private function sendMessageLocalized(Player $player, string $subject, array $search, array $replace){
        $message = (new PluginData())->getMessage($subject);
        $player->sendMessage(str_replace($search, $replace, $message));
    }

	/**
	 * calls when player join game:
	 * setup his inventory, teleport to pedestal, update info
	 * broadcast message inside arena, update arena data
	 * 
	 * @param Player $player
	 */
	private function acceptPlayer(Player $player){
		//check count players enought 3
		if(count($this->players) < self::MIN_PLAYER_COUNT){
			$this->humanReadableTime = (int)microtime(true);
		}
		//teleport player to waiting lobby
		$world = $this->plugin->getServer()->getWorldManager()->getWorldByName($this->lobbywaiting["world"]);
		$position = Vector3::fromString($this->lobbywaiting["spawn"]);
		$player->teleport(Position::fromObject($position, $world));
		//operations with player
		$this->addPlayerToTeam($player);
		$this->addDataPlayer($player);		
		$this->broadcastMessageLocalized("JOINED_GAME", ["#player"], [$player->getName()]);
		//update arena info
		$this->players[$player->getXuid()] = $player;
	}
	
	/**
	 * calls when need for data player
	 * 
	 * @param Player $player
	 * @return UHCPlayer
	 */
	public function getDataPlayer(Player $player) :?UHCPlayer{
		return $this->plugin->players[$player->getXuid()];
	}

	/**
	 * Look for team with less count of members,
	 * check if player has already have team 
	 * save player in arena teams list and save team name in player's data
	 * 
	 * @param Player $player
	 */
	private function addPlayerToTeam(Player $player){
		if($this->isTeamMode()){
			$minArray = [];
			$targetTeamPlayersCount = PHP_INT_MAX;
			foreach($this->teams as $teamName => $teamPlayers){
				$playersCount = count($teamPlayers);
				if($playersCount < $targetTeamPlayersCount and $playersCount < self::MAX_PLAYER_INTEAM_COUNT){
					$minArray[$teamName] = $teamName;
				}
			}
			$targetTeamName = array_rand($minArray);
			$this->teams[$targetTeamName][$player->getXuid()] = $player;
			$this->plugin->getPlayer($player)->setTeam($targetTeamName);
		}
	}
	
	/**
	 * Calls when /player or team/ win arena
	 * $candidate is a color of won team
	 */
	private function won(){
		if(count($this->players) == 0){
			$this->restart();
			return;
		}
		$winners = [];
		$candidate = false;
		//look for winner player or team
		if($this->isTeamMode()){
			foreach($this->teams as $color => $team){
				if(count($team) > 0){
					if($candidate){
						return;
					}else{
						$candidate = $color;
					}
				}
			}
			if($candidate){
				$winners = $this->teams[$candidate];
			}
		}else{
			if(count($this->players) === 2){
				foreach($this->players as $player){
					$candidate = $player->getName();
				}
				$winners = $this->players;
			}
		}
		if($candidate){
			$winnername = ucfirst($candidate);
			foreach($winners as $winner){
				if($winner instanceof Player){
					//update settings for winner
					$winner->setHealth(20);
					$winner->getHungerManager()->setFood(20);
					$winner->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
					$this->removePlayer($winner, true);
				}
				//messages for winners
				// TODO
			}
			//and send messages to all players in lobby
			foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
				$dataPlayer = $this->getDataPlayer($player);
			    if(!$dataPlayer->isInGame()){
				    $player->sendMessage($this->getMessageLocalized("WON_MATCH_BROADCAST", ["#winner", "#map"], [$winnername, $this->getNameArena()]));
				}
			}
			$this->restart();
		}
	}
}