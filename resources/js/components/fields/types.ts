export interface BlockField {
    key: string;
    type: string;
    label: string;
    required?: boolean;
    cols?: number;
    config?: { options?: { label: string; value: string | number }[] };
}

export const fieldString = (value: unknown): string =>
    typeof value === 'string' || typeof value === 'number' ? String(value) : '';
