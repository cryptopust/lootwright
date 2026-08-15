import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { demoFindings } from '@/data/demo-analysis';

import FindingRow from './FindingRow.vue';

describe('FindingRow', () => {
    it('reveals the deterministic trace and limitation only after the user asks why', async () => {
        const finding = demoFindings[0];
        const wrapper = mount(FindingRow, { props: { finding } });
        const trigger = wrapper.get('.disclosure-trigger');

        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(wrapper.find('.evidence-body').exists()).toBe(false);

        await trigger.trigger('click');

        expect(trigger.attributes('aria-expanded')).toBe('true');
        expect(wrapper.get('.evidence-body').text()).toContain(
            finding.evidence[0].rule,
        );
        expect(wrapper.get('.evidence-body').text()).toContain(
            finding.evidence[0].source,
        );
        expect(wrapper.get('.limitation-copy').text()).toContain(
            finding.limitation.tr,
        );
    });

    it('renders stored character and explanation text as escaped text, never HTML', () => {
        const hostile = {
            ...demoFindings[0],
            title: {
                tr: '<script>window.pwned=true</script>',
                en: '<img src=x onerror=window.pwned=true>',
            },
            summary: {
                tr: '<svg onload=window.pwned=true>',
                en: '<svg onload=window.pwned=true>',
            },
        };
        const wrapper = mount(FindingRow, { props: { finding: hostile } });

        expect(wrapper.text()).toContain('<script>window.pwned=true</script>');
        expect(wrapper.html()).not.toContain('<script>');
        expect(wrapper.html()).not.toContain('<svg onload');
    });
});
