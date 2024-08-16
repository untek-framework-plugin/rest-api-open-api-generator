<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto;

class RequestDto
{

    public string $method = 'GET';
    public string $uri;
    public array $query = [];
    public mixed $body = null;
    public array $headers = [];
    public ResponsetDto $response;

}
