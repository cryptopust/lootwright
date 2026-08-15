import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EditionBadge from './EditionBadge.vue';

describe('EditionBadge', () => {
    it.each([
        ['poe1', 'PoE 1', 'Path of Exile 1', 'is-poe1'],
        ['poe2', 'PoE 2', 'Path of Exile 2', 'is-poe2'],
    ] as const)(
        'keeps %s identity visible in text and accessible naming',
        (edition, label, accessibleName, className) => {
            const wrapper = mount(EditionBadge, { props: { edition } });

            expect(wrapper.text()).toContain(label);
            expect(wrapper.attributes('aria-label')).toBe(accessibleName);
            expect(wrapper.classes()).toContain(className);
        },
    );
});
