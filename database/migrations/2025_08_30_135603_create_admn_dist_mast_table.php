<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnDistMastTable extends Migration
{
    public function up(): void
    {
        Schema::create('admn_dist_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Dist_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_Stat_Mast_UIN');
            $table->string('Name', 255);
            $table->string('Code', 10)->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table
                ->foreign('Admn_Stat_Mast_UIN')
                ->references('Admn_Stat_Mast_UIN')
                ->on('admn_stat_mast')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_dist_mast');
    }
}
