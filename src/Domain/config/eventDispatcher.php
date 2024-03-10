<?php

use Untek\Core\EventDispatcher\Interfaces\EventDispatcherConfiguratorInterface;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Subscribers\GenerateOpenApiDocsSubscriber;

return function (EventDispatcherConfiguratorInterface $configurator): void {
    if(getenv('OPEN_API_ENABLED')) {
        $configurator->addSubscriber(GenerateOpenApiDocsSubscriber::class); // Генерация документации Open Api 3 YAML
    }
};
