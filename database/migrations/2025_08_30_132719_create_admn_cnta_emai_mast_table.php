<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaEmaiMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_cnta_emai_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Cnta_Emai_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Emai_Addr', 255);
            $table->string('Emai_Type', 30)->default('personal');
            $table->boolean('Is_Prmy')->default(0);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table
                ->foreign('Admn_User_Mast_UIN')
                ->references('Admn_User_Mast_UIN')
                ->on('admn_user_mast');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admn_cnta_emai_mast');
    }
}
