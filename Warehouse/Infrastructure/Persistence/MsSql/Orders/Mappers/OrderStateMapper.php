<?php


namespace App\Warehouse\Infrastructure\Persistence\MsSql\Orders\Mappers;


use App\Warehouse\Infrastructure\Exceptions\StateMapperOperationException;

class OrderStateMapper
{
    // TODO: Don't use magic strings but make separate static object
    // Task 19155: Gebruik geen magic strings in Ms Sql Order Mapper
    public static function toMsSql(string $state)
    {
        if ($state === "denied")
        {
            return "PICQERA";
        } else if ($state === "unprocessed")
        {
            return "PICQERT";
        } else if ($state === "processed")
        {
            return "PICQERV";
        } else if ($state === "completed_by_picqer")
        {
            return "PICQERV";
        } else if ($state === "processing")
        {
            return "PICQER";
        }
        else if ($state === "unknown")
        {
            return "PICQERA";
        } else if ($state === 'completed_by_delight')
        {
            return 'V';
        } else if ($state === 'completed')
        {
            return 'V';
        } else if ($state === 'completed_by_delight_binnen_specialist')
        {
            return 'PIN';
        } else if ($state === 'completed_by_picqer_binnen_specialist')
        {
            return 'PIN';
        } else if ($state === 'completed_binnen_specialist')
        {
            return 'PIN';
        }

        throw new StateMapperOperationException("Cannot map ".$state." to MSSql State");

    }

    public static function toEntity(string $msSqlStateName)
    {
        if ($msSqlStateName === "PICQERT")
        {
            return "unprocessed";
        } else if ($msSqlStateName === "PICQERA")
        {
            return "failed";
        } else if ($msSqlStateName === "PICQERV")
        {
            return "completed_by_picqer";
        } else if ($msSqlStateName === "PICQER")
        {
            return "processing";
        } else if ($msSqlStateName === "V")
        {
            return "completed_by_delight";
        } else if ($msSqlStateName === 'PIN')
        {
            return "completed_binnen_specialist";
        }

        // TODO: Make separate mapper table: from domain to ms sql
        return "unknown";
//        throw new StateMapperOperationException("Cannot map ".$msSqlStateName." to domain state");
    }
}
