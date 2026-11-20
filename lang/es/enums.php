<?php

/*
|--------------------------------------------------------------------------
| Enum labels
|--------------------------------------------------------------------------
|
| Read by App\Modules\Shared\Enums\Concerns\HasLabel:
| 'EnumClassBasename' => ['case_value' => 'Label'].
|
*/

return [

    'Role' => [
        'super_admin' => 'Super administrador',
        'admin' => 'Administrador',
        'inspector' => 'Inspector',
        'owner' => 'Propietario',
        'client' => 'Cliente',
    ],

    'Permission' => [
        'verification.documents.upload' => 'Subir documentos de verificación',
        'verification.documents.review' => 'Revisar documentos de verificación',
        'properties.create' => 'Crear propiedades',
        'properties.update' => 'Editar propiedades',
        'properties.archive' => 'Archivar propiedades',
        'properties.publish' => 'Publicar y pausar propiedades',
        'properties.view_any' => 'Ver todas las propiedades',
        'pricing.rate_plans.manage' => 'Gestionar planes de renta',
        'pricing.availability_blocks.manage' => 'Gestionar bloqueos de disponibilidad',
        'inspections.request' => 'Solicitar inspecciones',
        'inspections.assign' => 'Asignar inspecciones',
        'inspections.perform' => 'Realizar inspecciones',
        'inspections.view' => 'Ver inspecciones',
        'rentals.request' => 'Solicitar rentas',
        'rentals.view' => 'Ver rentas',
        'rentals.decide' => 'Aprobar o rechazar rentas',
        'rentals.cancel' => 'Cancelar rentas',
        'payments.charges.pay' => 'Pagar cargos',
        'payments.balances.view' => 'Ver saldos',
        'payments.simulation.configure' => 'Configurar simulación de pagos',
        'payments.commission_rules.manage' => 'Gestionar reglas de comisión',
        'catalog.manage' => 'Gestionar catálogos',
        'users.roles.assign' => 'Asignar roles',
        'users.suspend' => 'Suspender usuarios',
        'audit.view' => 'Ver bitácora',
    ],

    'VerificationKind' => [
        'client' => 'cliente',
        'owner' => 'propietario',
    ],

    'VerificationStatus' => [
        'unverified' => 'Correo sin confirmar',
        'email_verified' => 'Pendiente de documentos',
        'in_review' => 'En revisión',
        'verified' => 'Verificado',
        'rejected' => 'Rechazado',
        'expired' => 'Vencido',
    ],

    'CommunityType' => [
        'faculty' => 'Docente',
        'student' => 'Estudiante',
        'exchange_student' => 'Estudiante de intercambio',
    ],

    'DocumentType' => [
        'credential' => 'Credencial institucional',
        'acceptance_letter' => 'Carta de aceptación',
        'official_id' => 'Identificación oficial',
    ],

    'DocumentStatus' => [
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
    ],

    'AmenityScope' => ['property' => 'Áreas comunes', 'unit' => 'Espacio individual', 'both' => 'Ambos'],

    'AmenityCategory' => [
        'basics' => 'Básicos', 'kitchen' => 'Cocina', 'bathroom' => 'Baño', 'laundry' => 'Lavandería',
        'study' => 'Estudio', 'comfort' => 'Confort', 'safety' => 'Seguridad', 'outdoor' => 'Exterior',
    ],

    'ManagementMode' => ['self_managed' => 'Autogestionada', 'managed' => 'Administrada'],

    'PropertyType' => ['house' => 'Casa', 'apartment' => 'Departamento', 'room_in_house' => 'Cuarto en casa', 'other' => 'Otro'],

    'PropertyStatus' => [
        'draft' => 'Borrador', 'pending_inspection' => 'Pendiente de inspección', 'approved' => 'Aprobada',
        'rejected' => 'Rechazada', 'published' => 'Publicada', 'paused' => 'Pausada', 'archived' => 'Archivada',
    ],

    'PauseReason' => ['manual' => 'Pausa manual', 'inspection_expired' => 'Inspección vencida'],

    'UnitType' => [
        'entire_place' => 'Lugar completo', 'private_room' => 'Habitación privada',
        'shared_room' => 'Habitación compartida', 'bed' => 'Cama',
    ],

    'UnitStatus' => ['active' => 'Activo', 'paused' => 'Pausado'],

    'GenderPolicy' => ['mixed' => 'Mixto', 'women_only' => 'Solo mujeres', 'men_only' => 'Solo hombres'],

    'UnitTemplate' => ['entire_place' => 'Completa', 'by_rooms' => 'Por habitaciones', 'mixed' => 'Completa y por habitaciones'],

    'RatePeriod' => ['weekly' => 'Semanal', 'monthly' => 'Mensual', 'yearly' => 'Anual'],

    'BlockReason' => ['maintenance' => 'Mantenimiento', 'owner_use' => 'Uso del propietario', 'other' => 'Otro'],

    'ChecklistStatus' => ['draft' => 'Borrador', 'published' => 'Publicada', 'retired' => 'Retirada'],

    'ChecklistCategory' => [
        'electrical' => 'Eléctrico', 'gas' => 'Gas', 'fire_safety' => 'Incendio', 'security' => 'Seguridad',
        'structure' => 'Estructura', 'hygiene' => 'Higiene', 'listing' => 'Publicación',
    ],

    'ItemScope' => ['property' => 'Áreas comunes', 'unit' => 'Cada espacio rentable'],

    'InspectionType' => ['initial' => 'Inspección inicial', 'change' => 'Por cambio', 'renewal' => 'Renovación'],

    'InspectionStatus' => [
        'pending_assignment' => 'Por asignar', 'assigned' => 'Asignada', 'in_progress' => 'En curso',
        'completed' => 'Terminada', 'cancelled' => 'Cancelada',
    ],

    'InspectionResult' => ['approved' => 'Aprobada', 'approved_with_observations' => 'Aprobada con observaciones', 'rejected' => 'Rechazada'],

    'AnswerResult' => ['compliant' => 'Cumple', 'non_compliant' => 'No cumple', 'not_applicable' => 'No aplica'],

    'RentalStatus' => [
        'requested' => 'Solicitada', 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'expired' => 'Expirada',
        'confirmed' => 'Confirmada', 'active' => 'Activa', 'completed' => 'Finalizada', 'cancelled' => 'Cancelada',
    ],

    'ChargeStatus' => ['pending' => 'Pendiente', 'paid' => 'Pagado', 'failed' => 'Fallido', 'overdue' => 'Vencido', 'cancelled' => 'Cancelado'],

    'ChargeConcept' => ['rent' => 'Renta', 'rent_and_deposit' => 'Renta y depósito'],

    'CohabitationGender' => ['female' => 'Mujer', 'male' => 'Hombre'],

];
