# 07 · Arquitectura técnica

> Stack: **Laravel 13 (starter kit de React) + Inertia 3 + React/TypeScript + PostgreSQL 18**, PHP 8.5. Equipo: 1 desarrollador + agentes de IA. Canal: web responsiva. Infraestructura: por definir.

## 1. Vista general

```
┌──────────────────────────── Navegador (web responsiva) ────────────────────────────┐
│  React + TypeScript + Tailwind + shadcn/ui (del starter kit)                       │
│  Páginas Inertia por módulo  ·  Componentes compartidos                            │
└───────────────────────────────▲────────────────────────────────────────────────────┘
                                │ Inertia (sin API REST separada en el MVP)
┌───────────────────────────────┴──────────── Laravel (monolito modular) ────────────┐
│  Http (Controllers, FormRequests)  →  Actions (casos de uso, transacciones, RN-xx) │
│  Policies + roles/permisos  ·  Eventos de dominio → Listeners (en cola)            │
│                                                                                    │
│  Módulos: Identity · Verification · Catalog · Properties · Pricing · Rentals ·     │
│           Payments · Inspections · Notifications · Audit · Search · Shared         │
│                                                                                    │
│  Cola (driver database) · Scheduler (expiraciones, vencimientos, transiciones)     │
└──────────┬─────────────────────────────┬──────────────────────────────┬────────────┘
           │                             │                              │
     PostgreSQL                 Almacenamiento de archivos        Correo (SMTP)
 (btree_gist, rangos)          disco público / disco privado     (Mailpit/log en dev)
```

**Sobre "API primero" (D-31):** con Inertia no hace falta una API REST en el MVP. Para conservar esa opción, **toda la lógica vive en Actions** y los controllers solo traducen HTTP ↔ Action. Si mañana hay una app móvil, se agregan controllers de API que llaman a las **mismas Actions**.

## 2. Idioma del código

| Ámbito                                          | Idioma                                                   |
| ----------------------------------------------- | -------------------------------------------------------- |
| Código (clases, tablas, columnas, enums, rutas) | **Inglés**: convenciones de Laravel y del ecosistema     |
| Interfaz de usuario                             | **Español**, mediante archivos de traducción (`lang/es`) |
| Especificación y documentación                  | Español                                                  |

### Glosario de código (ES → EN)

| Especificación            | Código                                                         | Especificación              | Código                                |
| ------------------------- | -------------------------------------------------------------- | --------------------------- | ------------------------------------- |
| Propiedad                 | `Property`                                                     | Renta                       | `Rental`                              |
| Unidad                    | `Unit`                                                         | Cargo                       | `Charge`                              |
| Plan de renta             | `RatePlan`                                                     | Intento de pago             | `PaymentAttempt`                      |
| Bloqueo de disponibilidad | `AvailabilityBlock`                                            | Asiento de ledger           | `LedgerEntry`                         |
| Modalidad de gestión      | `management_mode`: `self_managed` \| `managed`                 | Regla de comisión           | `CommissionRule`                      |
| Regla de género           | `gender_policy`: `mixed` \| `women_only` \| `men_only`         | Inspección                  | `Inspection`                          |
| Comunidades permitidas    | `allowed_communities`                                          | Respuesta de inspección     | `InspectionAnswer`                    |
| Tipo de comunidad         | `community_type`: `faculty` \| `student` \| `exchange_student` | Plantilla de checklist      | `ChecklistTemplate` / `ChecklistItem` |
| Perfil de verificación    | `VerificationProfile` (`kind`: `client` \| `owner`)            | Documento de verificación   | `VerificationDocument`                |
| Institución / Campus      | `Institution` / `Campus`                                       | Amenidad / Regla de la casa | `Amenity` / `HouseRule`               |
| Tipos de unidad           | `entire_place` \| `private_room` \| `shared_room` \| `bed`     | Periodos                    | `weekly` \| `monthly` \| `yearly`     |

Los valores de estados y enums se guardan en inglés y _snake_case_. Sus etiquetas en español salen de `lang/es`.

## 3. Estructura del repositorio

```
app/
  Modules/
    Shared/                  # Money, valores, traits, clases base, excepciones de dominio
    Identity/                # usuarios, roles, suspensión
    Verification/            # perfiles, documentos, vigencias
    Catalog/                 # instituciones, campus, amenidades, reglas de casa, checklists
    Properties/              # propiedades, unidades (árbol), fotos, convivencia
    Pricing/                 # planes de renta, bloqueos, sugerencias de precio
    Rentals/                 # solicitudes, aprobación, disponibilidad, ciclo de vida
    Payments/                # cargos, pasarela, ledger, comisiones
    Inspections/             # asignación, checklist, dictamen, vigencia
    Notifications/           # listeners que traducen eventos → notificaciones
    Audit/                   # bitácora
    Search/                  # catálogo público de solo lectura (D-63)
  Http/ , Models/ ...        # lo que trae el starter kit (auth, settings), sin mover
database/
  migrations/                # centralizadas, con prefijo de módulo en el nombre
  factories/ , seeders/
resources/js/
  pages/<modulo>/...         # páginas Inertia por módulo (kebab-case)
  components/ui/             # shadcn/ui (starter kit)
  components/<modulo>/...    # componentes específicos de un módulo
  types/                     # tipos compartidos
lang/es.json                 # textos de la interfaz (clave = texto en inglés)
lang/es/                     # validación, auth y etiquetas de enums (enums.php)
.ai/guidelines/project.md    # reglas para agentes (Boost las copia a CLAUDE.md)
tests/
  Feature/<Modulo>/          # una prueba por criterio de aceptación de cada HU
  Unit/<Modulo>/
  Arch/                      # reglas de arquitectura (fronteras entre módulos)
docs/spec/                   # esta especificación
```

### Estructura interna de un módulo

```
app/Modules/Rentals/
  Actions/          RequestRental, ApproveRental, RejectRental, ExpireRentals, CancelRental …
  Contracts/        Interfaces públicas para otros módulos (p. ej. AvailabilityChecker)
  Enums/            RentalStatus (incluye transiciones permitidas)
  Events/           RentalRequested, RentalApproved, RentalConfirmed …
  Http/
    Controllers/
    Requests/       Validación de formato (las reglas de negocio van en las Actions)
  Jobs/
  Listeners/
  Models/
  Policies/
  Presenters/       Read-only shapes for the UI, usable by other modules and the dashboard
  routes.php
  RentalsServiceProvider.php   # registra rutas, policies, listeners, bindings y schedule
```

## 4. Reglas de arquitectura

Se verifican con **pruebas de arquitectura de Pest** en `tests/Arch`.

1. **Controllers delgados:** validan (FormRequest), autorizan (Policy), llaman a **una Action** y devuelven una respuesta Inertia o una redirección. Sin lógica de negocio.
2. **Las Actions son el único lugar donde se aplican las RN-xx.** Cada Action es una clase con un método `handle()`, transaccional cuando modifica datos y referenciada a sus RN en el docblock.
3. **Fronteras entre módulos:**
    - Un módulo puede **leer** modelos de otro a través de relaciones Eloquent (es un monolito y hay que ser pragmáticos).
    - Un módulo **nunca escribe** en modelos de otro: llama a sus `Actions/` o `Contracts/`, o reacciona a sus eventos.
4. **Los efectos secundarios van por eventos:** notificaciones, auditoría y otros cambios en módulos ajenos se hacen con listeners en cola que se despachan **después del commit** (`ShouldDispatchAfterCommit` / `afterCommit`). Esto reemplaza al patrón _outbox_ en el MVP.
5. **Estados con enums de PHP:** cada enum de estado declara sus transiciones válidas (`canTransitionTo()`), y las Actions lo validan antes de cambiar el estado.
6. **Dinero:** columnas `bigint` en centavos y el objeto de valor `Shared\Money` (basado en `brick/money`). Nunca `float`.
7. **Autorización:** roles y permisos con `spatie/laravel-permission` (D-32). Las reglas contextuales (por ejemplo, quién es el "operador", RN-60) van en las Policies.

## 5. Decisiones técnicas por tema

| Tema                 | Decisión                                                                                                                                                                                  | Justificación                                                                                                                           |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Autenticación        | La del starter kit (registro, login, verificación de correo, recuperación de contraseña)                                                                                                  | Ya resuelve HU-01.01, HU-01.02 y el paso de correo de HU-02.01                                                                          |
| Roles y permisos     | `spatie/laravel-permission`                                                                                                                                                               | Estándar de facto. Permisos granulares (D-32)                                                                                           |
| Fotos                | `spatie/laravel-medialibrary` en disco público                                                                                                                                            | Orden, portada, conversiones y miniaturas (HU-04.03)                                                                                    |
| Documentos privados  | Disco **privado** + `temporaryUrl()`                                                                                                                                                      | RN-07. Funciona igual en local y en S3                                                                                                  |
| Auditoría            | `spatie/laravel-activitylog`                                                                                                                                                              | RN-90, HU-11.01                                                                                                                         |
| Dinero               | `brick/money` envuelto en `Shared\Money`                                                                                                                                                  | Precisión y redondeo controlado                                                                                                         |
| Cola                 | Driver `database`                                                                                                                                                                         | Sin Redis en el MVP. Se cambia por configuración                                                                                        |
| Tareas programadas   | Laravel Scheduler                                                                                                                                                                         | Expiraciones (RN-61, RN-62), transiciones (RN-65), vencimientos (RN-04, RN-74, RN-85)                                                   |
| Notificaciones       | Laravel Notifications, canales `mail` + `database`                                                                                                                                        | El canal `database` da el centro de notificaciones (HU-10.02) casi sin trabajo extra                                                    |
| Correo en desarrollo | Mailpit (Docker) o driver `log`                                                                                                                                                           |                                                                                                                                         |
| Pasarela de pago     | `Payments\Contracts\PaymentGateway` → `SimulatedGateway`, en el provider                                                                                                                  | D-05. Cambiar a una pasarela real = nueva implementación                                                                                |
| Búsqueda geográfica  | `lat`/`lng` `numeric(9,6)` + filtro por _bounding box_ indexado + fórmula de **haversine**                                                                                                | PostGIS no está disponible localmente y la infra no está definida. Escala bien a miles de propiedades. Se migra a PostGIS si hace falta |
| Pruebas              | Pest (feature, unit y arch)                                                                                                                                                               |                                                                                                                                         |
| Calidad              | Pint (formato), Larastan nivel 7 (análisis estático), `vp check` (lint/formato del frontend) y `tsc`; todo junto con `composer ci:check`                                                  | Son las barreras de seguridad para el trabajo con agentes                                                                               |
| Traducción           | Backend: `__()` + `laravel-lang` (dev) para validación y auth. Frontend: `t()` de `resources/js/lib/i18n.ts`, que lee `lang/{locale}.json` compartido por Inertia una sola vez por idioma | Interfaz en español sin texto fijo; agregar inglés (estudiantes de intercambio) = un archivo nuevo                                      |
| Asistencia a agentes | Laravel Boost: servidor MCP, skills y guías en `CLAUDE.md`                                                                                                                                | Los agentes consultan la documentación de las versiones instaladas                                                                      |
| Desarrollo local     | PostgreSQL 18 de Homebrew + `composer run dev`                                                                                                                                            | Ya instalado. Sail/Docker queda como alternativa                                                                                        |

## 6. Disponibilidad y concurrencia (RN-22 a RN-25)

Es el punto más crítico del sistema. Tiene **dos capas de defensa**.

**Capa 1: Action con bloqueo** (`Rentals\Actions\ApproveRental`, y la misma lógica para bloqueos de disponibilidad):

```
DB::transaction:
  1. Property::lockForUpdate()->find(property_id)    → serializa operaciones de la misma propiedad
  2. Expira en línea las rentas APPROVED con expires_at vencido de esa propiedad
  3. unitIds = unidad + ancestros + descendientes     (profundidad máx. 3)
  4. ¿Existe una renta bloqueante o un AvailabilityBlock en unitIds que se traslape con [start, end)?
       → sí: error de dominio "sin disponibilidad"
  5. Cambia el estado + genera los cargos (RN-70) + dispara el evento después del commit
```

**Capa 2: restricción en PostgreSQL** (última línea de defensa, protege la misma unidad):

```sql
CREATE EXTENSION IF NOT EXISTS btree_gist;
ALTER TABLE rentals ADD CONSTRAINT rentals_no_overlap
  EXCLUDE USING gist (unit_id WITH =, daterange(start_date, end_date, '[)') WITH &&)
  WHERE (status IN ('approved', 'confirmed', 'active'));
```

Los conflictos entre unidades del árbol (padre ↔ hijo) los cubre la capa 1. La restricción garantiza que ni siquiera un error de programación pueda crear dos rentas en la misma unidad.

## 7. Flujo de trabajo con agentes

Como el equipo es una persona más agentes de IA, la consistencia depende de las convenciones escritas y de las pruebas automáticas:

- **`CLAUDE.md` en la raíz:** lo genera Laravel Boost (`php artisan boost:update`) a partir de `.ai/guidelines/project.md` (las reglas del proyecto) y de sus propias guías de Laravel. Las reglas se editan en `.ai/guidelines/`, nunca dentro del bloque generado.
- **Unidad de trabajo = una historia (HU-xx.yy).** Cada tarea indica su HU, y el commit también (`feat(rentals): HU-08.02 aprobar solicitud`).
- **Definición de terminado:**
    1. una prueba feature por cada criterio de aceptación de la HU,
    2. `composer ci:check` en verde (Pint, PHPStan, Pest, `vp check`, `tsc`),
    3. si cambia una regla de negocio, se actualiza `docs/spec/` en el mismo cambio.
- **Seeders de demostración:** usuarios de cada rol, instituciones, propiedades en todos los estados y rentas de ejemplo, para probar cualquier flujo de inmediato.
