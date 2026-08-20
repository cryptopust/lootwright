import { flushPromises, shallowMount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import NewAnalysis from './New.vue';

vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');

    return {
        ...actual,
        Head: { template: '<span />' },
        usePage: () => ({
            props: {
                auth: { user: { email_verified_at: '2026-08-20T00:00:00Z' } },
            },
        }),
    };
});

const catalog = {
    game: 'poe1',
    patch: '3.28',
    verified_at: '2026-08-20T00:00:00Z',
    source: 'https://www.poewiki.net/wiki/Ascendancy_class',
    classes: [
        {
            id: 'ranger',
            name: 'Ranger',
            availability: 'available',
            ascendancies: [
                { id: 'deadeye', name: 'Deadeye', type: 'regular' },
                { id: 'warden', name: 'Warden', type: 'regular' },
            ],
        },
        {
            id: 'witch',
            name: 'Witch',
            availability: 'available',
            ascendancies: [
                { id: 'elementalist', name: 'Elementalist', type: 'regular' },
            ],
        },
    ],
};

function mountWizard() {
    return shallowMount(NewAnalysis, {
        global: {
            stubs: {
                AppShell: { template: '<div><slot /></div>' },
            },
        },
    });
}

describe('New analysis wizard', () => {
    beforeEach(() => {
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue({ ok: true, json: async () => catalog }),
        );
    });

    it('renders seven accessible steps and supports forward/back navigation', async () => {
        const wrapper = mountWizard();
        await flushPromises();

        expect(wrapper.findAll('.wizard-steps li')).toHaveLength(7);
        expect(wrapper.find('[aria-current="step"]')?.text()).toContain('1');

        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        expect(wrapper.find('[aria-current="step"]')?.text()).toContain('2');
        await wrapper.get('.wizard-actions .is-secondary').trigger('click');
        expect(wrapper.find('[aria-current="step"]')?.text()).toContain('1');
    });

    it('clears an incompatible ascendancy when the class changes', async () => {
        const wrapper = mountWizard();
        await flushPromises();
        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        const selects = wrapper.findAll('select');
        await selects[0].setValue('ranger');
        await selects[1].setValue('warden');
        await selects[0].setValue('witch');

        expect((selects[1].element as HTMLSelectElement).value).toBe('');
        expect((selects[1].element as HTMLSelectElement).disabled).toBe(false);
    });

    it('switches games, fetches the isolated catalog and clears game-specific values', async () => {
        const wrapper = mountWizard();
        await flushPromises();
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.findAll('select')[0].setValue('ranger');
        await wrapper.findAll('select')[1].setValue('warden');
        await wrapper.get('.wizard-actions .is-secondary').trigger('click');
        await wrapper.get('input[value="poe2"]').setValue(true);
        await flushPromises();
        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(
            (wrapper.findAll('select')[0].element as HTMLSelectElement).value,
        ).toBe('');
        expect(
            (wrapper.findAll('select')[1].element as HTMLSelectElement).value,
        ).toBe('');
        expect(vi.mocked(fetch)).toHaveBeenCalledWith(
            '/api/catalog/poe2/character-options',
        );
    });

    it('applies conditional validation for PoB and budget fields', async () => {
        const wrapper = mountWizard();
        await flushPromises();
        await wrapper.get('input[value="analyse"]').setValue(true);
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.findAll('select')[0].setValue('ranger');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');

        expect(wrapper.get('[role="alert"]').text()).toContain('PoB');
        await wrapper.get('textarea').setValue('short');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        expect(wrapper.get('[role="alert"]').text()).toContain('PoB');

        await wrapper.get('textarea').setValue('abcdefghijkl');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        const budget = wrapper.find('input[pattern]');
        await budget.setValue('-1');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        expect(wrapper.get('[role="alert"]').text()).toContain('negatif');
    });

    it('does not write raw artifacts to localStorage', async () => {
        const setItem = vi.spyOn(Storage.prototype, 'setItem');
        const wrapper = mountWizard();
        await flushPromises();
        await wrapper.get('input[value="analyse"]').setValue(true);
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.findAll('select')[0].setValue('ranger');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.get('textarea').setValue('raw-secret-pob');

        expect(setItem).not.toHaveBeenCalled();
        expect(window.localStorage.getItem('raw-secret-pob')).toBeNull();
    });

    it('fills canonical class and ascendancy fields from a PoB import', async () => {
        vi.mocked(fetch).mockImplementation(async (input) => {
            const url = String(input);

            if (url.includes('build-imports')) {
                return {
                    ok: true,
                    json: async () => ({
                        import: {
                            canonical_build: {
                                character_class_id: 'poe1.pob.class.ranger',
                                ascendancy_id: 'poe1.pob.ascendancy.warden',
                                character_level: 92,
                                skills: [{ name: 'Lightning Arrow' }],
                            },
                        },
                    }),
                } as Response;
            }

            return {
                ok: true,
                json: async () =>
                    url.includes('analysis-draft') ? { draft: null } : catalog,
            } as Response;
        });
        const wrapper = mountWizard();
        await flushPromises();
        await wrapper.get('input[value="analyse"]').setValue(true);
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.findAll('select')[0].setValue('ranger');
        await wrapper.get('.wizard-actions .is-primary').trigger('click');
        await wrapper.get('textarea').setValue('abcdefghijkl');
        const importButton = wrapper
            .findAll('button')
            .find((button) => button.text().includes('PoB alanlarını'));
        expect(importButton).toBeDefined();
        await importButton?.trigger('click');
        await flushPromises();

        await wrapper.get('.wizard-actions .is-secondary').trigger('click');
        const selects = wrapper.findAll('select');
        expect((selects[0].element as HTMLSelectElement).value).toBe('ranger');
        expect((selects[1].element as HTMLSelectElement).value).toBe('warden');
        expect(
            (wrapper.get('input[type="number"]').element as HTMLInputElement)
                .value,
        ).toBe('92');
    });
});
