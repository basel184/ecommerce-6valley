<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestReverseTransfer extends Command
{
    protected $signature = 'test:reverse-transfer {order_id}';
    protected $description = 'اختبار النظام للبحث عن معرف المعاملة';

    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $this->info("🔍 اختبار النظام للطلب رقم: {$orderId}");
        
        try {
            // البحث في جدول payment_requests
            $this->info('📊 البحث في جدول payment_requests...');
            
            if (DB::getSchemaBuilder()->hasTable('payment_requests')) {
                $paymentRequest = DB::table('payment_requests')
                    ->whereJsonContains('order_ids', $orderId)
                    ->first();
                
                if ($paymentRequest) {
                    $this->info('✅ تم العثور على payment_request:');
                    $this->table(
                        ['المجال', 'القيمة'],
                        [
                            ['ID', $paymentRequest->id],
                            ['Payment Method', $paymentRequest->payment_method ?? 'غير محدد'],
                            ['Transaction Reference', $paymentRequest->transaction_reference ?? 'غير محدد'],
                            ['Transaction ID', $paymentRequest->transaction_id ?? 'غير محدد'],
                            ['Amount', $paymentRequest->payment_amount ?? 'غير محدد'],
                            ['Status', $paymentRequest->payment_status ?? 'غير محدد']
                        ]
                    );
                    
                    // تحديد المعرف الصحيح
                    if (!empty($paymentRequest->transaction_reference)) {
                        $this->info("🎯 المعرف الصحيح: {$paymentRequest->transaction_reference}");
                    } elseif (!empty($paymentRequest->transaction_id)) {
                        $this->info("🎯 المعرف الصحيح: {$paymentRequest->transaction_id}");
                    } else {
                        $this->info("🎯 المعرف الصحيح: {$paymentRequest->id}");
                    }
                    
                } else {
                    $this->warn('❌ لم يتم العثور على payment_request');
                }
            } else {
                $this->warn('❌ جدول payment_requests غير موجود');
            }
            
            // البحث في جدول orders
            $this->info('📊 البحث في جدول orders...');
            $order = DB::table('orders')->where('id', $orderId)->first();
            
            if ($order) {
                $this->info('✅ تم العثور على الطلب:');
                $this->table(
                    ['المجال', 'القيمة'],
                    [
                        ['ID', $order->id],
                        ['Payment Method', $order->payment_method ?? 'غير محدد'],
                        ['Payment Status', $order->payment_status ?? 'غير محدد'],
                        ['Transaction Reference', $order->transaction_reference ?? 'غير محدد'],
                        ['Amount', $order->order_amount ?? 'غير محدد']
                    ]
                );
            } else {
                $this->warn('❌ لم يتم العثور على الطلب');
            }
            
        } catch (Exception $e) {
            $this->error('❌ خطأ: ' . $e->getMessage());
        }
        
        return 0;
    }
}
