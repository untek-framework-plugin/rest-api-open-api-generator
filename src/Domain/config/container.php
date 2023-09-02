<?php

use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3\OpenApi3;

return [
	'singletons' => [
	    OpenApi3::class => function(\Psr\Container\ContainerInterface $container) {
            /** @var \Untek\Core\App\Interfaces\EnvStorageInterface $envStorage */
            $envStorage = $container->get(\Untek\Core\App\Interfaces\EnvStorageInterface::class);

            return new OpenApi3($envStorage->get('OPEN_API_REST_API_SOURCE_DIRECTORY'));
        }
	],
];