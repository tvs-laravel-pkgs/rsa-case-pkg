<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAspAmendmentBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asp_amendment_branches', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('amendment_id');
            $table->unsignedInteger('asp_id');
            $table->unsignedInteger('service_type_id');
            $table->string('type',10);
            $table->string('location' , 255)->nullable();
            $table->string('sub_code' , 255)->nullable();
            $table->string('contact_person' , 255)->nullable();
            $table->unsignedInteger('contact_number')->nullable();
            $table->string('lat', 255)->nullable();
            $table->string('long', 255)->nullable();
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
        Schema::dropIfExists('asp_amendment_branches');
    }
}
