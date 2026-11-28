# Pricing

> Vòng 1 · Mức domain: **Rich** · Tài liệu: [catalog-pricing.md](../../docs/03-domains/catalog-pricing.md)

## Trách nhiệm

Bảng giá niêm yết/sale/thành viên theo lịch và ưu tiên, áp giá hàng loạt, giải giá cho giỏ/đơn.

## Bất biến

- Giá là số nguyên ≥ 0; giá sale 0đ được phép nhưng cảnh báo, không chặn.
- `PricingStrategy` của plugin chỉ thu hẹp lựa chọn giá, không tạo giá ngoài bảng giá.
- Một bảng niêm yết `base` cho mọi SKU.

## Bảng sở hữu

- `price_lists`
- `prices`
- `price_history`
- `price_variant_index`
- `price_quotas`
- `price_quota_customers`
- `price_quota_usages`

## Contract công bố

| Contract | Loại | Ghi chú |
|---|---|---|
| `BulkPriceMode` | interface | tag `vani.pricing.bulk_modes`, plugin (experimental) |
| `CustomerGroupDirectory` | interface | giữa module |
| `ListPriceReader` | interface | plugin (0.5.119) |
| `ManagedPriceLists` | interface | plugin (0.5.115) |
| `PriceImporter` | interface | giữa module |
| `PriceListSchedule` | interface | giữa module |
| `PriceQuotaExceeded` | class | plugin (0.5.117) |
| `PriceQuotas` | interface | plugin (0.5.117) |
| `PriceResolver` | interface | giữa module |
| `PriceRounding` | interface | tag `vani.pricing.roundings`, plugin (experimental) |
| `PricingStrategy` | interface | tag `vani.pricing.strategies`, plugin |

## Event

`PriceChanged`

---

Bảng sở hữu và contract lấy từ code (migration `Schema::create`, `Contracts/`, `#[PluginApi]`); arch test D5 kiểm README liệt kê đủ bảng sở hữu. "plugin" = API cho plugin (ADR-048); "giữa module" = chỉ module khác của Core dùng.
