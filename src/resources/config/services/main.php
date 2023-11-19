<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Untek\Framework\RestApi\Presentation\Http\Symfony\Subscribers\RestApiHandleSubscriber;
use Untek\FrameworkPlugin\RestApiErrorHandle\Presentation\Http\Symfony\Controllers\RestApiErrorController;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3\OpenApi3;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Subscribers\GenerateOpenApiDocsSubscriber;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(OpenApi3::class, OpenApi3::class)
        ->args([
            getenv('OPEN_API_REST_API_SOURCE_DIRECTORY')
        ]);

    $services->set(GenerateOpenApiDocsSubscriber::class, GenerateOpenApiDocsSubscriber::class)
        ->args([
            service(OpenApi3::class)
        ]);
};