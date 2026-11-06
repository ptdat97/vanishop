/** Nhãn theo mã từ `vani-warehouse::labels` (server dịch). */
export interface WarehouseLabels {
    location_type: Record<string, string>;
    location_status: Record<string, string>;
    transfer_status: Record<string, string>;
    stock_action?: Record<string, string>;
}
