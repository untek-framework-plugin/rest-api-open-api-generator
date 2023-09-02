<?php

namespace Untek\FrameworkPlugin\RestApiOpenApiGenerator\Domain\Helpers;

use Untek\Model\Entity\Helpers\EntityHelper;
use Untek\Framework\Rpc\Domain\Model\RpcRequest;
use Untek\Sandbox\Sandbox\RpcMock\Domain\Libs\HasherHelper;

class RequestHelper
{

    public static function generateHash(RpcRequest $rpcRequestEntity)
    {
        $rpcRequestArray = EntityHelper::toArray($rpcRequestEntity);
        unset($rpcRequestArray['meta']['timestamp']);
        $hash = HasherHelper::generateDigest($rpcRequestArray);
        return $hash;
    }
}
