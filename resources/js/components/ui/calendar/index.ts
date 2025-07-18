import { cva, type VariantProps } from 'class-variance-authority'

export { default as Calendar } from './Calendar.vue'

export const calendarVariants = cva(
  'p-3',
  {
    variants: {
      variant: {
        default: 'bg-background text-foreground',
        outline: 'border border-input bg-background text-foreground',
      },
      size: {
        default: 'text-sm',
        sm: 'text-xs',
        lg: 'text-base',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
)

export type CalendarVariants = VariantProps<typeof calendarVariants>
