<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\BaseController;
use App\Models\Cart;
use App\Models\User;
use App\Services\AbandonedCartService;
use Carbon\Carbon;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AbandonedCartController extends BaseController
{
    public function __construct(
        private readonly AbandonedCartService $abandonedCartService
    ) {
    }

    /**
     * Display a listing of abandoned carts
     */
    public function index(Request|null $request, $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
    // Auto-mark stale carts as abandoned to keep the list always fresh
    $this->autoMarkAbandoned();

        $searchValue = $request->get('searchValue', '');
        $filter = $request->get('filter', 'all');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Cart::abandoned()->with(['customer', 'productWithDetails', 'sellerWithDetails']);

        // Apply search filter
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('customer', function ($customerQuery) use ($searchValue) {
                    $customerQuery->where('f_name', 'like', "%{$searchValue}%")
                        ->orWhere('l_name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('phone', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('productWithDetails', function ($productQuery) use ($searchValue) {
                    $productQuery->where('name', 'like', "%{$searchValue}%");
                })
                ->orWhere('cart_group_id', 'like', "%{$searchValue}%");
            });
        }

        // Apply date filter
        if ($from && $to) {
            $query->whereBetween('abandoned_at', [$from, $to]);
        }

        // Apply status filter
        switch ($filter) {
            case 'with_reminders':
                $query->where('reminder_sent', '>', 0);
                break;
            case 'without_reminders':
                $query->where('reminder_sent', 0);
                break;
            case 'recent':
                $query->where('abandoned_at', '>=', Carbon::now()->subDays(7));
                break;
            case 'old':
                $query->where('abandoned_at', '<', Carbon::now()->subDays(7));
                break;
        }

        $abandonedCarts = $query->orderBy('abandoned_at', 'desc')
            ->paginate(25);

        $statistics = $this->getStatistics();

        return view('admin-views.order.abandoned-cart.list', compact(
            'abandonedCarts',
            'statistics',
            'searchValue',
            'filter',
            'from',
            'to'
        ));
    }

    /**
     * Mark outdated carts as abandoned based on configured threshold.
     */
    private function autoMarkAbandoned(): void
    {
        try {
            $minutes = (int) config('abandoned_cart.threshold_minutes', 60);

            // Safety bounds: 5 minutes minimum, 7 days maximum
            $minutes = max(5, min($minutes, 60 * 24 * 7));

            $cutoff = \Carbon\Carbon::now()->subMinutes($minutes);

            // Only mark active carts older than cutoff
            $staleCarts = \App\Models\Cart::active()
                ->where('updated_at', '<', $cutoff)
                ->limit(500) // avoid massive one-shot updates
                ->get();

            foreach ($staleCarts as $cart) {
                $cart->markAsAbandoned();
            }
        } catch (\Throwable $e) {
            \Log::error('autoMarkAbandoned failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show abandoned cart details
     */
    public function show($id): View
    {
        $abandonedCart = Cart::abandoned()->with(['customer', 'productWithDetails', 'sellerWithDetails', 'cartShipping'])
            ->findOrFail($id);

        $relatedCarts = Cart::abandoned()->where('cart_group_id', $abandonedCart->cart_group_id)
            ->with(['productWithDetails'])
            ->get();

    // Provide Twilio templates (config-based) to the view. If using Content SIDs, they will be embedded in the config.
    $twilioTemplates = config('twilio.templates', []);

    return view('admin-views.order.abandoned-cart.details', compact('abandonedCart', 'relatedCarts', 'twilioTemplates'));
    }

    /**
     * Send reminder to customer
     */
    public function sendReminder(Request $request): JsonResponse
    {
        $cartId = $request->get('cart_id');
        $templateKey = $request->get('template_key');
        $contentSid = $request->get('content_sid');
        $abandonedCart = Cart::abandoned()->findOrFail($cartId);

        try {
            $this->abandonedCartService->sendReminder($abandonedCart, $templateKey, $contentSid);
            
            return response()->json([
                'success' => true,
                'message' => translate('reminder_sent_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => translate('failed_to_send_reminder')
            ], 500);
        }
    }

    /**
     * Send reminder to multiple customers
     */
    public function sendBulkReminders(Request $request): JsonResponse
    {
        $cartIds = $request->get('cart_ids', []);
    $templateKey = $request->get('template_key');
    $contentSid = $request->get('content_sid');
        
        if (empty($cartIds)) {
            return response()->json([
                'success' => false,
                'message' => translate('no_carts_selected')
            ], 400);
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($cartIds as $cartId) {
            try {
                $abandonedCart = Cart::abandoned()->find($cartId);
                if ($abandonedCart) {
                    $this->abandonedCartService->sendReminder($abandonedCart, $templateKey, $contentSid);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => translate('reminders_sent_successfully', [
                'success' => $successCount,
                'failed' => $failedCount
            ])
        ]);
    }

    /**
     * Delete abandoned cart
     */
    public function destroy($id): RedirectResponse
    {
        $abandonedCart = Cart::abandoned()->findOrFail($id);
        $abandonedCart->delete();

        ToastMagic::success(translate('abandoned_cart_deleted_successfully'));
        return redirect()->route('admin.abandoned-carts.index');
    }

    /**
     * Delete multiple abandoned carts
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $cartIds = $request->get('cart_ids', []);
        
        if (empty($cartIds)) {
            return response()->json([
                'success' => false,
                'message' => translate('no_carts_selected')
            ], 400);
        }

        Cart::abandoned()->whereIn('id', $cartIds)->delete();

        return response()->json([
            'success' => true,
            'message' => translate('abandoned_carts_deleted_successfully')
        ]);
    }

    /**
     * Restore abandoned cart to active cart
     */
    public function restoreCart(Request $request): JsonResponse
    {
        $cartGroupId = $request->get('cart_group_id');
        $customerId = $request->get('customer_id');

        try {
            $success = $this->abandonedCartService->restoreCart($cartGroupId, $customerId);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => translate('cart_restored_successfully')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => translate('failed_to_restore_cart')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => translate('failed_to_restore_cart')
            ], 500);
        }
    }

    /**
     * Export abandoned carts
     */
    public function export(Request $request)
    {
        $searchValue = $request->get('searchValue', '');
        $filter = $request->get('filter', 'all');
        $from = $request->get('from');
        $to = $request->get('to');

        $query = Cart::abandoned()->with(['customer', 'productWithDetails', 'sellerWithDetails']);

        // Apply filters (same as index method)
        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('customer', function ($customerQuery) use ($searchValue) {
                    $customerQuery->where('f_name', 'like', "%{$searchValue}%")
                        ->orWhere('l_name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%")
                        ->orWhere('phone', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('productWithDetails', function ($productQuery) use ($searchValue) {
                    $productQuery->where('name', 'like', "%{$searchValue}%");
                })
                ->orWhere('cart_group_id', 'like', "%{$searchValue}%");
            });
        }

        if ($from && $to) {
            $query->whereBetween('abandoned_at', [$from, $to]);
        }

        switch ($filter) {
            case 'with_reminders':
                $query->where('reminder_sent', '>', 0);
                break;
            case 'without_reminders':
                $query->where('reminder_sent', 0);
                break;
            case 'recent':
                $query->where('abandoned_at', '>=', Carbon::now()->subDays(7));
                break;
            case 'old':
                $query->where('abandoned_at', '<', Carbon::now()->subDays(7));
                break;
        }

        $abandonedCarts = $query->orderBy('abandoned_at', 'desc')->get();

        return $this->abandonedCartService->exportToExcel($abandonedCarts);
    }

    /**
     * Get statistics for abandoned carts
     */
    private function getStatistics(): array
    {
        $totalAbandonedCarts = Cart::abandoned()->count();
        $totalValue = Cart::abandoned()->sum(\DB::raw('price * quantity'));
        $cartsWithReminders = Cart::abandoned()->where('reminder_sent', '>', 0)->count();
        $cartsWithoutReminders = Cart::abandoned()->where('reminder_sent', 0)->count();
        $recentCarts = Cart::abandoned()->where('abandoned_at', '>=', Carbon::now()->subDays(7))->count();
        
        // New statistics for better insights
        $cartsWithInactiveProducts = Cart::abandoned()->whereHas('productWithDetails', function($query) {
            $query->where('status', 0);
        })->count();
        
        $cartsWithDeletedSellers = Cart::abandoned()->whereDoesntHave('sellerWithDetails')->count();

        return [
            'total' => $totalAbandonedCarts,
            'total_value' => $totalValue,
            'with_reminders' => $cartsWithReminders,
            'without_reminders' => $cartsWithoutReminders,
            'recent' => $recentCarts,
            'with_inactive_products' => $cartsWithInactiveProducts,
            'with_deleted_sellers' => $cartsWithDeletedSellers,
        ];
    }
} 