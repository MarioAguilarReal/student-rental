# 09 · Sistema de diseño

> Referencia de UX: los **patrones** de Airbnb (fotos primero, un solo paso siguiente claro, editor por secciones, mucho aire), **no** su marca: nombre, logotipo, tipografía y colores son propios. Estilo base: _framed sheet_ (marco tenue en L con una hoja blanca para el trabajo), elegido con la guía de diseño del proyecto. Vigente desde 2026-11-19; toda pantalla nueva lo sigue.

## 1. Principios

1. **Lo que sigue, primero.** Cada pantalla responde una pregunta y pone su acción principal arriba a la derecha del título (un solo botón primario por pantalla).
2. **Fotos primero.** Un inmueble se reconoce por su foto: tarjetas con la foto grande arriba y el texto debajo, sin caja alrededor.
3. **Una cosa a la vez.** Formularios largos se dividen en secciones con resumen (editor de propiedades); en el celular, primero la lista y luego la sección.
4. **El color comunica, no decora.** Interfaz en tinta y grises; el azul petróleo de marca solo en el logotipo, la selección y el dato principal; los estados en píldoras de tono suave.
5. **Estados honestos.** Los vacíos conservan su marco y dicen qué hacer; los ceros se muestran como 0; los errores aparecen junto a la acción que los causó.

## 2. Tokens (`resources/css/app.css`)

| Rol                               | Claro                 | Uso                                                                   |
| --------------------------------- | --------------------- | --------------------------------------------------------------------- |
| `background`                      | `#f5f8f8`             | Marco: barra lateral + barra superior (una "L" sin línea entre ellas) |
| `card`                            | `#ffffff`             | La hoja de trabajo y los paneles                                      |
| `foreground`                      | `#131416`             | Texto principal (tinta)                                               |
| `muted-foreground`                | `#5d5f60`             | Texto secundario (6.4:1 sobre blanco)                                 |
| `primary`                         | `#131416`             | **Botón principal: negro**, como en las herramientas de anfitrión     |
| `brand` / `brand-soft`            | `#1b6c80` / `#e3f0f3` | Logotipo, foco, barra de progreso, selección                          |
| `border` / `input`                | `#e6e9ea` / `#cdd0d2` | Bordes de tarjetas / de controles (un paso más oscuro)                |
| `accent`                          | `#ebeeee`             | Fondo de fila activa o al pasar el cursor (neutral)                   |
| `positive`, `negative`, `warning` | pares _soft_ + _ink_  | Solo en píldoras de estado                                            |

El modo oscuro usa neutros verdaderos (`#0b0c0c` de fondo) con las mismas funciones. Nada de degradados, brillos, desenfoques ni sombras en reposo (las sombras solo en lo que flota: menús y diálogos).

**Tipografía:** IBM Plex Sans (400/500/600), cargada vía Bunny Fonts. Escala: título de página 22/600 · sección 16–18/600 · cuerpo 14 · meta 12. Cifras que se alinean o cambian con `tabular`. Mayúsculas solo al inicio de la oración.

**Radios:** controles 8 px (`rounded-lg`), tarjetas y fotos 12 px (`rounded-xl`), hoja 16 px (`rounded-2xl`), píldoras y filtros redondos (`rounded-full`).

## 3. Estructura de pantalla

- **Marco y hoja** (`AppSidebarLayout` → `AppSheet`): barra lateral de 256 px y barra superior de 48 px sobre el marco; la página vive en una hoja blanca con borde de 1 px, separada 8 px de los bordes derecho e inferior. En el celular la hoja ocupa la pantalla.
- **Menú lateral:** grupo principal sin etiqueta; grupo "Administración" solo si hay permisos. Ítem activo = píldora gris neutral. Elementos filtrados por permiso en `resources/js/lib/navigation.ts`.
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
- **Páginas públicas (catálogo y ficha):** `PublicLayout` (marca a la izquierda, cuenta a la derecha, sin barra lateral). Catálogo: barra de búsqueda en píldora con campos rotulados, filtros rápidos en píldoras, "Más filtros" en diálogo, tarjetas con foto a la izquierda y mapa fijo con precios a la derecha (en el celular, botón Ver mapa / Ver lista). Ficha: título y ubicación aproximada, mosaico de fotos (portada + hasta 4), contenido a la izquierda y tarjeta de cotización fija a la derecha.
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

Degradados, brillos, desenfoques; sombras en tarjetas; íconos dentro de círculos de color; filas de tarjetas idénticas con ícono arriba; etiquetas pequeñas en mayúsculas con espaciado; emojis; botones de color de marca en cada acción (el primario es negro); violeta o índigo; títulos de más de 32 px dentro de la app.
