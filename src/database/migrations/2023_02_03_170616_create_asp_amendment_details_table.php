<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAspAmendmentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asp_amendment_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amendment_id');
            $table->unsignedInteger('asp_id'); 
            $table->unsignedInteger('tab_id');
            $table->unsignedInteger('field_id');
            $table->string('old_value' , 255);
            $table->string('new_value' , 255);
            $table->dateTime('effective_from')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('amendment_id')->references('id')->on('asp_amendments')->onDelete('CASCADE')->onUpdate('cascade');
            $table->foreign('asp_id')->references('id')->on('asps')->onDelete('CASCADE')->onUpdate('cascade');  
            $table->foreign('tab_id')->references('id')->on('configs')->onDelete('CASCADE')->onUpdate('cascade');
            $table->foreign('field_id')->references('id')->on('asp_fields')->onDelete('CASCADE')->onUpdate('cascade'); 

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asp_amendment_details');
    }
}
