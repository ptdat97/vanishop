/** Nhãn theo mã từ `inventory::labels` (server dịch). */
export interface InventoryLabels {
    location_type: Record<string, string>;
    location_status: Record<string, string>;
    stock_action: Record<string, string>;
    transfer_status: Record<string, string>;
    discrepancy: Record<string, string>;
    resolution: Record<string, string>;
    import_status: Record<string, string>;
    default_location: string;
}
