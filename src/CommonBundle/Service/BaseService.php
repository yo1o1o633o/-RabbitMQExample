<?php

namespace CommonBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseService
{
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}