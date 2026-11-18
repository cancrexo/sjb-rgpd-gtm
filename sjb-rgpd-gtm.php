<?php
/**
 * Plugin Name: SJB RGPD para Tag Manager
 * Plugin URI: https://www.sjbdixital.es
 * Description: Complemento de GDPR Cookie Compliance (Moove Agency). Fija el consentimiento por región y carga Google Tag Manager.
 * Version: 1.1.0
 * Author: Daniel "Cancrexo" Prol
 * Author URI: https://www.sjbdixital.es
 * Requires at least: 6.0
 * Requires PHP: 8.3
 * Requires Plugins: gdpr-cookie-compliance
 * Text Domain: sjb-rgpd-gtm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package sjb-rgpd-gtm
 * @author  Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', array( 'SJB_RGPD_GTM', 'consent_por_region' ), 0 );
add_action( 'admin_menu', array( 'SJB_RGPD_GTM', 'anadir_menu' ) );

/**
 * Consent Mode para Tag Manager, como complemento de GDPR Cookie Compliance (Moove Agency).
 *
 * El país se decide en el navegador. PHP solo indica de dónde leerlo.
 * Denegado en el EEE, Reino Unido y Suiza; concedido en el resto.
 * Si ya hay decisión guardada, se respeta la cookie del banner.
 */
class SJB_RGPD_GTM {

    /** Opción donde GDPR Cookie Compliance guarda sus ajustes. */
    private const OPCION_MOOVE = 'moove_gdpr_plugin_settings';

    /** Clave de sessionStorage. EU = zona restringida, XX = zona no restringida. */
    private const CLAVE_PAIS = 'sjb_rgpd_pais';

    /**
     * Códigos ISO del EEE, Reino Unido y Suiza.
     *
     * @var string[]
     */
    private const REGIONES_RESTRINGIDAS = array(
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU',
        'IS', 'IE', 'IT', 'LV', 'LI', 'LT', 'LU', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO',
        'SK', 'SI', 'ES', 'SE', 'GB', 'CH',
    );

    /**
     * cookie_cat de Moove (campo gtmc2) → clave de la cookie moove_gdpr_popup.
     *
     * @var array<int, string>
     */
    private const CATEGORIAS_GTM = array(
        2 => 'thirdparty',
        3 => 'advanced',
        4 => 'performance',
        5 => 'preference',
    );

    /**
     * Entrada en Ajustes.
     */
    public static function anadir_menu(): void {
        add_options_page(
            __( 'SJB RGPD para Tag Manager', 'sjb-rgpd-gtm' ),
            __( 'SJB RGPD Tag Manager', 'sjb-rgpd-gtm' ),
            'manage_options',
            'sjb-rgpd-gtm',
            array( __CLASS__, 'pantalla' )
        );
    }

    /**
     * Pantalla de solo lectura: qué hace el plugin, qué contenedor usa y cómo detecta el país.
     */
    public static function pantalla(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $moove_activo = class_exists( 'Moove_GDPR_Content' );
        $contenedor   = self::id_contenedor_moove();
        $fuentes      = array(
            'cloudflare' => __( 'Cloudflare', 'sjb-rgpd-gtm' ),
            'servidor'   => __( 'variables de país del servidor', 'sjb-rgpd-gtm' ),
            'zona'       => __( 'zona horaria del navegador', 'sjb-rgpd-gtm' ),
        );
        $retardos     = array(
            'idle'        => __( 'cuando el navegador queda libre, tras cargar la página', 'sjb-rgpd-gtm' ),
            'interaccion' => __( 'a la primera interacción, como máximo 5 segundos después de la carga', 'sjb-rgpd-gtm' ),
        );
        $fuente       = self::fuente_pais();
        $retardo      = self::modo_retardo();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p>
                <?php esc_html_e( 'Este plugin complementa a GDPR Cookie Compliance (Moove Agency). No muestra el banner de cookies. Fija el consentimiento de Google según la región y carga el contenedor de Tag Manager definido en ese plugin, en Consent Mode v2.', 'sjb-rgpd-gtm' ); ?>
            </p>
            <p>
                <?php esc_html_e( 'GDPR Cookie Compliance tiene que estar instalado y activo. El contenedor se lee de sus ajustes en cada carga. Aquí no se guarda una copia. El país del visitante no se escribe en el HTML: lo resuelve el navegador.', 'sjb-rgpd-gtm' ); ?>
            </p>
            <?php if ( ! $moove_activo ) : ?>
                <div class="notice notice-error">
                    <p><?php esc_html_e( 'GDPR Cookie Compliance (Moove Agency) no está activo. Sin ese plugin, esta web no carga Tag Manager.', 'sjb-rgpd-gtm' ); ?></p>
                </div>
            <?php elseif ( '' === $contenedor ) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e( 'No hay un contenedor GTM- en Consent Mode v2 de GDPR Cookie Compliance. Configúralo allí. Hasta entonces, este plugin no carga Tag Manager.', 'sjb-rgpd-gtm' ); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-info">
                    <p>
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: identificador del contenedor de Tag Manager. */
                                __( 'Usa el contenedor %s definido en GDPR Cookie Compliance (Consent Mode v2).', 'sjb-rgpd-gtm' ),
                                $contenedor
                            )
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %s: cloudflare, variables del servidor o zona horaria del navegador. */
                        __( 'Fuente de país en esta petición: %s.', 'sjb-rgpd-gtm' ),
                        $fuentes[ $fuente ] ?? $fuentes['zona']
                    )
                );
                ?>
            </p>
            <p>
                <?php
                echo esc_html(
                    sprintf(
                        /* translators: %s: modo de retardo de la carga de Tag Manager. */
                        __( 'Carga diferida de Tag Manager: %s.', 'sjb-rgpd-gtm' ),
                        $retardos[ $retardo ] ?? $retardos['idle']
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Ajustes de integraciones de Moove (gdin_values), o array vacío.
     *
     * @return array<string, mixed>
     */
    private static function valores_moove(): array {
        if ( ! class_exists( 'Moove_GDPR_Content' ) ) {
            return array();
        }

        $opciones = get_option( self::OPCION_MOOVE );
        if ( ! is_array( $opciones ) || empty( $opciones['gdin_values'] ) || ! is_string( $opciones['gdin_values'] ) ) {
            return array();
        }

        $valores = json_decode( $opciones['gdin_values'], true );

        return is_array( $valores ) ? $valores : array();
    }

    /**
     * Contenedor de Consent Mode v2 en GDPR Cookie Compliance.
     *
     * Vacío si ese plugin no está activo, la integración está apagada o el valor no empieza por GTM-.
     */
    private static function id_contenedor_moove(): string {
        $valores = self::valores_moove();
        if ( ! isset( $valores['gtmc2'] ) ) {
            return '';
        }

        $id = isset( $valores['gtmc2_id'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', trim( (string) $valores['gtmc2_id'] ) ) : '';
        if ( ! is_string( $id ) || 0 !== stripos( $id, 'GTM-' ) ) {
            return '';
        }

        return $id;
    }

    /**
     * Clave de la cookie moove_gdpr_popup que autoriza a cargar Tag Manager.
     *
     * El campo gtmc2 de Moove es el cookie_cat: 2 thirdparty, 3 advanced, 4 performance, 5 preference.
     */
    private static function clave_categoria_gtm(): string {
        $valores = self::valores_moove();
        $cat     = isset( $valores['gtmc2'] ) ? (int) $valores['gtmc2'] : 0;

        return self::CATEGORIAS_GTM[ $cat ] ?? 'thirdparty';
    }

    /**
     * De dónde puede salir el país, sin leer el código en sí.
     *
     * cloudflare, servidor o zona. El filtro sjb_rgpd_gtm_fuente puede forzarla.
     */
    private static function fuente_pais(): string {
        $fuente = 'zona';

        if ( ! empty( $_SERVER['HTTP_CF_RAY'] ) || ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
            $fuente = 'cloudflare';
        } elseif (
            self::codigo_servidor( 'GEOIP_COUNTRY_CODE' )
            || self::codigo_servidor( 'MM_COUNTRY_CODE' )
            || self::codigo_servidor( 'HTTP_X_COUNTRY_CODE' )
        ) {
            $fuente = 'servidor';
        }

        $fuente = apply_filters( 'sjb_rgpd_gtm_fuente', $fuente );
        if ( ! is_string( $fuente ) || ! in_array( $fuente, array( 'cloudflare', 'servidor', 'zona' ), true ) ) {
            return 'zona';
        }

        return $fuente;
    }

    /**
     * Hay un código de país no vacío en esa variable de servidor.
     */
    private static function codigo_servidor( string $clave ): bool {
        if ( empty( $_SERVER[ $clave ] ) || ! is_string( $_SERVER[ $clave ] ) ) {
            return false;
        }

        return '' !== trim( $_SERVER[ $clave ] );
    }

    /**
     * idle (por defecto) o interaccion. Filtro sjb_rgpd_gtm_retardo.
     */
    private static function modo_retardo(): string {
        $retardo = apply_filters( 'sjb_rgpd_gtm_retardo', 'idle' );
        if ( ! is_string( $retardo ) || ! in_array( $retardo, array( 'idle', 'interaccion' ), true ) ) {
            return 'idle';
        }

        return $retardo;
    }

    /**
     * Países donde, sin decisión previa, el consentimiento queda denegado.
     *
     * @return string[]
     */
    private static function regiones_restringidas(): array {
        $regiones = apply_filters( 'sjb_rgpd_gtm_regiones', self::REGIONES_RESTRINGIDAS );
        if ( ! is_array( $regiones ) ) {
            $regiones = self::REGIONES_RESTRINGIDAS;
        }

        $limpias = array();
        foreach ( $regiones as $codigo ) {
            $codigo = strtoupper( trim( (string) $codigo ) );
            if ( 1 === preg_match( '/^[A-Z]{2}$/', $codigo ) ) {
                $limpias[] = $codigo;
            }
        }

        return array_values( array_unique( $limpias ) );
    }

    /**
     * Imprime el consentimiento y, si toca, deja que el navegador cargue el contenedor.
     */
    public static function consent_por_region(): void {
        if ( is_admin() ) {
            return;
        }

        $gtm_id = self::id_contenedor_moove();
        if ( '' === $gtm_id ) {
            return;
        }

        remove_action( 'wp_head', array( 'Moove_GDPR_Content', 'gdpr_google_consent_mode2_snippet' ), 1 );

        $config = array(
            'fuente'     => self::fuente_pais(),
            'retardo'    => self::modo_retardo(),
            'categoria'  => self::clave_categoria_gtm(),
            'contenedor' => $gtm_id,
            'regiones'   => self::regiones_restringidas(),
            'paisUrl'    => esc_url_raw( plugins_url( 'pais.php', __FILE__ ) ),
            'clavePais'  => self::CLAVE_PAIS,
        );
        ?>
        <script>
        window.sjbRgpd = <?php echo wp_json_encode( $config ); ?>;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        /*SJB_RGPD_JS_INICIO*/
        (function () {
            var cfg = window.sjbRgpd || {};
            var cargado = false;
            var diferidoPedido = false;
            var consentEmitido = false;
            var pendienteInmediato = false;
            var bloqueado = false;
            var cookieInicial = leerCookie();
            var zonasEspeciales = [
                'Atlantic/Canary', 'Atlantic/Madeira', 'Atlantic/Azores', 'Atlantic/Reykjavik',
                'Atlantic/Faroe', 'Asia/Nicosia', 'Asia/Famagusta', 'America/Guadeloupe',
                'America/Martinique', 'America/Cayenne', 'America/St_Barthelemy', 'America/Marigot',
                'Indian/Reunion', 'Indian/Mayotte', 'Arctic/Longyearbyen'
            ];

            function zonaRestringida(zona) {
                var i;
                if (!zona || zona === 'UTC' || zona.indexOf('Etc/') === 0) {
                    return true;
                }
                if (zona.indexOf('Europe/') === 0) {
                    return true;
                }
                for (i = 0; i < zonasEspeciales.length; i++) {
                    if (zonasEspeciales[i] === zona) {
                        return true;
                    }
                }
                return false;
            }

            function codigoIso(valor) {
                if (typeof valor !== 'string') {
                    return '';
                }
                var codigo = valor.toUpperCase();
                if (!/^[A-Z]{2}$/.test(codigo) || codigo === 'XX') {
                    return '';
                }
                return codigo;
            }

            function leerCookie() {
                var encontrado = null;
                var datos = {};
                try {
                    encontrado = document.cookie.match(/(?:^|;\s*)moove_gdpr_popup=([^;]*)/);
                } catch (e) {}
                if (!encontrado) {
                    return { tiene: false, datos: datos };
                }
                try {
                    datos = JSON.parse(decodeURIComponent(encontrado[1])) || {};
                } catch (e) {
                    datos = {};
                }
                return { tiene: true, datos: datos };
            }

            function estadoDe(datos, clave) {
                return parseInt(datos[clave], 10) === 1 ? 'granted' : 'denied';
            }

            function categoriaAceptada(estado) {
                return !!(estado && estado.tiene && parseInt(estado.datos[cfg.categoria], 10) === 1);
            }

            function esRestringido(marca) {
                var i;
                if (marca === 'EU') {
                    return true;
                }
                if (!cfg.regiones) {
                    return true;
                }
                for (i = 0; i < cfg.regiones.length; i++) {
                    if (cfg.regiones[i] === marca) {
                        return true;
                    }
                }
                return false;
            }

            function emitirCookie(estado) {
                var ads = estadoDe(estado.datos, 'advanced');
                var estrictas = estadoDe(estado.datos, 'strict');
                window.gtag('consent', 'default', {
                    'ad_storage': ads,
                    'ad_user_data': ads,
                    'ad_personalization': ads,
                    'analytics_storage': estadoDe(estado.datos, 'thirdparty'),
                    'personalization_storage': estadoDe(estado.datos, 'preference'),
                    'security_storage': estrictas,
                    'functionality_storage': estrictas
                });
            }

            function emitirSenales(valor) {
                window.gtag('consent', 'default', {
                    'ad_storage': valor,
                    'ad_user_data': valor,
                    'ad_personalization': valor,
                    'analytics_storage': valor
                });
            }

            function cargarGtm() {
                var etiqueta, primero;
                if (bloqueado || cargado || !cfg.contenedor) {
                    return;
                }
                cargado = true;
                window.dataLayer.push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                });
                etiqueta = document.createElement('script');
                etiqueta.async = true;
                etiqueta.src = 'https://www.googletagmanager.com/gtm.js?id=' + cfg.contenedor;
                primero = document.getElementsByTagName('script')[0];
                if (primero && primero.parentNode) {
                    primero.parentNode.insertBefore(etiqueta, primero);
                } else if (document.head) {
                    document.head.appendChild(etiqueta);
                }
            }

            function esperarIdle() {
                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(function () {
                        cargarGtm();
                    }, { timeout: 2000 });
                    return;
                }
                window.setTimeout(cargarGtm, 1);
            }

            function esperarInteraccion() {
                var eventos = ['scroll', 'pointerdown', 'keydown', 'touchstart'];
                var i, disparado = false;
                function disparar() {
                    if (disparado) {
                        return;
                    }
                    disparado = true;
                    cargarGtm();
                }
                for (i = 0; i < eventos.length; i++) {
                    window.addEventListener(eventos[i], disparar, { passive: true, once: true });
                }
                window.setTimeout(disparar, 5000);
            }

            function cargarDiferido() {
                function arrancar() {
                    if (cfg.retardo === 'interaccion') {
                        esperarInteraccion();
                        return;
                    }
                    esperarIdle();
                }
                if (cargado || diferidoPedido) {
                    return;
                }
                diferidoPedido = true;
                if (document.readyState === 'complete') {
                    arrancar();
                    return;
                }
                window.addEventListener('load', arrancar);
            }

            function pedirInmediato() {
                if (cargado) {
                    return;
                }
                if (!consentEmitido) {
                    pendienteInmediato = true;
                    return;
                }
                cargarGtm();
            }

            function guardarPais(valor) {
                try {
                    sessionStorage.setItem(cfg.clavePais || 'sjb_rgpd_pais', valor);
                } catch (e) {}
            }

            function leerPaisGuardado() {
                var valor = '';
                try {
                    valor = sessionStorage.getItem(cfg.clavePais || 'sjb_rgpd_pais') || '';
                } catch (e) {
                    return '';
                }
                if (valor === 'EU' || valor === 'XX' || codigoIso(valor)) {
                    return valor;
                }
                return '';
            }

            function locDeTrace(texto) {
                var lineas, i, linea;
                if (!texto) {
                    return '';
                }
                lineas = String(texto).split('\n');
                for (i = 0; i < lineas.length; i++) {
                    linea = lineas[i];
                    if (linea.indexOf('loc=') === 0) {
                        return codigoIso(linea.substring(4).trim());
                    }
                }
                return '';
            }

            function paisDeJson(texto) {
                var json;
                try {
                    json = JSON.parse(texto);
                } catch (e) {
                    return '';
                }
                if (!json || typeof json.pais !== 'string') {
                    return '';
                }
                return codigoIso(json.pais);
            }

            function pedirTexto(url, listo) {
                var controlador = typeof AbortController === 'function' ? new AbortController() : null;
                var opciones = { cache: 'no-store' };
                var temporizador;
                var acabado = false;
                function terminar(texto) {
                    if (acabado) {
                        return;
                    }
                    acabado = true;
                    window.clearTimeout(temporizador);
                    listo(texto || '');
                }
                if (typeof window.fetch !== 'function') {
                    listo('');
                    return;
                }
                if (controlador) {
                    opciones.signal = controlador.signal;
                }
                temporizador = window.setTimeout(function () {
                    if (controlador) {
                        controlador.abort();
                    }
                    terminar('');
                }, 1500);
                window.fetch(url, opciones).then(function (respuesta) {
                    if (!respuesta || !respuesta.ok) {
                        terminar('');
                        return null;
                    }
                    return respuesta.text();
                }).then(function (texto) {
                    if (typeof texto === 'string') {
                        terminar(texto);
                    }
                }).catch(function () {
                    terminar('');
                });
            }

            function porZona(listo) {
                var zona = '';
                var marca;
                try {
                    zona = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
                } catch (e) {
                    zona = '';
                }
                marca = zonaRestringida(zona) ? 'EU' : 'XX';
                guardarPais(marca);
                listo(marca);
            }

            function resolverPais(listo) {
                var guardado = leerPaisGuardado();
                if (guardado) {
                    listo(guardado);
                    return;
                }
                if (cfg.fuente === 'cloudflare') {
                    pedirTexto('/cdn-cgi/trace', function (texto) {
                        var codigo = locDeTrace(texto);
                        if (codigo) {
                            guardarPais(codigo);
                            listo(codigo);
                            return;
                        }
                        porZona(listo);
                    });
                    return;
                }
                if (cfg.fuente === 'servidor' && cfg.paisUrl) {
                    pedirTexto(cfg.paisUrl, function (texto) {
                        var codigo = paisDeJson(texto);
                        if (codigo) {
                            guardarPais(codigo);
                            listo(codigo);
                            return;
                        }
                        porZona(listo);
                    });
                    return;
                }
                porZona(listo);
            }

            function cerrarDecision(marca) {
                var estado;
                if (consentEmitido) {
                    if (pendienteInmediato) {
                        cargarGtm();
                    }
                    return;
                }
                estado = leerCookie();
                if (estado.tiene) {
                    emitirCookie(estado);
                } else if (!marca || esRestringido(marca)) {
                    emitirSenales('denied');
                } else {
                    emitirSenales('granted');
                }
                consentEmitido = true;
                if (pendienteInmediato) {
                    cargarGtm();
                    return;
                }
                estado = leerCookie();
                if (estado.tiene) {
                    if (categoriaAceptada(estado)) {
                        cargarDiferido();
                    }
                    return;
                }
                if (marca && !esRestringido(marca)) {
                    cargarDiferido();
                }
            }

            function tieneClase(nodo, clase) {
                return !!(nodo && nodo.className && typeof nodo.className === 'string' && (' ' + nodo.className + ' ').indexOf(' ' + clase + ' ') !== -1);
            }

            function esBotonAceptar(nodo) {
                var clases = [
                    'moove-gdpr-infobar-allow-all',
                    'moove-gdpr-modal-allow-all',
                    'moove-gdpr-modal-save-settings'
                ];
                var i;
                while (nodo && nodo !== document) {
                    for (i = 0; i < clases.length; i++) {
                        if (tieneClase(nodo, clases[i])) {
                            return true;
                        }
                    }
                    nodo = nodo.parentNode;
                }
                return false;
            }

            function esBotonRechazar(nodo) {
                var clases = [
                    'moove-gdpr-infobar-reject-btn',
                    'moove-gdpr-modal-reject-all'
                ];
                var i;
                while (nodo && nodo !== document) {
                    for (i = 0; i < clases.length; i++) {
                        if (tieneClase(nodo, clases[i])) {
                            return true;
                        }
                    }
                    nodo = nodo.parentNode;
                }
                return false;
            }

            function revisarRechazo() {
                var estado = leerCookie();
                if (!estado.tiene) {
                    return;
                }
                if (categoriaAceptada(estado)) {
                    bloqueado = false;
                    return;
                }
                bloqueado = true;
                if (consentEmitido) {
                    window.gtag('consent', 'update', {
                        'ad_storage': 'denied',
                        'ad_user_data': 'denied',
                        'ad_personalization': 'denied',
                        'analytics_storage': 'denied',
                        'personalization_storage': 'denied'
                    });
                }
            }

            var empujar = window.dataLayer.push;
            window.dataLayer.push = function () {
                var i, pieza, inmediato = false, resultado;
                for (i = 0; i < arguments.length; i++) {
                    pieza = arguments[i];
                    if (!pieza || typeof pieza !== 'object') {
                        continue;
                    }
                    if (!categoriaAceptada(cookieInicial) && (pieza.event === 'cookie_consent_update' || (pieza[0] === 'consent' && pieza[1] === 'update'))) {
                        inmediato = true;
                    }
                }
                resultado = empujar.apply(window.dataLayer, arguments);
                if (inmediato) {
                    pedirInmediato();
                }
                return resultado;
            };

            document.addEventListener('click', function (evento) {
                if (!esBotonAceptar(evento.target) && !esBotonRechazar(evento.target)) {
                    return;
                }
                window.setTimeout(function () {
                    if (categoriaAceptada(leerCookie())) {
                        bloqueado = false;
                        pedirInmediato();
                    } else {
                        revisarRechazo();
                    }
                }, 500);
            });

            if (cookieInicial.tiene) {
                cerrarDecision('');
            } else {
                resolverPais(cerrarDecision);
            }
        })();
        /*SJB_RGPD_JS_FIN*/
        </script>
        <?php
    }
}
