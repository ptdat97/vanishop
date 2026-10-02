<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Series;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Identity\Persistence\Models\StaffUser;

function testWidget(string $key, Closure $render, ?string $permission = null, int $order = 10, int $width = 1): DashboardWidget
{
    return new class($key, $render, $permission, $order, $width) implements DashboardWidget
    {
        public function __construct(private string $k, private Closure $r, private ?string $p, private int $o, private int $w) {}

        public function key(): string
        {
            return $this->k;
        }

        public function label(): string
        {
            return 'Widget '.$this->k;
        }

        public function permission(): ?string
        {
            return $this->p;
        }

        public function width(): int
        {
            return $this->w;
        }

        public function order(): int
        {
            return $this->o;
        }

        public function render(): Metric|Series|Table
        {
            return ($this->r)();
        }
    };
}

function testReport(string $key, Closure $run, ?string $permission = null): ReportProvider
{
    return new class($key, $run, $permission) implements ReportProvider
    {
        public function __construct(private string $k, private Closure $r, private ?string $p) {}

        public function key(): string
        {
            return $this->k;
        }

        public function label(): string
        {
            return 'Báo cáo '.$this->k;
        }

        public function description(): string
        {
            return 'Mô tả';
        }

        public function permission(): ?string
        {
            return $this->p;
        }

        public function run(ReportPeriod $period): ReportResult
        {
            return ($this->r)($period);
        }
    };
}

function registerInsight(string $tag, string $id, object $implementation): void
{
    app()->instance($id, $implementation);
    app(Extensions::class)->tag([$id], $tag);
}

it('Tổng quan: widget theo thứ tự + quyền, widget lỗi bị bỏ, slot card vẫn chạy', function () {
    registerInsight(DashboardWidget::TAG, 'test.w.series', testWidget('series', fn () => new Series(['01/10', '02/10'], [100, 250], 'money', 'Doanh thu'), order: 20, width: 5));
    registerInsight(DashboardWidget::TAG, 'test.w.metric', testWidget('metric', fn () => new Metric('Đơn', 7, 'number', 'hint')));
    registerInsight(DashboardWidget::TAG, 'test.w.secret', testWidget('secret', fn () => new Metric('Bí mật', 1), 'reports.secret'));
    registerInsight(DashboardWidget::TAG, 'test.w.broken', testWidget('broken', fn () => throw new RuntimeException('hỏng')));

    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $this->actingAs($staff, 'staff')->get('/admin')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('widgets', 2)
            ->where('widgets.0.key', 'metric')
            ->where('widgets.0.data', ['kind' => 'metric', 'label' => 'Đơn', 'value' => 7, 'format' => 'number', 'hint' => 'hint'])
            ->where('widgets.1.key', 'series')
            ->where('widgets.1.width', 3)
            ->where('widgets.1.data.values', [100, 250])
            ->has('cards'));
});

it('Báo cáo: menu chỉ hiện khi có báo cáo xem được; trang danh sách + chi tiết theo khoảng thời gian', function () {
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();
    $this->actingAs($staff, 'staff')->get('/admin')
        ->assertInertia(fn (Assert $page) => $page->where('navigation', fn ($items) => ! collect($items)->pluck('key')->contains('reports')));

    $seen = null;
    registerInsight(ReportProvider::TAG, 'test.r.sales', testReport('sales', function (ReportPeriod $period) use (&$seen) {
        $seen = $period;

        return new ReportResult(
            new Table([new Column('day', 'Ngày'), new Column('revenue', 'Doanh thu', 'money')], [['day' => '2026-10-01', 'revenue' => 1000, 'ignored' => 'x']]),
            [new Metric('Doanh thu', 1000, 'money')],
            new Series(['01/10'], [1000], 'money'),
        );
    }));
    registerInsight(ReportProvider::TAG, 'test.r.secret', testReport('secret', fn () => throw new LogicException('không được gọi'), 'reports.secret'));

    $this->actingAs($staff, 'staff')->get('/admin')
        ->assertInertia(fn (Assert $page) => $page->where('navigation', fn ($items) => collect($items)->pluck('key')->contains('reports')));
    $this->actingAs($staff, 'staff')->get('/admin/reports')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Extension::Reports/Index')->has('reports', 1)->where('reports.0.key', 'sales'));

    $this->actingAs($staff, 'staff')->get('/admin/reports/sales?preset=custom&start=2026-09-01&end=2026-09-30')->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Extension::Reports/Show')
            ->where('period', ['preset' => 'custom', 'start' => '2026-09-01', 'end' => '2026-09-30'])
            ->where('result.table.rows', [['day' => '2026-10-01', 'revenue' => 1000]])
            ->where('result.summary.0.value', 1000)
            ->where('result.chart.values', [1000]));

    expect($seen->from->format(DATE_ATOM))->toBe('2026-09-01T00:00:00+07:00')
        ->and($seen->to->format(DATE_ATOM))->toBe('2026-10-01T00:00:00+07:00')
        ->and($seen->days())->toBe(30);

    $this->actingAs($staff, 'staff')->get('/admin/reports/secret')->assertNotFound();
    $this->actingAs($staff, 'staff')->get('/admin/reports/missing')->assertNotFound();
    $this->actingAs($staff, 'staff')->from('/admin/reports/sales')->get('/admin/reports/sales?preset=custom&start=2026-09-30&end=2026-09-01')
        ->assertRedirect('/admin/reports/sales')->assertSessionHasErrors('period');
});

it('Báo cáo: xuất CSV có BOM, chặn công thức; báo cáo lỗi → trang báo lỗi, CSV 503', function () {
    registerInsight(ReportProvider::TAG, 'test.r.csv', testReport('csv', fn () => new ReportResult(
        new Table([new Column('name', 'Tên'), new Column('qty', 'SL', 'number')], [['name' => '=HYPERLINK("x")', 'qty' => 2], ['name' => 'Áo, "thun"', 'qty' => -1]]),
    )));
    registerInsight(ReportProvider::TAG, 'test.r.broken', testReport('broken', fn () => throw new RuntimeException('hỏng')));
    $staff = StaffUser::factory()->withPermissions(['admin.access'])->create();

    $response = $this->actingAs($staff, 'staff')->get('/admin/reports/csv/export?preset=7d')->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('csv_')
        ->and($response->streamedContent())->toBe("\xEF\xBB\xBFTên,SL\n\"'=HYPERLINK(\"\"x\"\")\",2\n\"Áo, \"\"thun\"\"\",-1\n");

    $this->actingAs($staff, 'staff')->get('/admin/reports/broken')->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('result', null));
    $this->actingAs($staff, 'staff')->get('/admin/reports/broken/export')->assertStatus(503);
});

it('ReportPeriod: preset theo giờ Việt Nam, khoảng không hợp lệ bị từ chối', function () {
    $now = new DateTimeImmutable('2026-10-01T18:30:00Z'); // 02/10 01:30 giờ VN

    expect(ReportPeriod::fromPreset('today', now: $now)->toArray())->toBe(['preset' => 'today', 'start' => '2026-10-02', 'end' => '2026-10-02'])
        ->and(ReportPeriod::fromPreset('7d', now: $now)->toArray())->toBe(['preset' => '7d', 'start' => '2026-09-26', 'end' => '2026-10-02'])
        ->and(ReportPeriod::fromPreset('last_month', now: $now)->toArray())->toBe(['preset' => 'last_month', 'start' => '2026-09-01', 'end' => '2026-09-30'])
        ->and(fn () => ReportPeriod::fromPreset('year'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => ReportPeriod::fromPreset('custom', '2026-13-01', '2026-12-01'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Metric('x', 1, 'bogus'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new Series(['a'], [1, 2]))->toThrow(InvalidArgumentException::class);
});
