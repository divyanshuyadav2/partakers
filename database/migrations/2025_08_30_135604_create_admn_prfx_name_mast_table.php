<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnPrfxNameMastTable extends Migration
{
    public function up(): void
    {
        Schema::create('admn_prfx_name_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Prfx_Name_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->string('Prfx_Name', 100);
            $table->text('Prfx_Name_Desp')->nullable();
            $table->unsignedBigInteger('Stau_UIN')->nullable();
            $table->unsignedBigInteger('CrBy')->nullable();
            $table->unsignedBigInteger('MoBy')->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->unsignedBigInteger('VfBy')->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_prfx_name_mast');
    }
}
