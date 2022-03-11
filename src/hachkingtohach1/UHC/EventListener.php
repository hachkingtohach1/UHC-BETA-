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
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\InkParticle;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use hachkingtohach1\UHC\player\UHCPlayer;

/**
 * base plugin EventListener, holding events like onPlayerLogin etc
 * 
 */
class EventListener implements Listener{	
	/*@var UHC*/
	private ?UHC $plugin;
	
	const SIZE_BORDER = 1;
	
	/**
	 * base class constructor
	 * 
	 * @param UHC $plugin
	 */
	public function __construct(UHC $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param Player $player
	 */
	private function isPlayer(Player $player) :bool{
		if(isset($this->plugin->players[$player->getXuid()])){
			return true;
		}
		return false;
	}

    /**
     * @param Player $player
     * @return UHCPlayer|null
     */
	private function getDataPlayer(Player $player) :?UHCPlayer{
		return $this->plugin->players[$player->getXuid()];
	}

	/**
	 * Calls when player comes into game
	 * 
	 * @param PlayerLoginEvent $event
	 */
	public function onPlayerLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();	
		$this->plugin->players[$player->getXuid()] = new UHCPlayer($player);
	}

    /**
     * Calls when player comes into game
     * check to can send message when player death in arena
     *
     * @param string $attacker
     * @param int $lastAttack
     * @return bool
     */
	private function canSendDeathMessage(string $attacker, int $lastAttack) :bool{
		if($lastAttack != 0 or $attacker != ""){
			$timeDiff = microtime(true) - $lastAttack;
			if($timeDiff <= 5){
				return true;
			}
		}	
        return false;		
	}
	
	/**
	 * Calls when player join game
	 * 
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
	    $event->setJoinMessage(TextFormat::GREEN."[+] ".TextFormat::GRAY.$player->getName());
	}
	
	/**
	 * Calls when player quit game
	 * Unset data for player when in arena
	 * 
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if($this->isPlayer($player)){			
			$dataPlayer = $this->getDataPlayer($player);
			if($dataPlayer->isInGame()){
			    $dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
			    if($dataArena instanceof Arenas){	
				    $dataArena->removePlayer($player, false, true);
				}
			}
		}
		$event->setQuitMessage("");
	}
	
	/**
	 * Calls when player cause damage
	 * 
	 * @param EntityDamageEvent $event
	 */
	public function onEntityDamage(EntityDamageEvent $event){		
		$cause = $event->getCause();	
		$entity = $event->getEntity();		
		if($entity instanceof Player){
			if($this->isPlayer($entity)){
				$dataPlayer = $this->getDataPlayer($entity);
				if($dataPlayer->isInGame()){
					$dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
					if($dataArena->isStarted()){
						if($event->getFinalDamage() >= $entity->getHealth()){
						    $event->cancel();
                            $attacker = $dataPlayer->getAttacker();
						    $lastAttack = $dataPlayer->getLastAttack();							
							switch($cause){
								case $event::CAUSE_CONTACT:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_CONTACT_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_CONTACT", ["#player1"], [$entity->getName()]);
									}
							    	break;
						    	case $event::CAUSE_ENTITY_ATTACK:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_ENTITY_ATTACK", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}
							    	break;
								case $event::CAUSE_PROJECTILE:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_PROJECTILE_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_PROJECTILE", ["#player1"], [$entity->getName()]);
									}
							    	break;
								case $event::CAUSE_SUFFOCATION:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_SUFFOCATION_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_SUFFOCATION", ["#player1"], [$entity->getName()]);
									}
							   		break;
								case $event::CAUSE_FALL:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_FALL_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_FALL", ["#player1"], [$entity->getName()]);
									}
							    	break;
                                case $event::CAUSE_FIRE_TICK:
                                case $event::CAUSE_FIRE:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_FIRE_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_FIRE", ["#player1"], [$entity->getName()]);
									}
							    	break;
                                case $event::CAUSE_LAVA:
									$dataArena->broadcastMessageLocalized("CAUSE_LAVA", ["#player1"], [$entity->getName()]);
							    	break;
								case $event::CAUSE_BLOCK_EXPLOSION:
								    $dataArena->broadcastMessageLocalized("CAUSE_BLOCK_EXPLOSION", ["#player1"], [$entity->getName()]);
							    	break;
								case $event::CAUSE_ENTITY_EXPLOSION:
								    $dataArena->broadcastMessageLocalized("CAUSE_ENTITY_EXPLOSION", ["#player1"], [$entity->getName()]);
							    	break;
								case $event::CAUSE_VOID:
								    if($this->canSendDeathMessage($attacker, $lastAttack)){
									    $dataArena->broadcastMessageLocalized("CAUSE_VOID_PLAYER", ["#player1", "#player2"], [$entity->getName(), $attacker]);
									}else{
										$dataArena->broadcastMessageLocalized("CAUSE_VOID", ["#player1"], [$entity->getName()]);
									}
							    	break;
								case $event::CAUSE_SUICIDE:
								    $dataArena->broadcastMessageLocalized("CAUSE_SUICIDE", ["#player1"], [$entity->getName()]);
							    	break;
								case $event::CAUSE_MAGIC:
								    $dataArena->broadcastMessageLocalized("CAUSE_MAGIC", ["#player1"], [$entity->getName()]);
							    	break;
							}
							$dataArena->setSpectator($entity);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Calls when player attack other player in game
	 * Check player in team and check when invincible
	 * 
	 * @param EntityDamageByEntityEvent $event
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if($entity instanceof Player and $damager instanceof Player){
			if($this->isPlayer($entity) and $this->isPlayer($damager)){
				$dataEntity = $this->getDataPlayer($entity);
				$dataDamager = $this->getDataPlayer($damager);
				if($dataEntity->isInGame() and $dataDamager->isInGame()){
                    $dataArenaEntity = $this->plugin->arenas[$dataEntity->getNameArena()];
                    $dataArenaDamager = $this->plugin->arenas[$dataDamager->getNameArena()];					
				    if($dataEntity->getNameArena() == $dataDamager->getNameArena()){   
						//set time for last attack and rewrite name attacker in data player
						$lastAttack = microtime(true);
						$dataEntity->setAttacker($damager->getName());
						$dataDamager->setAttacker($entity->getName());
						$dataEntity->setLastAttack($lastAttack);
						$dataDamager->setLastAttack($lastAttack);
						//check event
						if($dataArenaEntity->isTeamMode()){
						    if($dataArenaEntity->isStarted() and $dataArenaDamager->isStarted() and $dataEntity->getTeam() == $dataDamager->getTeam()){
					            $event->cancel();
							}
						}
						if($dataArenaEntity->isStarted() and $dataArenaDamager->isStarted() and $dataArenaEntity->isInvincible() and $dataArenaDamager->isInvincible()){
							$event->cancel();
						}
						if(!$dataArenaEntity->isStarted() and !$dataArenaDamager->isStarted()){
							$event->cancel();
						}
					}
				}
			}
		}
	}
	
	/**
	 * Calls when player move into game
	 * 
	 * @param PlayerMoveEvent $event
	 */
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		if($this->isPlayer($player)){
			$dataPlayer = $this->getDataPlayer($player);
			if($dataPlayer->isInGame()){
				$dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
				if($dataArena->isStarted()){
					//add particle when player go near border
					$world = $player->getWorld();
                	$minX = (float)$player->getLocation()->x * 16;
                	$maxX = (float)$minX + 16;
					$minY = (float)$player->getLocation()->y * 16;
                	$maxY = (float)$minY + 16;
                	$minZ = (float)$player->getLocation()->z * 16;
                	$maxZ = (float)$minZ + 16;
                	for($x = $minX; $x <= $maxX; $x += 0.5){
                    	for($y = $minY; $y <= $maxY; $y += 0.5){
							for($z = $minZ; $z <= $maxZ; $z += 0.5){
                        	    if($x === $minX || $x === $maxX || $z === $minZ || $z === $maxZ){
                            	    $world->loadChunk($x >> 4, $z >> 4);
								    $player->getWorld()->addParticle(new Vector3($x, $y, $z), new InkParticle(self::SIZE_BORDER), [$player]);
								}
							}
                    	}
                	}
					$worldArena = $this->plugin->getServer()->getWorldManager()->getWorldByName($dataArena->getWorld()->getFolderName());
					$xs = (int)$worldArena->getSpawnLocation()->x + $dataArena->getBorder();
        			$zs = (int)$worldArena->getSpawnLocation()->z + $dataArena->getBorder();		
        			//player's current XZ
        			$xp = $player->getPosition()->getFloorX();
        			$zp = $player->getPosition()->getFloorZ();			
        			//calculate
        			$x1 = abs($xp);
        			$z1= abs($zp);
        			$x2 = abs($xs);
        			$z2= abs($zs);	
        			//checking if player XZ is greater than border
        			if($x1 >= $x2 or $z1 >= $z2){
						$ev = new EntityDamageByEntityEvent($player, $player, EntityDamageEvent::CAUSE_MAGIC, 0.1);
                		$player->attack($ev);
           				$player->sendPopup(TextFormat::BOLD.TextFormat::RED."-[!]-[!]-[!]-[!]-[!]-");
					}
				}
			}
		}
	}
	
	/**
	 * Calls when player interact any block
	 * 
	 * @param PlayerInteractEvent $event
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		if($this->isPlayer($player)){
			$dataPlayer = $this->getDataPlayer($player);			
			if($dataPlayer->isInGame()){
				$dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
				if(!$dataArena->isStarted()){
					$event->cancel();
				}
			}
		}
	}
	
	/**
	 * Calls when player place block
	 * 
	 * @param BlockPlaceEvent $event
	 */
	public function onBlockPlace(BlockPlaceEvent $event){
		$player = $event->getPlayer();
		if($this->isPlayer($player)){
			$dataPlayer = $this->getDataPlayer($player);			
			if($dataPlayer->isInGame()){
				$dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
				if(!$dataArena->isStarted()){
					$event->cancel();
				}
			}
		}
	}
	
	/**
	 * Calls when player break block
	 * 
	 * @param BlockBreakEvent $event
	 */
	public function onBlockBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		if($this->isPlayer($player)){
			$dataPlayer = $this->getDataPlayer($player);			
			if($dataPlayer->isInGame()){
				$dataArena = $this->plugin->arenas[$dataPlayer->getNameArena()];
				if(!$dataArena->isStarted()){
					$event->cancel();
				}
			}
		}
	}
}