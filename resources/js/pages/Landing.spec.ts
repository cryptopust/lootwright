import { config, shallowMount } from '@vue/test-utils';
import { afterAll, beforeAll, describe, expect, it } from 'vitest';

import Landing from './Landing.vue';

describe('Landing', () => {
    beforeAll(() => {
        config.global.renderStubDefaultSlot = true;
    });

    afterAll(() => {
        config.global.renderStubDefaultSlot = false;
    });

    it('renders the product boundary without protected imagery or Trade links', () => {
        const wrapper = shallowMount(Landing, {
            global: {
                stubs: {
                    Head: true,
                },
            },
        });

        expect(wrapper.get('h1').text()).toContain('Build kararlarını');
        expect(wrapper.findAll('img')).toHaveLength(0);
        expect(wrapper.html()).not.toContain('pathofexile.com/trade');
    });
});
