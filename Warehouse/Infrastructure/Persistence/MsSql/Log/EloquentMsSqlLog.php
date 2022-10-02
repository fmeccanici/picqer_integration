<?php

namespace App\Warehouse\Infrastructure\Persistence\MsSql\Log;

use Illuminate\Database\Eloquent\Model;

class EloquentMsSqlLog extends Model
{
    protected $table = 'dbo.Log';
    protected $connection = 'storemanager';
    public $timestamps = false;
    protected $guarded = [];

}
