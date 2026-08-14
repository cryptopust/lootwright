import { config, shallowMount } from '@vue/test-utils';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';

import Welcome from './Welcome.vue';

describe('Welcome', () => {
    beforeAll(() => {
        config.global.renderStubDefaultSlot = true;
    });

    afterAll(() => {
        config.global.renderStubDefaultSlot = false;
    });

    it('renders the neutral foundation shell and required notice', () => {
        const wrapper = shallowMount(Welcome, {
            global: {
                stubs: {
                    Head: true,
                },
            },
        });

        expect(wrapper.get('h1').text()).toContain('Build decisions');
        expect(wrapper.text()).toContain(
            "This product isn't affiliated with or endorsed by Grinding Gear Games in any way.",
        );
        expect(wrapper.findAll('img')).toHaveLength(0);
        expect(wrapper.html()).not.toContain('pathofexile.com');
    });
});
