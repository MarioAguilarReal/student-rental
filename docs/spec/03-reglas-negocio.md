# 03 · Reglas de negocio

Reglas que el sistema **debe garantizar en el backend**, independientemente de la interfaz.

## Verificación de usuarios

- **RN-01** Navegar por el catálogo es libre. Solo un cliente con verificación `VERIFICADO` y vigente puede **solicitar rentas**.
- **RN-02** Al verificar el correo, si el dominio pertenece a una `Institucion` registrada, el cliente queda asociado a ella automáticamente. Si no (por ejemplo, un estudiante de intercambio con correo de su universidad de origen), elige la institución destino manualmente.
- **RN-03** Documentos requeridos:
    - Docente / Estudiante: **credencial vigente** de la institución.
    - Estudiante de intercambio: **credencial** de la institución destino **o carta de aceptación**, que debe indicar la fecha de fin de la estancia.
- **RN-04** Vigencia de la verificación: estudiantes 6 meses, docentes 12 meses, intercambio hasta el fin de la estancia (máx. 12 meses). Al vencer pasa a `VENCIDO`: las rentas existentes continúan, pero no puede solicitar nuevas hasta renovar. Se avisa 15 días antes.
- **RN-05** Se captura la **fecha de nacimiento** en el registro. La política para menores de edad está **pendiente** (D-21, B-19): por ahora no se restringe el registro.
- **RN-06** Antes de **solicitar la inspección** de una propiedad, el propietario debe tener su identidad `VERIFICADA` (identificación oficial, revisada por un administrador) **y** adjuntar a esa propiedad un comprobante de propiedad o una autorización del dueño para rentar (D-20, D-49).
- **RN-07** Los documentos de verificación son **privados**. Solo los ven el usuario que los subió y los administradores, y únicamente mediante URLs firmadas temporales.
- **RN-08** Nadie revisa su propia verificación: un administrador con perfil de cliente o propietario no puede aprobar ni rechazar su propia solicitud (conflicto de interés).

## Propiedades

- **RN-10** Para pasar a `PUBLICADA` se requiere, todo a la vez:
    1. propietario `VERIFICADO`,
    2. inspección `APROBADA` (o `APROBADA_CON_OBSERVACIONES`) vigente,
    3. al menos **una unidad rentable** con **un plan activo**,
    4. al menos **3 fotos** de la propiedad.
- **RN-11** **Cambios críticos** que regresan la propiedad a `PENDIENTE_INSPECCION`: dirección o ubicación, agregar o eliminar unidades, cambiar el tipo o la capacidad de una unidad. Cambiar precio, descripción, fotos, amenidades o reglas **no** requiere reinspección.
- **RN-12** Mientras una propiedad no está `PUBLICADA` no aparece en el catálogo ni acepta solicitudes. Las rentas ya `CONFIRMADA`/`ACTIVA` **continúan** sin cambios.
- **RN-13** "Eliminar" significa **archivar** (borrado lógico). No se puede archivar una propiedad con rentas en estado `APROBADA`, `CONFIRMADA` o `ACTIVA`.
- **RN-14** Cambiar la modalidad de gestión aplica **solo a rentas nuevas** (las existentes conservan su copia de modalidad y comisión).
- **RN-15** Un administrador puede crear una propiedad **a nombre de un propietario** registrado (típico de la modalidad `ADMINISTRADA`).

## Unidades y disponibilidad

- **RN-20** Jerarquía permitida:
    - Raíz: `COMPLETA`, `HABITACION_PRIVADA` o `HABITACION_COMPARTIDA`.
    - Hijos de `COMPLETA`: `HABITACION_PRIVADA` o `HABITACION_COMPARTIDA`.
    - Hijos de `HABITACION_COMPARTIDA`: solo `CAMA`.
    - `HABITACION_PRIVADA` y `CAMA` no tienen hijos.
- **RN-21** Cada unidad tiene un indicador `rentable`. Por ejemplo, una casa puede rentarse solo por habitaciones (raíz no rentable) y una habitación compartida solo por camas.
- **RN-22** **Conflicto de disponibilidad:** una unidad no está disponible en `[inicio, fin)` si existe una renta **bloqueante** o un **bloqueo de disponibilidad** que se traslape en la **misma unidad, cualquiera de sus ancestros o cualquiera de sus descendientes**.
- **RN-23** Estados de renta **bloqueantes**: `APROBADA` (solo hasta `expira_en`), `CONFIRMADA`, `ACTIVA`. Una renta `SOLICITADA` **no bloquea**: puede haber varias solicitudes compitiendo.
- **RN-24** Al aprobar una solicitud se revalida la disponibilidad. Cuando una renta pasa a `CONFIRMADA`, las solicitudes `SOLICITADA` que se traslapan y quedaron en conflicto se **rechazan automáticamente** con el motivo "sin disponibilidad", y se notifica a sus clientes.
- **RN-25** La validación de disponibilidad y la transición a estado bloqueante ocurren en **una sola transacción con bloqueo a nivel de propiedad**, para evitar dobles rentas por concurrencia.
- **RN-26** Los bloqueos de disponibilidad (mantenimiento, uso del propietario) solo pueden crearse sobre fechas sin rentas bloqueantes.

## Convivencia

- **RN-30** `regla_genero`: `MIXTO` | `SOLO_MUJERES` | `SOLO_HOMBRES`. Se define en la propiedad y puede **restringirse** (nunca relajarse) en una unidad. Si la unidad no la define, hereda la del ancestro más cercano.
- **RN-31** `comunidades_permitidas`: subconjunto de {`DOCENTE`, `ESTUDIANTE`, `ESTUDIANTE_INTERCAMBIO`}. Vacío = todas. Se hereda y solo se puede restringir, igual que RN-30.
- **RN-32** Para solicitar una unidad con restricción de género, el cliente debe haber declarado su **género para fines de convivencia**. El dato es opcional en el perfil, solo se pide cuando aplica y **nunca se muestra a terceros**: el operador solo ve "cumple la regla".
- **RN-33** **Reglas de la casa** desde un catálogo (mascotas, fumar, fiestas, visitas nocturnas, horario de silencio), cada una con `permitido` (sí/no) y una nota opcional. Son informativas, y el cliente las **acepta explícitamente** al solicitar.

## Precios

- **RN-40** Cada unidad rentable tiene como máximo **un plan activo por periodo** (`SEMANAL`, `MENSUAL`, `ANUAL`).
- **RN-41** Precios sugeridos, calculados a partir del mensual, editables y con factores configurables:
    - Semanal ≈ mensual ÷ 4 × **1.15**
    - Anual ≈ mensual × 12 × **0.90**
    - Redondeo a múltiplos de $10.
- **RN-42** Cálculo de periodos: semanal = 7 días; mensual = mismo día del mes siguiente (si no existe, el último día del mes); anual = 12 meses calendario. `fecha_fin` es exclusiva.
- **RN-43** Cada plan define `min_periodos` y `max_periodos`. La solicitud debe respetarlos.
- **RN-44** Depósito en garantía configurable por unidad. Valor sugerido: un mes de renta.
- **RN-45** La solicitud guarda una **copia de los valores comerciales** (precio por periodo, depósito, % de comisión, modalidad) para que los cambios posteriores no la afecten.

## Comisión

- **RN-50** Comisión: **10 %** si la propiedad es `AUTOGESTIONADA`, **20 %** si es `ADMINISTRADA`. Se lee de `ReglaComision` (configurable, con vigencia), nunca de un valor fijo en el código.
- **RN-51** La comisión se calcula **solo sobre la renta**, nunca sobre el depósito.
- **RN-52** La comisión se **descuenta al propietario**. El cliente paga exactamente el precio publicado, sin tarifa de servicio.

## Rentas

- **RN-60** Toda renta requiere aprobación del **operador** de la propiedad (ver [roles](02-roles-permisos.md)).
- **RN-61** Una solicitud sin respuesta en **72 h** pasa a `EXPIRADA` (D-22).
- **RN-62** Después de aprobarse, el cliente tiene **48 h** para pagar el primer cargo. Si no paga, pasa a `EXPIRADA` y se libera la disponibilidad.
- **RN-63** La fecha de inicio debe ser **al menos el día siguiente** y **como máximo 12 meses** en el futuro.
- **RN-64** Un cliente no puede tener **dos rentas bloqueantes traslapadas** (D-25).
- **RN-65** Transiciones automáticas: `CONFIRMADA` → `ACTIVA` en `fecha_inicio`; `ACTIVA` → `FINALIZADA` en `fecha_fin`.
- **RN-66** Cancelación (MVP): **solo un administrador**, con motivo obligatorio. Los cargos pendientes pasan a `CANCELADO`. **No hay reembolso automático** (TODO, ver backlog).
- **RN-67** Al rechazar una solicitud, el motivo es obligatorio y se muestra al cliente.

## Pagos (simulados)

- **RN-70** Calendario de cargos, que se genera al aprobar:
    - `SEMANAL`: **un solo cargo** = total de la renta + depósito.
    - `MENSUAL`: cargo 1 = primer mes + depósito; luego un cargo por mes, con vencimiento en el inicio de cada periodo.
    - `ANUAL`: precio anual repartido en 12 cuotas mensuales (la última ajusta el redondeo); cargo 1 = cuota 1 + depósito.
- **RN-71** La renta pasa a `CONFIRMADA` cuando el **primer cargo se paga**.
- **RN-72** La pasarela simulada aprueba los pagos por defecto. El Super Admin puede configurar escenarios (fallo, demora) para pruebas.
- **RN-73** Cada pago exitoso genera asientos de **partida doble** en el ledger:
    - Renta: `CLIENTE` → `PLATAFORMA_COMISION` (comisión) + `PROPIETARIO_POR_PAGAR` (renta − comisión).
    - Depósito: `CLIENTE` → `DEPOSITOS_EN_GARANTIA`.
- **RN-74** Un cargo posterior no pagado en su fecha pasa a `VENCIDO` y se notifica al cliente y al operador. En el MVP no hay penalizaciones (TODO).
- **RN-75** Los montos nunca se recalculan después de generarse. Las correcciones se hacen con asientos de ajuste, nunca editando ni borrando asientos.

## Inspecciones

- **RN-80** Tipos: `ALTA` (primera vez), `POR_CAMBIO` (RN-11), `RENOVACION` (por vencimiento).
- **RN-81** El administrador asigna el inspector. El inspector **no puede ser propietario** de la propiedad inspeccionada.
- **RN-82** Las plantillas de checklist están **versionadas**. Una inspección usa la versión vigente al crearse.
- **RN-83** Si cualquier **ítem crítico** queda `NO_CUMPLE`, el resultado es forzosamente `RECHAZADA`.
- **RN-84** Los ítems con `requiere_foto` necesitan al menos una foto para poder enviar la inspección.
- **RN-85** Vigencia de **12 meses**. Se avisa 30 días antes y se genera una inspección `RENOVACION`. Al vencer sin renovarse, la propiedad pasa a `PAUSADA` (las rentas existentes continúan).
- **RN-86** Si la inspección es `RECHAZADA`, el propietario ve las observaciones, corrige y solicita una nueva inspección.
- **RN-87** El checklist cubre la propiedad (áreas comunes) y **cada unidad rentable**.

## Auditoría

- **RN-90** Se registra actor, fecha, entidad, acción y valores antes/después en: cambios de estado (propiedad, renta, inspección, verificación), precios, modalidad, reglas de comisión, asignación de roles y suspensiones.
