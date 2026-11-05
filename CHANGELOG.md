# Changelog

Los cambios notables de SJB RGPD para Tag Manager. La versión del plugin es la de la cabecera (`1.0.0`).

## 1.0.0 — 2026-11-05

- Complemento de GDPR Cookie Compliance (Moove Agency): Consent Mode por región y carga del contenedor de Tag Manager definido en su Consent Mode v2.
- Pantalla en **Ajustes → SJB RGPD Tag Manager**. No guarda el identificador: lo lee en cada carga.
- Sin GDPR Cookie Compliance activo, o sin un contenedor `GTM-` en Consent Mode v2, no carga Tag Manager.
- Sin decisión previa, denegado en el EEE, Reino Unido y Suiza; concedido en el resto.
- Con la cookie `moove_gdpr_popup`, se respeta la elección del banner.
- Sustituye el snippet de consentimiento que imprime GDPR Cookie Compliance.
- La rutina deja el tema y queda en este plugin.
