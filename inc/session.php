<?php
/**
 * Session du candidat (cookie HttpOnly), CSRF, messages flash et limite
 * d'essais. Un candidat n'a pas de mot de passe : il reprend son dossier avec
 * son numéro et sa date de naissance.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_ESSAIS_MAX    = 6;
const CEB_ESSAIS_DUREE  = 15 * MINUTE_IN_SECONDS;

add_action( 'init', static function () {
	if ( PHP_SESSION_ACTIVE === session_status() || headers_sent() || wp_doing_cron() ) {
		return;
	}
	session_name( 'ceb_concours' );
	session_set_cookie_params( array(
		'lifetime' => 0,
		'path'     => COOKIEPATH ?: '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
	session_start();
}, 1 );

function ceb_jeton_csrf() {
	if ( empty( $_SESSION['ceb_csrf'] ) ) {
		$_SESSION['ceb_csrf'] = bin2hex( random_bytes( 32 ) );
	}
	return $_SESSION['ceb_csrf'];
}

function ceb_champ_csrf() {
	echo '<input type="hidden" name="ceb_csrf" value="' . esc_attr( ceb_jeton_csrf() ) . '">';
}

function ceb_csrf_valide() {
	$recu = (string) ( $_POST['ceb_csrf'] ?? '' );
	return '' !== $recu && ! empty( $_SESSION['ceb_csrf'] ) && hash_equals( $_SESSION['ceb_csrf'], $recu );
}

function ceb_flash( $type, $message ) {
	$_SESSION['ceb_flash'][] = array( $type, $message );
}

function ceb_lire_flash() {
	$messages = $_SESSION['ceb_flash'] ?? array();
	unset( $_SESSION['ceb_flash'] );
	return $messages;
}

function ceb_memoriser( array $saisie, array $erreurs ) {
	$_SESSION['ceb_saisie']  = $saisie;
	$_SESSION['ceb_erreurs'] = $erreurs;
}

/** @return array{0: array, 1: array} saisie, erreurs de l'envoi précédent. */
function ceb_reprendre() {
	$r = array( $_SESSION['ceb_saisie'] ?? array(), $_SESSION['ceb_erreurs'] ?? array() );
	unset( $_SESSION['ceb_saisie'], $_SESSION['ceb_erreurs'] );
	return $r;
}

function ceb_rediriger( $url ) {
	wp_safe_redirect( $url, 303 );
	exit;
}

function ceb_dossier_courant() {
	$id = (int) ( $_SESSION['ceb_dossier'] ?? 0 );
	return $id ? ceb_dossier( $id ) : null;
}

function ceb_ouvrir_dossier( $id ) {
	session_regenerate_id( true );
	$_SESSION['ceb_dossier'] = (int) $id;
}

function ceb_ip() {
	return (string) ( $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '' );
}

/** Vrai si cette clé (adresse IP, numéro) a déjà épuisé ses essais. */
function ceb_essais_bloques( $cle ) {
	return (int) get_transient( 'ceb_essais_' . md5( $cle ) ) >= CEB_ESSAIS_MAX;
}

function ceb_essai_rate( $cle ) {
	$t = 'ceb_essais_' . md5( $cle );
	set_transient( $t, (int) get_transient( $t ) + 1, CEB_ESSAIS_DUREE );
}
