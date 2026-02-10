<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admn_user_logi_mast', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('User_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));

            $table->string('User_Name', 50);
            $table->string('User_Logo', 100)->nullable();

            $table->string('Prmy_Emai', 64)->unique();
            $table->unsignedBigInteger('Prmy_Emai_Vefi')->nullable();

            $table->string('Altn_Emai', 64)->nullable();
            $table->unsignedBigInteger('Altn_Emai_Vefi')->nullable();

            $table->unsignedBigInteger('Cutr_UIN');
            $table->unsignedBigInteger('ISD_UIN');

            $table->unsignedBigInteger('Prmy_MoNo');
            $table->unsignedBigInteger('Prmy_MoNo_Vefi')->nullable();
            $table->integer('OTP_Prmy_MoNo')->nullable();

            $table->unsignedBigInteger('Altn_MoNo')->nullable();
            $table->unsignedBigInteger('Altn_MoNo_Vefi')->nullable();
            $table->integer('OTP_Altn_MoNo')->nullable();

            $table->string('User_pasw', 256);
            $table->unsignedInteger('Pasw_Self_Gntd')->nullable();
            $table->unsignedBigInteger('Pasw_Rest_By')->nullable();
            $table->unsignedBigInteger('Pasw_Rest_On')->nullable();

            $table->unsignedBigInteger('CrBy')->nullable();
            $table->unsignedBigInteger('Stau_UIN')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_logi_mast');
    }
};
