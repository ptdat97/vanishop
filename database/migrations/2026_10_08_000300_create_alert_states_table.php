<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trạng thái từng cảnh báo vận hành (vani:alerts:check): chống gửi lặp mỗi phút, nhắc lại theo chu kỳ, báo khi hết.
        Schema::create('alert_states', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('severity', 16);
            $table->string('status', 16); // firing | resolved
            $table->string('title');
            $table->text('detail');
            $table->timestamp('fired_at');
            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('notify_count')->default(0);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_states');
    }
};
