<?php


namespace App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows;


use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\EloquentActivity;
use App\Warehouse\Infrastructure\Persistence\Eloquent\Activities\EloquentActivityInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class EloquentOrderFlow
 * @package App\Warehouse\Infrastructure\Persistence\Eloquent\OrderFlows
 *
 * @property integer $id
 * @property string $type;
 * @property boolean $on_stock
 * @property string $description
 */
class EloquentOrderFlow extends Model
{
    protected $connection = "warehouse";
    protected $table = "order_flows";
    protected $guarded = [];

    public function inputs(): HasMany
    {
        return $this->hasMany(EloquentActivityInput::class, "order_flow_id");
    }

    public function activities(): HasMany
    {
        return $this->hasMany(EloquentActivity::class, "order_flow_id");
    }
}
