export interface StaffUser {
    id: number;
    name: string;
    email: string;
}

export interface NavigationItem {
    key: string;
    label: string;
    url: string;
}

export interface SharedProps {
    app: { name: string; locale: string };
    auth: { staff: StaffUser | null };
    navigation: NavigationItem[];
    urls: { logout?: string };
    [key: string]: unknown;
}
