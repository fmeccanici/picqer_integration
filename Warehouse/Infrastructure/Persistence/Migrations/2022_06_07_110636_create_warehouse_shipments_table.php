<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('warehouse')->create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('order_reference');
            $table->string('reference')->unique();
            $table->boolean('track_and_trace_mail_sent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('warehouse')->dropIfExists('shipments');
    }
}
