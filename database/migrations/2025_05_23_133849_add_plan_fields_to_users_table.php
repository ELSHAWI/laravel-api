<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->string('plan_type')->default('free');
            // $table->string('plan_status')->default('inactive');
            // $table->timestamp('plan_expires_at')->nullable();
            $table->string('stripe_id')->nullable();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'plan_expires_at',
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at'
            ]);
        });
    }
};