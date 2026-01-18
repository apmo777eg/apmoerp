<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Customer Portal Controller
 *
 * Allows customers to view their invoices, orders, and account information.
 */
class CustomerPortalController extends Controller
{
    /**
     * Show portal login page
     */
    public function login()
    {
        return view('portal.login');
    }

    /**
     * Authenticate customer
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (! $customer || ! Hash::check($request->password, $customer->portal_password)) {
            return back()->withErrors([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        if (! $customer->portal_enabled) {
            return back()->withErrors([
                'email' => __('Portal access is not enabled for this account. Please contact support.'),
            ]);
        }

        session(['customer_portal' => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
        ]]);

        return redirect()->route('portal.dashboard');
    }

    /**
     * Show portal dashboard
     */
    public function dashboard()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $recentOrders = Sale::where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            'total_orders' => Sale::where('customer_id', $customer->id)->count(),
            // V35-MED-06 FIX: Exclude all non-revenue statuses
            'total_spent' => Sale::where('customer_id', $customer->id)
                ->whereNotIn('status', SaleStatus::nonRevenueStatuses())
                ->sum('total_amount'),
            'pending_orders' => Sale::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->count(),
            'loyalty_points' => $customer->loyalty_points ?? 0,
        ];

        return view('portal.dashboard', compact('customer', 'recentOrders', 'stats'));
    }

    /**
     * Show customer orders/invoices
     */
    public function orders(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $query = Sale::where('customer_id', $customer->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('reference_number', 'like', '%'.$request->search.'%');
        }

        $orders = $query->paginate(15);

        return view('portal.orders', compact('customer', 'orders'));
    }

    /**
     * Show order details
     */
    public function orderDetails(int $orderId)
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $order = Sale::where('customer_id', $customer->id)
            ->where('id', $orderId)
            ->with(['items.product', 'payments'])
            ->firstOrFail();

        return view('portal.order-details', compact('customer', 'order'));
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(int $orderId)
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $order = Sale::where('customer_id', $customer->id)
            ->where('id', $orderId)
            ->firstOrFail();

        // Generate PDF using existing print service
        $printService = app(\App\Services\Print\PrintService::class);
        $html = $printService->renderInvoice([
            'invoice_number' => $order->code,
            'invoice_date' => $order->created_at->format('Y-m-d'),
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
            ],
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product?->name ?? __('Item'),
                'qty' => $item->qty,
                'price' => $item->unit_price,
                'total' => $item->line_total,
            ])->toArray(),
            'subtotal' => $order->sub_total,
            'tax' => $order->tax_total,
            'discount' => $order->discount_total,
            'total' => $order->grand_total,
            'status' => $order->status,
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Show customer profile
     */
    public function profile()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        return view('portal.profile', compact('customer'));
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return back()->with('success', __('Profile updated successfully.'));
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $customer->portal_password)) {
            return back()->withErrors(['current_password' => __('Current password is incorrect.')]);
        }

        $customer->update([
            'portal_password' => Hash::make($request->password),
        ]);

        return back()->with('success', __('Password changed successfully.'));
    }

    /**
     * Show loyalty points
     */
    public function loyaltyPoints()
    {
        $customer = $this->getAuthenticatedCustomer();
        if (! $customer) {
            return redirect()->route('portal.login');
        }

        $transactions = \App\Models\LoyaltyTransaction::where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('portal.loyalty', compact('customer', 'transactions'));
    }

    /**
     * Logout from portal
     */
    public function logout()
    {
        session()->forget('customer_portal');

        return redirect()->route('portal.login');
    }

    /**
     * Get authenticated customer
     */
    protected function getAuthenticatedCustomer(): ?Customer
    {
        $session = session('customer_portal');
        if (! $session || ! isset($session['id'])) {
            return null;
        }

        return Customer::find($session['id']);
    }
}
