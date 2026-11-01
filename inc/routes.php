<?php
/**
 * Adresses propres et traitement des formulaires (Post/Redirect/Get).
 *
 *   /                        accueil
 *   /candidature/{1..6}      étapes du formulaire
 *   /mon-dossier             suivi du candidat
 *   /mon-dossier/fiche       fiche PDF
 *   /reprendre               reprise par numéro et date de naissance
 *   /verifier/{jeton}        contrôle public du QR code
 *   /fichier/{photo|recu}    lecture protégée
 *   /gestion                 espace scolarité
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_ROUTES_VERSION = '2';

function ceb_regles() {
	return array(
		'^candidature/?$'                 => 'index.php?ceb_page=candidature&ceb_arg=1',
		'^candidature/([1-6])/?$'         => 'index.php?ceb_page=candidature&ceb_arg=$matches[1]',
		'^mon-dossier/?$'                 => 'index.php?ceb_page=mon-dossier',
		'^mon-dossier/fiche/?$'           => 'index.php?ceb_page=fiche',
		'^mon-dossier/notifications/?$'   => 'index.php?ceb_page=notifications',
		'^reprendre/?$'                   => 'index.php?ceb_page=reprendre',
		'^quitter/?$'                     => 'index.php?ceb_page=quitter',
		'^verifier/([a-f0-9]{32})/?$'     => 'index.php?ceb_page=verifier&ceb_arg=$matches[1]',
		'^fichier/(photo|recu)/?$'        => 'index.php?ceb_page=fichier&ceb_arg=$matches[1]',
		'^gestion/?$'                     => 'index.php?ceb_page=gestion',
	);
}

add_action( 'init', static function () {
	foreach ( ceb_regles() as $motif => $cible ) {
		add_rewrite_rule( $motif, $cible, 'top' );
	}
	if ( get_option( 'ceb_routes_version' ) !== CEB_ROUTES_VERSION ) {
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
		flush_rewrite_rules();
		update_option( 'ceb_routes_version', CEB_ROUTES_VERSION, false );
	}
} );

add_filter( 'query_vars', static fn( $v ) => array_merge( $v, array( 'ceb_page', 'ceb_arg' ) ) );

/* Formulaires : traités avant tout affichage. */
add_action( 'template_redirect', static function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['ceb_action'] ) ) {
		return;
	}
	if ( ! ceb_csrf_valide() ) {
		ceb_flash( 'erreur', 'La page a expiré. Réessaie.' );
		ceb_rediriger( wp_get_referer() ?: ceb_url() );
	}
	$actions = array(
		'etape'     => 'ceb_action_etape',
		'soumettre' => 'ceb_action_soumettre',
		'reprendre' => 'ceb_action_reprendre',
		'decision'  => 'ceb_action_decision',
		'reglages'  => 'ceb_action_reglages',
		'equipe'    => 'ceb_action_equipe',
	);
	$action = (string) $_POST['ceb_action'];
	if ( isset( $actions[ $action ] ) ) {
		$actions[ $action ]();
	}
	exit;
}, 1 );

add_action( 'template_redirect', static function () {
	$page = get_query_var( 'ceb_page' );
	$arg  = get_query_var( 'ceb_arg' );
	if ( '' === $page ) {
		if ( is_front_page() || is_home() ) {
			/* L'en-tête dépend de la session : pas de copie gardée par le navigateur. */
			nocache_headers();
			ceb_afficher_page( 'accueil' );
		}
		return;
	}
	/* WordPress a déjà conclu à un 404 : ces adresses ne sont pas des contenus à lui. */
	status_header( 200 );
	nocache_headers();
	switch ( $page ) {
		case 'candidature':
			ceb_page_candidature( (int) $arg );
			break;
		case 'mon-dossier':
			if ( ! ceb_dossier_courant() ) {
				ceb_rediriger( ceb_url( 'reprendre' ) );
			}
			ceb_afficher_page( 'mon-dossier' );
			break;
		case 'fiche':
			$d = ceb_dossier_courant();
			if ( ! $d || 'brouillon' === $d->statut ) {
				ceb_rediriger( ceb_url( 'mon-dossier' ) );
			}
			ceb_envoyer_fiche( $d );
			break;
		case 'notifications':
			$d = ceb_dossier_courant();
			if ( ! $d ) {
				ceb_rediriger( ceb_url( 'reprendre' ) );
			}
			if ( isset( $_GET['json'] ) ) {
				ceb_envoyer_json( ceb_non_lues_candidat( $d->id ) );
			}
			ceb_afficher_page( 'notifications' );
			break;
		case 'reprendre':
			ceb_afficher_page( 'reprendre' );
			break;
		case 'quitter':
			unset( $_SESSION['ceb_dossier'], $_SESSION['ceb_choix'] );
			ceb_rediriger( ceb_url() );
			break;
		case 'verifier':
			$GLOBALS['ceb_verifie'] = ceb_dossier_par_jeton( $arg );
			ceb_afficher_page( 'verifier' );
			break;
		case 'fichier':
			ceb_servir_fichier( $arg );
			break;
		case 'gestion':
			if ( ! is_user_logged_in() ) {
				auth_redirect();
			}
			if ( ! ceb_peut_gerer() ) {
				wp_die( 'Cet espace est réservé à la scolarité.', 'Accès refusé', array( 'response' => 403 ) );
			}
			ceb_page_gestion();
			break;
	}
	exit;
}, 5 );

function ceb_afficher_page( $gabarit ) {
	status_header( 200 );
	require CEB_DIR . '/templates/' . $gabarit . '.php';
	exit;
}

/** Aiguille vers l'étape demandée sans laisser sauter une étape incomplète. */
function ceb_page_candidature( $etape ) {
	$dossier = ceb_dossier_courant();
	if ( ! $dossier && 'ouvert' !== ceb_etat_session() ) {
		ceb_flash( 'erreur', 'Les inscriptions ne sont pas ouvertes.' );
		ceb_rediriger( ceb_url() );
	}
	if ( $dossier && ! ceb_modifiable( $dossier ) ) {
		ceb_rediriger( ceb_url( 'mon-dossier' ) );
	}
	if ( ! $dossier && $etape > 2 ) {
		ceb_rediriger( ceb_url( 'candidature/1' ) );
	}
	if ( ! $dossier && 2 === $etape && empty( $_SESSION['ceb_choix'] ) ) {
		ceb_rediriger( ceb_url( 'candidature/1' ) );
	}
	if ( $dossier && $etape > (int) $dossier->etape && $etape > ceb_premiere_etape_manquante( $dossier ) ) {
		ceb_rediriger( ceb_url( 'candidature/' . ceb_premiere_etape_manquante( $dossier ) ) );
	}
	$GLOBALS['ceb_etape'] = $etape;
	ceb_afficher_page( 'candidature' );
}

function ceb_page_gestion() {
	$vue = sanitize_key( $_GET['vue'] ?? 'bord' );
	$droits = array( 'reglages' => 'ceb_regler', 'equipe' => 'ceb_equipe', 'activite' => 'ceb_exporter' );
	if ( isset( $droits[ $vue ] ) ) {
		ceb_exiger( $droits[ $vue ] );
	}
	if ( 'notifs-json' === $vue ) {
		ceb_envoyer_json( ceb_non_lues_equipe(), ceb_derniere_equipe() );
	}
	if ( 'export-csv' === $vue ) {
		ceb_exporter_csv();
	}
	if ( 'export-fiches' === $vue ) {
		ceb_exporter_fiches();
	}
	if ( isset( $_GET['fiche'] ) ) {
		$d = ceb_dossier( (int) $_GET['fiche'] );
		if ( $d ) {
			ceb_envoyer_fiche( $d );
		}
	}
	ceb_afficher_page( 'gestion' );
}
