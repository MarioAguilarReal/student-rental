# Reglas del proyecto (Student Rental)

Plataforma de renta tipo Airbnb para docentes, estudiantes y estudiantes de intercambio, con inspección obligatoria de propiedades.

## Fuente de verdad: `docs/spec/`

- Antes de implementar, lee la historia (`05-historias-usuario.md`), las reglas que cita (`03-reglas-negocio.md`), el modelo (`04-modelo-dominio.md`) y la arquitectura (`07-arquitectura.md`).
- Si una tarea contradice la especificación, **pregunta** antes de implementar. Si una regla cambia, actualiza `docs/spec/` en el mismo cambio.
- Unidad de trabajo = una historia `HU-xx.yy`. Menciónala en el commit (`feat(rentals): HU-08.02 …`) y cita las `RN-xx` en el docblock de cada Action.

## Estructura modular

- `app/Modules/<Module>/` es una carpeta base aprobada. Módulos: Shared, Identity, Verification, Catalog, Properties, Pricing, Rentals, Payments, Inspections, Notifications, Audit, Search (catálogo de solo lectura, D-63).
- Dentro de un módulo: `Actions/`, `Contracts/`, `Enums/`, `Events/`, `Http/Controllers/`, `Http/Requests/`, `Jobs/`, `Listeners/`, `Models/`, `Policies/`, `routes.php` y `<Module>ServiceProvider.php` (extiende `App\Modules\Shared\Providers\ModuleServiceProvider`; ahí se registran policies, listeners y tareas programadas).
- Lo que trae el starter kit (`app/Models/User.php`, `app/Http/Controllers/Settings`, Fortify) se queda donde está.
- Migraciones, factories y seeders siguen en `database/`.

## Reglas de arquitectura (las hacen cumplir `tests/Arch`)

- **Controllers delgados:** FormRequest (formato) → Policy (autorización) → una Action → respuesta Inertia. Sin lógica de negocio ni `DB`.
- **Las reglas de negocio viven solo en Actions:** clase con `handle()`, transaccional si escribe y sin conocer HTTP.
- **Violación de una regla:** `throw new BusinessRuleViolation('RN-xx', __('Mensaje'), 'campo')`, que se muestra como error de validación.
- **Entre módulos:** leer modelos ajenos por relaciones está permitido; **escribir** solo mediante sus Actions o Contracts, o reaccionando a sus eventos. `Http`, `Jobs`, `Listeners` y `Policies` son internos de cada módulo.
- **Efectos secundarios** (notificaciones, auditoría) mediante eventos con listeners en cola que se despachan después del commit.
- **Estados:** enums con `HasTransitions` (`assertCanTransitionTo()` antes de cambiar el estado) y `HasLabel`.
- **Dinero:** `App\Modules\Shared\Money`, guardado como `bigint` en centavos más una columna de moneda. Porcentajes en puntos base (1000 = 10 %). Nunca `float`.
- **Permisos:** enums `App\Modules\Identity\Enums\{Role, Permission}`. El seeder los sincroniza solo. Las reglas contextuales (por ejemplo, "operador de la propiedad") van en Policies. Si cambia la matriz, actualiza `docs/spec/02-roles-permisos.md`.

## Idioma

- **Código en inglés** (clases, tablas, columnas, enums, rutas). Usa el glosario de `docs/spec/07-arquitectura.md` §2.
- **Interfaz en español.** Nunca escribas texto visible fijo en el código:
    - Backend: `__('English source text')`.
    - Frontend: `const { t } = useTranslation();` de `@/lib/i18n` y luego `t('English source text')`.
    - La traducción va en `lang/es.json`; las etiquetas de enums, en `lang/es/enums.php`.

## Interfaz (sistema de diseño)

- Sigue `docs/spec/09-sistema-diseno.md`. Toda pantalla usa `Page` (título 22 px, una oración, acción principal a la derecha) y los componentes del sistema: `ListingCard`, `FilterChips`, `PersonAvatar`, `ProgressSteps`, `EmptyState`, `FileField`, `FormActions`, `Badge` con tonos.
- Colores solo desde los tokens de `resources/css/app.css` (`primary` negro para el botón principal; `brand` solo en logotipo, selección y dato principal). Nunca hex sueltos en componentes, salvo los tintes de identidad de `PersonAvatar`.
- Sin degradados, sombras en reposo, íconos en círculos de color, mayúsculas espaciadas ni emojis. Radios: controles `rounded-lg`, tarjetas y fotos `rounded-xl`, píldoras `rounded-full`.
- Formularios largos por secciones (patrón del editor de propiedades) con un "Guardar" por sección en `FormActions`. Listas vacías con `EmptyState`.

## Base de datos

- PostgreSQL también en las pruebas (`student_rental_testing`). Está disponible `btree_gist` para restricciones de exclusión.
- No uses dependencias atadas a infraestructura (Redis, PostGIS, servicios de una nube) hasta que se defina la infraestructura (D-44).

## Pruebas y definición de terminado

- Pest con sintaxis de funciones (`php artisan make:test --pest`). Una prueba feature por criterio de aceptación, en `tests/Feature/<Module>/`.
- **Terminado** = `composer ci:check` en verde (Pint, PHPStan nivel 7, Pest, `vp check`, `tsc`) y especificación actualizada si cambió alguna regla.
- Entorno local: `php artisan migrate:fresh --seed` crea un usuario por rol (`superadmin|admin|inspector|owner|client@example.test`, contraseña `password`).
