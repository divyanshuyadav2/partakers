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
        Schema::create('admn_orga_mast', function (Blueprint $table) {
            // No default value - the trigger will handle it
            $table->unsignedBigInteger('Orga_UIN')->primary() ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            
            $table->unsignedBigInteger('Orga_Type_UIN');
            $table->string('Orga_Name', 30);
            $table->string('orga_Logo', 100);
            $table->unsignedBigInteger('Dt_of_Incp');
            $table->unsignedBigInteger('Fnal_Year_Strt_Dt');
            $table->unsignedBigInteger('Actn_Strt_Dt');
            $table->string('Web_Addr', 20);
            $table->unsignedBigInteger('Cnta_Pers');
            $table->unsignedBigInteger('Admt_Pers_1st');
            $table->unsignedBigInteger('Admt_Pers_2nd');
            $table->unsignedBigInteger('Admt_Pers_3rd');
            $table->unsignedBigInteger('Admt_Pers_4th');
            $table->unsignedBigInteger('Assc_Part_Code');
            $table->unsignedBigInteger('Sytm_Admt_Pers');
            $table->unsignedBigInteger('Eft_Dt');
            $table->unsignedBigInteger('Renw_Dt');
            $table->unsignedBigInteger('Expy_Dt');
            $table->unsignedBigInteger('Orga_Del_Dt');
            $table->unsignedBigInteger('Stau_UIN');
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();
            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();
            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();
            $table->unsignedBigInteger('Stat_UIN');
            $table->string('pin_UIN', 10);
            $table->string('Prmy_Emai', 64);
            $table->unsignedBigInteger('Prmy_Emai_Vefi');
            $table->integer('mpos_key');
        });

        // Create trigger for automatic UIN generation
        DB::unprepared('
            CREATE TRIGGER generate_orga_uin 
            BEFORE INSERT ON admn_orga_mast 
            FOR EACH ROW 
            BEGIN 
                IF NEW.Orga_UIN IS NULL OR NEW.Orga_UIN = 0 THEN
                    SET NEW.Orga_UIN = CAST(CONCAT((UNIX_TIMESTAMP() - 1592592000), LPAD(FLOOR(RAND() * 100), 2, "0")) AS UNSIGNED);
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS generate_orga_uin');
        Schema::dropIfExists('admn_orga_mast');
    }
};