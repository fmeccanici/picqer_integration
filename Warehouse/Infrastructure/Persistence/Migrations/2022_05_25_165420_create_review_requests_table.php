<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('warehouse')->create('review_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_reference');
            $table->timestamp('delivery_date')->nullable();
            $table->integer('quantity_sent');
            $table->timestamp('last_sent')->nullable();
            $table->string('customer_email');
            $table->string('customer_name');
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
        Schema::connection('warehouse')->dropIfExists('review_requests');
    }
}
