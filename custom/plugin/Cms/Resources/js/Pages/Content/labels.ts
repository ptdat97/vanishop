export type Kind = 'pages' | 'posts';

export const kindLabels: Record<Kind, { plural: string; singular: string }> = {
    pages: { plural: 'Trang', singular: 'trang' },
    posts: { plural: 'Bài viết', singular: 'bài viết' },
};

export const statusLabels: Record<string, { label: string; tone: 'neutral' | 'warning' | 'success' }> = {
    draft: { label: 'Nháp', tone: 'neutral' },
    scheduled: { label: 'Hẹn giờ', tone: 'warning' },
    published: { label: 'Đã đăng', tone: 'success' },
};
