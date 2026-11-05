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
 * Text Domain: sjb-rgpd-gtm
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package sjb-rgpd-gtm
 * @author  Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', array( 'SJB_RGPD_GTM', 'consent_por_region' ), 0 );

/**
 * Consent Mode para Tag Manager, como complemento de GDPR Cookie Compliance (Moove Agency).
 *
 * Denegado en el EEE, Reino Unido y Suiza; concedido en el resto.
 * Si ya hay decisión guardada, se respeta la cookie del banner.
 */
class SJB_RGPD_GTM {

    /** Contenedor de Google Tag Manager. */
    private const GTM_ID = 'GTM-P49NW7VH';

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
     * Imprime el consentimiento por defecto y el contenedor, antes que el resto de scripts.
     */
    public static function consent_por_region(): void {
        if ( is_admin() ) {
            return;
        }

        remove_action( 'wp_head', array( 'Moove_GDPR_Content', 'gdpr_google_consent_mode2_snippet' ), 1 );

        $regiones = implode( "','", array_map( 'esc_js', self::REGIONES_RESTRINGIDAS ) );
        $gtm_id   = esc_js( self::GTM_ID );
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
