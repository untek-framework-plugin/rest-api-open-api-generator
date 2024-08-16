<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3\OpenApi3;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Subscribers\GenerateOpenApiDocsSubscriber;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()->defaults()->public()->autoconfigure();

    $services->set(OpenApi3::class, OpenApi3::class)
        ->args([
            getenv('OPEN_API_REST_API_SOURCE_DIRECTORY')
        ]);

    if (getenv('OPEN_API_ENABLED')) {
        $services->set(GenerateOpenApiDocsSubscriber::class, GenerateOpenApiDocsSubscriber::class)
            ->args([
                service(OpenApi3::class)
            ]);
    }
};