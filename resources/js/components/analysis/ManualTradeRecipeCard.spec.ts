import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { demoRecipes } from '@/data/demo-analysis';

import ManualTradeRecipeCard from './ManualTradeRecipeCard.vue';

describe('ManualTradeRecipeCard', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('copies only Lootwright plain text after an explicit click', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        });
        const wrapper = mount(ManualTradeRecipeCard, {
            props: { recipe: demoRecipes[0] },
        });

        expect(writeText).not.toHaveBeenCalled();

        await wrapper.get('button.button.is-secondary').trigger('click');

        expect(writeText).toHaveBeenCalledOnce();
        const copied = String(writeText.mock.calls[0][0]);
        expect(copied).toContain('Lootwright manual Trade recipe');
        expect(copied).not.toMatch(/https?:\/\//);
        expect(wrapper.get('.copy-status').text()).toContain(
            'Tarif kopyalandı',
        );
    });

    it('shows exact strict values and relaxes only the approved broad variant', async () => {
        const wrapper = mount(ManualTradeRecipeCard, {
            props: { recipe: demoRecipes[0] },
        });
        const variantButtons = wrapper.findAll('.segmented-control button');

        expect(wrapper.text()).toContain('min 90');
        expect(wrapper.text()).toContain('+#% to Cold Resistance');
        expect(variantButtons[0].attributes('aria-pressed')).toBe('true');

        await variantButtons[1].trigger('click');

        expect(variantButtons[1].attributes('aria-pressed')).toBe('true');
        expect(wrapper.text()).toContain('min 70');
        expect(wrapper.text()).not.toContain('min 90');
        expect(
            wrapper
                .get('a[href="https://www.pathofexile.com/trade"]')
                .attributes('href'),
        ).toBe('https://www.pathofexile.com/trade');
        expect(wrapper.html()).not.toContain('/api/trade/');
    });

    it('removes the official homepage action when the emergency switch is off', () => {
        const wrapper = mount(ManualTradeRecipeCard, {
            props: {
                recipe: demoRecipes[0],
                externalLinkEnabled: false,
            },
        });

        expect(
            wrapper
                .find('a[href="https://www.pathofexile.com/trade"]')
                .exists(),
        ).toBe(false);
        expect(wrapper.text()).toContain(
            'Harici bağlantılar acil durum anahtarıyla kapalı',
        );
    });
});
