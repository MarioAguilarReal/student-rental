# 02 · Roles y permisos

## Actores

| Rol               | Cómo se obtiene             | Descripción                                                                          |
| ----------------- | --------------------------- | ------------------------------------------------------------------------------------ |
| **Visitante**     | Sin sesión                  | Explora el catálogo público.                                                         |
| **Cliente**       | Autorregistro               | Docente, estudiante o estudiante de intercambio. Solicita rentas una vez verificado. |
| **Propietario**   | Autorregistro               | Dueño de inmuebles. Registra propiedades y elige la modalidad de gestión.            |
| **Inspector**     | Asignado por un Super Admin | Personal de campo que inspecciona propiedades.                                       |
| **Administrador** | Asignado por un Super Admin | Operación: verificaciones, inspecciones, propiedades administradas, cancelaciones.   |
| **Super Admin**   | Semilla del sistema         | Configuración de la plataforma, catálogos y gestión de roles.                        |

Un usuario puede tener **varios roles** (por ejemplo, un docente que también es propietario). Las restricciones de conflicto de interés se validan con reglas de negocio (RN-81), no excluyendo roles.

## Permisos

Formato `module.resource.action`, en inglés como el resto del código (D-46). La fuente de verdad en el código es `App\Modules\Identity\Enums\Role::permissions()`, y `RolesAndPermissionsSeeder` la sincroniza. Ver el catálogo público no requiere permiso.

El sufijo **(propio)** significa que el permiso solo aplica a recursos del usuario o a propiedades que opera. Esa restricción la aplican las Policies, no el permiso.

| Permiso                                                                  |     Cliente     |     Propietario      |   Inspector    |              Admin              | Super Admin |
| ------------------------------------------------------------------------ | :-------------: | :------------------: | :------------: | :-----------------------------: | :---------: |
| `verification.documents.upload` (propio)                                 |       ✅        |          ✅          |                |                                 |             |
| `verification.documents.review`                                          |                 |                      |                |               ✅                |     ✅      |
| `properties.create`                                                      |                 |     ✅ (propio)      |                | ✅ (a nombre de un propietario) |     ✅      |
| `properties.update`                                                      |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `properties.archive`                                                     |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `properties.publish`                                                     |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `properties.view_any`                                                    |                 |                      |                |               ✅                |     ✅      |
| `pricing.rate_plans.manage`                                              |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `pricing.availability_blocks.manage`                                     |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `inspections.request`                                                    |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `inspections.assign`                                                     |                 |                      |                |               ✅                |     ✅      |
| `inspections.perform` (asignadas)                                        |                 |                      |       ✅       |                                 |             |
| `inspections.view`                                                       |                 |     ✅ (propio)      | ✅ (asignadas) |               ✅                |     ✅      |
| `rentals.request`                                                        | ✅ (verificado) |                      |                |                                 |             |
| `rentals.view`                                                           |   ✅ (propio)   | ✅ (sus propiedades) |                |               ✅                |     ✅      |
| `rentals.decide`                                                         |                 | ✅ (autogestionadas) |                |       ✅ (administradas)        |     ✅      |
| `rentals.cancel`                                                         |                 |                      |                |               ✅                |     ✅      |
| `payments.charges.pay` (propio)                                          |       ✅        |                      |                |                                 |             |
| `payments.balances.view`                                                 |                 |     ✅ (propio)      |                |               ✅                |     ✅      |
| `payments.simulation.configure`                                          |                 |                      |                |                                 |     ✅      |
| `catalog.manage` (instituciones, amenidades, reglas de casa, checklists) |                 |                      |                |                                 |     ✅      |
| `payments.commission_rules.manage`                                       |                 |                      |                |                                 |     ✅      |
| `users.roles.assign`                                                     |                 |                      |                |                                 |     ✅      |
| `users.suspend`                                                          |                 |                      |                |               ✅                |     ✅      |
| `audit.view`                                                             |                 |                      |                |               ✅                |     ✅      |

## Reglas de autorización especiales

- **Operador de la propiedad:** en `AUTOGESTIONADA` es el propietario; en `ADMINISTRADA` es cualquier administrador. El operador aprueba rentas y bloquea fechas.
- En una propiedad `ADMINISTRADA` el propietario **mantiene acceso de lectura** a sus rentas y a su saldo, pero no aprueba rentas.
- El **Inspector** solo ve las propiedades que tiene asignadas y no puede inspeccionar una propiedad propia.
