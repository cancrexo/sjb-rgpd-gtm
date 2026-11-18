# Changelog

Los cambios notables de SJB RGPD para Tag Manager. La versión del plugin es la de la cabecera (`1.1.0`).

## 1.1.0 — 2026-11-18

- El país se decide en el navegador. El HTML no incluye el código del visitante, así que la página puede ir cacheada.
- Sin cookie, el consentimiento es `denied` o `granted` según el país, sin parámetro `region`. Tag Manager no se carga en el EEE, Reino Unido, Suiza ni si el país no se conoce, hasta que se acepta el banner.
- Con cookie, Tag Manager solo se carga si la categoría de Consent Mode v2 (`gtmc2`) vale `1`.
- Fuentes de país: Cloudflare (`/cdn-cgi/trace`), variables del servidor (`pais.php`, sin WordPress ni caché) o zona horaria. La sesión de la pestaña recuerda el resultado.
- La carga permitida espera a `load` y a `requestIdleCallback`. El filtro `sjb_rgpd_gtm_retardo` puede cambiarlo a la primera interacción.
- Filtros `sjb_rgpd_gtm_fuente` y `sjb_rgpd_gtm_regiones`.
- La pantalla de ajustes indica la fuente de país de esa petición y el modo de retardo.
- Quien ya tenía la categoría aceptada no pierde el retardo: el `consent update` que Moove vuelve a inyectar en cada carga no se trata como una aceptación nueva.
- Sin cookie y fuera de la zona restringida, rechazar el banner cancela la carga pendiente y, si Tag Manager ya estaba cargado, envía un `consent update` denegado.

## 1.0.0 — 2026-11-05

- Complemento de GDPR Cookie Compliance (Moove Agency): Consent Mode por región y carga del contenedor de Tag Manager definido en su Consent Mode v2.
- Pantalla en **Ajustes → SJB RGPD Tag Manager**. No guarda el identificador: lo lee en cada carga.
- Sin GDPR Cookie Compliance activo, o sin un contenedor `GTM-` en Consent Mode v2, no carga Tag Manager.
- Sin decisión previa, denegado en el EEE, Reino Unido y Suiza; concedido en el resto.
- Con la cookie `moove_gdpr_popup`, se respeta la elección del banner.
- Sustituye el snippet de consentimiento que imprime GDPR Cookie Compliance.
- La rutina deja el tema y queda en este plugin.
