<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnAddrTypeMastTable extends Migration
{
    public function up()
    {
        Schema::create('admn_addr_type_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Addr_Type_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->string('Name', 30);
            $table->string('Cr_By', 30)->nullable();
            $table->timestamp('Cr_On')->nullable();
            $table->string('Mo_By', 30)->nullable();
            $table->timestamp('Mo_On')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();
            $table->string('Vf_By', 30)->nullable();
            $table->timestamp('Vf_On')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admn_addr_type_mast');
    }
}
