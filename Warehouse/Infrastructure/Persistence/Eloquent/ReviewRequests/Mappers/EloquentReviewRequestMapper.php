<?php

namespace App\Warehouse\Infrastructure\Persistence\Eloquent\ReviewRequests\Mappers;

use App\Warehouse\Domain\ReviewRequests\ReviewRequest;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Common\EntityMapperTrait;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Common\ModelMapperTrait;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Common\ReflectionClassCache;
use App\Warehouse\Infrastructure\Persistence\Eloquent\ReviewRequests\EloquentReviewRequest;
use Carbon\CarbonImmutable;

class EloquentReviewRequestMapper extends ReviewRequest
{
    use EntityMapperTrait;
    use ModelMapperTrait;

    public static function createModelCore(ReviewRequest $entity): EloquentReviewRequest
    {
        $model = new EloquentReviewRequest();

        $model->id = $entity->id;
        $model->order_reference = $entity->orderReference;
        $model->last_sent = $entity->lastSent?->toDateTimeString();
        $model->delivery_date = $entity->lastSent?->toDateTimeString();
        $model->quantity_sent = $entity->quantitySent;
        $model->customer_email = $entity->customerEmail;
        $model->customer_name = $entity->customerName;

        // TODO: Save model and set identity
        $model->save();
        $entity->setIdentity($model->id);

        // mapToModel hasOne's

        // mapToModels hasMany's

        return $model;
    }

    protected static function reconstituteEntityCore(EloquentReviewRequest $model): ReviewRequest
    {
        $orderLineClass = ReflectionClassCache::getReflectionClass(ReviewRequest::class);
        /** @var ReviewRequest $entity */
        $entity = $orderLineClass->newInstanceWithoutConstructor();

        // TODO: Map attributes
        $entity->id = $model->id;
        $entity->orderReference = $model->order_reference;
        $entity->lastSent = ! $model->last_sent ? null : CarbonImmutable::parse($model->last_sent);
        $entity->deliveryDate = ! $model->delivery_date ? null: CarbonImmutable::parse($model->delivery_date);
        $entity->quantitySent = $model->quantity_sent;
        $entity->customerEmail = $model->customer_email;
        $entity->customerName = $model->customer_name;

        // mapToEntity hasOne's

        // mapToEntities hasMany's

        return $entity;
    }
    protected static function updateModelCore(ReviewRequest $entity, EloquentReviewRequest $model): void
    {
        $model->id = $entity->id;
        $model->order_reference = $entity->orderReference;
        $model->last_sent = $entity->lastSent?->toDateTimeString();
        $model->delivery_date = $entity->lastSent?->toDateTimeString();
        $model->quantity_sent = $entity->quantitySent;
        $model->customer_email = $entity->customerEmail;
        $model->customer_name = $entity->customerName;

        // TODO: Save model and set identity
        $model->save();
        $entity->setIdentity($model->id);

        // createOrUpdateModel hasOne's

        // createOrUpdateModel hasMany's
    }

    protected static function deleteModelCore(EloquentReviewRequest $model): void
    {
        // purgeModel hasOne's

        // purgeModels hasMany's

        // TODO: Delete model
        $model->delete();
    }

    protected static function pruneModelCore(ReviewRequest $entity, EloquentReviewRequest $model): void
    {
        // pruneModel hasOne's

        // pruneModel hasMany's
    }
}
