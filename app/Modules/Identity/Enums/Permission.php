<?php

namespace App\Modules\Identity\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * Granular permissions (D-32). The role matrix lives in Role::permissions()
 * and is documented in docs/spec/02-roles-permisos.md.
 *
 * Permissions answer "may this role do X at all?". Contextual rules such as
 * "only the operator of this property" (RN-60) belong in Policies.
 */
enum Permission: string
{
    use HasLabel;

    // Verification
    case VerificationDocumentsUpload = 'verification.documents.upload';
    case VerificationDocumentsReview = 'verification.documents.review';

    // Properties
    case PropertiesCreate = 'properties.create';
    case PropertiesUpdate = 'properties.update';
    case PropertiesArchive = 'properties.archive';
    case PropertiesPublish = 'properties.publish';
    case PropertiesViewAny = 'properties.view_any';

    // Pricing & availability
    case RatePlansManage = 'pricing.rate_plans.manage';
    case AvailabilityBlocksManage = 'pricing.availability_blocks.manage';

    // Inspections
    case InspectionsRequest = 'inspections.request';
    case InspectionsAssign = 'inspections.assign';
    case InspectionsPerform = 'inspections.perform';
    case InspectionsView = 'inspections.view';

    // Rentals
    case RentalsRequest = 'rentals.request';
    case RentalsView = 'rentals.view';
    case RentalsDecide = 'rentals.decide';
    case RentalsCancel = 'rentals.cancel';

    // Payments
    case ChargesPay = 'payments.charges.pay';
    case BalancesView = 'payments.balances.view';
    case PaymentSimulationConfigure = 'payments.simulation.configure';
    case CommissionRulesManage = 'payments.commission_rules.manage';

    // Platform
    case CatalogManage = 'catalog.manage';
    case UsersRolesAssign = 'users.roles.assign';
    case UsersSuspend = 'users.suspend';
    case AuditView = 'audit.view';
}
