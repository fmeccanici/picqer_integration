<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers;


use App\Warehouse\Domain\Orders\OrderState;
use App\Warehouse\Infrastructure\Exceptions\StateMapperOperationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// TODO: Make non static: Such that you don't have to query everytime
class StateMapper
{
    /**
     * @throws StateMapperOperationException
     */
    public static function toId(string $stateCode): int
    {
        $foundState = DB::connection("storemanager")->table('dbo.SalesOrderState')
            ->where('Code', '=', $stateCode)
            ->first();

        if ($foundState === null)
        {
            throw new StateMapperOperationException("State code " . $stateCode . " could not be found");
        }

        return $foundState->Id;
    }

    /**
     * @throws StateMapperOperationException
     */
    public static function toName(string $id): string
    {
        $state = DB::connection("storemanager")->table('dbo.SalesOrderState')
            ->where('Id', '=', (int) $id)
            ->first();

        if ($state === null)
        {
            throw new StateMapperOperationException("State id ".$id." is invalid");

        }

        return $state->Name;
    }

    public static function fromNameToDomain(string $stateName)
    {
        // The states apart from Picqer states are necessary to correctly map split orders with other statuses than Picqer
        $mapping = [
            'Picqer - Te verwerken' => OrderState::NEW,
            'Picqer - In behandeling' => OrderState::PROCESSING,
            'Picqer - Verzonden/afgehaald' => OrderState::COMPLETED,
            'Picqer - Afgewezen' => OrderState::DENIED,
            'Verzonden/afgehaald' => OrderState::COMPLETED,
            'Verf orders Binnenspecialist' => OrderState::COMPLETED,
            'Staal: verzonden naar klant' => OrderState::COMPLETED
        ];

        $domainStateName = Arr::get($mapping, $stateName, OrderState::UNKNOWN);

        if ($domainStateName == OrderState::UNKNOWN)
        {
            Log::notice('State name ' . $stateName . ' cannot be converted to domain state');
        }

        return $domainStateName;
    }

    public static function fromDomainToName(string $domainName)
    {
        $mapping = [
            OrderState::NEW => 'Picqer - Te verwerken',
            OrderState::PROCESSING => 'Picqer - In behandeling',
            OrderState::COMPLETED_BY_PICQER => 'Picqer - Verzonden/afgehaald',
            OrderState::COMPLETED_BY_DELIGHT => 'Verzonden/afgehaald',
            OrderState::COMPLETED_BINNEN_SPECIALIST => 'Verf orders Binnenspecialist',
            OrderState::COMPLETED_BY_DELIGHT_BINNEN_SPECIALIST => 'Verf orders Binnenspecialist',
            OrderState::COMPLETED_BY_PICQER_BINNEN_SPECIALIST => 'Verf orders Binnenspecialist',
            OrderState::COMPLETED => 'Verzonden/afgehaald',
            OrderState::DENIED => 'Picqer - Afgewezen',
        ];

        $stateName = Arr::get($mapping, $domainName, OrderState::UNKNOWN);

        if ($stateName == OrderState::UNKNOWN)
        {
            Log::notice('Domain name ' . $domainName . ' cannot be converted to Ms Sql order state');
        }

        return $stateName;
    }
}
