import { shallowMount } from '@vue/test-utils';
import type { VueWrapper } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';

import StatusBanner from '@/components/app/StatusBanner.vue';

import NewAnalysis from './New.vue';

function mountWizard(): VueWrapper {
    return shallowMount(NewAnalysis, {
        global: {
            stubs: {
                AppShell: { template: '<div><slot /></div>' },
                Head: true,
            },
        },
    });
}

describe('New analysis wizard', () => {
    beforeEach(() => {
        window.localStorage.clear();
    });

    it('blocks an empty build input before moving to the goal step', async () => {
        const wrapper = mountWizard();

        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(wrapper.get('[role="alert"]').text()).toContain(
            'en az 12 karakterlik',
        );
        expect(
            wrapper.get('.wizard-steps [aria-current="step"]').text(),
        ).toContain('1');
    });

    it('rejects a PoE2 marker when PoE1 is selected', async () => {
        const wrapper = mountWizard();
        await wrapper
            .get('textarea')
            .setValue('PathOfBuilding2 fixture payload');

        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(wrapper.get('[role="alert"]').text()).toContain(
            'edition işareti çelişiyor',
        );
    });

    it('makes the inactive PoE2 boundary explicit', async () => {
        const wrapper = mountWizard();
        await wrapper.get('input[value="poe2"]').setValue(true);
        const banner = wrapper.findComponent(StatusBanner);

        expect(banner.exists()).toBe(true);
        expect(banner.props('tone')).toBe('warning');
        expect(banner.props('title')).toBe('PoE2 analizi kapalı');
        expect(String(banner.props('body'))).toContain(
            'Trade tarifi üretilmez',
        );
    });

    it('requires explicit processing consent before review', async () => {
        const wrapper = mountWizard();
        await wrapper.get('textarea').setValue('eNrtFixtureBuildInput');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper
            .get('textarea')
            .setValue('Haritalamada daha dayanıklı olmak istiyorum.');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(wrapper.get('[role="alert"]').text()).toContain(
            'saklama açıklamasını onayla',
        );

        await wrapper.get('.consent-choice input').setValue(true);
        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(wrapper.get('legend').text()).toContain('Gönderim öncesi');
    });
});
