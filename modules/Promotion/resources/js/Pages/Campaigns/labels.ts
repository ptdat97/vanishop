export const stateLabels: Record<string, string> = {
    draft: 'Nháp',
    scheduled: 'Sắp chạy',
    running: 'Đang chạy',
    ended: 'Đã kết thúc',
    stopped: 'Đã dừng',
};

export const stateClasses: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-600',
    scheduled: 'bg-amber-50 text-amber-700',
    running: 'bg-emerald-50 text-emerald-700',
    ended: 'bg-slate-100 text-slate-500',
    stopped: 'bg-red-50 text-red-700',
};
