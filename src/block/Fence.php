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

namespace pocketmine\block;

use pocketmine\block\utils\SupportType;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use function count;

class Fence extends Transparent{
	/** @var Facing[] facing name => facing */
	protected array $connections = [];

	public function getThickness() : float{
		return 0.25;
	}

	public function readStateFromWorld() : Block{
		parent::readStateFromWorld();

		$this->collisionBoxes = null;

		foreach(Facing::HORIZONTAL as $facing){
			$block = $this->getSide($facing);
			if($block instanceof static || $block instanceof FenceGate || $block->getSupportType($facing->opposite()) === SupportType::FULL){
				$this->connections[$facing->name] = $facing;
			}else{
				unset($this->connections[$facing->name]);
			}
		}

		return $this;
	}

	protected function recalculateCollisionBoxes() : array{
		$inset = 0.5 - $this->getThickness() / 2;

		$bbs = [];

		$connectWest = isset($this->connections[Facing::WEST->name]);
		$connectEast = isset($this->connections[Facing::EAST->name]);

		if($connectWest || $connectEast){
			//X axis (west/east)
			$bbs[] = AxisAlignedBB::one()
				->squashedCopy(Axis::Z, $inset)
				->extendedCopy(Facing::UP, 0.5)
				->trimmedCopy(Facing::WEST, $connectWest ? 0 : $inset)
				->trimmedCopy(Facing::EAST, $connectEast ? 0 : $inset);
		}

		$connectNorth = isset($this->connections[Facing::NORTH->name]);
		$connectSouth = isset($this->connections[Facing::SOUTH->name]);

		if($connectNorth || $connectSouth){
			//Z axis (north/south)
			$bbs[] = AxisAlignedBB::one()
				->squashedCopy(Axis::X, $inset)
				->extendedCopy(Facing::UP, 0.5)
				->trimmedCopy(Facing::NORTH, $connectNorth ? 0 : $inset)
				->trimmedCopy(Facing::SOUTH, $connectSouth ? 0 : $inset);
		}

		if(count($bbs) === 0){
			//centre post AABB (only needed if not connected on any axis - other BBs overlapping will do this if any connections are made)
			return [
				AxisAlignedBB::one()
					->extendedCopy(Facing::UP, 0.5)
					->contractedCopy($inset, 0, $inset)
			];
		}

		return $bbs;
	}

	public function getSupportType(Facing $facing) : SupportType{
		return $facing->axis() === Axis::Y ? SupportType::CENTER : SupportType::NONE;
	}
}
