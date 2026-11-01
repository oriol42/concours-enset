<?php
/**
 * Réglages d'une session du concours, modifiables par la scolarité sans
 * toucher au code. Les valeurs par défaut viennent de l'arrêté 2026/2027 :
 * à vérifier à chaque nouvel arrêté, surtout la limite d'âge et le compte.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_OPTION_REGLAGES = 'ceb_session';

function ceb_reglages_defaut() {
	return array(
		'annee'           => '2027/2028',
		'ouverture'       => '',   /* AAAA-MM-JJ ; vide : ouvert dès maintenant */
		'cloture'         => '',   /* AAAA-MM-JJ ; vide : pas de date affichée */
		'date_cycle1'     => '',
		'date_cycle2'     => '',
		'frais'           => 20000,
		'banque'          => 'SCB Cameroun',
		'compte'          => '10002-00059-90001427012-73',
		'age_cycle1'      => 29,
		'age_cycle2'      => 32,
		'date_age'        => '2027-01-01',
		'centres_fermes'  => array(),
		'options_fermees' => array(),
		'telephone'       => '',
		'email'           => '',
	);
}

function ceb_reglages() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = wp_parse_args( (array) get_option( CEB_OPTION_REGLAGES, array() ), ceb_reglages_defaut() );
	}
	return $cache;
}

function ceb_reglage( $cle ) {
	return ceb_reglages()[ $cle ];
}

function ceb_enregistrer_reglages( array $valeurs ) {
	update_option( CEB_OPTION_REGLAGES, array_merge( ceb_reglages(), $valeurs ), false );
}

/** Aujourd'hui, à l'heure du Cameroun. */
function ceb_aujourdhui() {
	return ( new DateTimeImmutable( 'now', new DateTimeZone( 'Africa/Douala' ) ) )->format( 'Y-m-d' );
}

/** « a_venir », « ouvert » ou « clos » : la clôture vaut jusqu'à la fin du jour indiqué. */
function ceb_etat_session() {
	$jour = ceb_aujourdhui();
	if ( ceb_reglage( 'ouverture' ) && $jour < ceb_reglage( 'ouverture' ) ) {
		return 'a_venir';
	}
	if ( ceb_reglage( 'cloture' ) && $jour > ceb_reglage( 'cloture' ) ) {
		return 'clos';
	}
	return 'ouvert';
}

/** Préfixe des numéros : UEB-2028- pour 2027/2028, comme sur les fiches de l'ENSET. */
function ceb_prefixe_numero() {
	return 'UEB-' . substr( ceb_reglage( 'annee' ), -4 ) . '-';
}

function ceb_date_fr( $iso ) {
	static $mois = array( 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre' );
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $iso );
	return $d ? ltrim( $d->format( 'd' ), '0' ) . ' ' . $mois[ (int) $d->format( 'n' ) - 1 ] . ' ' . $d->format( 'Y' ) : '';
}

function ceb_fcfa( $montant ) {
	return number_format( (float) $montant, 0, ',', ' ' ) . ' FCFA';
}

/**
 * Pièces à déposer, d'après l'arrêté. Le dépôt reste physique : la plateforme
 * prépare la fiche et le dépôt, elle ne remplace pas les originaux certifiés.
 */
function ceb_pieces( $cycle ) {
	$notes = 'CYC2' === $cycle
		? 'Les relevés de notes de Licence (niveaux I, II et III)'
		: 'Les relevés de notes du Probatoire ou GCE O/L, et du Baccalauréat ou GCE A/L';
	return array(
		'La fiche de candidature imprimée et signée',
		'Une copie certifiée de l\'acte de naissance',
		$notes,
		'Une copie certifiée du diplôme présenté ou l\'attestation de réussite',
		'Un certificat médical délivré par un médecin de l\'Administration',
		'Un extrait de casier judiciaire, bulletin n°3',
		'Le reçu de paiement des frais de concours',
		'Une enveloppe A4 timbrée portant ton adresse',
		'Deux photos d\'identité 4 x 4',
	);
}
