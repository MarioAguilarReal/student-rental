# 05 · Historias de usuario

Formato: **Como** [rol] **quiero** [acción] **para** [beneficio]. Los criterios de aceptación hacen referencia a las [reglas de negocio](03-reglas-negocio.md).

## Resumen de épicas

| Épica | Nombre                    | Historias | Must |
| ----- | ------------------------- | :-------: | :--: |
| E01   | Identidad y acceso        |     4     |  3   |
| E02   | Verificación de comunidad |     5     |  5   |
| E03   | Catálogos y configuración |     5     |  4   |
| E04   | Propiedades y unidades    |     9     |  9   |
| E05   | Precios y disponibilidad  |     3     |  2   |
| E06   | Inspecciones              |     6     |  5   |
| E07   | Búsqueda y descubrimiento |     2     |  2   |
| E08   | Rentas                    |     7     |  7   |
| E09   | Pagos simulados           |     5     |  4   |
| E10   | Notificaciones            |     2     |  1   |
| E11   | Auditoría                 |     1     |  1   |

---

## E01 · Identidad y acceso

### HU-01.01 Registro · Must

**Como** visitante **quiero** crear una cuenta como cliente o como propietario **para** usar la plataforma.

- Datos obligatorios: nombre, apellidos, correo, contraseña, fecha de nacimiento, tipo de perfil (cliente / propietario).
- El correo es único. Si ya existe, se muestra un error genérico (no se revela si la cuenta existe).
- La fecha de nacimiento es obligatoria. Por ahora no se restringe por edad (política pendiente, RN-05).
- Se envía un correo de verificación. Sin correo verificado no se puede subir documentación.
- Un usuario existente puede **agregar el otro perfil** (cliente ↔ propietario) desde su cuenta.

### HU-01.02 Inicio de sesión y recuperación · Must

**Como** usuario **quiero** iniciar sesión, cerrarla y recuperar mi contraseña **para** acceder de forma segura.

- Bloqueo temporal tras 5 intentos fallidos.
- El enlace de recuperación es de un solo uso y expira en 1 hora.
- Un usuario `SUSPENDIDO` no puede iniciar sesión.

### HU-01.03 Asignación de roles internos · Must

**Como** Super Admin **quiero** asignar los roles Inspector y Administrador **para** habilitar al personal interno.

- Solo el Super Admin asigna y revoca roles internos (queda en auditoría, RN-90).
- Un usuario puede tener varios roles.

### HU-01.04 Suspensión de usuarios · Should

**Como** administrador **quiero** suspender a un usuario **para** frenar comportamientos indebidos.

- Motivo obligatorio. La suspensión cierra sus sesiones activas.
- Las rentas activas del usuario no se modifican automáticamente (se gestionan a mano).

---

## E02 · Verificación de comunidad

### HU-02.01 Verificar correo y asociar institución · Must

**Como** cliente **quiero** verificar mi correo **para** iniciar mi validación como miembro de la comunidad.

- Si el dominio coincide con una institución, se asocia automáticamente (RN-02). Si no, el cliente elige la institución destino.
- El cliente elige su tipo de comunidad: docente, estudiante o estudiante de intercambio.
- Estado resultante: `CORREO_VERIFICADO`.

### HU-02.02 Subir documentos de cliente · Must

**Como** cliente **quiero** subir mi credencial (o carta de aceptación si soy de intercambio) **para** quedar verificado y poder rentar.

- Formatos JPG, PNG o PDF, máximo 10 MB. Se guardan como archivo **privado** (RN-07).
- Para intercambio con carta, la fecha de fin de estancia es obligatoria (RN-03).
- Estado resultante: `EN_REVISION`. El cliente ve el estado de su trámite.

### HU-02.03 Verificación de propietario · Must

**Como** propietario **quiero** subir mi identificación oficial y el comprobante de propiedad **para** poder enviar mis propiedades a inspección.

- El comprobante se asocia a una propiedad específica.
- Sin verificación aprobada, el botón "Solicitar inspección" está deshabilitado con una explicación (RN-06).

### HU-02.04 Revisar documentos · Must

**Como** administrador **quiero** una bandeja de verificaciones pendientes **para** aprobarlas o rechazarlas.

- Lista ordenada por antigüedad, con filtro por tipo de usuario.
- Visor del documento mediante una URL firmada temporal.
- Al aprobar se define `verificado_hasta` según RN-04. Al rechazar, el motivo es obligatorio y se notifica al usuario.

### HU-02.05 Vencimiento y renovación · Must

**Como** cliente **quiero** que me avisen antes de que venza mi verificación **para** renovarla a tiempo.

- Aviso 15 días antes. Al vencer, el estado pasa a `VENCIDO` (RN-04).
- Con la verificación vencida no puede crear solicitudes, pero sus rentas vigentes continúan.

---

## E03 · Catálogos y configuración

### HU-03.01 Instituciones y campus · Must

**Como** Super Admin **quiero** registrar instituciones con sus dominios de correo y campus **para** verificar usuarios y calcular la cercanía.

- Un campus requiere coordenadas. Una institución puede tener varios dominios.

### HU-03.02 Amenidades y reglas de la casa · Must

**Como** Super Admin **quiero** gestionar el catálogo de amenidades y reglas de la casa **para** estandarizar la información.

- Una amenidad tiene ámbito (propiedad, unidad o ambos). Desactivar no borra las asignaciones existentes.

### HU-03.03 Plantillas de checklist · Must

**Como** Super Admin **quiero** crear y versionar el checklist de inspección **para** estandarizar la revisión de seguridad.

- Cada ítem tiene categoría, ámbito, si es crítico y si requiere foto.
- Publicar una nueva versión no afecta las inspecciones ya creadas (RN-82).

### HU-03.04 Reglas de comisión · Must

**Como** Super Admin **quiero** configurar el porcentaje de comisión por modalidad con fecha de vigencia **para** ajustar el modelo de ingreso sin desplegar código.

- Valores iniciales: 10 % autogestionada, 20 % administrada (RN-50).
- No puede haber dos reglas vigentes que se traslapen para la misma modalidad.

### HU-03.05 Parámetros del sistema · Should

**Como** Super Admin **quiero** configurar plazos y factores (expiraciones, vigencias, factores de precio sugerido) **para** ajustar la operación.

---

## E04 · Propiedades y unidades

### HU-04.01 Crear propiedad · Must

**Como** propietario **quiero** registrar mi propiedad **para** ofrecerla en renta.

- Datos: título, descripción, tipo de inmueble, dirección, ubicación en mapa y modalidad de gestión.
- Se crea en `BORRADOR`. Se puede guardar incompleta.
- Un administrador puede crearla a nombre de un propietario existente (RN-15).

### HU-04.02 Definir unidades · Must

**Como** propietario **quiero** definir cómo se renta mi propiedad (completa, por habitaciones o por camas) **para** maximizar la ocupación.

- Asistente con tres plantillas iniciales: "Casa completa", "Por habitaciones", "Mixta". Después se pueden ajustar.
- Se valida la jerarquía permitida (RN-20). Cada unidad tiene capacidad, si es rentable y depósito.
- Si la propiedad ya pasó por inspección, agregar o quitar unidades y cambiar su tipo o capacidad la regresa a `PENDIENTE_INSPECCION` (RN-11), con una advertencia previa.

### HU-04.03 Fotos · Must

**Como** propietario **quiero** subir y ordenar fotos de la propiedad y de cada unidad **para** mostrar el espacio.

- Mínimo 3 para publicar (RN-10), máximo 30. JPG, PNG o WEBP, máximo 10 MB, con miniaturas generadas.
- Se puede reordenar, elegir portada y escribir texto alternativo.

### HU-04.04 Amenidades, reglas y convivencia · Must

**Como** propietario **quiero** indicar amenidades, reglas de la casa y restricciones de convivencia **para** que los inquilinos sepan qué esperar.

- Amenidades en dos niveles (propiedad y unidad). La unidad muestra también las heredadas.
- Restricción de género y de tipo de comunidad: la unidad solo puede **restringir** respecto a la propiedad (RN-30, RN-31).

### HU-04.05 Editar propiedad · Must

**Como** propietario **quiero** editar mi propiedad **para** mantenerla actualizada.

- Los cambios no críticos se aplican de inmediato. Los críticos muestran un aviso de reinspección (RN-11).
- Cambiar la modalidad solo afecta rentas futuras (RN-14).

### HU-04.06 Solicitar inspección · Must

**Como** propietario **quiero** enviar mi propiedad a inspección **para** poder publicarla.

- Requiere propietario verificado, al menos una unidad y la dirección completa.
- Crea una `Inspeccion` de tipo `ALTA` o `POR_CAMBIO` en `PENDIENTE_ASIGNACION`.

### HU-04.07 Publicar y pausar · Must

**Como** propietario **quiero** publicar o pausar mi propiedad **para** controlar cuándo recibe solicitudes.

- Publicar valida RN-10 y muestra qué requisito falta.
- Pausar oculta la propiedad del catálogo. Las solicitudes en `SOLICITADA` se mantienen hasta que el operador las resuelva.

### HU-04.08 Archivar · Must

**Como** propietario **quiero** archivar una propiedad **para** retirarla definitivamente.

- Se bloquea si hay rentas `APROBADA`, `CONFIRMADA` o `ACTIVA` (RN-13).

### HU-04.09 Gestión global · Must

**Como** administrador **quiero** ver y filtrar todas las propiedades por estado, modalidad y propietario **para** operar la plataforma.

- El administrador puede editar propiedades `ADMINISTRADAS` como operador.

---

## E05 · Precios y disponibilidad

### HU-05.01 Planes de renta · Must

**Como** operador **quiero** definir los precios semanal, mensual y/o anual de cada unidad **para** ofrecer distintas duraciones.

- Al capturar el mensual, el sistema sugiere el semanal y el anual (RN-41). Son editables.
- Mínimo y máximo de periodos por plan (RN-43). Máximo un plan activo por periodo (RN-40).
- Se muestra una vista previa del ingreso neto del propietario: precio − comisión según la modalidad.

### HU-05.02 Depósito · Must

**Como** operador **quiero** definir el depósito de cada unidad **para** tener una garantía.

- Valor sugerido: un mes (RN-44). Puede ser $0.

### HU-05.03 Bloquear fechas · Should

**Como** operador **quiero** bloquear fechas de una unidad **para** mantenimiento o uso propio.

- No se permite bloquear sobre rentas bloqueantes (RN-26). El bloqueo afecta a ancestros y descendientes (RN-22).

---

## E06 · Inspecciones

### HU-06.01 Asignar inspección · Must

**Como** administrador **quiero** asignar inspecciones pendientes a inspectores con una fecha **para** organizar el trabajo de campo.

- No se puede asignar al propietario de la propiedad (RN-81). Se puede reasignar.
- El inspector y el propietario reciben una notificación con la fecha.

### HU-06.02 Agenda del inspector · Must

**Como** inspector **quiero** ver mis inspecciones asignadas con dirección y mapa **para** planear mi ruta.

- El inspector ve la dirección exacta y los datos de contacto del operador.
- La interfaz debe funcionar bien en un celular (responsive).

### HU-06.03 Ejecutar checklist · Must

**Como** inspector **quiero** llenar el checklist de la propiedad y de cada unidad, con fotos y comentarios **para** documentar su estado.

- Se generan secciones para áreas comunes y para cada unidad rentable (RN-87).
- Cada ítem: Cumple / No cumple / No aplica, con comentario. Si el ítem lo exige, foto obligatoria (RN-84).
- Se guarda el avance parcial (estado `EN_PROGRESO`).

### HU-06.04 Emitir dictamen · Must

**Como** inspector **quiero** emitir el dictamen **para** cerrar la inspección.

- Un ítem crítico en "No cumple" fuerza `RECHAZADA` (RN-83).
- Las observaciones son obligatorias si el resultado no es `APROBADA`.
- Al aprobar, se fija `vigente_hasta` = hoy + 12 meses y la propiedad pasa a `APROBADA`.

### HU-06.05 Consultar resultado · Must

**Como** propietario **quiero** ver el resultado y las observaciones de la inspección **para** corregir lo necesario.

- Ve los ítems que no cumplen, con fotos y comentarios. Puede solicitar una nueva inspección (RN-86).

### HU-06.06 Renovación automática · Should

**Como** sistema **quiero** generar la inspección de renovación 30 días antes del vencimiento **para** evitar que las propiedades se pausen.

- Al vencer sin renovarse: propiedad `PAUSADA` con motivo `INSPECCION_VENCIDA` (RN-85).

---

## E07 · Búsqueda y descubrimiento

### HU-07.01 Buscar unidades · Must

**Como** visitante o cliente **quiero** buscar espacios con filtros **para** encontrar uno que se ajuste a mí.

- Filtros: campus/institución y distancia, tipo de espacio, periodo (semanal/mensual/anual), rango de precio, fechas, amenidades y regla de género.
- Solo aparecen unidades rentables de propiedades `PUBLICADA` con inspección vigente.
- Resultados en lista y en mapa. El mapa muestra la **ubicación aproximada**, no la dirección exacta.
- Si el cliente está verificado, se ocultan o marcan las unidades cuyas reglas de convivencia no cumple.

### HU-07.02 Detalle de la propiedad · Must

**Como** cliente **quiero** ver el detalle de una propiedad y sus unidades **para** decidir si la rento.

- Muestra fotos, descripción, amenidades heredadas y propias, reglas de la casa, convivencia, planes y depósito.
- Insignia "**Inspeccionada**" con la fecha de la última inspección aprobada.
- Calendario de disponibilidad por unidad y cálculo del total según las fechas y el plan elegidos.

---

## E08 · Rentas

### HU-08.01 Solicitar renta · Must

**Como** cliente verificado **quiero** solicitar una unidad para unas fechas y un plan **para** reservar mi lugar.

- Valida verificación vigente (RN-01), convivencia (RN-30 a RN-32), periodos (RN-43), fechas (RN-63), disponibilidad (RN-22) y no traslape propio (RN-64).
- El cliente debe aceptar las reglas de la casa (RN-33). Mensaje opcional al operador.
- Muestra un desglose: total de renta, depósito y calendario de cargos. Se copian los valores comerciales (RN-45).
- Estado `SOLICITADA`, con `expira_en` = +72 h.

### HU-08.02 Aprobar o rechazar · Must

**Como** operador **quiero** revisar las solicitudes de mis unidades **para** aprobar o rechazar a los inquilinos.

- Ve el perfil del cliente (nombre, tipo de comunidad, institución, estado de verificación y su mensaje). **No ve** sus documentos.
- Aprobar revalida la disponibilidad (RN-24, RN-25), genera el calendario de cargos (RN-70) y fija `expira_en` = +48 h.
- Rechazar requiere motivo (RN-67).

### HU-08.03 Expiraciones automáticas · Must

**Como** sistema **quiero** expirar solicitudes y aprobaciones vencidas **para** liberar el inventario.

- RN-61 y RN-62. Se notifica al cliente y al operador.

### HU-08.04 Mis rentas (cliente) · Must

**Como** cliente **quiero** ver mis solicitudes y rentas con su estado y sus cargos **para** dar seguimiento.

- Al confirmarse la renta, el cliente ve la **dirección exacta** y el contacto del operador.

### HU-08.05 Rentas de mis propiedades · Must

**Como** propietario **quiero** ver las rentas de mis propiedades **para** conocer la ocupación.

- En propiedades `ADMINISTRADAS` es solo lectura.

### HU-08.06 Ciclo automático · Must

**Como** sistema **quiero** activar y finalizar las rentas según sus fechas **para** reflejar la ocupación real (RN-65).

### HU-08.07 Cancelación administrativa · Must

**Como** administrador **quiero** cancelar una renta con un motivo **para** atender casos excepcionales.

- Los cargos pendientes pasan a `CANCELADO`. Sin reembolso automático (RN-66). Se notifica a las partes.

---

## E09 · Pagos simulados

### HU-09.01 Pagar un cargo · Must

**Como** cliente **quiero** pagar mis cargos con el método simulado **para** confirmar y mantener mi renta.

- La pantalla indica claramente que es un "**Pago de prueba**".
- Si se paga el primer cargo, la renta pasa a `CONFIRMADA` (RN-71). Un pago fallido permite reintentar.

### HU-09.02 Contabilidad · Must

**Como** plataforma **quiero** registrar cada pago en el ledger de partida doble **para** saber cuánto corresponde a comisión, al propietario y a depósitos (RN-73, RN-75).

### HU-09.03 Saldo del propietario · Must

**Como** propietario **quiero** ver mis ingresos por renta: bruto, comisión y neto **para** conocer mis ganancias.

### HU-09.04 Panel financiero · Must

**Como** administrador **quiero** ver las comisiones generadas, los saldos por pagar a propietarios, los depósitos retenidos y los cargos vencidos **para** controlar la operación.

### HU-09.05 Escenarios de simulación · Should

**Como** Super Admin **quiero** forzar resultados de pago (éxito, fallo, demora) **para** probar los flujos (RN-72).

---

## E10 · Notificaciones

### HU-10.01 Correos transaccionales · Must

**Como** usuario **quiero** recibir un correo cuando ocurra algo relevante **para** actuar a tiempo.

| Evento                                                 | Destinatario                         |
| ------------------------------------------------------ | ------------------------------------ |
| Registro / recuperación de contraseña                  | Usuario                              |
| Verificación aprobada, rechazada, por vencer o vencida | Usuario                              |
| Inspección asignada / reprogramada                     | Inspector, operador                  |
| Inspección completada (resultado)                      | Propietario, admin                   |
| Inspección por vencer                                  | Propietario, admin                   |
| Nueva solicitud de renta                               | Operador                             |
| Solicitud aprobada / rechazada / expirada              | Cliente                              |
| Renta confirmada                                       | Cliente, operador, propietario       |
| Cargo próximo a vencer / vencido / fallido             | Cliente (+ operador si está vencido) |
| Renta cancelada                                        | Cliente, operador, propietario       |

### HU-10.02 Centro de notificaciones · Should

**Como** usuario **quiero** ver mis notificaciones dentro de la app **para** no depender del correo.

---

## E11 · Auditoría

### HU-11.01 Bitácora · Must

**Como** administrador **quiero** consultar quién cambió qué y cuándo **para** resolver disputas y tener control.

- Filtros por entidad, actor y fechas. La bitácora es de solo lectura (RN-90).
