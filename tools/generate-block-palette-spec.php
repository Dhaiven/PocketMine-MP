<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\tools\generate_block_palette_spec;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\AssumptionFailedError;
use function array_values;
use function count;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function get_class;
use function json_encode;
use function ksort;

require dirname(__DIR__) . '/vendor/autoload.php';

if(count($argv) !== 3){
	fwrite(STDERR, "Required arguments: input palette file path, output JSON file path\n");
	exit(1);
}
$palette = BlockStateDictionary::loadFromString(file_get_contents($argv[1]));

$reportMap = [];

foreach($palette->getStates() as $state){
	$name = $state->getName();
	foreach($state->getStates() as $propertyName => $value){
		if($value instanceof IntTag || $value instanceof StringTag){
			$rawValue = $value->getValue();
		}elseif($value instanceof ByteTag){
			$rawValue = match($value->getValue()){
				0 => false,
				1 => true,
				default => throw new AssumptionFailedError("Unexpected ByteTag value for $name -> $propertyName ($value)")
			};
		}else{
			throw new AssumptionFailedError("Unexpected tag type for $name -> $propertyName ($value)");
		}
		$reportMap[$name][$propertyName][get_class($value) . ":" . $value->getValue()] = $rawValue;
	}
}

foreach($reportMap as $blockName => $propertyList){
	foreach($propertyList as $propertyName => $propertyValues){
		ksort($reportMap[$blockName][$propertyName]);
		$reportMap[$blockName][$propertyName] = array_values($propertyValues);
	}
}
ksort($reportMap, SORT_STRING);

file_put_contents($argv[2], json_encode($reportMap, JSON_PRETTY_PRINT));