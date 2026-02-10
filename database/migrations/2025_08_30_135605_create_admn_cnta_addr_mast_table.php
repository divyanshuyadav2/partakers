<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdmnCntaAddrMastTable extends Migration
{
    public function up(): void
    {
        Schema::create('admn_cnta_addr_mast', function (Blueprint $table) {
            // Primary key with timestamp-based UIN
            $table
                ->unsignedBigInteger('Admn_Cnta_Addr_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            // Foreign key
            $table->unsignedBigInteger('Admn_User_Mast_UIN');
            $table->unsignedBigInteger('Admn_Addr_Type_Mast_UIN')->nullable();  // Address type reference (if you use it)
            // Address fields
            $table->text('Addr')->nullable();
            $table->string('Loca', 255)->nullable();
            $table->string('Lndm', 255)->nullable();  // Landmark (if you use it)
            $table->boolean('Is_Prmy')->default(false);

            // Foreign keys for location hierarchy - ADDED THESE BASED ON YOUR CREATE.PHP
            $table->unsignedBigInteger('Admn_Cutr_Mast_UIN')->nullable();  // Country
            $table->unsignedBigInteger('Admn_Stat_Mast_UIN')->nullable();  // State
            $table->unsignedBigInteger('Admn_Dist_Mast_UIN')->nullable();  // District
            $table->unsignedBigInteger('Admn_PinCode_Mast_UIN')->nullable();  // Pincode

            // Audit columns
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();

            // Foreign key constraints
            $table
                ->foreign('Admn_User_Mast_UIN')
                ->references('Admn_User_Mast_UIN')
                ->on('admn_user_mast')
                ->onDelete('cascade');
            $table
                ->foreign('Admn_Addr_Type_Mast_UIN')
                ->references('Admn_Addr_Type_Mast_UIN')
                ->on('admn_addr_type_mast')
                ->onDelete('cascade');

            // ADDED: Location hierarchy foreign keys (optional constraints)
            $table
                ->foreign('Admn_Cutr_Mast_UIN')
                ->references('Admn_Cutr_Mast_UIN')
                ->on('admn_cutr_mast')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table
                ->foreign('Admn_Stat_Mast_UIN')
                ->references('Admn_Stat_Mast_UIN')
                ->on('admn_stat_mast')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table
                ->foreign('Admn_Dist_Mast_UIN')
                ->references('Admn_Dist_Mast_UIN')
                ->on('admn_dist_mast')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table
                ->foreign('Admn_PinCode_Mast_UIN')
                ->references('Admn_PinCode_Mast_UIN')
                ->on('admn_pincode_mast')
                ->onDelete('set null')
                ->onUpdate('cascade');

            // Indexes for better performance
            $table->index(['Admn_User_Mast_UIN', 'Is_Prmy']);  // For finding primary address
            $table->index('Admn_Cutr_Mast_UIN');
            $table->index('Admn_Addr_Type_Mast_UIN');
            $table->index('Admn_Stat_Mast_UIN');
            $table->index('Admn_Dist_Mast_UIN');
            $table->index('Admn_PinCode_Mast_UIN');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_cnta_addr_mast');
    }
}
