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

namespace hachkingtohach1\UHC\data;

/**
 * Contains coords for arena and team tiles in lobby,
 * main variable is an array of map data (deathmatch/lobbywaiting coords for each arena)
 */
class PluginData{
	/*@var array*/
	private static $data = [
	    "aa-001" => [
		    "world" => "aa-001",
			"spawns" => [
			    1 => "963,86,14",
				2 => "1044,71,26",
				3 => "1024,82,152",
				4 => "1068,70,281",
				5 => "1106,65,445",
				6 => "1289,66,450",
				7 => "1196,71,680",
				8 => "1100,65,676"
			],
			"deathmatch" => [
			    "spawn" => "256,4,256",
				"world" => "Waiting"
			],
			"lobbywaiting" => [
			    "spawn" => "256,4,256",
				"world" => "Waiting"
			],
			"team" => false,
			"border" => 1000
		]
	];
	/*@var array*/
	private static $messages = [
	    //events
	    "JOINED_GAME" => "§9#player has joined the game!",
		"LEFT_GAME" => "§c#player has left the game!",		
		"STARTING_IN" => "§fStarting in §6#time",
		"WON_MATCH" => "§f#winner §6won the match.\n§7Returning to the lobby...",
		"WON_MATCH_BROADCAST" => "§a#winner won the match on arena #map",
		
		//arena
        "STARTED" => "§aStarted!",
        "NOT_ENOUGHT_PLAYER" => "§cNot enough players to start a match!",		
        "INVINCIBILITY" => "§aYou have §65 §aminute(s) for invincibility.",
        "END_INVINCIBILITY" => "§6The time for invincibility is over!",        
		"BORDER_UPDATE" => "§bBorder updated < #border - #border >",
		"DEATHMATCH_START" => "§cDeathmatch started!",		
		"DEATHMATCH" => "§3Deathmatch starting in §e#time",
		
		//format
		"STARTING_FORMAT" => "§fStarting in",	
		"INVINCIBILITY_FORMAT" => "§fPvP enables in",	
        "DEATHMATCH_FORMAT" => "§fDeathmatch in",
		"ENDGAME_FORMAT" => "§fGame ends in",
        "BORDER_FORMAT" => "§fWorld Border",			

        //events for death and case damage
		"CAUSE_CONTACT" => "§c#player1 was killed by magic",
		"CAUSE_CONTACT_PLAYER" => "§c#player1 was killed by magic from #player2",
        "CAUSE_ENTITY_ATTACK" => "§c#player1 was slain by #player2",
        "CAUSE_PROJECTILE_PLAYER" => "§c#player1 was shot dead by #player2",
		"CAUSE_PROJECTILE" => "§c#player1 was shot dead",
		"CAUSE_SUFFOCATION_PLAYER" => "§c#player1 was suffocated to death by #player2",
		"CAUSE_SUFFOCATION" => "§c#player1 was suffocated to death",
		"CAUSE_FALL_PLAYER" => "§c#player1 was so afraid of #player2 that he fell to his death",
		"CAUSE_FALL" => "§c#player1 fell from above and died",
		"CAUSE_FIRE_PLAYER" => "§c#player1 was burned by #player2",		
		"CAUSE_FIRE" => "§c#player1 was burned to death",
        "CAUSE_LAVA" => "§c#player was burned by lava",	
		"CAUSE_BLOCK_EXPLOSION" => "§c#player killed by an explosion",
        "CAUSE_ENTITY_EXPLOSION" => "§c#player was killed by an explosive object",
        "CAUSE_VOID_PLAYER" => "§c#player fell into the void by #player2",	
		"CAUSE_VOID" => "§c#player fell into the void",	
        "CAUSE_SUICIDE" => "§c#player committed suicide",	
        "CAUSE_MAGIC" => "§c#player was killed by magic",

        //ip server
		"IP_SERVER" => "§cfoxcraft.zapto.org"
	];
	/*@var array*/
	private static $recipes = [
	    0 => [
		    "a" => ["264:0:1:false:false", "264:0:1:false:false", "264:0:1:false:false"],
            "b" => ["264:0:1:false:false", "280:0:1:false:false", "264:0:1:false:false"],
            "c" => ["0", "280:0:1:false:false", "0"],
			"result" => "278:0:1:Good Diamond Pickaxe:false",
			"enchant" => [
			    [15, 1]
			]
		]
	];
	
	/**
	 * @return array
	 */
	public function getData() :array{
		return self::$data;
	}

    /**
     * @param string $key
     * @return string
     */
	public function getMessage(string $key) :string{
		return self::$messages[$key];
	}
	
	/**
	 * @return array
	 */
	public function getRecipes() :array{
		return self::$recipes;
	}
}