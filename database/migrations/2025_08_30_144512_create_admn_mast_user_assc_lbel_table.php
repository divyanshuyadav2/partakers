<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admn_user_assc_lbel_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('Orga_User_Assc_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            $table->unsignedBigInteger('Orga_UIN');
            $table->string('User_Assc_Lbel_Name', 64)->nullable();
            $table->string('User_Assc_Lbel_Desp', 256)->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admn_user_assc_lbel_mast');
    }
};
