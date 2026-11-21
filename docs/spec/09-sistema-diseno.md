# 09 · Sistema de diseño

> Referencia de UX: los **patrones** de Airbnb (fotos primero, un solo paso siguiente claro, editor por secciones, mucho aire), **no** su marca: nombre, logotipo, tipografía y colores son propios. Estilo base: _framed sheet_ (marco tenue en L con una hoja blanca para el trabajo), elegido con la guía de diseño del proyecto. Vigente desde 2026-11-19; paleta **"Pacífico y sol"** desde 2026-11-25 (D-87). Toda pantalla nueva lo sigue.

## 1. Principios

1. **Lo que sigue, primero.** Cada pantalla responde una pregunta y pone su acción principal arriba a la derecha del título (un solo botón primario por pantalla).
2. **Fotos primero.** Un inmueble se reconoce por su foto: tarjetas con la foto grande arriba y el texto debajo, sin caja alrededor.
3. **Una cosa a la vez.** Formularios largos se dividen en secciones con resumen (editor de propiedades); en el celular, primero la lista y luego la sección.
4. **El color comunica, no decora.** Un solo color de marca, el **turquesa del Pacífico nayarita**, en la acción principal, la selección, el logotipo y el dato principal (el precio); el **amarillo sol** solo en los precios del mapa. Neutros teñidos del turquesa; los estados en píldoras de tono suave.
5. **Estados honestos.** Los vacíos conservan su marco y dicen qué hacer; los ceros se muestran como 0; los errores aparecen junto a la acción que los causó.

## 2. Tokens (`resources/css/app.css`)

| Rol                               | Claro                 | Oscuro                | Uso                                                                   |
| --------------------------------- | --------------------- | --------------------- | --------------------------------------------------------------------- |
| `background`                      | `#eef7f6`             | `#081110`             | Marco: barra lateral + barra superior                                 |
| `card`                            | `#ffffff`             | `#0f1a19`             | La hoja de trabajo y los paneles                                      |
| `foreground`                      | `#0f1a19`             | `#eef7f6`             | Texto principal (tinta teñida)                                        |
| `muted-foreground`                | `#4b5b5a`             | `#a9bcb9`             | Texto secundario (7.1:1 sobre blanco)                                 |
| `primary` = `brand`               | `#00786f`             | `#2ed3c0`             | Botón principal, píldora seleccionada, foco, logotipo (5.4:1 / 8.9:1) |
| `brand-soft` / `brand-ink`        | `#d8f3ef` / `#00524c` | `#0f2f2b` / `#9ef0e4` | Ítem activo del menú, franja del catálogo, "Inspeccionada", precio    |
| `sun` / `sun-foreground`          | `#ffc53d` / `#2b1d00` | `#ffd166` / `#2b1d00` | Solo precios en el mapa (10.4:1)                                      |
| `border` / `input`                | `#dbe8e6` / `#bfd3d0` | `#1e2e2c` / `#2f4441` | Bordes de tarjetas / de controles                                     |
| `accent`                          | `#e3f1ef`             | `#172625`             | Fondo de fila activa o al pasar el cursor                             |
| `positive`, `negative`, `warning` | pares _soft_ + _ink_  | pares _soft_ + _ink_  | Solo en píldoras de estado                                            |
| `chart-1…5`                       | turquesa, sol, rosa   | igual, aclarados      | Gráficas: el turquesa en el dato principal                            |

**Reglas de la paleta:** todo par de texto cumple WCAG AA (4.5:1 o más); nunca texto blanco sobre el amarillo sol; un solo botón relleno por pantalla; los colores viven en `resources/css/app.css` y los componentes usan tokens (las librerías que necesitan un color en texto lo leen con `cssVar()` de `lib/css-var.ts`).

El modo oscuro usa neutros teñidos del turquesa (`#081110` de fondo) con las mismas funciones; el turquesa se aclara y lleva tinta oscura encima. Nada de degradados, brillos, desenfoques ni sombras en reposo (las sombras solo en lo que flota: menús y diálogos).

**Tipografía:** IBM Plex Sans (400/500/600), cargada vía Bunny Fonts. Escala: título de página 22/600 · sección 16–18/600 · cuerpo 14 · meta 12. Cifras que se alinean o cambian con `tabular`. Mayúsculas solo al inicio de la oración.

**Radios:** controles 8 px (`rounded-lg`), tarjetas y fotos 12 px (`rounded-xl`), hoja 16 px (`rounded-2xl`), píldoras y filtros redondos (`rounded-full`).

## 3. Estructura de pantalla

- **Marco y hoja** (`AppSidebarLayout` → `AppSheet`): barra lateral de 256 px y barra superior de 48 px sobre el marco; la página vive en una hoja blanca con borde de 1 px, separada 8 px de los bordes derecho e inferior. En el celular la hoja ocupa la pantalla.
- **Menú lateral:** grupo principal sin etiqueta; grupo "Administración" solo si hay permisos. Ítem activo = píldora `brand-soft` con texto `brand-ink`. Elementos filtrados por permiso en `resources/js/lib/navigation.ts`; "Notificaciones" muestra el número de no leídas.
- **`Page`** (`components/page.tsx`): enlace "volver" opcional, título `h1` de 22 px con su estado al lado (`meta`), una oración de descripción y las acciones a la derecha. Anchos: `narrow` (formularios, perfiles), `default` (listas y colas), `wide` (catálogos, editor).

## 4. Componentes del sistema

| Componente                                                            | Cuándo                                                                                                         |
| --------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `Page`, `PageSection`                                                 | Toda pantalla y cada bloque titulado dentro de ella                                                            |
| `ListingCard`                                                         | Cualquier inmueble en lista: foto 4:3 redondeada, estado en píldora sobre la foto, título y dos líneas de dato |
| `FilterChips`                                                         | Filtros entre pares con su conteo (los ceros se muestran); en el celular se deslizan de lado                   |
| `PersonAvatar`                                                        | Personas: iniciales con un tinte pálido estable por persona (identidad, no dato)                               |
| `ProgressSteps`                                                       | Requisitos o pasos ("3 de 5") con barra en color de marca                                                      |
| `EmptyState`                                                          | Lista vacía: un ícono gris, qué pasa y la acción para resolverlo                                               |
| `FileField`                                                           | Toda subida de archivos: zona de arrastre que dice qué acepta y muestra lo elegido                             |
| `FormActions`                                                         | Barra de guardado fija al pie de cada sección de formulario (un "Guardar" por sección)                         |
| `Badge` (tonos `neutral`, `positive`, `negative`, `warning`, `brand`) | Estados. Mapas de estado → tono en `verification-status-badge.tsx` y `properties/property-status-badge.tsx`    |
| `NativeSelect`                                                        | Listas desplegables en formularios con estado                                                                  |

## 5. Patrones

- **Listas de trabajo (colas, usuarios):** filtros con conteo arriba; filas con avatar, nombre, una línea de contexto, metadatos a la derecha y chevron; toda la fila es el enlace. Sin tablas con encabezados en mayúsculas ni filas cebra.
- **Catálogos de inmuebles:** cuadrícula de `ListingCard` (1 → 2 → 3 → 4 columnas), 24 px entre columnas y 40 px entre filas.
- **Editor por secciones** (`pages/properties/edit.tsx`): a la izquierda la lista de secciones con resumen y marca de completo (✓) o pendiente (punto); a la derecha una sola sección; la sección se refleja en la URL (`#fotos`). El siguiente paso del ciclo de vida es el botón principal del encabezado. En el celular: lista → sección con "Todas las secciones".
- **Inicio:** "Lo que sigue" (tareas con su botón), luego las cosas del usuario (propiedades como tarjetas), luego su estado.
- **Páginas públicas (catálogo y ficha):** `PublicLayout` (marca a la izquierda, cuenta a la derecha, sin barra lateral). Catálogo: franja `brand-soft` con el título y una oración de apoyo, barra de búsqueda en píldora con campos rotulados (dos filas en tablet; en el celular un resumen tocable que la despliega, para que las viviendas aparezcan en la primera pantalla), filtros rápidos en píldoras, "Más filtros" en diálogo, tarjetas con foto a la izquierda y mapa fijo con precios a la derecha (en el celular, botón Ver mapa / Ver lista). Ficha: título y ubicación aproximada, mosaico de fotos (portada + hasta 4), contenido a la izquierda y tarjeta de cotización fija a la derecha.
- **Mapas:** Leaflet con la tipografía del producto; ubicación pública siempre aproximada (círculo o pin en cuadrícula de ~500 m).
- **Cuadrículas:** siempre `grid-cols-1` en móvil y `minmax(0,1fr)` en columnas flexibles, para que un título truncado no desborde la pantalla.
- **Acceso (login, registro):** una tarjeta blanca centrada sobre el marco, con el logotipo y el nombre arriba.
- **Formularios:** etiqueta arriba del campo, controles de 40 px, ayuda en una línea gris debajo, error en rojo debajo del campo; los cambios críticos avisan antes de guardar (`ReinspectionNotice`).
- **Acciones destructivas:** al final, en su propia sección, con la consecuencia en palabras y confirmación que nombra el objeto.

## 6. Movimiento y accesibilidad

- Solo `transform` y `opacity`: los botones se hunden 2 % al presionar, las fotos de las tarjetas crecen 3 % al pasar el cursor, la barra de progreso se anima. Con `prefers-reduced-motion` se desactiva.
- Foco visible en todo control (anillo en color de marca). Textos de 4.5:1 como mínimo. Íconos decorativos con `aria-hidden`; los que son la única etiqueta llevan `aria-label`.
- Todo texto visible pasa por `t()` (ver [07 §2](07-arquitectura.md)); los plurales usan `singular|plural` con `:count`.

## 7. Qué no hacer

Degradados, brillos, desenfoques; sombras en tarjetas; íconos dentro de círculos de color; filas de tarjetas idénticas con ícono arriba; etiquetas pequeñas en mayúsculas con espaciado; emojis; más de un botón relleno por pantalla; el amarillo sol fuera del mapa; texto blanco sobre amarillo; violeta o índigo; títulos de más de 32 px dentro de la app.
