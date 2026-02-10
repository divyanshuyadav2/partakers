<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaLinkMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_cnta_link_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Cnta_Link_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->string('Tokn', 255);
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->nullable();
            $table->string('Cnta_Tag', 255)->default('ByLink');
            $table->boolean('Is_Used')->default(0);
            $table->timestamp('Expy_Dt')->nullable();
            $table->boolean('Is_Actv')->default(1);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table->unique('Tokn');
            $table
                ->foreign('Admn_Orga_Mast_UIN')
                ->references('Orga_UIN')
                ->on('admn_orga_mast')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('admn_cnta_link_mast', function (Blueprint $table) {
            // Drop the foreign key first, then the column
            $table->dropForeign(['Admn_Orga_Mast_UIN']);
            $table->dropColumn('Admn_Orga_Mast_UIN');
        });
    }
}
