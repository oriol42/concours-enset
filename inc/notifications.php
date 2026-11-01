<?php
/**
 * Centre de notifications.
 *
 * Le candidat reçoit chaque décision sur son dossier, dans la plateforme et
 * par courriel. L'équipe reçoit les nouvelles candidatures et les corrections ;
 * chacun garde son propre repère de lecture.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

function ceb_notifier( $cible, $dossier, $titre, $texte ) {
	global $wpdb;
	$wpdb->insert( ceb_table( 'notifs' ), array(
		'cible' => $cible, 'dossier_id' => $dossier->id, 'titre' => mb_substr( $titre, 0, 120 ),
		'texte' => mb_substr( $texte, 0, 255 ), 'cree_le' => current_time( 'mysql' ),
	) );
}

/** Notification et courriel : un courriel qui échoue ne bloque jamais la décision. */
function ceb_notifier_candidat( $d, $titre, $texte ) {
	ceb_notifier( 'candidat', $d, $titre, $texte );
	if ( $d->email ) {
		wp_mail( $d->email, $titre . ' (' . $d->numero . ')', $texte . "\n\nSuivre mon dossier : " . ceb_url( 'reprendre' ) . "\n\nENSET d'Ebolowa, service de la scolarité" );
	}
}

function ceb_notifier_equipe( $d, $titre, $texte ) {
	ceb_notifier( 'equipe', $d, $titre, $texte );
}

function ceb_notifs_candidat( $id, $limite = 30 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'candidat' AND dossier_id = %d ORDER BY id DESC LIMIT %d", $id, $limite ) );
}

function ceb_non_lues_candidat( $id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'candidat' AND dossier_id = %d AND lu = 0", $id ) );
}

function ceb_marquer_candidat( $id ) {
	global $wpdb;
	$wpdb->update( ceb_table( 'notifs' ), array( 'lu' => 1 ), array( 'cible' => 'candidat', 'dossier_id' => $id, 'lu' => 0 ) );
}

function ceb_notifs_equipe( $limite = 60 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'equipe' ORDER BY id DESC LIMIT %d", $limite ) );
}

function ceb_repere_equipe() {
	return (int) get_user_meta( get_current_user_id(), 'ceb_notif_vue', true );
}

function ceb_non_lues_equipe() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'equipe' AND id > %d", ceb_repere_equipe() ) );
}

function ceb_marquer_equipe() {
	global $wpdb;
	$dernier = (int) $wpdb->get_var( 'SELECT MAX(id) FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'equipe'" );
	update_user_meta( get_current_user_id(), 'ceb_notif_vue', $dernier );
}

/** Compteur lu par la cloche toutes les 30 secondes. */
function ceb_envoyer_json( $n, $titre = '' ) {
	nocache_headers();
	wp_send_json( array( 'n' => $n, 'titre' => $titre ) );
}

function ceb_derniere_equipe() {
	global $wpdb;
	return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT titre FROM ' . ceb_table( 'notifs' ) . " WHERE cible = 'equipe' AND id > %d ORDER BY id DESC LIMIT 1", ceb_repere_equipe() ) );
}
