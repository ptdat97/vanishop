<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Closure;

/**
 * Service contract: màn hình Admin của Core lấy phần mở rộng do plugin khai báo (ADR-030, extension-surface-v2 §4.A).
 * Tài nguyên: `product`, `order`, `customer` (đã nối), các tài nguyên khác theo đợt sau. Id truyền cho plugin là id
 * nội bộ (int) của bản ghi Core. Chỉ trả phần của plugin đang bật.
 */
interface AdminScreen
{
    public const RESOURCES = ['product', 'order', 'customer'];

    /**
     * Phần form của plugin + giá trị hiện tại (null khi tạo mới).
     *
     * Khoá input trong form: `extensions.<input>.<key>.<field>` (`input` = id plugin dạng slug, vd. `vani-hello-world`).
     *
     * @return list<array{plugin: string, input: string, key: string, label: string, fields: list<array<string, mixed>>, values: array<string, mixed>}>
     */
    public function formSections(string $resource, ?int $id): array;

    /**
     * Rule validate cho input `extensions.<input>.<section>.<field>` — Core validate trước khi lưu.
     *
     * @return array<string, list<mixed>>
     */
    public function validationRules(string $resource): array;

    /**
     * Lưu bản ghi Core rồi các phần form của plugin trong **cùng transaction** (lỗi ở plugin → rollback cả hai).
     *
     * @template T
     *
     * @param  array<string, mixed>  $input  giá trị `extensions` của request
     * @param  Closure(): T  $save  thao tác lưu của Core
     * @param  Closure(T): int  $idOf  lấy id bản ghi từ kết quả lưu
     * @return T
     */
    public function saving(string $resource, array $input, Closure $save, Closure $idOf): mixed;

    /**
     * Cột của plugin cho một trang danh sách.
     *
     * @param  list<int>  $ids
     * @return array{columns: list<array{key: string, label: string}>, values: array<int, array<string, scalar|null>>}
     */
    public function columns(string $resource, array $ids): array;

    /**
     * @return list<array{key: string, label: string, options: array<string, string>}>
     */
    public function filters(string $resource): array;

    /**
     * Id thoả các bộ lọc của plugin đang chọn (giao nhau); null nếu không chọn bộ lọc nào.
     *
     * @param  array<string, mixed>  $values  khoá `plugin:key` => giá trị
     * @return list<int>|null
     */
    public function filterIds(string $resource, array $values): ?array;

    /**
     * Thao tác nhân viên hiện tại được phép dùng.
     *
     * @param  'detail'|'bulk'  $scope
     * @return list<array{key: string, label: string, confirm: bool, url: string}>
     */
    public function actions(string $resource, string $scope): array;

    /**
     * Tab của plugin trên trang chi tiết.
     *
     * @return list<array{key: string, label: string, rows: list<array{label: string, value: string}>}>
     */
    public function tabs(string $resource, int $id): array;
}
