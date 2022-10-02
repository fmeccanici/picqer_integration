<?php

use App\Warehouse\Presentation\Http\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::match(["get", "post"], "/estimate-shipping-date", [WarehouseController::class, "estimateShippingDate"]);
Route::match(["get", "post"], "/estimate-delivery-date", [WarehouseController::class, "estimateDeliveryDate"]);
Route::match(["get", "post"], "/explain-delivery-date", [WarehouseController::class, "explainDeliveryDate"]);
Route::match(["put"], "/change-picklist-comments", [WarehouseController::class, "changePicklistComments"]);
Route::match(["get"], "/search-picklist", [WarehouseController::class, "searchPicklist"]);
Route::match(["get"], "/get-order-from-picklist", [WarehouseController::class, "getOrderFromPicklist"]);
Route::match(["get"], "/notify-customer-of-fully-shipped-order", [WarehouseController::class, "notifyCustomerOfFullyShippedOrder"]);
Route::post("/snooze-picklist", [WarehouseController::class, "snoozePicklist"])->name("snooze-picklist");
Route::post("/unsnooze-picklist", [WarehouseController::class, "unsnoozePicklist"])->name("unsnooze-picklist");
Route::post("/webhooks/{webhookName?}", [WarehouseController::class, "handleWebhook"]);
Route::post("/transfer-new-orders-to-picqer", [WarehouseController::class, 'transferNewOrdersToPicqer'])->name("transfer-new-orders-to-picqer")->withoutMiddleware('auth:api');
Route::match(["get", "post"], "/list-backorders", [WarehouseController::class, "listBackorders"])->name('list-backorders');
Route::match(["get", "post"], "/delay-backorder", [WarehouseController::class, "delayBackorder"])->name('delay-backorder');
Route::match(["get", "post"], "/list-delay-backorder-reasons", [WarehouseController::class, "listDelayBackorderReasons"])->name('list-delay-backorder-reasons');
Route::match(["put"], "/orders/delivery_option", [WarehouseController::class, "changeDeliveryOption"])->name('change-delivery-option-of-order');
Route::post("/orders/delivery_option", [WarehouseController::class, "getDeliveryOption"])->name("get-delivery-option");
Route::get('/orders/{orderReference}/cancel/', [WarehouseController::class, 'cancelOrder'])->name('cancel-order')->withoutMiddleware('auth:api');
Route::get('/orders/{orderReference}/cancel/after-discussing', [WarehouseController::class, 'cancelOrderAfterDiscussing'])->name('cancel-order-after-discussing')->withoutMiddleware('auth:api');
Route::put('/orders/{orderReference}/delivery-date/', [WarehouseController::class, 'changeOrderDeliveryDate'])->name('change-order-delivery-date')->withoutMiddleware('auth:api');
Route::put('/orders/{orderReference}/delivery-date/after-discussing', [WarehouseController::class, 'changeOrderDeliveryDateAfterDiscussing'])->name('change-order-delivery-date-after-discussing')->withoutMiddleware('auth:api');

Route::post('/orders/handle-shipment-created', [WarehouseController::class, 'handleShipmentCreated'])->name('handle-shipment-created')->withoutMiddleware('auth:api');
Route::post("/webhooks/{webhookName?}", [WarehouseController::class, "handleWebhook"])
    ->withoutMiddleware('auth:api')
    ->name('handle-warehouse-webhook');


Route::get("/picklists/{picklistId}/pdf", [WarehouseController::class, 'streamPicklistPdf'])->name('stream-picklist')->withoutMiddleware('auth:api');
Route::get("/picklists/batch/{batchPicklistId}/saw-list", [WarehouseController::class, 'generateSawListFromBatchPicklist'])->name('generate-saw-list-from-batch-picklist')->withoutMiddleware('auth:api');

Route::match(["get", "post"], '/selector/products-per-level', [WarehouseController::class, 'getProductItemsPerLevel']);
