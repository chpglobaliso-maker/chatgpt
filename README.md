Actualización 1.4: fotografía documental y composición editorial en 19 páginas interiores y 11 fichas de normas/cursos. La cabecera combina texto e imagen; los textos se presentan en columnas y los créditos acompañan cada foto. Se conserva la portada compacta, el logo oficial, el favicon y la incompatibilidad entre certificación y otros servicios.

La construcción completa ahora ejecuta `build.mjs`, `reference-design.mjs` e `interior-design.mjs`, en ese orden (también mediante el script `build` del paquete). `interior.css` se carga después de los estilos anteriores. Los interiores publicados se presentan mediante plantillas de tema específicas; el contenido previo en los registros de WordPress se conserva. Para modificar su presentación en producción, usar Aspecto → Editor → Plantillas; para reproducir los cambios, modificar las fuentes y reconstruir. Las fichas usan plantillas `single-{tipo}-{slug}.html`.

# CHP GLOBAL ISO SpA — WordPress

Actualización visual 1.1: portada compacta según la referencia proporcionada (cabecera con marca circular y menús desplegables, banner industrial con profesionales, cuatro servicios con iconos, paneles de industria/ERP, franja de normas y pie compacto). La portada se edita en la plantilla `front-page.html` del tema, también disponible en el editor del sitio. El contenido anterior de la página Inicio permanece conservado. Las páginas interiores y el plugin/formularios no se sustituyen al actualizar el tema.

Para reproducir la versión 1.1, ejecutar `node scripts/build.mjs` seguido de `node scripts/reference-design.mjs`, o el script `build` del paquete. `reference.css` contiene los estilos de la referencia. Las dos composiciones generadas se identifican como ilustrativas; no son fotos documentales de una planta ni capturas de un ERP operativo.

Implementación del brief de la tarea #1. Tema de bloques independiente, plugin de catálogos/solicitudes y contenido editable. Sin credenciales en el repositorio.

## Instalación

Requiere WordPress 6.7+, PHP 8.1+, HTTPS y copias de seguridad de archivos y base de datos verificadas. Respaldar también configuración del servidor por el hosting. Guardar respaldos fuera del repositorio público y restringir su acceso.

1. Instalar `theme/chp-global-iso` como tema y `plugin/chp-site` como plugin mediante ZIP desde WordPress. Mantener el tema anterior para reversión.
2. Activar CHP Site. En Herramientas → Preparar sitio CHP, crear borradores. El importador no sobrescribe slugs existentes.
3. Activar CHP Global ISO y revisar la vista previa de Inicio y páginas interiores.
4. Publicar desde Preparar sitio CHP. Solo publica borradores creados por el importador; configura Inicio, privacidad y zona America/Santiago. No elimina contenido previo. Revisar enlaces permanentes y descartar a borrador los ejemplos de instalación mediante la interfaz.
5. Probar formularios, permisos, correo, navegación y vistas móviles. El contenido permanece en WordPress si se desactiva el plugin; las fichas reaparecen al reactivarlo.

## Edición

Las 20 páginas comerciales y 4 adicionales se editan en Gutenberg. Se generan con grupos, títulos y párrafos nativos; tarjetas y fotografías usan bloques HTML. Normas y cursos tienen tipos de contenido con editor y taxonomía. Las fichas de cursos son rutas de interés, no convocatorias contratables. `chp_caso` permite publicar casos solo con autorización. Los artículos pueden gestionarse con Entradas; no se inventan casos ni resultados.

## Formularios y correo

El formulario nativo registra solicitudes privadas accesibles solo con `manage_options`. Incluye nonce, validación de opciones en servidor, límites de longitud, señuelo antispam y límite temporal por correo/IP con hash. El aviso a contacto@chpglobaliso.cl contiene solo un enlace administrativo, sin datos del solicitante. `wp_mail=true` solo indica aceptación por el transporte, nunca entrega. La confirmación al visitante solo declara registro. El estado técnico de aceptación se conserva en `_chp_mail_accepted`.

Antes de usar el correo como canal único, verificar casilla, MX, SPF/DKIM/DMARC y una prueba de ida y recepción. Configurar SMTP mediante un plugin mantenido y credenciales en almacenamiento seguro del hosting, nunca en Git. El registro privado evita perder solicitudes si falla el correo. No hay conexión a un CRM externo ni ERP en esta versión. Fluent Forms puede sustituir al formulario cuando su configuración y almacenamiento hayan sido revisados; no instalar formularios duplicados.

## SEO, accesibilidad y privacidad

Títulos de WordPress, descripciones por extracto, Open Graph, Organization JSON-LD y sitemap nativo `/wp-sitemap.xml`. Si se activa Yoast o Rank Math se evita salida duplicada del plugin CHP. Confirmación con noindex. Jerarquía H1, landmarks, enlace para saltar, foco visible, etiquetas y estados de formulario, menú móvil nativo, reducción de movimiento. Sin tracking ni fuentes remotas: no se requiere consentimiento de analítica mientras no exista. GA4/Search Console requieren propiedad y configuración verificadas; no se inventan IDs ni verificaciones.

## Construcción y comprobación

Instalar dependencias con pnpm, ejecutar `node scripts/build.mjs` y `node scripts/check.mjs`. `dist` es una vista estática de diseño; no sustituye las pruebas funcionales del WordPress. Ejecutar también `php -l` y pruebas del formulario en PHP/WordPress de staging antes de una actualización. No es necesaria una compilación para servir WordPress.

Las fuentes Manrope e Inter se alojan localmente con licencias OFL incluidas. Fotografías WebP con créditos y enlaces públicos en `/creditos/`; ver `docs/ASSETS.md`. No son clientes ni instalaciones operadas por CHP.

## Reversión

Con acceso administrativo, cambiar al tema Twenty Twenty-Five y restablecer la página de inicio anterior en Ajustes → Lectura. Los valores previos se conservan en la opción `chp_previous_front`. Desactivar CHP Site si hay una incidencia. Para revertir contenido/configuración, restaurar la base de datos y los componentes de archivo del respaldo previo mediante UpdraftPlus. Si falla el acceso a WordPress, la recuperación requiere hosting; no se ha afirmado una restauración de desastre sin acceso a ese panel. Nunca restaurar producción solo para hacer una prueba.

## Pendientes de negocio para la operación completa

Confirmar contacto corporativo y política de conservación; datos legales y equipo; casos autorizados; funcionalidades reales del ERP; docentes, horas, fechas y condiciones de cada curso; LMS si se requiere venta/evaluación online; CRM; correo autenticado; MFA y recuperación a cargo del propietario; respaldo externo programado; verificación de Search Console y medición con consentimiento; mediciones de Core Web Vitals en tráfico real.
