<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaTagMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_cnta_tag_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Cnta_Tag_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Tag_Mast_UIN');
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

            $table
                ->foreign('Admn_Tag_Mast_UIN')
                ->references('Admn_Tag_Mast_UIN')
                ->on('admn_tag_mast');

            $table->unique(['Admn_User_Mast_UIN', 'Admn_Tag_Mast_UIN']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('admn_cnta_tag_mast');
    }
}
