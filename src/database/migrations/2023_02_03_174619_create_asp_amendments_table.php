<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAspAmendmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asp_amendments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number', 64);
            $table->string('status', 64)->nullable();
            $table->unsignedInteger('l1_approved_by_id')->nullable();
            $table->dateTime('l1_approved_at')->nullable();
            $table->unsignedInteger('l2_approved_by_id')->nullable();
            $table->dateTime('l2_approved_at')->nullable();
            $table->unsignedInteger('l3_approved_by_id')->nullable();
            $table->dateTime('l3_approved_at')->nullable();
            $table->unsignedInteger('l1_rejected_by_id')->nullable();
            $table->dateTime('l1_rejected_at')->nullable();
            $table->unsignedInteger('l2_rejected_by_id')->nullable();
            $table->dateTime('l2_rejected_at')->nullable();
            $table->unsignedInteger('l3_rejected_by_id')->nullable();
            $table->dateTime('l3_rejected_at')->nullable();
            $table->string('general_remarks', 3000)->nullable();
            $table->string('rejected_reason', 1000)->nullable();
            $table->unsignedInteger('created_by_id')->nullable();
            $table->unsignedInteger('updated_by_id')->nullable();
            $table->unsignedInteger('deleted_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('l1_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('l2_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('l3_approved_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('l1_rejected_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('l2_rejected_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('l3_rejected_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade'); 
            $table->foreign('deleted_by_id')->references('id')->on('users')->onDelete('SET NULL')->onUpdate('cascade');    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asp_amendments');
    }
}
