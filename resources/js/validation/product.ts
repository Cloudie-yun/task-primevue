import { z } from 'zod';

export const basicDetailsSchema = z.object({
    name: z.string().min(1, 'Product name is required').max(255),
    description: z.string().min(1, 'Description is required').max(1000, 'Description is too long, only 1000 characters allowed'),
    price: z.number({ message: 'Price is required' }).positive('Price must be greater than 0'),
});

// Keep productSchema too if anything else still references the full shape, or just alias:
export const productSchema = basicDetailsSchema.extend({
    category_id: z.number({ message: 'Please select a category' }).nullable().refine((v) => v !== null, {
        message: 'Please select a category',
    }),
});

export const variantRowSchema = z.object({
    additional_price: z
        .number({ message: 'Enter a valid amount' })
        .min(0, 'Additional price cannot be negative'),
    stock: z
        .number({ message: 'Please enter a valid number' })
        .int('Stock must be a whole number')
        .min(0, 'Stock cannot be negative')
});