<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAspAmendmentServiceTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asp_amendment_service_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amendment_id');
            $table->unsignedInteger('asp_id');
            $table->unsignedInteger('service_type_id');
            $table->unsignedInteger('is_mobile');
            $table->string('type',10);
            $table->dateTime('effective_from')->nullable();
            $table->unsignedDecimal('range_limit', 5, 2)->nullable();
            $table->unsignedDecimal('below_range_price', 12, 2)->nullable();
            $table->unsignedDecimal('below_range_price_margin', 5, 2)->nullable();
            $table->unsignedDecimal('above_range_price', 12, 2)->nullable();
            $table->unsignedDecimal('above_range_price_margin', 5, 2)->nullable();
            $table->unsignedDecimal('waiting_charge_per_hour', 12, 2)->nullable();
            $table->unsignedDecimal('empty_return_range_price', 12, 2)->nullable();
            $table->unsignedTinyInteger('adjustment_type')->nullable();
            $table->unsignedDecimal('adjustment', 12, 2)->nullable();
            $table->unsignedInteger('fleet_count')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('amendment_id')->references('id')->on('asp_amendments')->onDelete('CASCADE')->onUpdate('cascade');
            $table->foreign('asp_id')->references('id')->on('asps')->onDelete('CASCADE')->onUpdate('cascade');  
            $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('CASCADE')->onUpdate('cascade');  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asp_amendment_service_types');
    }
}
