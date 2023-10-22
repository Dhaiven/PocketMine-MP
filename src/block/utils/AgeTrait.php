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

namespace pocketmine\block\utils;

use pocketmine\data\runtime\RuntimeDataDescriber;
use function decbin;
use function strlen;

trait AgeTrait{

	protected int $age = self::MIN_AGE;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->boundedInt(strlen(decbin($this->age)), self::MIN_AGE, self::MAX_AGE, $this->age);
	}

	public function getAge() : int{
		return $this->age;
	}

	/** @return $this */
	public function setAge(int $age) : self{
		if($age < self::MIN_AGE || $age > self::MAX_AGE){
			throw new \InvalidArgumentException("Age must be in the range " . self::MIN_AGE . " ... " . self::MAX_AGE);
		}
		$this->age = $age;
		return $this;
	}
}
