import { z } from 'zod';

export const productSchema = z.object({
    name: z.string().min(1, 'Product name is required').max(255),
    category_id: z.number({ message: 'Please select a category' }).nullable().refine((v) => v !== null, {
        message: 'Please select a category',
    }),
    description: z.string().min(1, 'Description is required'),
    price: z.number({ message: 'Price is required' }).positive('Price must be greater than 0'),
});