<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thư mục ảo của Thư viện ảnh: đường dẫn dạng "san-pham/ao-thun" ('' = gốc). File thật vẫn nằm theo checksum.
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique();
            $table->timestamps();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->string('folder', 191)->default('')->after('path')->index();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['folder']);
            $table->dropColumn('folder');
        });
        Schema::dropIfExists('media_folders');
    }
};
