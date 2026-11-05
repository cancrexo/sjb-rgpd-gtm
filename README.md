# SJB RGPD para Tag Manager

Complemento de GDPR Cookie Compliance (Moove Agency). Fija el Consent Mode de Google según la región y carga Tag Manager. No pinta el aviso de cookies: el banner sigue siendo el de ese plugin.

Versión actual: **1.0.0**

Autor: Daniel "Cancrexo" Prol · [cancrexo@gmail.com](mailto:cancrexo@gmail.com)

---

## Requisitos

- WordPress ≥ 6.0
- PHP ≥ 8.3
- [GDPR Cookie Compliance](https://wordpress.org/plugins/gdpr-cookie-compliance/) de Moove Agency, que guarda la decisión en la cookie `moove_gdpr_popup`

---

## Instalación

1. Copia la carpeta del plugin en `wp-content/plugins/sjb-rgpd-gtm`.
2. En el escritorio de WordPress: **Plugins → Plugins instalados**.
3. Activa **SJB RGPD para Tag Manager**. Hace falta que GDPR Cookie Compliance esté instalado y activo.

El contenedor no se escribe aquí. Se lee en cada carga del Consent Mode v2 de GDPR Cookie Compliance (`gtmc2_id`). La lista de países sigue en `sjb-rgpd-gtm.php`.

Ruta: **Ajustes → SJB RGPD Tag Manager**. La pantalla solo informa: qué hace el plugin y qué contenedor está usando. Si el otro plugin está apagado, o Consent Mode v2 no tiene un identificador `GTM-`, esta web no carga Tag Manager.

---

## Qué hace

En `wp_head`, con prioridad 0, antes que el resto de scripts:

1. Retira el snippet de consentimiento que imprime GDPR Cookie Compliance (`gdpr_google_consent_mode2_snippet`).
2. Si la cookie `moove_gdpr_popup` ya tiene una decisión, la envía a Google:

| Clave de la cookie | Señal de Consent Mode |
|--------------------|------------------------|
| `advanced` | `ad_storage`, `ad_user_data`, `ad_personalization` |
| `thirdparty` | `analytics_storage` |
| `preference` | `personalization_storage` |
| `strict` | `security_storage`, `functionality_storage` |

Un valor `1` es `granted`. Cualquier otro valor, o una clave ausente, es `denied`.

3. Si todavía no hay cookie, manda dos valores por defecto:
   - **Denegado** (`ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage`) en el EEE, Reino Unido y Suiza.
   - **Concedido** en el resto de países.
4. Carga el contenedor `GTM-` leído de Consent Mode v2. Si no hay ninguno, no imprime nada y deja el snippet de GDPR Cookie Compliance como estaba.

---

## Licencia

GPL v2 o posterior.
