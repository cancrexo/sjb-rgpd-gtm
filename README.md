# SJB RGPD para Tag Manager

Complemento de GDPR Cookie Compliance (Moove Agency). Fija el Consent Mode de Google según la región y carga Tag Manager. No pinta el aviso de cookies: el banner sigue siendo el de ese plugin.

Versión actual: **1.1.0**

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

Ruta: **Ajustes → SJB RGPD Tag Manager**. La pantalla solo informa: qué hace el plugin, qué contenedor está usando, de dónde se puede leer el país en esa petición y cómo se retrasa Tag Manager. Si el otro plugin está apagado, o Consent Mode v2 no tiene un identificador `GTM-`, esta web no carga Tag Manager.

---

## Qué hace

En `wp_head`, con prioridad 0, antes que el resto de scripts:

1. Retira el snippet de consentimiento que imprime GDPR Cookie Compliance (`gdpr_google_consent_mode2_snippet`), solo si hay un contenedor `GTM-`.
2. El navegador decide. PHP no escribe el país en el HTML: la misma página sirve para todos, también con caché (W3 Total Cache, WP Fastest Cache, Varnish).
3. Si la cookie `moove_gdpr_popup` ya tiene una decisión, la envía a Google y Tag Manager se carga solo si la categoría asignada a Consent Mode v2 vale `1`:

| Clave de la cookie | Señal de Consent Mode |
|--------------------|------------------------|
| `advanced` | `ad_storage`, `ad_user_data`, `ad_personalization` |
| `thirdparty` | `analytics_storage` |
| `preference` | `personalization_storage` |
| `strict` | `security_storage`, `functionality_storage` |

Un valor `1` es `granted`. Cualquier otro valor, o una clave ausente, es `denied`.

La categoría de Tag Manager sale del campo `gtmc2` de Moove: `2` terceros, `3` avanzadas, `4` rendimiento, `5` preferencias. Si no es una de esas, se usa `thirdparty`.

4. Si todavía no hay cookie:
   - País del EEE, Reino Unido o Suiza, o país desconocido: las cuatro señales de anuncios y analítica quedan `denied`, sin parámetro `region`, y Tag Manager no se carga hasta que se acepte el banner.
   - Resto de países: esas cuatro señales quedan `granted` y Tag Manager se carga de forma diferida (ver «Carga diferida»).

### Aceptar o rechazar en el banner

Si al cargar la página la categoría de Tag Manager no estaba aceptada y el usuario acepta, Tag Manager se carga al momento, sin recargar. Se detecta por el `consent update` y el evento `cookie_consent_update` que inyecta GDPR Cookie Compliance. Como respaldo, se relee la cookie 500 ms después del clic en los botones de aceptar o de guardar ajustes.

Si al cargar la página la categoría ya estaba aceptada, ese `consent update` que Moove repite en cada carga se ignora y se mantiene la carga diferida.

Si el usuario rechaza sin decisión previa (`moove-gdpr-infobar-reject-btn`, `moove-gdpr-modal-reject-all` o guardar ajustes con la categoría a `0`), se cancela la carga pendiente. Si Tag Manager ya estaba cargado, se envía un `consent update` `denied` en `ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage` y `personalization_storage`.

Si se retira un consentimiento dado antes, GDPR Cookie Compliance recarga la página y el plugin vuelve a decidir con la cookie nueva.

Tag Manager se carga una sola vez por página.

### De dónde sale el país

El navegador usa el primer dato válido y lo guarda en `sessionStorage` (`sjb_rgpd_pais`) para el resto de la pestaña. `EU` significa «zona horaria restringida» y `XX` «zona horaria no restringida». No son códigos de país.

1. Ese valor guardado, si ya existe.
2. La fuente que elige PHP, igual para todos los visitantes de esa petición:
   - `cloudflare` si la petición trae `CF-RAY` o `CF-IPCountry`. El navegador pide `/cdn-cgi/trace` y lee `loc=`.
   - `servidor` si hay `GEOIP_COUNTRY_CODE`, `MM_COUNTRY_CODE` o `HTTP_X_COUNTRY_CODE`. El navegador pide `pais.php`, que no carga WordPress y responde `{"pais":"XX"}` sin caché.
   - `zona` en otro caso, o si el fetch falla, tarda más de 1,5 s o no da un código de dos letras. Se mira `Intl` y la zona `Europe/…`, más una lista de territorios de ultramar. `UTC`, `Etc/…` o una zona ilegible cuentan como restringidas.
3. Mientras no hay país, no se pide `gtm.js`. El `consent default` sale antes que el contenedor.

`pais.php` envía `Cache-Control: private, no-store`. No debe cachearse con la página.

#### Límites de la zona horaria

No mide dónde está el visitante, sino la zona configurada en su equipo. Un viajero con el reloj sin cambiar, o una VPN, puede dar otro resultado.

Toda zona `Europe/…` cuenta como restringida, también la de países europeos de fuera del EEE (por ejemplo Serbia o Turquía). El error va siempre hacia el lado prudente.

El país real por IP solo se obtiene con Cloudflare o con GeoIP en el servidor, y el plugin lo detecta solo.

### Comprobar si el servidor tiene GeoIP

Tres vías:

- En **Ajustes → SJB RGPD Tag Manager**, la línea «Fuente de país en esta petición» dice «variables de país del servidor».
- Abrir en el navegador `/wp-content/plugins/sjb-rgpd-gtm/pais.php`. `{"pais":"ES"}` quiere decir que sí. `{"pais":""}`, que no, o que la variable tiene otro nombre. Solo se leen `GEOIP_COUNTRY_CODE`, `MM_COUNTRY_CODE` y `HTTP_X_COUNTRY_CODE`.
- Por SSH:

```bash
apachectl -M | grep -i maxmind
nginx -V 2>&1 | grep -io geoip2
```

### Carga diferida

Cuando el consentimiento ya permite Tag Manager, o el país no es restringido, el contenedor espera a `load` y luego a que el navegador quede libre (`requestIdleCallback`, 2 s como máximo). También se aplica a los visitantes que vuelven con la categoría ya aceptada.

### Filtros

| Filtro | Valores | Por defecto |
|--------|---------|-------------|
| `sjb_rgpd_gtm_fuente` | `cloudflare`, `servidor`, `zona` | la detectada en la petición |
| `sjb_rgpd_gtm_regiones` | lista de códigos ISO de dos letras | EEE, Reino Unido y Suiza |
| `sjb_rgpd_gtm_retardo` | `idle`, `interaccion` | `idle` |

`interaccion` carga Tag Manager en el primer scroll, pulsación, tecla o toque, como máximo 5 segundos después de `load`.

---

## Licencia

GPL v2 o posterior.
