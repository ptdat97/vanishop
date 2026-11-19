import { t } from '@admin/i18n';
export type Kind = 'pages' | 'posts';

/** Nhãn theo ngôn ngữ Admin — gọi trong setup (không tính ở cấp module, để đổi theo ngôn ngữ). */
export function kindLabels(): Record<Kind, { plural: string; singular: string }> {
    return {
        pages: { plural: t('vani-cms::admin.content_labels.pages'), singular: t('vani-cms::admin.content_labels.page') },
        posts: { plural: t('vani-cms::admin.content_labels.posts'), singular: t('vani-cms::admin.content_labels.post') },
    };
}

export function statusLabels(): Record<string, { label: string; tone: 'neutral' | 'warning' | 'success' }> {
    return {
        draft: { label: t('vani-cms::admin.content_labels.draft'), tone: 'neutral' },
        scheduled: { label: t('vani-cms::admin.content_labels.scheduled'), tone: 'warning' },
        published: { label: t('vani-cms::admin.content_labels.published'), tone: 'success' },
    };
}
