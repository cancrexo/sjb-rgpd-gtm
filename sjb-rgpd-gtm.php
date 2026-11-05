<?php
/**
 * Plugin Name: SJB RGPD para Tag Manager
 * Plugin URI: https://www.sjbdixital.es
 * Description: Complemento de GDPR Cookie Compliance (Moove Agency). Fija el consentimiento por región y carga Google Tag Manager.
 * Version: 1.0.0
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
 * Denegado en el EEE, Reino Unido y Suiza; concedido en el resto.
 * Si ya hay decisión guardada, se respeta la cookie del banner.
 */
class SJB_RGPD_GTM {

    /** Opción donde GDPR Cookie Compliance guarda sus ajustes. */
    private const OPCION_MOOVE = 'moove_gdpr_plugin_settings';

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
     * Pantalla de solo lectura: qué hace el plugin y qué contenedor usa.
     */
    public static function pantalla(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $moove_activo = class_exists( 'Moove_GDPR_Content' );
        $contenedor   = self::id_contenedor_moove();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p>
                <?php esc_html_e( 'Este plugin complementa a GDPR Cookie Compliance (Moove Agency). No muestra el banner de cookies. Fija el consentimiento de Google según la región y carga el contenedor de Tag Manager definido en ese plugin, en Consent Mode v2.', 'sjb-rgpd-gtm' ); ?>
            </p>
            <p>
                <?php esc_html_e( 'GDPR Cookie Compliance tiene que estar instalado y activo. El contenedor se lee de sus ajustes en cada carga. Aquí no se guarda una copia.', 'sjb-rgpd-gtm' ); ?>
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
        </div>
        <?php
    }

    /**
     * Contenedor de Consent Mode v2 en GDPR Cookie Compliance.
     *
     * Vacío si ese plugin no está activo, la integración está apagada o el valor no empieza por GTM-.
     */
    private static function id_contenedor_moove(): string {
        if ( ! class_exists( 'Moove_GDPR_Content' ) ) {
            return '';
        }

        $opciones = get_option( self::OPCION_MOOVE );
        if ( ! is_array( $opciones ) || empty( $opciones['gdin_values'] ) || ! is_string( $opciones['gdin_values'] ) ) {
            return '';
        }

        $valores = json_decode( $opciones['gdin_values'], true );
        if ( ! is_array( $valores ) || ! isset( $valores['gtmc2'] ) ) {
            return '';
        }

        $id = isset( $valores['gtmc2_id'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', trim( (string) $valores['gtmc2_id'] ) ) : '';
        if ( ! is_string( $id ) || 0 !== stripos( $id, 'GTM-' ) ) {
            return '';
        }

        return $id;
    }

    /**
     * Imprime el consentimiento por defecto y el contenedor, antes que el resto de scripts.
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

        $regiones = implode( "','", array_map( 'esc_js', self::REGIONES_RESTRINGIDAS ) );
        $gtm_id   = esc_js( $gtm_id );
        ?>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        (function () {
            var tiene = false, datos = {}, encontrado = null;
            try {
                encontrado = document.cookie.match(/(?:^|;\s*)moove_gdpr_popup=([^;]*)/);
                tiene = !!encontrado;
            } catch (e) {}
            if (encontrado) {
                try {
                    datos = JSON.parse(decodeURIComponent(encontrado[1])) || {};
                } catch (e) {
                    datos = {};
                }
            }
            var estado = function (clave) {
                return tiene && parseInt(datos[clave], 10) === 1 ? 'granted' : 'denied';
            };
            if (tiene) {
                var ads = estado('advanced');
                var estrictas = estado('strict');
                gtag('consent', 'default', {
                    'ad_storage': ads,
                    'ad_user_data': ads,
                    'ad_personalization': ads,
                    'analytics_storage': estado('thirdparty'),
                    'personalization_storage': estado('preference'),
                    'security_storage': estrictas,
                    'functionality_storage': estrictas
                });
                return;
            }
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied',
                'region': ['<?php echo $regiones; ?>']
            });
            gtag('consent', 'default', {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'granted'
            });
        })();
        </script>
        <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','<?php echo $gtm_id; ?>');
        </script>
        <?php
    }
}
