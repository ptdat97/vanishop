/** Nhãn plugin từ `extension::labels` (server dịch). */
export interface PluginLabels {
    plugin_status: Record<string, string>;
    plugin_kind: Record<string, string>;
}

/** Tông màu StatusBadge theo trạng thái plugin. */
export function pluginStatusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    return ({ installed: 'info', enabled: 'success', draining: 'warning', failed: 'danger' } as const)[status as 'installed'] ?? 'neutral';
}
