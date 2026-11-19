# Especificación funcional — Plataforma de renta para comunidad escolar

> Nombre del producto: **por definir** · Versión: **v0.3** · Fecha: 2026-11-18 · Stack: **Laravel 13 + Inertia 3 + React (TypeScript) + PostgreSQL 18**

Plataforma tipo marketplace (modelo híbrido) para que docentes, estudiantes y estudiantes de intercambio renten casas completas, habitaciones privadas o espacios compartidos, con **inspección obligatoria** de cada propiedad antes de publicarse.

## Índice

| #   | Documento                                       | Contenido                                                                          |
| --- | ----------------------------------------------- | ---------------------------------------------------------------------------------- |
| 01  | [Decisiones](01-decisiones.md)                  | Registro de decisiones de producto y arquitectura                                  |
| 02  | [Roles y permisos](02-roles-permisos.md)        | Actores, permisos y matriz de acceso                                               |
| 03  | [Reglas de negocio](03-reglas-negocio.md)       | Reglas numeradas (RN-xx) que el sistema debe garantizar                            |
| 04  | [Modelo de dominio](04-modelo-dominio.md)       | Módulos, entidades, campos y máquinas de estado                                    |
| 05  | [Historias de usuario](05-historias-usuario.md) | Épicas e historias con criterios de aceptación                                     |
| 06  | [Backlog futuro](06-backlog.md)                 | Funcionalidades fuera del MVP                                                      |
| 07  | [Arquitectura técnica](07-arquitectura.md)      | Stack, estructura modular, reglas de arquitectura, concurrencia, flujo con agentes |
| 08  | [Plan de iteraciones](08-plan-iteraciones.md)   | Orden de construcción del MVP                                                      |

## Convenciones

- `D-xx`: decisión · `RN-xx`: regla de negocio · `HU-EE.NN`: historia de usuario (épica.número).
- Prioridad de historias según MoSCoW: **Must** (MVP), **Should** (MVP si da tiempo), **Could** (posterior).
- Dinero en **centavos (entero)** + código de moneda ISO 4217 (default `MXN`). Porcentajes en **puntos base** (1000 = 10 %).
- Fechas de renta como rango semiabierto `[inicio, fin)`. Timestamps en UTC.

## Glosario

| Término                   | Definición                                                                                                                                      |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| **Propiedad**             | Inmueble físico con una dirección (casa, departamento). Es lo que se inspecciona.                                                               |
| **Unidad**                | Espacio rentable dentro de una propiedad: casa completa, habitación privada, habitación compartida o cama. Se organizan en árbol.               |
| **Plan de renta**         | Precio de una unidad para un periodo (semanal, mensual o anual).                                                                                |
| **Renta**                 | Contrato de uso de una unidad por un cliente en un rango de fechas.                                                                             |
| **Modalidad de gestión**  | `AUTOGESTIONADA` (opera el propietario, 10 % de comisión) o `ADMINISTRADA` (opera la empresa, 20 % de comisión).                                |
| **Cargo**                 | Monto programado que el cliente debe pagar (renta de un periodo o depósito).                                                                    |
| **Ledger**                | Registro contable de partida doble de todos los movimientos de dinero (simulado en el MVP).                                                     |
| **Inspección**            | Revisión en campo, basada en un checklist, que dictamina si una propiedad es segura y coincide con lo publicado.                                |
| **Verificación**          | Proceso que confirma la identidad y la pertenencia a la comunidad escolar (clientes) o la identidad y la propiedad del inmueble (propietarios). |
| **Reglas de convivencia** | Restricciones de género y de tipo de comunidad, y reglas de la casa (mascotas, fumar, etc.).                                                    |
