<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaPhonMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_cnta_phon_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Cnta_Phon_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->string('Phon_Numb', 20);
            $table->string('Phon_Type', 50);
            $table->string('Cutr_Code', 5)->nullable();
            $table->boolean('Is_Prmy')->default(0);
            $table->boolean('Has_WtAp')->default(0);
            $table->boolean('Has_Telg')->default(0);
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

            $table->unique(['Admn_User_Mast_UIN', 'Phon_Numb']);  // same user must not store same phone twice
        });
    }

    public function down()
    {
        Schema::dropIfExists('admn_cnta_phon_mast');
    }
}
