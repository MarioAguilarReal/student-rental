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

];
