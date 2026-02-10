<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdmnUserMastTable extends Migration
{
    public function up(): void
    {
        Schema::create('admn_user_mast', function (Blueprint $table) {
            /* --------------- Keys --------------- */
            // Primary-key – timestamp so it auto-populates with the current time
            $table
                ->unsignedBigInteger('Admn_User_Mast_UIN')
                ->primary()
                ->default(DB::raw('(UNIX_TIMESTAMP() - 1592592000)'));
            // FK to the organisation that owns the contact
            $table->unsignedBigInteger('Admn_Orga_Mast_UIN');

            /* --------------- Contact fields --------------- */
            $table->unsignedBigInteger('Prfx_UIN')->nullable();
            $table->string('FaNm', 255);
            $table->string('MiNm', 255)->nullable();
            $table->string('LaNm', 255)->nullable();
            $table->string('Gend', 20)->nullable();
            $table->string('Prfl_Pict', 255)->nullable();

            $table->date('Brth_Dt')->nullable();  // Birthdate
            $table->date('Anvy_Dt')->nullable();  // Anniversary
            $table->date('Deth_Dt')->nullable();  // Death
            $table->string('Comp_Name', 255)->nullable();
            $table->string('Comp_Dsig', 255)->nullable();  // Designation
            $table->string('Comp_LdLi', 255)->nullable();  // Land-line
            $table->string('Comp_Desp', 1000)->nullable();
            $table->string('Comp_Emai', 255)->nullable();
            $table->string('Comp_Web', 255)->nullable();
            $table->text('Comp_Addr')->nullable();

            $table->string('Prfl_Name', 255)->nullable();
            $table->text('Prfl_Addr')->nullable();

            /* --------------- Social / web links --------------- */
            $table->string('Web', 255)->nullable();
            $table->string('FcBk', 255)->nullable();
            $table->string('Twtr', 255)->nullable();
            $table->string('LnDn', 255)->nullable();
            $table->string('Intg', 255)->nullable();  // Instagram
            $table->string('Yaho', 255)->nullable();
            $table->string('Redt', 255)->nullable();

            /* --------------- Notes --------------- */
            $table->text('Note')->nullable();

            /* --------------- Status flags --------------- */
            $table->unsignedBigInteger('Is_Actv')->default(100201);
            $table->unsignedBigInteger('Is_Vf')->default(100206);

            /* --------------- Audit columns --------------- */
            $table->string('CrBy', 255)->nullable();
            $table->timestamp('CrOn')->nullable();

            $table->string('MoBy', 255)->nullable();
            $table->timestamp('MoOn')->nullable();

            $table->string('VfBy', 255)->nullable();
            $table->timestamp('VfOn')->nullable();

            $table->string('Del_By', 30)->nullable();
            $table->timestamp('Del_On')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admn_user_mast');
    }
}
