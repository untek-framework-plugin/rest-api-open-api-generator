<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Subscribers\GenerateOpenApiDocsSubscriber;

return function (EventDispatcherInterface $eventDispatcher, ContainerInterface $container) {
    if(getenv('OPEN_API_ENABLED')) {
        $eventDispatcher->addSubscriber($container->get(GenerateOpenApiDocsSubscriber::class)); // Генерация документации Open Api 3 YAML
    }
};
