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

namespace hachkingtohach1\UHC\recipe;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\data\bedrock\EnchantmentIdMap;
use hachkingtohach1\UHC\UHC;
use hachkingtohach1\UHC\data\PluginData;

class Recipe{
    /*@var UHC*/
	private $plugin;
	
	/**
	 * @param UHC $plugin
	 */
    public function __construct(UHC $plugin){
		$this->plugin = $plugin;
    } 	

    /**
	 * @param int $id
	 * @return Enchantment
	 */
    private function getEnchantment(int $id) :?Enchantment{
		return EnchantmentIdMap::getInstance()->fromId($id);
	}	
    
	/**
	 * @param string $data
	 * @return Item
	 */
    private function getItem(string $dataitem) :Item{
	    $data = explode(":", $dataitem);
		$class = new ItemFactory();
		$item = $class->get(0, 0, 0, null);
		if(count($data) > 1){
            /** @var string $data */
            $item = $class->get($data[0], $data[1], $data[2], null);
		    //check item have custom name 
		    if($data[3] != "false"){
			    $item->setCustomName($data[3]);
			}
			//check item have string tag 
		    if($data[4] != "false"){
				$tag = explode("/", $data[4]);
			    $nbt = $item->getNamedTag();
		        if($tag[0] == "int"){
				    $nbt->setInt($tag[1], $tag[2]);
				}
				if($tag[0] == "string"){
				    $nbt->setString($tag[1], $tag[2]);
				}
		        $item->setNamedTag($nbt);
			}
		}
        return $item;
    }
	
	/**
	 * Call to register recipe for new item from PluginData
	 */
	public function registerItems(){		
		foreach((new PluginData())->getRecipes() as $recipe){
            $result = $this->getItem($recipe["result"]);
            //check item have enchantments
            if(count($recipe["enchant"]) >= 1){
                foreach($recipe["enchant"] as $enchant){
                    $result->addEnchantment(new EnchantmentInstance($this->getEnchantment($enchant[0]), $enchant[1]));
                }
            }
            $recipes = new ShapedRecipe( 
			    ["abc","def","ghi"], 
			    [			
		            "a" => $this->getItem($recipe["a"][0]),
		            "b" => $this->getItem($recipe["a"][1]),
		            "c" => $this->getItem($recipe["a"][2]),
		            "d" => $this->getItem($recipe["b"][0]),
		            "e" => $this->getItem($recipe["b"][1]),
		            "f" => $this->getItem($recipe["b"][2]),
		            "g" => $this->getItem($recipe["c"][0]),
		            "h" => $this->getItem($recipe["c"][1]),
		            "i" => $this->getItem($recipe["c"][2]), 
			    ],
			    [$result]
			);					
            //register recipe			
            $this->plugin->getServer()->getCraftingManager()->registerShapedRecipe($recipes);
        }
	}
}
