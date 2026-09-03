import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import AffixRow from './AffixRow.vue';
import ItemCard from './ItemCard.vue';
import StatChip from './StatChip.vue';
import TerminalBlock from './TerminalBlock.vue';

describe('ARPG evidence components', () => {
    it('renders unknown values explicitly instead of zero or an empty string', () => {
        const wrapper = mount(StatChip, {
            props: {
                label: 'Exact price',
                value: null,
                note: 'no-live-listings',
            },
        });
        expect(wrapper.text()).toContain('bilinmiyor');
        expect(wrapper.text()).toContain('no-live-listings');
    });

    it('labels rarity with text and item level with tabular data', () => {
        const wrapper = mount(ItemCard, {
            props: {
                slot: 'HELMET',
                name: 'Fixture Helm',
                baseName: 'Test Base',
                rarity: 'rare',
                ilvl: 84,
                fixture: true,
                affixes: [],
            },
        });
        expect(wrapper.get('.rarity-badge').text()).toContain('rare');
        expect(wrapper.text()).toContain('ilvl 84');
        expect(wrapper.text()).toContain('CANLI OYUN İDDİASI DEĞİL');
    });

    it('exposes roll progress as an accessible textual image', () => {
        const wrapper = mount(AffixRow, {
            props: {
                text: 'Life',
                value: '+92',
                minimum: 80,
                maximum: 99,
                roll: 92,
                tier: 'T1',
            },
        });
        expect(wrapper.get('[role="img"]').attributes('aria-label')).toContain(
            '92',
        );
        expect(wrapper.text()).toContain('T1');
    });

    it('copies only the visible manual recipe text', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        Object.defineProperty(navigator, 'clipboard', {
            configurable: true,
            value: { writeText },
        });
        const wrapper = mount(TerminalBlock, {
            props: { content: '# fixture\nlife.min = 80' },
        });
        await wrapper.get('button').trigger('click');
        expect(writeText).toHaveBeenCalledWith('# fixture\nlife.min = 80');
        expect(wrapper.text()).toContain('Kopyalandı');
        expect(wrapper.get('.is-threshold').text()).toBe('80');
    });
});
