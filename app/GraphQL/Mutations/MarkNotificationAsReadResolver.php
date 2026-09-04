<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class MarkNotificationAsReadResolver
{
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $notification = $context->user()
            ->notifications()
            ->where('id', $args['id'])
            ->firstOrFail();

        $notification->markAsRead();

        return $notification;
    }
}
