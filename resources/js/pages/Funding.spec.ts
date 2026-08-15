import { shallowMount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import Funding from './Funding.vue';

const funding = {
    requested_enabled: true,
    enabled: false,
    accepting_funds: false,
    policy_decision: 'deny',
    activation_requirements: {
        dated_policy_decision: true,
        permission_evidence_recorded: true,
        operator_activation: true,
        public_disclosure_versioned: true,
    },
    currency: 'USD',
    pricing_model: 'gpt-5.4-nano',
    pricing_reviewed_on: '2026-08-15',
    pricing_source: 'https://developers.openai.com/api/docs/pricing',
    cost_projections: [
        {
            scenario: 'base',
            analyses_per_month: 10_000,
            ai_usage_rate_basis_points: 4_000,
            estimated_ai_calls: 4_000,
            hosting_monthly_cents: 5_500,
            ai_monthly_cents: 195,
            total_monthly_cents: 5_695,
        },
    ],
};

describe('Funding', () => {
    it('keeps all monetization actions hidden even after an operator enable request', () => {
        const wrapper = shallowMount(Funding, {
            props: { funding },
            global: {
                stubs: {
                    AppShell: { template: '<div><slot /></div>' },
                    Head: true,
                },
            },
        });

        expect(wrapper.text()).toContain('Para talebi yok');
        expect(wrapper.text()).toContain('gpt-5.4-nano');
        expect(wrapper.text()).toContain('GGG tarafından onaylanmış değildir');
        expect(wrapper.findAll('a')).toHaveLength(0);
        expect(wrapper.findAll('button')).toHaveLength(0);
        expect(wrapper.findAll('form')).toHaveLength(0);
        expect(wrapper.html()).not.toContain('href=');
    });
});
