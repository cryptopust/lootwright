import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AppShell from './AppShell.vue';

describe('AppShell', () => {
    it('renders landmarks, skip navigation, and the required non-affiliation notice', () => {
        const wrapper = mount(AppShell, {
            slots: {
                default: '<h1>Fixture content</h1>',
            },
        });

        expect(wrapper.get('a[href="#main-content"]').text()).toBe(
            'İçeriğe geç',
        );
        expect(wrapper.get('main').attributes('id')).toBe('main-content');
        expect(wrapper.text()).toContain(
            "This product isn't affiliated with or endorsed by Grinding Gear Games in any way.",
        );
    });
});
