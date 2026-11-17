<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\AvailabilityDay;
use App\Models\CatalogItem;
use App\Models\ClientProfile;
use App\Models\CompanyBankAccount;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\Invoice;
use App\Models\Media;
use App\Models\OrderItem;
use App\Models\OrderObjection;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\PriceChangeRequest;
use App\Models\ProviderBankAccount;
use App\Models\QuoteDecision;
use App\Models\QuoteProposal;
use App\Models\QuoteRequest;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\Venue;
use App\Models\VenueFacility;
use App\Models\VenueProfile;
use App\Models\VenueRoom;
use App\Policies\CatalogItemPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ProviderProfilePolicy;
use App\Policies\VenuePolicy;
use App\Policies\VenueRoomPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // BE-2: alias morph pendek sesuai rancangan (subject_type catalog_item,
        // venue_room, dst). enforceMorphMap menolak morph di luar peta ini.
        Relation::enforceMorphMap([
            'user' => User::class,
            'client_profile' => ClientProfile::class,
            'vendor_profile' => VendorProfile::class,
            'venue_profile' => VenueProfile::class,
            'provider_bank_account' => ProviderBankAccount::class,
            'company_bank_account' => CompanyBankAccount::class,
            'catalog_item' => CatalogItem::class,
            'venue' => Venue::class,
            'venue_room' => VenueRoom::class,
            'venue_facility' => VenueFacility::class,
            'media' => Media::class,
            'price_change_request' => PriceChangeRequest::class,
            'availability_day' => AvailabilityDay::class,
            'event' => Event::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'refund_request' => RefundRequest::class,
            'payout' => Payout::class,
            'event_review' => EventReview::class,
            'quote_request' => QuoteRequest::class,
            'quote_proposal' => QuoteProposal::class,
            'quote_decision' => QuoteDecision::class,
            'order_item' => OrderItem::class,
            'order_objection' => OrderObjection::class,
            'audit_log' => AuditLog::class,
        ]);

        // BE-3: policy eksplisit (tanpa auto-discovery) untuk katalog,
        // venue, ruangan, media, dan verifikasi penyedia.
        Gate::policy(CatalogItem::class, CatalogItemPolicy::class);
        Gate::policy(Venue::class, VenuePolicy::class);
        Gate::policy(VenueRoom::class, VenueRoomPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(VendorProfile::class, ProviderProfilePolicy::class);
        Gate::policy(VenueProfile::class, ProviderProfilePolicy::class);
    }
}
