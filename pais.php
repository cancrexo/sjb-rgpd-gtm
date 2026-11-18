<?php
/**
 * País del visitante según las variables del servidor.
 *
 * No carga WordPress: la página puede ir cacheada y esta respuesta no.
 *
 * @package sjb-rgpd-gtm
 * @author  Daniel "Cancrexo" Prol <cancrexo@gmail.com>
 */

if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && realpath( (string) $_SERVER['SCRIPT_FILENAME'] ) !== __FILE__ ) {
    return;
}

header( 'Content-Type: application/json; charset=utf-8' );
header( 'Cache-Control: private, no-store, max-age=0' );
header( 'X-Robots-Tag: noindex' );

$pais  = '';
$claves = array( 'GEOIP_COUNTRY_CODE', 'MM_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE' );

foreach ( $claves as $clave ) {
    if ( empty( $_SERVER[ $clave ] ) || ! is_string( $_SERVER[ $clave ] ) ) {
        continue;
    }

    $codigo = strtoupper( trim( $_SERVER[ $clave ] ) );
    if ( 1 === preg_match( '/^[A-Z]{2}$/', $codigo ) && 'XX' !== $codigo ) {
        $pais = $codigo;
        break;
    }
}

echo '{"pais":"' . $pais . '"}';
