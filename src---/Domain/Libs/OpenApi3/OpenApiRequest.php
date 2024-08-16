<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Libs\OpenApi3;

use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Untek\Core\Arr\Helpers\ArrayHelper;
use Untek\Core\FileSystem\Helpers\FileStorageHelper;
use Untek\Core\Text\Helpers\Inflector;
use Untek\Model\Entity\Helpers\EntityHelper;
use Untek\Framework\Rpc\Domain\Model\RpcRequest;
use Untek\Framework\Rpc\Domain\Model\RpcResponse;
use Untek\Component\FormatAdapter\Drivers\Yaml;
use Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Dto\RequestDto;
use Untek\Sandbox\Sandbox\RpcClient\Symfony4\Admin\Forms\RequestForm;
use Untek\Sandbox\Sandbox\RpcMock\Domain\Libs\HasherHelper;

class OpenApiRequest
{

    private $sourceDirectory;

    public function __construct(string $sourceDirectory)
    {
        $this->sourceDirectory = $sourceDirectory;
    }

    protected function generateRpcRequest(RpcRequest $rpcRequestEntity, RpcResponse $rpcResponseEntity)
    {
        $data = $this->getData($rpcRequestEntity);
        $data = $this->forgePaginate($rpcResponseEntity, $data);
        $data = $this->clearPayloadTails($data);
        $requestArray = [
            "jsonrpc" => "2.0",
            "method" => $rpcRequestEntity->getMethod(),
            "params" => $data,
        ];
        return $requestArray;
    }

    protected function clearPayloadTails($data)
    {
        if (empty($data['body'])) {
            unset($data['body']);
        }

        if (empty($data['meta'])) {
            unset($data['meta']);
        }
        return $data;
    }

    protected function generateRpcResponse(RpcRequest $rpcRequestEntity, RpcResponse $rpcResponseEntity)
    {
        $rpcResponse = [
            "jsonrpc" => "2.0",
        ];
        if ($rpcResponseEntity->getError()) {
            $rpcResponse['error'] = $rpcResponseEntity->getError();
        }
        if ($rpcResponseEntity->getResult()) {
            $rpcResponse['result']['body'] = $rpcResponseEntity->getResult();
        }
        $responseMeta = $rpcResponseEntity->getMeta();
        if ($responseMeta) {
            $rpcResponse['result']['meta'] = $responseMeta;
        }
        if (!empty($rpcResponse['result'])) {
            $rpcResponse['result'] = $this->clearPayloadTails($rpcResponse['result']);
        }
        return $rpcResponse;
    }

    protected function getData(RpcRequest $rpcRequestEntity)
    {
        $data = [
            'body' => [],
            'meta' => [],
        ];
        if ($rpcRequestEntity->getParams()) {
            $data['body'] = $rpcRequestEntity->getParams();
        }
        if ($rpcRequestEntity->getMeta()) {
            $meta = $rpcRequestEntity->getMeta();
            if (array_key_exists('timestamp', $meta)) {
                unset($meta['timestamp']);
            }
            if (array_key_exists('version', $meta)) {
                unset($meta['version']);
            }
            if (array_key_exists('Authorization', $meta)) {
                unset($meta['Authorization']);
            }
            $data['meta'] = $meta;
        }
        return $data;
    }

    protected function isHasAuth(RpcRequest $rpcRequestEntity): bool
    {
        if ($rpcRequestEntity->getMeta() == null) {
            return false;
        }
        return array_key_exists('Authorization', $rpcRequestEntity->getMeta());
    }

    protected function forgePaginate(RpcResponse $rpcResponseEntity, $data)
    {
        $responseMeta = $rpcResponseEntity->getMeta();
        $isPaginate = isset($responseMeta['perPage']) && isset($responseMeta['totalCount']) && isset($responseMeta['page']);
        if ($isPaginate) {
            $data['body']['perPage'] = $responseMeta['perPage'];
            $data['body']['page'] = $responseMeta['page'];
        }
        return $data;
    }

    public function createPostRequest(RequestDto $requestDto) {
        $dataSchemaEncoder = new DataSchema();

        $responseSchema = $dataSchemaEncoder->encode($requestDto->response->body);
        $responseSchema['example'] = $requestDto->response->body;
        $statusCode = $requestDto->response->statusCode;

        unset($responseSchema['description']);

        $postConfig = [
            'tags' => [
                'Default'
            ],
            'summary' => 'Description',
            'responses' => [
                $statusCode => [
                    'content' => [
                        'application/json' => [
                            'schema' => $responseSchema
                        ]
                    ]
                ]
            ],
        ];

//        dd($responseSchema);

        if($requestDto->body) {
            $requestSchema = $dataSchemaEncoder->encode($requestDto->body);
            unset($requestSchema['description']);
//            dd($requestSchema);
            $requestSchema['example'] = $requestDto->body;
            $postConfig['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => $requestSchema
                    ]
                ]
            ];
        }

        if($requestDto->query) {
            $postConfig['parameters'] = $dataSchemaEncoder->encodeParameters($requestDto->query, 'query');
        }

        return $postConfig;
    }
}