<?php declare(strict_types=1);

namespace App\Services\Api;

use OpenAPI\Server\Api\AdminsApiInterface;
use OpenAPI\Server\Model\AdminRequest;

class AdminApiService implements AdminsApiInterface
{
    public function adminsCreatePost(AdminRequest $adminRequest): mixed
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
