<?php

namespace App\Modules\Identity\Enums;

use App\Modules\Shared\Enums\Concerns\HasLabel;

/**
 * Platform roles (docs/spec/02-roles-permisos.md). A user may hold several roles.
 */
enum Role: string
{
    use HasLabel;

    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Inspector = 'inspector';
    case Owner = 'owner';
    case Client = 'client';

    /**
     * Roles a visitor can pick when signing up (D-10). Internal roles are assigned by a Super Admin.
     *
     * @return list<self>
     */
    public static function selfAssignable(): array
    {
        return [self::Client, self::Owner];
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::Admin => [
                Permission::VerificationDocumentsReview,
                Permission::PropertiesCreate,
                Permission::PropertiesUpdate,
                Permission::PropertiesArchive,
                Permission::PropertiesPublish,
                Permission::PropertiesViewAny,
                Permission::RatePlansManage,
                Permission::AvailabilityBlocksManage,
                Permission::InspectionsRequest,
                Permission::InspectionsAssign,
                Permission::InspectionsView,
                Permission::RentalsView,
                Permission::RentalsDecide,
                Permission::RentalsCancel,
                Permission::BalancesView,
                Permission::UsersSuspend,
                Permission::AuditView,
            ],
            self::Inspector => [
                Permission::InspectionsPerform,
                Permission::InspectionsView,
            ],
            self::Owner => [
                Permission::VerificationDocumentsUpload,
                Permission::PropertiesCreate,
                Permission::PropertiesUpdate,
                Permission::PropertiesArchive,
                Permission::PropertiesPublish,
                Permission::RatePlansManage,
                Permission::AvailabilityBlocksManage,
                Permission::InspectionsRequest,
                Permission::InspectionsView,
                Permission::RentalsView,
                Permission::RentalsDecide,
                Permission::BalancesView,
            ],
            self::Client => [
                Permission::VerificationDocumentsUpload,
                Permission::RentalsRequest,
                Permission::RentalsView,
                Permission::ChargesPay,
            ],
        };
    }
}
