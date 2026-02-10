<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admn_user_orga_rela', function (Blueprint $table) {
            // Primary key (timestamp-based as per your requirement)
            $table
                ->unsignedBigInteger('User_Assc_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));

            // Foreign keys
            $table->unsignedBigInteger('Orga_User_Assc_UIN');
            $table->unsignedBigInteger('Orga_UIN');
            $table->unsignedBigInteger('User_UIN');

            // Other columns
            $table->timestamp('Expy')->nullable();
            $table->unsignedBigInteger('Stau_UIN')->nullable();

            // Audit columns
            $table->timestamp('CrOn')->nullable();
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('MoBy', 255)->nullable();

            // Define foreign key constraints
            $table
                ->foreign('Orga_User_Assc_UIN')
                ->references('Orga_User_Assc_UIN')
                ->on('admn_user_assc_lbel_mast')
                ->onDelete('cascade');

            $table
                ->foreign('Orga_UIN')
                ->references('Orga_UIN')
                ->on('admn_orga_mast')
                ->onDelete('cascade');

            $table
                ->foreign('User_UIN')
                ->references('User_UIN')
                ->on('admn_user_logi_mast')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_orga_rela');
    }
};
