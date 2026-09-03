export type User = {
    id: number;
    name: string;
    email: string;
    locale?: 'en' | 'tr';
    avatar?: string;
    email_verified_at: string | null;
    role: 'member' | 'admin' | 'super_admin';
    status: 'active' | 'suspended' | 'pending_deletion';
    two_factor_enabled: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
