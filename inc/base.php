<?php
/**
 * Base du concours : deux tables propres, créées à l'activation du thème,
 * sans lien avec la plateforme d'inscription universitaire.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_BASE_VERSION = '4';

function ceb_table( $nom = 'dossiers' ) {
	global $wpdb;
	return $wpdb->prefix . 'ceb_' . $nom;
}

function ceb_schema() {
	global $wpdb;
	$charset = $wpdb->get_charset_collate();
	$d = ceb_table();
	$j = ceb_table( 'journal' );
	return array(
		"CREATE TABLE IF NOT EXISTS $d (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			numero VARCHAR(20) NULL,
			annee VARCHAR(9) NOT NULL,
			jeton CHAR(32) NOT NULL,
			etape TINYINT UNSIGNED NOT NULL DEFAULT 1,
			statut VARCHAR(12) NOT NULL DEFAULT 'brouillon',
			motif VARCHAR(255) NOT NULL DEFAULT '',
			paiement VARCHAR(12) NOT NULL DEFAULT 'attente',
			cycle VARCHAR(4) NOT NULL DEFAULT '',
			diplome VARCHAR(12) NOT NULL DEFAULT '',
			filiere VARCHAR(10) NOT NULL DEFAULT '',
			option_code VARCHAR(6) NOT NULL DEFAULT '',
			centre VARCHAR(6) NOT NULL DEFAULT '',
			langue CHAR(1) NOT NULL DEFAULT '1',
			noms VARCHAR(120) NOT NULL DEFAULT '',
			prenoms VARCHAR(120) NOT NULL DEFAULT '',
			date_naissance DATE NULL,
			lieu_naissance VARCHAR(120) NOT NULL DEFAULT '',
			sexe CHAR(1) NOT NULL DEFAULT '',
			nationalite VARCHAR(60) NOT NULL DEFAULT '',
			marital CHAR(1) NOT NULL DEFAULT '1',
			fonctionnaire TINYINT(1) NOT NULL DEFAULT 0,
			handicap TINYINT(1) NOT NULL DEFAULT 0,
			telephone VARCHAR(20) NOT NULL DEFAULT '',
			email VARCHAR(120) NOT NULL DEFAULT '',
			adresse VARCHAR(160) NOT NULL DEFAULT '',
			region VARCHAR(2) NOT NULL DEFAULT '',
			departement VARCHAR(40) NOT NULL DEFAULT '',
			arrondissement VARCHAR(80) NOT NULL DEFAULT '',
			cni VARCHAR(20) NULL,
			cni_date DATE NULL,
			cni_lieu VARCHAR(80) NOT NULL DEFAULT '',
			urgence_nom VARCHAR(120) NOT NULL DEFAULT '',
			urgence_tel VARCHAR(20) NOT NULL DEFAULT '',
			etablissement VARCHAR(160) NOT NULL DEFAULT '',
			annee_diplome SMALLINT UNSIGNED NULL,
			mention VARCHAR(8) NOT NULL DEFAULT '',
			photo VARCHAR(80) NOT NULL DEFAULT '',
			recu VARCHAR(80) NOT NULL DEFAULT '',
			recu_type VARCHAR(30) NOT NULL DEFAULT '',
			recu_empreinte CHAR(40) NOT NULL DEFAULT '',
			transaction_num VARCHAR(30) NULL,
			alerte_age TINYINT(1) NOT NULL DEFAULT 0,
			pieces_recues VARCHAR(120) NOT NULL DEFAULT '',
			depot_le DATETIME NULL,
			soumis_le DATETIME NULL,
			cree_le DATETIME NOT NULL,
			modifie_le DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY numero (numero),
			UNIQUE KEY jeton (jeton),
			UNIQUE KEY cni_annee (annee, cni),
			UNIQUE KEY transaction_annee (annee, transaction_num),
			KEY liste (annee, statut, paiement),
			KEY telephone (telephone)
		) $charset",
		"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ceb_notifs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			cible VARCHAR(10) NOT NULL,
			dossier_id BIGINT UNSIGNED NOT NULL,
			titre VARCHAR(120) NOT NULL,
			texte VARCHAR(255) NOT NULL,
			lu TINYINT(1) NOT NULL DEFAULT 0,
			cree_le DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY boite (cible, dossier_id, lu)
		) $charset",
		"CREATE TABLE IF NOT EXISTS $j (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			dossier_id BIGINT UNSIGNED NOT NULL,
			auteur VARCHAR(60) NOT NULL,
			action VARCHAR(160) NOT NULL,
			cree_le DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY dossier (dossier_id)
		) $charset",
	);
}

/* Les tables se créent seules, à l'activation comme après une mise à jour du thème. */
add_action( 'after_setup_theme', static function () {
	if ( get_option( 'ceb_base_version' ) === CEB_BASE_VERSION ) {
		return;
	}
	global $wpdb;
	foreach ( ceb_schema() as $sql ) {
		$wpdb->query( $sql );
	}
	/* Bases créées avant le contrôle des pièces. */
	if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . ceb_table() . " LIKE 'depot_le'" ) ) {
		$wpdb->query( 'ALTER TABLE ' . ceb_table() . " ADD pieces_recues VARCHAR(120) NOT NULL DEFAULT '', ADD depot_le DATETIME NULL" );
	}
	if ( ! $wpdb->get_var( 'SHOW COLUMNS FROM ' . ceb_table() . " LIKE 'recu_empreinte'" ) ) {
		$wpdb->query( 'ALTER TABLE ' . ceb_table() . " ADD recu_empreinte CHAR(40) NOT NULL DEFAULT ''" );
	}
	update_option( 'ceb_base_version', CEB_BASE_VERSION, false );
} );

function ceb_dossier( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ceb_table() . ' WHERE id = %d', $id ) );
}

function ceb_dossier_par_numero( $numero ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ceb_table() . ' WHERE numero = %s', $numero ) );
}

function ceb_dossier_par_jeton( $jeton ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ceb_table() . ' WHERE jeton = %s', $jeton ) );
}

/**
 * Crée le dossier à la première étape réellement remplie : jamais à la simple
 * visite d'une page. Les fichiers de l'ancienne plateforme comptent 2 668
 * inscriptions vides sur 4 524 faute de cette règle.
 */
function ceb_creer_dossier( array $donnees ) {
	global $wpdb;
	$maintenant = current_time( 'mysql' );
	$wpdb->insert( ceb_table(), array_merge( $donnees, array(
		'annee'      => ceb_reglage( 'annee' ),
		'jeton'      => bin2hex( random_bytes( 16 ) ),
		'cree_le'    => $maintenant,
		'modifie_le' => $maintenant,
	) ) );
	$id = (int) $wpdb->insert_id;
	if ( ! $id ) {
		return 0;
	}
	$wpdb->update( ceb_table(), array( 'numero' => ceb_prefixe_numero() . str_pad( (string) $id, 6, '0', STR_PAD_LEFT ) ), array( 'id' => $id ) );
	return $id;
}

function ceb_maj_dossier( $id, array $donnees ) {
	global $wpdb;
	$donnees['modifie_le'] = current_time( 'mysql' );
	return false !== $wpdb->update( ceb_table(), $donnees, array( 'id' => $id ) );
}

function ceb_journaliser( $dossier_id, $auteur, $action ) {
	global $wpdb;
	$wpdb->insert( ceb_table( 'journal' ), array(
		'dossier_id' => $dossier_id, 'auteur' => mb_substr( $auteur, 0, 60 ),
		'action' => mb_substr( $action, 0, 160 ), 'cree_le' => current_time( 'mysql' ),
	) );
}

function ceb_journal( $dossier_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ceb_table( 'journal' ) . ' WHERE dossier_id = %d ORDER BY id DESC', $dossier_id ) );
}

/** Dernières actions, pour le journal d'activité. */
function ceb_activite( $limite = 120 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare(
		'SELECT j.*, d.numero, d.noms, d.prenoms FROM ' . ceb_table( 'journal' ) . ' j LEFT JOIN ' . ceb_table() . ' d ON d.id = j.dossier_id ORDER BY j.id DESC LIMIT %d',
		$limite
	) );
}
