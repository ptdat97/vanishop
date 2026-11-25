import type { ExtensionField, ExtensionFieldValue } from '@admin/types';

export type RepeaterRow = Record<string, ExtensionFieldValue>;
export type BuilderValue = ExtensionFieldValue | RepeaterRow[];
export type BuilderBlock = { type: string; config: Record<string, BuilderValue> };
export type BuilderField = ExtensionField | (Omit<ExtensionField, 'type'> & {
    type: 'repeater';
    max: number;
    fields: ExtensionField[];
});

export function emptyBuilderValue(field: BuilderField): BuilderValue {
    if (field.type === 'repeater') return [];
    return field.type === 'bool' ? false : null;
}
