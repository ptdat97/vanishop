<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Bỏ phạm vi gán vai trò (owner/location) — Owner 2026-11-19: phân quyền theo team ở mức module, không giới hạn theo kho.
| Một nhân viên gán mỗi vai trò một lần.
*/

return new class extends Migration
{
    public function up(): void
    {
        // Cùng vai trò gán ở nhiều phạm vi → giữ một dòng.
        $keep = DB::table('staff_role_assignments')->selectRaw('min(id) as id')->groupBy('staff_user_id', 'role_id')->pluck('id');
        DB::table('staff_role_assignments')->whereNotIn('id', $keep)->delete();

        // Thêm unique mới trước khi bỏ unique cũ: MySQL cần một index cho khoá ngoại staff_user_id.
        Schema::table('staff_role_assignments', function (Blueprint $table) {
            $table->unique(['staff_user_id', 'role_id'], 'staff_role_unique');
        });
        Schema::table('staff_role_assignments', function (Blueprint $table) {
            $table->dropUnique('staff_role_scope_unique');
            $table->dropColumn(['scope_type', 'scope_id']);
        });
    }

    public function down(): void
    {
        Schema::table('staff_role_assignments', function (Blueprint $table) {
            $table->string('scope_type', 32)->default('owner');
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->unique(['staff_user_id', 'role_id', 'scope_type', 'scope_id'], 'staff_role_scope_unique');
        });
        Schema::table('staff_role_assignments', function (Blueprint $table) {
            $table->dropUnique('staff_role_unique');
        });
    }
};
