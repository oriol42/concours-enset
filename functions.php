<?php
/**
 * Point d'entrée du thème Concours ENSET.
 *
 *   inc/referentiel.php  cycles, diplômes, filières, options, liaisons, centres, mentions
 *   inc/regions.php      régions et départements du Cameroun
 *   inc/reglages.php     session du concours (année, dates, frais, compte, pièces)
 *   inc/base.php         tables propres au concours, accès aux dossiers
 *   inc/session.php      session PHP du candidat, CSRF, messages, limite d'essais
 *   inc/fichiers.php     photo et reçu : contrôle, réencodage, lecture protégée
 *   inc/candidature.php  étapes du formulaire, validation, soumission
 *   inc/fiche-pdf.php    fiche officielle et récépissé en PDF
 *   inc/notifications.php centre de notifications du candidat et de l'équipe
 *   inc/gestion.php      espace scolarité : décisions, statistiques, exports
 *   inc/vues.php         gabarits communs, champs, icônes
 *   inc/routes.php       adresses propres et traitement des formulaires
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

define( 'CEB_VERSION', '1.0.0' );
define( 'CEB_DIR', get_template_directory() );
define( 'CEB_URI', get_template_directory_uri() );

foreach ( array( 'referentiel', 'regions', 'reglages', 'base', 'session', 'fichiers', 'candidature', 'fiche-pdf', 'notifications', 'gestion', 'vues', 'routes' ) as $ceb_module ) {
	require_once CEB_DIR . '/inc/' . $ceb_module . '.php';
}

add_action( 'after_setup_theme', static function () {
	add_theme_support( 'title-tag' );
} );

/* Pas de barre WordPress sur les pages publiques : la plateforme a son propre en-tête. */
add_filter( 'show_admin_bar', '__return_false' );

add_action( 'wp_enqueue_scripts', static function () {
	/* La plateforme n'utilise aucun bloc WordPress : autant de Ko épargnés aux candidats. */
	foreach ( array( 'wp-block-library', 'global-styles', 'classic-theme-styles' ) as $style ) {
		wp_dequeue_style( $style );
	}
	$v = static fn( $f ) => filemtime( CEB_DIR . '/' . $f );
	wp_enqueue_style( 'ceb-polices', CEB_URI . '/assets/css/polices.css', array(), $v( 'assets/css/polices.css' ) );
	wp_enqueue_style( 'ceb-app', CEB_URI . '/assets/css/app.css', array( 'ceb-polices' ), $v( 'assets/css/app.css' ) );
	wp_enqueue_script( 'ceb-app', CEB_URI . '/assets/js/app.js', array(), $v( 'assets/js/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );

	$page = get_query_var( 'ceb_page' ) ?: 'accueil';
	if ( 'accueil' === $page ) {
		wp_enqueue_script( 'gsap', CEB_URI . '/assets/js/vendor/gsap.min.js', array(), '3', array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_enqueue_script( 'gsap-st', CEB_URI . '/assets/js/vendor/ScrollTrigger.min.js', array( 'gsap' ), '3', array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_enqueue_script( 'ceb-accueil', CEB_URI . '/assets/js/accueil.js', array( 'gsap-st' ), $v( 'assets/js/accueil.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
	if ( 'candidature' === $page ) {
		wp_enqueue_script( 'ceb-candidature', CEB_URI . '/assets/js/candidature.js', array(), $v( 'assets/js/candidature.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
	if ( 'gestion' === $page ) {
		wp_enqueue_style( 'ceb-gestion', CEB_URI . '/assets/css/gestion.css', array( 'ceb-app' ), $v( 'assets/css/gestion.css' ) );
		if ( 'bord' === sanitize_key( $_GET['vue'] ?? 'bord' ) && ! isset( $_GET['dossier'] ) ) { // phpcs:ignore -- lecture seule
			wp_enqueue_script( 'chartjs', CEB_URI . '/assets/js/vendor/chart.umd.min.js', array(), '4.5.1', array( 'strategy' => 'defer', 'in_footer' => true ) );
			wp_enqueue_script( 'ceb-bord', CEB_URI . '/assets/js/gestion-bord.js', array( 'chartjs' ), $v( 'assets/js/gestion-bord.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		}
	}
} );
