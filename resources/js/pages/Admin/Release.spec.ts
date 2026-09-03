import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Release from './Release.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<span />' },
}));

const edition = (game: 'poe1' | 'poe2', isPublic: boolean) => ({
    edition: game,
    public: isPublic,
    status: 'FAIL' as const,
    ruleset: null,
    coverage: [
        {
            category: 'skill_gem',
            observed_records: 0,
            expected_records: null,
            coverage_percent: null,
            status: 'missing',
        },
    ],
    parser: {
        adapter: null,
        observed_completed_analysis: false,
        coverage_status: 'not_observed',
    },
    analysis_rules: {
        available: 0,
        expected: null,
        coverage_percent: null,
        status: 'missing',
    },
    recommendation_trace: { recommendations: 0, complete_traces: 0 },
    unsupported_mechanics: {
        sample_size: 0,
        analyses_with_unsupported: 0,
        rate_percent: null,
    },
    latencies_ms: {
        import: null,
        analysis_end_to_end: null,
        planner: null,
        trade_recipe: null,
    },
    gates: [
        {
            key: 'staging_acceptance',
            label: 'Manuel staging kabulü',
            status: 'BLOCKED' as const,
            evidence: 'İmzalı staging kabul kaydı yok.',
            critical: true,
        },
    ],
    blockers: ['Manuel staging kabulü: İmzalı staging kabul kaydı yok.'],
    limitations: ['Trade-vocabulary coverage is not observed.'],
});

describe('Admin release dashboard', () => {
    it('renders independent edition verdicts and unknown metrics explicitly', () => {
        const wrapper = mount(Release, {
            props: {
                releaseGate: {
                    generated_at: '2026-08-21T00:00:00Z',
                    overall_status: 'FAIL',
                    active_release_edition: 'poe1',
                    scope_notice: 'Fixture acceptance değildir.',
                    editions: {
                        poe1: edition('poe1', true),
                        poe2: edition('poe2', false),
                    },
                    market_provider: {
                        source: 'POENINJA-ECONOMY-001',
                        enabled: false,
                        status: 'not_observed',
                        last_completed_at: null,
                        notice: 'Market observations are evidence.',
                    },
                    regressions: {
                        status: 'not_observed',
                        failures: null,
                        generated_at: null,
                        scope: 'Structural only.',
                    },
                    ai: {
                        calls_today: 0,
                        failures_today: 0,
                        average_latency_ms: null,
                    },
                },
            },
            global: {
                stubs: {
                    AppShell: { template: '<main><slot /></main>' },
                    AdminNav: { template: '<nav />' },
                },
            },
        });

        expect(wrapper.text()).toContain('Path of Exile 1');
        expect(wrapper.text()).toContain('Path of Exile 2');
        expect(wrapper.text()).toContain('BLOCKED');
        expect(wrapper.text()).toContain('bilinmiyor');
        expect(wrapper.text()).toContain('POENINJA-ECONOMY-001');
    });
});
