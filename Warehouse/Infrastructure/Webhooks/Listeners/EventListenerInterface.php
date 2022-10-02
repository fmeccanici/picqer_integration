<?php


namespace App\Warehouse\Infrastructure\Webhooks\Listeners;

use Illuminate\Http\Request;

interface EventListenerInterface
{
    public function handle(Request $request);
}
