<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Seeder của plugin đã chạy (plugin-system §7): cài/nâng cấp chỉ chạy seeder chưa chạy; gỡ --purge xoá dòng để cài lại
| seed lại từ đầu. Gỡ giữ dữ liệu thì giữ dòng — dữ liệu seed còn trong DB.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugin_seeders', function (Blueprint $table) {
            $table->string('plugin_id', 128);
            $table->string('seeder', 255);
            $table->dateTime('ran_at');
            $table->primary(['plugin_id', 'seeder']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_seeders');
    }
};
