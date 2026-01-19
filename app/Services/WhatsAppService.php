<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\Sms\SmsManager;
use App\Traits\HandlesServiceErrors;

/**
 * WhatsAppService - WhatsApp messaging integration
 *
 * STATUS: ACTIVE - Production-ready WhatsApp service
 * PURPOSE: Send WhatsApp messages for invoices, payment reminders, and loyalty notifications
 * FEATURES:
 *   - Invoice delivery via WhatsApp
 *   - Payment reminders
 *   - Loyalty points notifications
 *   - Custom messages
 * INTEGRATION: Uses SmsManager for WhatsApp API connectivity
 * USAGE: Called by sales/customer/loyalty services for automated messaging
 *
 * This service is fully implemented and provides WhatsApp messaging functionality
 * for customer communication and engagement.
 */
class WhatsAppService
{
    use HandlesServiceErrors;

    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    public function sendInvoice(Sale $sale, ?string $customMessage = null): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($sale, $customMessage) {
                $customer = $sale->customer;

                if (! $customer || ! $customer->phone) {
                    return false;
                }

                $message = $customMessage ?? $this->buildInvoiceMessage($sale);

                return $this->smsManager->sendWhatsApp($customer->phone, $message);
            },
            operation: 'sendInvoice',
            context: ['sale_id' => $sale->id],
            defaultValue: false
        );
    }

    public function sendPaymentReminder(Customer $customer, float $amount, string $dueDate): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $amount, $dueDate) {
                if (! $customer->phone) {
                    return false;
                }

                $message = __("Dear :name,\n\nThis is a friendly reminder that you have a payment of :amount due on :date.\n\nPlease contact us if you have any questions.\n\nThank you!", [
                    'name' => $customer->name,
                    'amount' => number_format($amount, 2),
                    'date' => $dueDate,
                ]);

                return $this->smsManager->sendWhatsApp($customer->phone, $message);
            },
            operation: 'sendPaymentReminder',
            context: ['customer_id' => $customer->id, 'amount' => $amount, 'due_date' => $dueDate],
            defaultValue: false
        );
    }

    public function sendLoyaltyPointsNotification(Customer $customer, int $points, int $totalPoints): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($customer, $points, $totalPoints) {
                if (! $customer->phone) {
                    return false;
                }

                $message = __("Congratulations :name!\n\nYou've earned :points loyalty points!\n\nYour total points: :total\n\nThank you for shopping with us!", [
                    'name' => $customer->name,
                    'points' => $points,
                    'total' => $totalPoints,
                ]);

                return $this->smsManager->sendWhatsApp($customer->phone, $message);
            },
            operation: 'sendLoyaltyPointsNotification',
            context: ['customer_id' => $customer->id, 'points' => $points, 'total_points' => $totalPoints],
            defaultValue: false
        );
    }

    protected function buildInvoiceMessage(Sale $sale): string
    {
        $items = $sale->items->map(function ($item) {
            return "• {$item->product->name} x{$item->qty} = ".number_format(decimal_float($item->line_total), 2);
        })->join("\n");

        // V37-LOW-01 FIX: Use sale_date (business date) instead of created_at for accurate invoice date
        // For backdated sales or offline sync, created_at may not reflect the actual sale date
        $saleDate = $sale->sale_date ?? $sale->created_at;

        return __("Invoice #:invoice\n\nDear :customer,\n\nThank you for your purchase!\n\n:items\n\nSubtotal: :subtotal\nTax: :tax\nDiscount: :discount\n\nTotal: :total\n\nDate: :date\n\nThank you for shopping with us!", [
            'invoice' => $sale->code,
            'customer' => $sale->customer?->name ?? __('Customer'),
            'items' => $items,
            'subtotal' => number_format(decimal_float($sale->sub_total), 2),
            'tax' => number_format(decimal_float($sale->tax_total), 2),
            'discount' => number_format(decimal_float($sale->discount_total), 2),
            'total' => number_format(decimal_float($sale->grand_total), 2),
            'date' => $saleDate->format('Y-m-d H:i'),
        ]);
    }

    public function sendCustomMessage(string $phone, string $message): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => $this->smsManager->sendWhatsApp($phone, $message),
            operation: 'sendCustomMessage',
            context: ['phone' => $phone],
            defaultValue: false
        );
    }
}
