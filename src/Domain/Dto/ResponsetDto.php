<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto;

class ResponsetDto
{

    public int $statusCode;
    public mixed $body;
    public array $headers = [];

}
