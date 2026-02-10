<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnTagMastTable extends Migration
{
    public function up(): void
    {
        Schema::create('admn_tag_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Admn_Tag_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN')->nullable();
            $table->string('Name', 255);
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            $table
                ->foreign('Admn_Orga_Mast_UIN')
                ->references('Orga_UIN')
                ->on('admn_orga_mast');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_tag_mast');
    }
}
