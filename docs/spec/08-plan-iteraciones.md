# 08 · Plan de iteraciones

No hay fecha objetivo, así que se avanza por **iteraciones verticales**: cada una deja funcionalidad usable de punta a punta (base de datos → lógica → interfaz → pruebas). Las notificaciones y la auditoría de cada flujo se construyen **dentro** de la iteración que crea ese flujo, no al final.

| #   | Iteración                    | Épicas / historias                                                | Resultado demostrable                                                                                                                                            |
| --- | ---------------------------- | ----------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0   | **Cimientos**                | —                                                                 | Proyecto Laravel + React funcionando sobre PostgreSQL, módulos vacíos, roles y permisos, pruebas de arquitectura, `CLAUDE.md`, seeders base, interfaz en español |
| 1   | **Identidad y verificación** | E01, E02, HU-03.01                                                | Registro de cliente y propietario, verificación por correo y documentos, bandeja de revisión del administrador, vencimientos                                     |
| 2   | **Propiedades y unidades**   | E04, HU-03.02                                                     | Alta de propiedades con árbol de unidades, fotos, amenidades, reglas de la casa y convivencia, gestión del administrador                                         |
| 3   | **Precios y disponibilidad** | E05, HU-03.04 (lectura de comisión para la vista previa del neto) | Planes con precios sugeridos, depósito, bloqueos, vista previa del ingreso neto                                                                                  |
| 4   | **Inspecciones**             | E06, HU-03.03                                                     | Checklist versionado, asignación, ejecución desde el celular, dictamen, publicación con todos los requisitos (RN-10)                                             |
| 5   | **Búsqueda**                 | E07                                                               | Catálogo público con filtros, mapa aproximado y detalle con calendario                                                                                           |
| 6   | **Rentas**                   | E08                                                               | Solicitud, aprobación con control de concurrencia, expiraciones, ciclo automático, cancelación administrativa                                                    |
| 7   | **Pagos simulados**          | E09, HU-03.04 (gestión)                                           | Calendario de cargos, pago de prueba, ledger, saldo del propietario, panel financiero                                                                            |
| 8   | **Cierre del MVP**           | E10 (resto), E11, HU-01.04, HU-03.05                              | Centro de notificaciones, bitácora consultable, parámetros configurables, revisión general de seguridad                                                          |

## Iteración 0, en detalle — ✅ completada (2026-11-18)

1. `git init` + crear el proyecto con el **starter kit de React** de Laravel (TypeScript, Inertia, Tailwind, shadcn/ui).
2. Configurar PostgreSQL (`pgsql`), habilitar `btree_gist`, zona horaria UTC y locale `es`.
3. Instalar `spatie/laravel-permission`, `spatie/laravel-medialibrary`, `spatie/laravel-activitylog`, `brick/money`, `larastan/larastan` y **Pest** (si el starter kit no lo trae).
4. Crear la estructura `app/Modules/*` con un ServiceProvider por módulo y el módulo `Shared` (Money y clases base de Action y de excepción de dominio).
5. Escribir las pruebas de arquitectura (`tests/Arch`) que hacen cumplir las reglas de la sección 4 de [07-arquitectura](07-arquitectura.md).
6. Seeder de roles y permisos ([02](02-roles-permisos.md)) + un usuario por rol para desarrollo.
7. Traducción base al español (`lang/es`), incluidas las pantallas de autenticación del starter kit.
8. `CLAUDE.md` generado por Laravel Boost a partir de `.ai/guidelines/project.md` (convenciones, comandos y definición de terminado).
9. Verificación completa con `composer ci:check` (ya la trae el starter kit): Pint, PHPStan nivel 7, Pest, `vp check` y `tsc`.

## Iteración 1 — ✅ completada (2026-11-19)

Historias: HU-01.01 a HU-01.04, HU-02.01 a HU-02.05 y HU-03.01 (también HU-01.04, que era _Should_).

- **Identity:** registro como cliente o propietario con fecha de nacimiento; agregar el otro perfil; roles internos (solo Super Admin); suspensión con motivo, cierre de sesiones y bloqueo de inicio de sesión.
- **Verification:** perfil por tipo, asociación automática de institución por dominio de correo (incluye subdominios), envío de documentos privados, bandeja de revisión (más antiguas primero), aprobación con vigencia según RN-04, rechazo con motivo, avisos y vencimiento diarios.
- **Catalog:** instituciones con dominios de correo únicos y campus con coordenadas.
- **Notifications / Audit:** correo + notificación en la base de datos para cada decisión; bitácora de verificaciones, roles y suspensiones.
- Pendiente de interfaz: el centro de notificaciones (HU-10.02, iteración 8) — las notificaciones ya se guardan.

## Iteración 2 — ✅ completada (2026-11-19)

Historias: HU-03.02 y HU-04.01 a HU-04.09.

- **Catalog:** amenidades (categoría y ámbito: áreas comunes, espacio o ambos) y reglas de la casa, con un catálogo inicial que se siembra en todos los entornos.
- **Properties:** alta (también a nombre de un propietario, RN-15), datos generales, dirección con pin en mapa, árbol de espacios con plantillas iniciales y validación de jerarquía (RN-20), convivencia que solo puede restringirse (RN-30, RN-31), amenidades por nivel, reglas de la casa, hasta 30 fotos con miniaturas WEBP, comprobante de propiedad privado (D-49), solicitud de inspección (RN-06), publicación con lista de requisitos (RN-10), pausa y archivado (RN-13).
- **Cambios críticos (RN-11):** cambiar la ubicación o agregar, quitar o cambiar el tipo o la capacidad de un espacio regresa a inspección una propiedad ya inspeccionada, con aviso previo en la interfaz.
- **Dependencias con iteraciones futuras:** publicar exige un plan de precio activo (contrato `Pricing\Contracts\RatePlanCatalog`, que en la Iteración 3 deja de responder "no") y una inspección aprobada (Iteración 4). Archivar y quitar espacios revisarán rentas bloqueantes en la Iteración 6.

## Iteración 3 — ✅ completada (2026-11-20)

Historias: HU-05.01 a HU-05.03 y HU-03.04 (lectura de la comisión).

- **Pricing:** planes semanal, mensual y anual por espacio rentable con precios sugeridos (RN-41, factores en `config/pricing.php`), mínimo y máximo de periodos (RN-43), un plan por periodo (RN-40, índice único), depósito (RN-44) y bloqueos de fechas por familia de espacios (RN-22, RN-26).
- **Payments:** reglas de comisión con vigencia (10 % / 20 %, RN-50) y el contrato `CommissionRates`; la gestión desde la interfaz llega en la Iteración 7.
- **Publicación:** `RatePlanCatalog` ya responde con los planes reales; solo falta la inspección aprobada (Iteración 4) para poder publicar.
- **Interfaz:** sección "Precios y disponibilidad" en el editor, con lo que recibe el propietario después de la comisión, sugerencias de un clic y bloqueos.
- **Pendiente para la Iteración 6:** los bloqueos y los cambios de espacios revisarán rentas bloqueantes.

## Iteración 4 — ✅ completada (2026-11-20)

Historias: HU-03.03 y HU-06.01 a HU-06.06.

- **Checklist versionado** (super administrador): la versión vigente es de solo lectura; los cambios se hacen en un borrador que se publica como versión nueva (RN-82). Checklist inicial de 14 ítems de seguridad y fidelidad, 8 críticos.
- **Ciclo:** la solicitud de inspección (o un cambio crítico) abre la inspección; el administrador asigna inspector y fecha (RN-81); el inspector la ejecuta en el celular, ítem por ítem, con fotos (RN-84, RN-87); el dictamen actualiza la propiedad: aprobada con vigencia de 12 meses o rechazada (RN-83).
- **El propietario** ve en su editor la fecha, el inspector y, al terminar, el dictamen, las observaciones y lo que no cumplió con su evidencia.
- **Diario:** renovación 30 días antes y pausa al vencer (RN-85).
- **Notificaciones** (correo + base de datos) y **auditoría** de asignaciones, dictámenes y versiones del checklist.
- **Ya se puede publicar** de punta a punta: verificación → comprobante → precios → inspección aprobada → 3 fotos → publicar.

## Iteración 5 — ✅ completada (2026-11-20)

Historias: HU-07.01 y HU-07.02.

- **Catálogo público en `/`:** campus y distancia, periodo, fecha de entrada, tipo de espacio, precio, amenidades, convivencia y orden; los filtros viven en la URL. Resultados como tarjetas con foto ("desde", espacios, distancia) y mapa con precios en ubicaciones aproximadas.
- Solo aparecen espacios rentables con plan activo de propiedades publicadas con inspección vigente; a un cliente verificado se le ocultan los espacios que no admiten su comunidad (RN-31).
- **Ficha `/stays/{id}`:** galería, insignia de inspección, espacios con precios y depósito, amenidades, reglas, mapa aproximado, fechas no disponibles y cotización (total, depósito, fecha de salida).
- **Vista previa** para el propietario desde su editor ("Ver ficha").
- El botón "Solicitar renta" queda listo y deshabilitado hasta la Iteración 6.

## Iteración 6 — ✅ completada (2026-11-23)

Historias: HU-08.01 a HU-08.07.

- **Solicitud** desde la ficha: desglose, calendario de cargos, reglas de la casa que se aceptan, mensaje opcional y género de convivencia solo si el espacio lo exige (RN-32, privado). Valida verificación, convivencia, periodos, fechas, disponibilidad y traslapes propios; copia los valores comerciales (RN-45) y da 72 h al operador.
- **Aprobación con concurrencia** (RN-24, RN-25): bloqueo de la propiedad, expiración en línea de aprobaciones vencidas, revalidación de la familia del espacio y del cliente, calendario de cargos (RN-70, D-70) y restricción de exclusión en PostgreSQL. Rechazo con motivo (RN-67).
- **Disponibilidad:** el contrato `UnitAvailability` ya suma las rentas bloqueantes (D-66); el catálogo, la ficha y la cotización no cambiaron. Los bloqueos de fechas (RN-26), archivar (RN-13) y quitar espacios (D-69) revisan las rentas.
- **Tareas programadas:** expiraciones cada 15 minutos (RN-61, RN-62) y ciclo diario Confirmada → Activa → Finalizada (RN-65).
- **Pantallas:** "Mis rentas" (el cliente ve la dirección exacta y el contacto al confirmarse), "Rentas" para propietarios y administradores (solo lectura en administradas) y el detalle con aprobar, rechazar y cancelar (solo administradores, RN-66).
- **Notificaciones** (correo + base de datos) y **auditoría** de cada cambio de estado.
- **Pendiente para la Iteración 7:** pagar el primer cargo llama a `ConfirmRental` (RN-71), que ya rechaza automáticamente las solicitudes en conflicto (RN-24). Mientras tanto, una renta aprobada expira a las 48 h.

## Iteración 7 — ✅ completada (2026-11-24)

Historias: HU-09.01 a HU-09.05 y HU-03.04 (gestión).

- **Pago de prueba** desde el detalle de la renta: el cliente paga el cargo que toca (en orden, D-75) en un diálogo que dice claramente "Pago de prueba". Pagar el primer cargo confirma la renta (RN-71) y rechaza las solicitudes en conflicto (RN-24). Un pago rechazado se puede reintentar.
- **Pasarela** detrás de `Payments\Contracts\PaymentGateway` (D-05) con `SimulatedGateway`: aprueba por defecto; el Super Admin la configura para rechazar o responder con demora (RN-72, D-76, D-77).
- **Ledger de partida doble** (RN-73): por cada cargo pagado, cliente → comisión de la plataforma + por pagar al propietario + depósitos en garantía; inmutable en PostgreSQL (RN-75, D-78).
- **Cargos vencidos** (RN-74): tarea diaria a las 00:10; aviso al cliente y al operador (D-80).
- **Pantallas:** "Ingresos" del propietario (cobrado, comisión, neto, por cobrar y depósitos, por renta), "Finanzas" para administradores (comisiones, saldos por pagar a propietarios, depósitos, cargos vencidos y últimos pagos), "Comisiones" (tarifas con vigencia, D-79) y "Pagos de prueba" para el Super Admin.
- **Notificaciones** (recibo, pago rechazado, cargo vencido) y **auditoría** de pagos, comisiones y escenarios.
- **Fuera del MVP:** reembolsos (B-01), pasarela real (B-02), pagos a propietarios (B-03) y penalizaciones (B-11).
