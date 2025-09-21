<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Models\ReverseTransfer;
use App\Models\ReverseTransferStatus;
use App\Models\User;
use App\Services\ReverseTransferService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class ReverseTransferController extends BaseController
{
    protected $reverseTransferService;

    public function __construct(ReverseTransferService $reverseTransferService)
    {
        $this->reverseTransferService = $reverseTransferService;
    }

    public function index(?Request $request, ?string $status = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        $query = ReverseTransfer::with(['order', 'customer', 'admin', 'approvedBy', 'rejectedBy', 'processedBy']);

        // فلترة حسب الحالة
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // البحث
        if ($request && $request->has('searchValue') && $request->searchValue) {
            $searchValue = $request->searchValue;
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                  ->orWhere('transaction_id', 'like', "%{$searchValue}%")
                  ->orWhere('gateway_transaction_id', 'like', "%{$searchValue}%")
                  ->orWhere('reference_number', 'like', "%{$searchValue}%")
                  ->orWhereHas('order', function ($orderQuery) use ($searchValue) {
                      $orderQuery->where('id', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($searchValue) {
                      $customerQuery->where('f_name', 'like', "%{$searchValue}%")
                                   ->orWhere('l_name', 'like', "%{$searchValue}%")
                                   ->orWhere('phone', 'like', "%{$searchValue}%");
                  });
            });
        }

        // فلترة حسب بوابة الدفع
        if ($request && $request->has('payment_gateway') && $request->payment_gateway) {
            $query->where('payment_gateway', $request->payment_gateway);
        }

        $reverseTransfers = $query->orderBy('created_at', 'desc')
                                 ->paginate(getWebConfig('pagination_limit'));

        // إحصائيات
        $statistics = $this->reverseTransferService->getStatistics();

        return view('admin-views.reverse-transfer.list', compact('reverseTransfers', 'status', 'statistics'));
    }

    public function create(): View
    {
        // الحصول على الطلبات المدفوعة
        $orders = Order::where('payment_status', 'paid')
                      ->with(['customer'])
                      ->orderBy('created_at', 'desc')
                      ->get();

        // بوابات الدفع المتاحة
        $paymentGateways = [
            'myfatoorah' => 'MyFatoorah',
            'tabby' => 'Tabby',
            'tamara' => 'Tamara',
            'bank_transfer' => translate('bank_transfer'),
            'cash' => translate('cash'),
            'check' => translate('check'),
            'other' => translate('other')
        ];

        return view('admin-views.reverse-transfer.create', compact('orders', 'paymentGateways'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
            'payment_method' => 'required|string',
            'payment_gateway' => 'nullable|string',
            'refund_reason_code' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            
            $data = $request->all();
            $data['customer_id'] = $order->customer_id;

            $reverseTransfer = $this->reverseTransferService->createReverseTransfer($data);

            ToastMagic::success(translate('reverse_transfer_request_created_successfully'));
            return redirect()->route('admin.reverse-transfer.show', $reverseTransfer->id);

        } catch (\Exception $e) {
            ToastMagic::error($e->getMessage());
            return back()->withInput();
        }
    }

    public function show($id): View
    {
        $reverseTransfer = ReverseTransfer::with([
            'order', 'customer', 'admin', 'approvedBy', 'rejectedBy', 'processedBy', 'statusHistory.changedBy'
        ])->findOrFail($id);

        return view('admin-views.reverse-transfer.details', compact('reverseTransfer'));
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $reverseTransfer = ReverseTransfer::findOrFail($id);
            $status = $request->input('status');
            $notes = $request->input('notes');

            // التحقق من صحة الانتقال بين الحالات
            if (!$this->isValidStatusTransition($reverseTransfer->status, $status)) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتقال غير صحيح بين الحالات'
                ], 400);
            }

            // معالجة خاصة للحوالات عبر بوابات الدفع
            if ($status === 'processed' && $reverseTransfer->isGatewayPayment()) {
                $result = $this->reverseTransferService->processGatewayReverseTransfer($reverseTransfer);
                
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['error'] ?? 'فشل في معالجة الحوالة العكسية'
                    ], 400);
                }
            } else {
                // تحديث الحالة العادية
                $this->reverseTransferService->updateReverseTransferStatus($reverseTransfer, $status, $notes);
            }

            return response()->json([
                'success' => true,
                'message' => translate('reverse_transfer_status_updated_successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $reverseTransfer = ReverseTransfer::findOrFail($id);

            if (!$reverseTransfer->canBeDeleted()) {
                ToastMagic::error(translate('cannot_delete_non_pending_transfer'));
                return back();
            }

            $reverseTransfer->delete();

            ToastMagic::success(translate('reverse_transfer_deleted_successfully'));
            return redirect()->route('admin.reverse-transfer.index');

        } catch (\Exception $e) {
            ToastMagic::error($e->getMessage());
            return back();
        }
    }

    public function export(Request $request, $status = null)
    {
        try {
            $filters = [
                'status' => $status,
                'payment_gateway' => $request->get('payment_gateway'),
                'search' => $request->get('search'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $query = $this->reverseTransferService->search($filters);
            $reverseTransfers = $query->get();

            // إنشاء ملف CSV
            $filename = 'reverse_transfers_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($reverseTransfers) {
                $file = fopen('php://output', 'w');
                
                // رأس الجدول
                fputcsv($file, [
                    'ID', 'Order ID', 'Customer', 'Amount', 'Status', 'Payment Gateway',
                    'Original Payment', 'Reason', 'Created At', 'Processed At'
                ]);

                // البيانات
                foreach ($reverseTransfers as $transfer) {
                    fputcsv($file, [
                        $transfer->id,
                        $transfer->order_id,
                        $transfer->customer ? ($transfer->customer->f_name . ' ' . $transfer->customer->l_name) : '',
                        $transfer->amount,
                        $transfer->status_text,
                        $transfer->gateway_display_name,
                        $transfer->original_payment_display_name,
                        $transfer->reason,
                        $transfer->created_at->format('Y-m-d H:i:s'),
                        $transfer->processed_at ? $transfer->processed_at->format('Y-m-d H:i:s') : ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            ToastMagic::error($e->getMessage());
            return back();
        }
    }

    /**
     * التحقق من صحة الانتقال بين الحالات
     */
    private function isValidStatusTransition($currentStatus, $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['approved', 'rejected'],
            'approved' => ['processed', 'rejected'],
            'processed' => ['completed', 'rejected'],
            'completed' => [],
            'rejected' => ['approved'] // يمكن إعادة الموافقة على الحوالة المرفوضة
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * الحصول على إحصائيات الحوالات العكسية
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->reverseTransferService->getStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث المتقدم في الحوالات العكسية
     */
    public function advancedSearch(Request $request): View
    {
        $filters = $request->only([
            'status', 'payment_gateway', 'search', 'date_from', 'date_to',
            'amount_min', 'amount_max', 'original_payment_method'
        ]);

        $query = $this->reverseTransferService->search($filters);
        $reverseTransfers = $query->paginate(getWebConfig('pagination_limit'));

        // خيارات الفلترة
        $statuses = ['pending', 'approved', 'rejected', 'processed', 'completed'];
        $paymentGateways = [
            'myfatoorah' => 'MyFatoorah',
            'tabby' => 'Tabby',
            'tamara' => 'Tamara',
            'bank_transfer' => translate('bank_transfer'),
            'cash' => translate('cash'),
            'check' => translate('check'),
            'other' => translate('other')
        ];

        return view('admin-views.reverse-transfer.advanced-search', compact(
            'reverseTransfers', 'filters', 'statuses', 'paymentGateways'
        ));
    }
}