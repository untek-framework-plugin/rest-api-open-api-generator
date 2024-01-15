<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Untek\Component\FormatAdapter\Drivers\Yaml;
use Untek\Component\Http\Helpers\SymfonyHttpResponseHelper;
use Untek\Component\Http\Helpers\UrlHelper;
use Untek\Core\Arr\Helpers\ArrayHelper;
use Untek\Core\FileSystem\Helpers\FileStorageHelper;
use Untek\Framework\Rpc\Domain\Model\RpcRequest;
use Untek\Framework\Rpc\Domain\Model\RpcResponse;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto\RequestDto;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto\ResponsetDto;

class OpenApi3
{

    private $sourceDirectory;
    private $openApiRequest;

    public function __construct(string $sourceDirectory)
    {
        $this->sourceDirectory = $sourceDirectory;
        $this->openApiRequest = new OpenApiRequest($sourceDirectory);
    }

    protected function extractHeaders($all)
    {
        $headers = [];
        foreach ($all as $headerKey => $headerValues) {
            $headers[$headerKey] = $headerValues[0];
        }
        return $headers;
    }

    protected function createRequsetDto(Request $request, Response $response): RequestDto
    {
        $urlData = UrlHelper::parse($request->getUri());

        $requestDto = new RequestDto();
        $requestDto->method = $request->getMethod();
        $requestDto->uri = $urlData['path'];

        $requestDto->uri = str_replace('/rest-api', '', $requestDto->uri);

        if (!empty($urlData['query'])) {
            $requestDto->query = $urlData['query'];
        }
        $requestDto->headers = SymfonyHttpResponseHelper::extractHeaders($request->headers->all());

        if ($request->getMethod() != 'GET') {
            $content = $request->getContent();
            $content = trim($content);
            if ($content) {
                $requestDto->body = json_decode($content, JSON_OBJECT_AS_ARRAY);
            }
        }

        $responseDto = new ResponsetDto();
        $responseDto->statusCode = $response->getStatusCode();
        $responseDto->body = json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);
        $responseDto->headers = SymfonyHttpResponseHelper::extractHeaders($response->headers->all());

        $requestDto->response = $responseDto;

        return $requestDto;
    }

    public function encode(Request $request, Response $response)
    {
        $requestDto = $this->createRequsetDto($request, $response);
        $postConfig = $this->openApiRequest->createPostRequest($requestDto);
        $paramsSchemaEncoder = new ParametersSchema();
        $this->makeEndpointConfig($requestDto, $postConfig);
        $tag = trim($requestDto->uri, '/');
        $main = $this->getPathsForMain($requestDto);
        $this->addPathInMain($main, $tag);
    }

    protected function getPathsForMain(RequestDto $requestDto)
    {
        $actionName = $requestDto->uri;
        $endPointPath = $this->getEndpointFileName($requestDto);
        $main['paths'][$actionName]['$ref'] = "./$endPointPath";
        return $main;
    }

    protected function getEndpointFileName(RequestDto $requestDto)
    {
        $endPointPath = trim($requestDto->uri, '/');
        return $endPointPath . '.yaml';
    }

    protected function makeEndpointConfig(RequestDto $requestDto, array $postConfig)
    {
        $methodName = mb_strtolower($requestDto->method);
        $res = [
            $methodName => $postConfig,
        ];

        $endPointPath = $this->getEndpointFileName($requestDto);

        $config = $this->loadYaml($endPointPath);
        $config = ArrayHelper::merge($config, $res);

        $this->saveYaml($endPointPath, $config);
    }

    protected function saveYaml($fileName, $data)
    {
        $encoder = new Yaml(2);
        $docsDir = $this->sourceDirectory;
        $mainYaml = $encoder->encode($data);
        $mainFile = "$docsDir/$fileName";
        FileStorageHelper::save($mainFile, $mainYaml);
    }

    protected function loadYaml($fileName)
    {
        $encoder = new Yaml(2);
        $docsDir = $this->sourceDirectory;
        $mainFile = "$docsDir/$fileName";
        if (is_file($mainFile)) {
            $yaml = file_get_contents($mainFile);
        } else {
            $yaml = '';
        }
        return $encoder->decode($yaml) ?: [];
    }

    protected function addPathInMain(array $config, string $tag)
    {
        $main = $this->loadYaml('index.yaml');
        $main = ArrayHelper::merge($main, $config);
        $this->saveYaml('index.yaml', $main);
    }
}