<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaRefaMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_cnta_refa_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Cnta_Refa_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Refa_Name', 255);
            $table->string('Refa_Phon', 255)->nullable();
            $table->string('Refa_Emai', 255)->nullable();
            $table->string('Refa_Rsip', 50)->default('friend');
            $table->string('Comp_Name', 255)->nullable();
            $table->string('Comp_Dsig', 255)->nullable();
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
        Schema::dropIfExists('admn_cnta_refa_mast');
    }
}
