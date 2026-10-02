<?php

declare(strict_types=1);

namespace Plugin\ProvincesVn\Infrastructure;

use Modules\Checkout\Contracts\AddressDirectory;
use RuntimeException;

/**
 * Danh mục 2 cấp tỉnh/thành – phường/xã sau sắp xếp 07/2025 (34 tỉnh/thành), đọc từ file dữ liệu của plugin.
 * Nạp một lần mỗi tiến trình; tra cứu theo mã bằng chỉ mục trong bộ nhớ.
 */
final class VnDivisions implements AddressDirectory
{
    /** @var array<string, array{code: string, name: string, wards: array<string, array{code: string, name: string}>}>|null */
    private ?array $provinces = null;

    public function __construct(private readonly string $dataFile) {}

    public function code(): string
    {
        return 'vn-2025';
    }

    public function provinces(): array
    {
        return array_values(array_map(fn (array $province): array => ['code' => $province['code'], 'name' => $province['name']], $this->load()));
    }

    public function wards(string $provinceCode): array
    {
        $wards = array_values($this->load()[$provinceCode]['wards'] ?? []);
        // Sắp theo thứ tự tiếng Việt (Đ sau D) khi có ext-intl.
        $collator = class_exists(\Collator::class) ? new \Collator('vi_VN') : null;
        usort($wards, fn (array $a, array $b): int => $collator?->compare($a['name'], $b['name']) ?: strcmp($a['name'], $b['name']));

        return $wards;
    }

    public function province(string $provinceCode): ?array
    {
        $province = $this->load()[$provinceCode] ?? null;

        return $province === null ? null : ['code' => $province['code'], 'name' => $province['name']];
    }

    public function ward(string $provinceCode, string $wardCode): ?array
    {
        return $this->load()[$provinceCode]['wards'][$wardCode] ?? null;
    }

    public function count(): int
    {
        return count($this->load());
    }

    /**
     * @return array<string, array{code: string, name: string, wards: array<string, array{code: string, name: string}>}>
     */
    private function load(): array
    {
        if ($this->provinces !== null) {
            return $this->provinces;
        }

        $data = json_decode((string) @file_get_contents($this->dataFile), true);
        if (! is_array($data) || ! isset($data['provinces']) || ! is_array($data['provinces'])) {
            throw new RuntimeException("Không đọc được dữ liệu địa giới [{$this->dataFile}].");
        }

        $this->provinces = [];
        foreach ($data['provinces'] as $province) {
            $wards = [];
            foreach ($province['wards'] as $ward) {
                $wards[(string) $ward['code']] = ['code' => (string) $ward['code'], 'name' => (string) $ward['name']];
            }
            $this->provinces[(string) $province['code']] = ['code' => (string) $province['code'], 'name' => (string) $province['name'], 'wards' => $wards];
        }

        return $this->provinces;
    }
}
