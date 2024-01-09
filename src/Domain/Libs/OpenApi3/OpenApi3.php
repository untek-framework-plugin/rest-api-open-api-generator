<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3;

use GuzzleHttp\Psr7\Query;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Untek\Core\Arr\Helpers\ArrayHelper;
use Untek\Core\FileSystem\Helpers\FileStorageHelper;
use Untek\Core\Text\Helpers\Inflector;
use Untek\Model\Entity\Helpers\EntityHelper;
use Untek\Framework\Rpc\Domain\Model\RpcRequest;
use Untek\Framework\Rpc\Domain\Model\RpcResponse;
use Untek\Component\Http\Helpers\SymfonyHttpResponseHelper;
use Untek\Component\Http\Helpers\UrlHelper;
use Untek\Component\FormatAdapter\Drivers\Php;
use Untek\Component\FormatAdapter\Drivers\Yaml;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto\RequestDto;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto\ResponsetDto;
use Untek\Sandbox\Sandbox\RpcClient\Symfony4\Admin\Forms\RequestForm;
use Untek\Sandbox\Sandbox\RpcMock\Domain\Libs\HasherHelper;

class OpenApi3
{

    private $sourceDirectory;
    private $openApiRequest;

    public function __construct(string $sourceDirectory)
    {
        $this->sourceDirectory = $sourceDirectory;
        $this->openApiRequest = new OpenApiRequest($sourceDirectory);
    }

    protected function extractHeaders($all) {
        $headers = [];
        foreach ($all as $headerKey => $headerValues) {
            $headers[$headerKey] = $headerValues[0];
        }
        return $headers;
    }

    protected function createRequsetDto(Request $request, Response $response): RequestDto {

//        $r = parse_url($request->getUri());
//        dd($r);
//        dd((Query::parse($r['query'])));

        $urlData = UrlHelper::parse($request->getUri());

        $requestDto = new RequestDto();
        $requestDto->method = $request->getMethod();
        $requestDto->uri = $urlData['path'];

        $requestDto->uri = str_replace('/rest-api', '', $requestDto->uri);

        if(!empty($urlData['query'])) {
            $requestDto->query = $urlData['query'];
//            dd($urlData['query']);
        }
        $requestDto->headers = SymfonyHttpResponseHelper::extractHeaders($request->headers->all());

        $content = $request->getContent();
        $content = trim($content);
        if($content) {
            $requestDto->body = json_decode($content, JSON_OBJECT_AS_ARRAY);
        }

        $responseDto = new ResponsetDto();
        $responseDto->statusCode = $response->getStatusCode();
        $responseDto->body = json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY);
        $responseDto->headers = SymfonyHttpResponseHelper::extractHeaders($response->headers->all());

        $requestDto->response = $responseDto;

        return $requestDto;
    }

    public function encode(Request $request, Response $response) {
        $requestDto = $this->createRequsetDto($request, $response);

        $postConfig = $this->openApiRequest->createPostRequest($requestDto);

        $paramsSchemaEncoder = new ParametersSchema();
        /*$postConfig['parameters'] = [];

        if ($data['meta']) {
            $parameters = $paramsSchemaEncoder->encode($data['meta']);
            $postConfig['parameters'] = ArrayHelper::merge(
                $postConfig['parameters'],
                $parameters
            );
        }
        if ($data['body']) {
            $parameters = $paramsSchemaEncoder->encode($data['body'], 'body');
            $postConfig['parameters'] = ArrayHelper::merge(
                $postConfig['parameters'],
                $parameters
            );
        }*/


        $this->makeEndpointConfig($requestDto, $postConfig);

        $tag = trim($requestDto->uri, '/');
//        $actionName = $requestDto->uri;
//        dd(123);

//        $methodName = $request->getMethod();
//        list($tag, $actionName) = explode('.', $methodName);

        $main = $this->getPathsForMain($requestDto);
//        dd($main);
        $this->addPathInMain($main, $tag);
    }

    protected function getPathsForMain(RequestDto $requestDto)
    {
//        $tag = trim($requestDto->uri, '/');

        $actionName = $requestDto->uri;

//        $methodName = $requestDto->uri;
//        list($tag, $actionName) = explode('.', $methodName);
        $endPointPath = $this->getEndpointFileName($requestDto);

//        $hash = RequestHelper::generateHash($requestDto);
        $main['paths'][$actionName]['$ref'] = "./$endPointPath";
        return $main;
    }

    protected function getEndpointFileName(RequestDto $requestDto)
    {
        $endPointPath = trim($requestDto->uri, '/');
//        $endPointPath = $endPointPath . '/' . mb_strtolower($requestDto->method);
        return $endPointPath . '.yaml';

//        $methodName = $requestDto->getMethod();
//        list($tag, $actionName) = explode('.', $methodName);
//        $hash = RequestHelper::generateHash($requestDto);
//        $actionHash = $actionName . '-' . $hash;
//        $endPointPath = "$tag/$actionHash.yaml";
//        return $endPointPath;
    }

    protected function makeEndpointConfig(RequestDto $requestDto, array $postConfig)
    {
        $methodName = mb_strtolower($requestDto->method);
//        $actionName = $requestDto->uri;
//        dd($actionName);
//        list($tag, $actionName) = explode('.', $methodName);
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
//        dd($mainFile);
        FileStorageHelper::save($mainFile, $mainYaml);
    }

    protected function loadYaml($fileName)
    {
        $encoder = new Yaml(2);
        $docsDir = $this->sourceDirectory;
        $mainFile = "$docsDir/$fileName";
        if(is_file($mainFile)) {
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
//        ksort($main['paths']);

        /*if (!empty($main['tags'])) {
            $hasTag = false;
            foreach ($main['tags'] as $tagItem) {
                if ($tagItem['name'] == $tag) {
                    $hasTag = true;
                }
            }
        } else {
            $hasTag = false;
        }

        if (!$hasTag) {
            $main['tags'][] = [
                'name' => $tag,
                'description' => Inflector::titleize($tag),
            ];
        }*/

        $this->saveYaml('index.yaml', $main);
    }
}