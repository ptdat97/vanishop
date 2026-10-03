export type Kind = "pages" | "posts";

export const kindLabels: Record<Kind, { plural: string; singular: string }> = {
    pages: { plural: "Trang", singular: "trang" },
    posts: { plural: "Bài viết", singular: "bài viết" },
};

export const statusLabels: Record<string, { label: string; class: string }> = {
    draft: { label: "Nháp", class: "bg-slate-100 text-slate-700" },
    scheduled: { label: "Hẹn giờ", class: "bg-amber-100 text-amber-800" },
    published: { label: "Đã đăng", class: "bg-emerald-100 text-emerald-800" },
};
