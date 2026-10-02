export interface NavItem {
    key: string;
    label: string;
    url: string;
}

export interface CategoryNode {
    id: number;
    slug: string;
    name: string | null;
    status: 'active' | 'hidden';
    position: number;
    depth: number;
    image_url: string | null;
    children: CategoryNode[];
}

export type Translations<T extends string> = Partial<Record<'vi' | 'en', Partial<Record<T, string | null>>>>;
