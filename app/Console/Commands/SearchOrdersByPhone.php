<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReverseTransferService;

class SearchOrdersByPhone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:search-by-phone {phone} {--amount= : Search for specific amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'البحث عن الطلبات برقم الهاتف والمبلغ';

    /**
     * Execute the console command.
     */
    public function handle(ReverseTransferService $reverseTransferService)
    {
        $phone = $this->argument('phone');
        $amount = $this->option('amount');

        $this->info("🔍 البحث عن الطلبات لرقم الهاتف: {$phone}");

        if ($amount) {
            $this->info("💰 البحث عن طلب بمبلغ: {$amount} ريال");
            $this->searchByPhoneAndAmount($reverseTransferService, $phone, $amount);
        } else {
            $this->info("📋 عرض جميع الطلبات المتاحة");
            $this->searchAllOrdersByPhone($reverseTransferService, $phone);
        }
    }

    /**
     * البحث عن طلب محدد برقم الهاتف والمبلغ
     */
    protected function searchByPhoneAndAmount($reverseTransferService, $phone, $amount)
    {
        try {
            $result = $reverseTransferService->findBestMatchingOrder($phone, $amount);

            if ($result['success']) {
                $this->info("✅ " . $result['message']);
                
                $bestMatch = $result['best_match'];
                $this->table(
                    ['المعرف', 'المبلغ', 'الفرق', 'التاريخ', 'طريقة الدفع'],
                    [[
                        $bestMatch['id'],
                        $bestMatch['amount'] . ' ريال',
                        $bestMatch['difference'] . ' ريال',
                        $bestMatch['created_at'],
                        $bestMatch['payment_method']
                    ]]
                );

                if (!empty($result['suggestions'])) {
                    $this->info("💡 اقتراحات أخرى:");
                    $this->displaySuggestions($result['suggestions']);
                }
            } else {
                $this->warn("⚠️ " . $result['message']);
                
                if (!empty($result['suggestions'])) {
                    $this->info("💡 أفضل الاقتراحات:");
                    $this->displaySuggestions($result['suggestions']);
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ: " . $e->getMessage());
        }
    }

    /**
     * البحث عن جميع الطلبات برقم الهاتف
     */
    protected function searchAllOrdersByPhone($reverseTransferService, $phone)
    {
        try {
            $result = $reverseTransferService->findAvailableOrdersByPhone($phone);

            if ($result['success']) {
                $this->info("✅ " . $result['message']);
                $this->info("📊 إجمالي الطلبات: " . $result['total_orders']);
                $this->info("💰 المبالغ المتاحة: " . implode(', ', $result['available_amounts']));

                // عرض الطلبات مجمعة حسب المبلغ
                foreach ($result['orders'] as $amount => $orders) {
                    $this->info("\n💰 المبلغ: {$amount} ريال");
                    $this->table(
                        ['المعرف', 'التاريخ', 'طريقة الدفع', 'معرف المعاملة'],
                        $orders->map(function($order) {
                            return [
                                $order['id'],
                                $order['created_at'],
                                $order['payment_method'],
                                $order['transaction_reference'] ?? 'غير متوفر'
                            ];
                        })->toArray()
                    );
                }
            } else {
                $this->warn("⚠️ " . $result['message']);
            }

        } catch (\Exception $e) {
            $this->error("❌ خطأ: " . $e->getMessage());
        }
    }

    /**
     * عرض الاقتراحات
     */
    protected function displaySuggestions($suggestions)
    {
        $this->table(
            ['المعرف', 'المبلغ', 'الفرق', 'التاريخ', 'طريقة الدفع'],
            array_map(function($suggestion) {
                return [
                    $suggestion['id'],
                    $suggestion['amount'] . ' ريال',
                    $suggestion['difference'] . ' ريال',
                    $suggestion['created_at'],
                    $suggestion['payment_method']
                ];
            }, $suggestions)
        );
    }
}
