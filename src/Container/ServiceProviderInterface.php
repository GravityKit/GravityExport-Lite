<?php

namespace GFExcel\Container;

use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderInterface as LeagueServiceProviderInterface;

interface ServiceProviderInterface extends LeagueServiceProviderInterface, BootableServiceProviderInterface
{
}
