import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
    'inline-flex shrink-0 items-center justify-center gap-3 border text-xs font-bold tracking-[0.14em] uppercase transition-[background-color,color,border-color,transform] outline-none focus-visible:ring-2 focus-visible:ring-arcane focus-visible:ring-offset-2 focus-visible:ring-offset-obsidian disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default:
                    'border-ember bg-ember text-obsidian hover:-translate-y-0.5 hover:bg-bone',
                outline:
                    'border-bone/30 bg-transparent text-bone hover:border-arcane hover:text-arcane',
                ghost: 'border-transparent bg-transparent text-bone hover:text-arcane',
            },
            size: {
                default: 'h-10 px-5',
                sm: 'h-8 px-3 text-[0.65rem]',
                lg: 'h-12 px-6',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
