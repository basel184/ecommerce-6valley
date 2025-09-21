<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbandonedCartExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $abandonedCarts;

    public function __construct($abandonedCarts)
    {
        $this->abandonedCarts = $abandonedCarts;
    }

    public function collection()
    {
        return $this->abandonedCarts;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Product Name',
            'Quantity',
            'Price',
            'Total Value',
            'Cart Group ID',
            'Abandoned Date',
            'Reminders Sent',
            'Last Reminder Date',
            'Seller',
            'Is Guest',
        ];
    }

    public function map($abandonedCart): array
    {
        $customer = $abandonedCart->customer;
        $product = $abandonedCart->product;
        $seller = $abandonedCart->seller;

        return [
            $abandonedCart->id,
            $customer ? $customer->f_name . ' ' . $customer->l_name : 'Guest',
            $customer ? $customer->email : 'N/A',
            $customer ? $customer->phone : 'N/A',
            $product ? $product->name : 'Product Not Found',
            $abandonedCart->quantity,
            $abandonedCart->price,
            $abandonedCart->price * $abandonedCart->quantity,
            $abandonedCart->cart_group_id,
            $abandonedCart->abandoned_at ? $abandonedCart->abandoned_at->format('Y-m-d H:i:s') : 'N/A',
            $abandonedCart->reminder_sent,
            $abandonedCart->last_reminder_sent_at ? $abandonedCart->last_reminder_sent_at->format('Y-m-d H:i:s') : 'N/A',
            $seller ? $seller->f_name . ' ' . $seller->l_name : 'N/A',
            $abandonedCart->is_guest ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
        ];
    }
} 