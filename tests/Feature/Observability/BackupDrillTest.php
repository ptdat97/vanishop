<?php

use App\Observability\AlertRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
| Go-live gate: diễn tập khôi phục backup — nạp bản mới nhất vào DB tạm, đối chiếu, xoá DB tạm, ghi bằng chứng; cảnh báo
| khi quá hạn diễn tập. Cần MySQL thật (mysqldump/mysql) — bỏ qua trên SQLite.
*/

beforeEach(function () {
    Storage::fake('local');
    config(['backup.backup.destination.disks' => ['local'], 'backup.backup.password' => null]);
});

$drillDatabases = fn (): array => array_map(fn ($row): string => (string) array_values((array) $row)[0], DB::select("SHOW DATABASES LIKE '%\\_drill\\_%'"));

it('backup mới → diễn tập đạt, có số dòng đối chiếu, DB tạm bị xoá, kết quả được ghi', function () use ($drillDatabases) {
    $this->artisan('vani:backup:drill', ['--fresh' => true])->expectsOutputToContain('Diễn tập khôi phục đạt.')->assertSuccessful();

    $drill = DB::table('backup_drills')->sole();
    $details = json_decode($drill->details, true);
    expect($drill->status)->toBe('ok')
        ->and($drill->backup_path)->toEndWith('.zip')
        ->and($details['tables'])->toHaveKeys(['orders', 'payments', 'stock_levels'])
        ->and($details['migrations'])->toBeGreaterThan(0)
        ->and($details['dropped'])->toBeTrue()
        ->and($drillDatabases())->not->toContain($details['drill_database']);
})->skip(fn () => DB::getDriverName() !== 'mysql', 'Cần MySQL');

it('backup hỏng hoặc không có backup → không đạt, mã thoát 1, vẫn ghi kết quả', function () {
    $this->artisan('vani:backup:drill')->expectsOutputToContain('Không có bản backup')->assertFailed();

    Storage::disk('local')->put('VaniShop/2026-10-08-01-00-00.zip', 'không phải zip');
    $this->artisan('vani:backup:drill')->expectsOutputToContain('zip hỏng')->assertFailed();

    expect(DB::table('backup_drills')->pluck('status')->all())->toBe(['failed', 'failed']);
})->skip(fn () => DB::getDriverName() !== 'mysql', 'Cần MySQL');

it('cảnh báo: production chưa diễn tập / quá hạn → backup.drill_stale; diễn tập gần đây → ổn', function () {
    $this->freezeTime();
    $rule = fn () => app(AlertRules::class)->evaluate()['backup.drill_stale'];
    expect($rule())->toBeNull(); // không phải production

    app()->detectEnvironment(fn () => 'production');
    expect($rule())->toMatchArray(['severity' => 'normal'])->and($rule()['title'])->toContain('diễn tập');

    $record = ['disk' => 'local', 'backup_path' => 'VaniShop/x.zip', 'duration_ms' => 1, 'details' => '{}'];
    DB::table('backup_drills')->insert([...$record, 'status' => 'ok', 'created_at' => now()->subDays(101)]);
    DB::table('backup_drills')->insert([...$record, 'status' => 'failed', 'created_at' => now()->subDay()]);
    expect($rule())->not->toBeNull();

    DB::table('backup_drills')->insert([...$record, 'status' => 'ok', 'created_at' => now()->subDays(10)]);
    expect($rule())->toBeNull();
});
