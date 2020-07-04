<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history_log', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date', 0);
            $table->string('user',255); //by name, so no need to join to get data
            $table->integer('user_id');
            $table->string('action',255);
            $table->string('type',255);
            $table->string('summary',500);
            $table->text('change');
            $table->string('location',500);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('history_log');
    }
}
