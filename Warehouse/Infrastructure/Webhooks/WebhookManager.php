<?php


namespace App\Warehouse\Infrastructure\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookManager
{
    public static function handle(Request $request, string $webhookName)
    {
        if (! $webhookClass = config('warehouse.webhooks.'.$webhookName.".handler"))
        {
            return;
        }

        $webhook = new $webhookClass($request);
        $webhook->handle();
    }
}
