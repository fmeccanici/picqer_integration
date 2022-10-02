<?php

namespace App\Warehouse\Infrastructure\Persistence\MsSql\Log;

use Illuminate\Database\Eloquent\Model;

class EloquentMsSqlLogItem extends Model
{
    protected $table = 'dbo.LogItem';
    protected $connection = 'storemanager';
    public $timestamps = false;
    protected $guarded = [];
}
