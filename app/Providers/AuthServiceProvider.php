<?php

declare(strict_types=1);

namespace App\Providers;

// Models
use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Property;
use App\Models\Purchase;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalUnit;
use App\Models\ReportTemplate;
use App\Models\Sale;
use App\Models\ScheduledReport;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\Vehicle;
use App\Models\WorkCenter;
// Policies
use App\Policies\BranchPolicy;
use App\Policies\ManufacturingPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\RentalPolicy;
use App\Policies\ReportTemplatePolicy;
use App\Policies\SalePolicy;
use App\Policies\ScheduledReportPolicy;
use App\Policies\TicketPolicy;
use App\Policies\VehiclePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        Branch::class => BranchPolicy::class,
        Product::class => ProductPolicy::class,
        Purchase::class => PurchasePolicy::class,
        Sale::class => SalePolicy::class,
        Vehicle::class => VehiclePolicy::class,
        Notification::class => NotificationPolicy::class,
        Ticket::class => TicketPolicy::class,

        // Rental domain mapped to a generic policy handling multiple models
        RentalContract::class => RentalPolicy::class,
        RentalInvoice::class => RentalPolicy::class,
        Property::class => RentalPolicy::class,
        RentalUnit::class => RentalPolicy::class,
        Tenant::class => RentalPolicy::class,

        // Manufacturing domain mapped to a generic policy handling multiple models
        BillOfMaterial::class => ManufacturingPolicy::class,
        ProductionOrder::class => ManufacturingPolicy::class,
        WorkCenter::class => ManufacturingPolicy::class,

        // V58-AUTH-01: Reporting domain policies for scheduled reports and templates
        ScheduledReport::class => ScheduledReportPolicy::class,
        ReportTemplate::class => ReportTemplatePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super Admin shortcut (works with spatie/permission)
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'super-admin'])) {
                return true;
            }

            return null;
        });

        // Ability for impersonation if you use it
        Gate::define('impersonate', function ($user) {
            return (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate.users'))
                || (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Super Admin', 'super-admin']));
        });
    }
}
