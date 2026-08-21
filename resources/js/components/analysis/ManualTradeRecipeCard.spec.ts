import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { demoRecipes } from '@/data/demo-analysis';
import type { TradeRecipeView } from '@/types/analysis-ui';

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

    it('copies the currently selected production recipe and discloses unsupported filters', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        });
        const recipe: TradeRecipeView = {
            game_edition: 'poe1',
            slot: 'helmet',
            item_class: 'Armour > Helmets',
            base_constraints: {},
            rarity: null,
            influence_or_edition_equivalent: null,
            corruption_constraints: null,
            required_modifiers: [
                {
                    canonical_modifier_id: 'defence.maximum_life',
                    label: '+# to maximum Life',
                    minimum: '90',
                },
            ],
            optional_modifiers: [],
            excluded_modifiers: [],
            minimum_values: { 'defence.maximum_life': '90' },
            weights: {},
            dependencies: [
                { slot: 'ring', reason: 'Review resistance before replacing.' },
            ],
            broad_recipe: 'BROAD MANUAL FILTER',
            strict_recipe: 'STRICT MANUAL FILTER',
            explanation: 'Deterministic fixture explanation.',
            provenance: {
                source_id: 'LOOTWRIGHT-001',
                source_version: 'fixture-1',
                checksum_sha256: 'a'.repeat(64),
            },
            unsupported_filters: [
                {
                    modifier_id: 'unknown.modifier',
                    reason: 'No exact approved mapping exists.',
                },
            ],
            ruleset: {
                edition: 'poe1',
                id: 'fixture.ruleset',
                version: '1.0.0',
                checksum_sha256: 'b'.repeat(64),
            },
        };
        const wrapper = mount(ManualTradeRecipeCard, { props: { recipe } });
        const buttons = wrapper.findAll('.segmented-control button');

        await buttons[1].trigger('click');
        await wrapper.get('button.button.is-secondary').trigger('click');

        expect(writeText).toHaveBeenCalledWith('BROAD MANUAL FILTER');
        expect(wrapper.text()).toContain('unknown.modifier');
        expect(wrapper.text()).toContain('ring: Review resistance before replacing.');
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
