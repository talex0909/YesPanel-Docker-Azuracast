<?php

namespace App\Entity\Interfaces;

use App\Entity;

interface StationCloneAwareInterface
{
    public function setStation(Entity\Station $station): void;
}
