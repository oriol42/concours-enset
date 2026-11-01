<?php
/**
 * Espace scolarité : suivi des dossiers, décisions, statistiques et exports.
 * L'accès suit la capacité « ceb_gerer » du rôle Scolarité concours.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_PAR_PAGE = 30;

const CEB_STATUTS = array(
	'brouillon' => 'En cours de saisie',
	'soumis'    => 'À examiner',
	'valide'    => 'Validé',
	'rejete'    => 'À corriger',
);

const CEB_PAIEMENTS = array(
	'attente' => 'À vérifier',
	'verifie' => 'Vérifié',
	'rejete'  => 'Rejeté',
);

/*
 * Droits :
 *   Direction       consulte dossiers et chiffres, sans rien modifier ;
 *   Agent           traite tout le dossier : paiement, pièces, validation, exports ;
 *   Administrateur  règle en plus la session et gère l'équipe.
 */
const CEB_ROLES = array(
	'ceb_direction' => array( 'nom' => 'Direction', 'aide' => 'Consulte les dossiers et les chiffres, sans rien modifier', 'caps' => array( 'ceb_voir' ) ),
	'ceb_scolarite' => array( 'nom' => 'Agent de scolarité', 'aide' => 'Traite les dossiers : paiements, pièces, validation, exports', 'caps' => array( 'ceb_voir', 'ceb_paiements', 'ceb_pieces', 'ceb_valider', 'ceb_exporter' ) ),
	'ceb_admin'     => array( 'nom' => 'Administrateur', 'aide' => 'Fait tout, règle la session et gère l\'équipe', 'caps' => array( 'ceb_voir', 'ceb_paiements', 'ceb_pieces', 'ceb_valider', 'ceb_exporter', 'ceb_regler', 'ceb_equipe' ) ),
);

add_action( 'after_setup_theme', static function () {
	if ( get_option( 'ceb_role_version' ) === '3' ) {
		return;
	}
	/* Ancien rôle Responsable : fusionné avec Agent de scolarité. */
	foreach ( get_users( array( 'role' => 'ceb_responsable' ) ) as $ancien ) {
		$ancien->set_role( 'ceb_scolarite' );
	}
	remove_role( 'ceb_responsable' );
	foreach ( CEB_ROLES as $role => $r ) {
		remove_role( $role );
		add_role( $role, $r['nom'] . ' (concours)', array_fill_keys( array_merge( array( 'read' ), $r['caps'] ), true ) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( CEB_ROLES['ceb_admin']['caps'] as $cap ) {
			$admin->add_cap( $cap );
		}
	}
	update_option( 'ceb_role_version', '3', false );
} );

function ceb_peut( $cap ) {
	return current_user_can( $cap );
}

function ceb_peut_gerer() {
	return ceb_peut( 'ceb_voir' );
}

function ceb_exiger( $cap ) {
	if ( ! ceb_peut( $cap ) ) {
		wp_die( 'Ton rôle ne permet pas cette action.', 'Accès refusé', array( 'response' => 403 ) );
	}
}

/*
 * Files de travail : un dossier avance d'une file à la suivante.
 * Paiement à vérifier, puis pièces à recevoir à la scolarité, puis prêt à valider.
 */
const CEB_FILES = array(
	'paiements' => array( 'nom' => 'Paiements à vérifier', 'sql' => "statut = 'soumis' AND paiement = 'attente'" ),
	'pieces'    => array( 'nom' => 'Pièces à recevoir', 'sql' => "statut = 'soumis' AND paiement = 'verifie' AND depot_le IS NULL" ),
	'valider'   => array( 'nom' => 'Prêts à valider', 'sql' => "statut = 'soumis' AND paiement = 'verifie' AND depot_le IS NOT NULL" ),
	'corriger'  => array( 'nom' => 'À corriger', 'sql' => "statut = 'rejete'" ),
	'valides'   => array( 'nom' => 'Validés', 'sql' => "statut = 'valide'" ),
	'saisie'    => array( 'nom' => 'En saisie', 'sql' => "statut = 'brouillon'" ),
	'age'       => array( 'nom' => 'Limite d\'âge', 'sql' => "alerte_age = 1 AND statut <> 'brouillon'" ),
);

const CEB_MOTIFS = array( 'Reçu illisible', 'Montant incorrect', 'Numéro de transaction introuvable', 'Photo non conforme', 'Pièce manquante', 'Diplôme non conforme' );

/** Pièces attendues pour ce dossier ; fonctionnaire : l'autorisation s'ajoute. */
function ceb_pieces_dossier( $d ) {
	$pieces = ceb_pieces( $d->cycle );
	if ( $d->fonctionnaire ) {
		$pieces[] = 'L\'autorisation du ministère de tutelle';
	}
	return $pieces;
}

function ceb_pieces_cochees( $d ) {
	return array_map( 'intval', array_filter( explode( ',', (string) $d->pieces_recues ), 'strlen' ) );
}

/** Filtres de la liste, repris de l'adresse et bornés aux valeurs connues. */
function ceb_filtres() {
	$lire = static fn( $cle ) => sanitize_text_field( wp_unslash( (string) ( $_REQUEST[ $cle ] ?? '' ) ) );
	$f    = array(
		'q'        => $lire( 'q' ),
		'file'     => $lire( 'file' ),
		'cycle'    => $lire( 'cycle' ),
		'filiere'  => $lire( 'filiere' ),
		'centre'   => $lire( 'centre' ),
	);
	foreach ( array( 'file' => CEB_FILES, 'cycle' => CEB_CYCLES, 'filiere' => CEB_FILIERES, 'centre' => CEB_CENTRES ) as $cle => $valeurs ) {
		if ( ! isset( $valeurs[ $f[ $cle ] ] ) ) {
			$f[ $cle ] = '';
		}
	}
	return $f;
}

/** @return array{0: string, 1: array} clause WHERE et ses paramètres. */
function ceb_clause( array $f ) {
	global $wpdb;
	$sql  = array( 'annee = %s' );
	$args = array( ceb_reglage( 'annee' ) );
	$map  = array( 'cycle' => 'cycle', 'filiere' => 'filiere', 'centre' => 'centre' );
	foreach ( $map as $cle => $col ) {
		if ( '' !== $f[ $cle ] ) {
			$sql[]  = "$col = %s";
			$args[] = $f[ $cle ];
		}
	}
	if ( $f['file'] ) {
		$sql[] = CEB_FILES[ $f['file'] ]['sql'];
	}
	if ( '' !== $f['q'] ) {
		$like   = '%' . $wpdb->esc_like( $f['q'] ) . '%';
		$sql[]  = '(numero LIKE %s OR noms LIKE %s OR prenoms LIKE %s OR telephone LIKE %s OR cni LIKE %s OR transaction_num LIKE %s)';
		$args   = array_merge( $args, array_fill( 0, 6, $like ) );
	}
	return array( implode( ' AND ', $sql ), $args );
}

function ceb_compter( array $f ) {
	global $wpdb;
	list( $where, $args ) = ceb_clause( $f );
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . ceb_table() . " WHERE $where", $args ) );
}

function ceb_liste( array $f, $limite, $debut = 0 ) {
	global $wpdb;
	list( $where, $args ) = ceb_clause( $f );
	$args[] = $limite;
	$args[] = $debut;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . ceb_table() . " WHERE $where ORDER BY " . ( in_array( $f['file'], array( 'paiements', 'pieces', 'valider' ), true ) ? 'soumis_le ASC' : 'id DESC' ) . ' LIMIT %d OFFSET %d', $args ) );
}

/** Chiffres du tableau de bord, brouillons à part : ils ne sont pas encore des candidatures. */
function ceb_statistiques() {
	global $wpdb;
	$t = ceb_table();
	$a = ceb_reglage( 'annee' );
	$totaux = $wpdb->get_row( $wpdb->prepare(
		"SELECT COUNT(*) total,
			SUM(statut <> 'brouillon') recus,
			SUM(statut = 'brouillon') brouillons,
			SUM(statut = 'valide') valides,
			SUM(statut <> 'brouillon' AND paiement = 'verifie') verifies,
			SUM(statut <> 'brouillon' AND depot_le IS NOT NULL) deposes,
			SUM(statut = 'rejete') rejetes,
			SUM(statut <> 'brouillon' AND paiement = 'attente') paiements,
			SUM(statut <> 'brouillon' AND alerte_age = 1) alertes
		FROM $t WHERE annee = %s", $a
	) );
	$repartir = static function ( $col ) use ( $wpdb, $t, $a ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT $col cle, COUNT(*) n FROM $t WHERE annee = %s AND statut <> 'brouillon' GROUP BY $col ORDER BY n DESC", $a ) );
	};
	$filieres_sexe = $wpdb->get_results( $wpdb->prepare( "SELECT filiere, sexe, COUNT(*) n FROM $t WHERE annee = %s AND statut <> 'brouillon' GROUP BY filiere, sexe", $a ) );
	return array( 'totaux' => $totaux, 'centres' => $repartir( 'centre' ), 'cycles' => $repartir( 'cycle' ), 'sexes' => $repartir( 'sexe' ), 'filieres_sexe' => $filieres_sexe );
}

/** Décisions sur un dossier : chacune suit un droit et s'inscrit au journal. */
function ceb_action_decision() {
	$decision = ceb_post( 'decision' );
	$droits   = array(
		'verifier_paiement' => 'ceb_paiements',
		'rejeter_paiement'  => 'ceb_paiements',
		'pieces'            => 'ceb_pieces',
		'valider'           => 'ceb_valider',
		'rejeter'           => 'ceb_valider',
		'rouvrir'           => 'ceb_valider',
	);
	if ( ! isset( $droits[ $decision ] ) ) {
		ceb_rediriger( ceb_url( 'gestion' ) );
	}
	ceb_exiger( $droits[ $decision ] );
	$dossier = ceb_dossier( (int) ceb_post( 'dossier' ) );
	$motif   = ceb_texte( 'motif', 255 );
	if ( ! $dossier ) {
		ceb_rediriger( ceb_url( 'gestion' ) );
	}
	$retour = ceb_url( 'gestion' ) . '?dossier=' . $dossier->id;
	$auteur = wp_get_current_user()->display_name;
	if ( in_array( $decision, array( 'rejeter_paiement', 'rejeter' ), true ) && '' === $motif ) {
		ceb_flash( 'erreur', 'Indique le motif : le candidat le verra dans son dossier.' );
		ceb_rediriger( $retour );
	}
	switch ( $decision ) {
		case 'verifier_paiement':
			ceb_maj_dossier( $dossier->id, array( 'paiement' => 'verifie' ) );
			ceb_journaliser( $dossier->id, $auteur, 'Paiement vérifié' );
			ceb_notifier_candidat( $dossier, 'Paiement vérifié', 'Ton paiement est vérifié. Dépose maintenant tes pièces à la scolarité.' );
			break;
		case 'rejeter_paiement':
			ceb_maj_dossier( $dossier->id, array( 'paiement' => 'rejete', 'statut' => 'rejete', 'motif' => $motif ) );
			ceb_journaliser( $dossier->id, $auteur, 'Paiement rejeté : ' . $motif );
			ceb_notifier_candidat( $dossier, 'Paiement rejeté', 'Motif : ' . $motif . '. Corrige ton dossier et renvoie-le.' );
			break;
		case 'pieces':
			$total  = count( ceb_pieces_dossier( $dossier ) );
			$avant  = (bool) $dossier->depot_le;
			$coches = array_values( array_filter( array_map( 'intval', (array) ( $_POST['pieces'] ?? array() ) ), static fn( $i ) => $i >= 0 && $i < $total ) );
			ceb_maj_dossier( $dossier->id, array(
				'pieces_recues' => implode( ',', $coches ),
				'depot_le'      => count( $coches ) === $total ? ( $dossier->depot_le ?: current_time( 'mysql' ) ) : null,
			) );
			ceb_journaliser( $dossier->id, $auteur, 'Pièces reçues : ' . count( $coches ) . ' sur ' . $total );
			if ( ! $avant && count( $coches ) === $total ) {
				ceb_notifier_candidat( $dossier, 'Pièces reçues', 'La scolarité a reçu toutes tes pièces.' );
			}
			break;
		case 'valider':
			if ( 'verifie' !== $dossier->paiement ) {
				ceb_flash( 'erreur', 'Vérifie d\'abord le paiement.' );
				ceb_rediriger( $retour );
			}
			if ( ! $dossier->depot_le ) {
				ceb_flash( 'erreur', 'Toutes les pièces doivent être pointées avant la validation.' );
				ceb_rediriger( $retour );
			}
			ceb_maj_dossier( $dossier->id, array( 'statut' => 'valide', 'motif' => '' ) );
			ceb_journaliser( $dossier->id, $auteur, 'Dossier validé' );
			ceb_notifier_candidat( $dossier, 'Dossier validé', 'Ton dossier est validé : tu es admis à composer au centre de ' . ( CEB_CENTRES[ $dossier->centre ] ?? '' ) . '.' );
			break;
		case 'rejeter':
			ceb_maj_dossier( $dossier->id, array( 'statut' => 'rejete', 'motif' => $motif ) );
			ceb_journaliser( $dossier->id, $auteur, 'Renvoyé au candidat : ' . $motif );
			ceb_notifier_candidat( $dossier, 'Dossier à corriger', 'Motif : ' . $motif . '. Corrige ton dossier et renvoie-le.' );
			break;
		case 'rouvrir':
			ceb_maj_dossier( $dossier->id, array( 'statut' => 'soumis' ) );
			ceb_journaliser( $dossier->id, $auteur, 'Dossier rouvert' );
			ceb_notifier_candidat( $dossier, 'Dossier rouvert', 'La scolarité a rouvert ton dossier pour le réexaminer.' );
			break;
	}
	ceb_flash( 'succes', 'Décision enregistrée.' );
	$suivant = ceb_post( 'suivant' ) ? ceb_suivant( $dossier->id, ceb_post( 'suivant' ) ) : 0;
	ceb_rediriger( $suivant ? ceb_url( 'gestion' ) . '?dossier=' . $suivant : $retour );
}

/** Ajout d'un membre, changement de rôle ou retrait d'accès. */
function ceb_action_equipe() {
	ceb_exiger( 'ceb_equipe' );
	$retour = add_query_arg( 'vue', 'equipe', ceb_url( 'gestion' ) );
	$action = ceb_post( 'operation' );
	$role   = ceb_post( 'role' );
	if ( 'ajouter' === $action ) {
		$email = sanitize_email( ceb_post( 'email' ) );
		$nom   = ceb_texte( 'nom', 80 );
		if ( ! is_email( $email ) || mb_strlen( $nom ) < 2 || ! isset( CEB_ROLES[ $role ] ) ) {
			ceb_flash( 'erreur', 'Indique le nom, un e-mail valide et le rôle.' );
			ceb_rediriger( $retour );
		}
		if ( email_exists( $email ) ) {
			ceb_flash( 'erreur', 'Un compte existe déjà avec cet e-mail.' );
			ceb_rediriger( $retour );
		}
		$mdp = wp_generate_password( 14, false );
		$id  = wp_insert_user( array( 'user_login' => $email, 'user_email' => $email, 'display_name' => $nom, 'first_name' => $nom, 'user_pass' => $mdp, 'role' => $role ) );
		if ( is_wp_error( $id ) ) {
			ceb_flash( 'erreur', $id->get_error_message() );
			ceb_rediriger( $retour );
		}
		ceb_flash( 'succes', $nom . ' peut se connecter avec ' . $email . ' et le mot de passe provisoire ' . $mdp . '. Transmets-le maintenant : il ne s\'affichera plus.' );
		ceb_rediriger( $retour );
	}
	$user = get_userdata( (int) ceb_post( 'membre' ) );
	/* Son propre compte et les administrateurs WordPress restent hors de portée. */
	if ( ! $user || get_current_user_id() === $user->ID || user_can( $user, 'manage_options' ) ) {
		ceb_flash( 'erreur', 'Ce compte ne peut pas être modifié ici.' );
		ceb_rediriger( $retour );
	}
	if ( 'role' === $action && isset( CEB_ROLES[ $role ] ) ) {
		$user->set_role( $role );
		ceb_flash( 'succes', 'Rôle mis à jour.' );
	} elseif ( 'retirer' === $action ) {
		$user->set_role( '' );
		ceb_flash( 'succes', 'Accès retiré.' );
	}
	ceb_rediriger( $retour );
}

/** Membres de l'équipe : les comptes qui ont un rôle du concours. */
function ceb_equipe() {
	return get_users( array( 'role__in' => array_keys( CEB_ROLES ), 'orderby' => 'display_name' ) );
}

function ceb_role_de( $user ) {
	foreach ( array_keys( CEB_ROLES ) as $role ) {
		if ( in_array( $role, (array) $user->roles, true ) ) {
			return $role;
		}
	}
	return '';
}

function ceb_action_reglages() {
	ceb_exiger( 'ceb_regler' );
	$annee = ceb_post( 'annee' );
	if ( ! preg_match( '/^\d{4}\/\d{4}$/', $annee ) ) {
		ceb_flash( 'erreur', 'Saisis l\'année au format 2027/2028.' );
		ceb_rediriger( ceb_url( 'gestion' ) . '?vue=reglages' );
	}
	$date = static fn( $cle ) => ceb_date_valide( ceb_post( $cle ) ) ? ceb_post( $cle ) : '';
	$liste = static fn( $cle ) => array_values( array_map( 'sanitize_key', array_map( 'strval', (array) ( $_POST[ $cle ] ?? array() ) ) ) );
	$centres_ouverts = array_map( 'strtoupper', $liste( 'centres' ) );
	$options_ouvertes = array_map( 'strtoupper', $liste( 'options' ) );
	ceb_enregistrer_reglages( array(
		'annee'           => $annee,
		'ouverture'       => $date( 'ouverture' ),
		'cloture'         => $date( 'cloture' ),
		'date_cycle1'     => $date( 'date_cycle1' ),
		'date_cycle2'     => $date( 'date_cycle2' ),
		'date_age'        => $date( 'date_age' ) ?: ceb_reglage( 'date_age' ),
		'frais'           => max( 0, (int) ceb_post( 'frais' ) ),
		'banque'          => ceb_texte( 'banque', 60 ),
		'compte'          => ceb_texte( 'compte', 60 ),
		'age_cycle1'      => max( 15, (int) ceb_post( 'age_cycle1' ) ),
		'age_cycle2'      => max( 15, (int) ceb_post( 'age_cycle2' ) ),
		'telephone'       => ceb_texte( 'telephone', 60 ),
		'email'           => sanitize_email( ceb_post( 'email' ) ),
		'centres_fermes'  => array_values( array_diff( array_keys( CEB_CENTRES ), $centres_ouverts ) ),
		'options_fermees' => array_values( array_diff( array_keys( CEB_OPTIONS ), $options_ouvertes ) ),
	) );
	ceb_flash( 'succes', 'Réglages enregistrés.' );
	ceb_rediriger( ceb_url( 'gestion' ) . '?vue=reglages' );
}

/** Neutralise les cellules qu'un tableur prendrait pour une formule. */
function ceb_cellule( $valeur ) {
	$v = (string) $valeur;
	return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
}

function ceb_exporter_csv() {
	ceb_exiger( 'ceb_exporter' );
	$lignes = ceb_liste( ceb_filtres(), 100000 );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="candidats-' . str_replace( '/', '-', ceb_reglage( 'annee' ) ) . '.csv"' );
	$sortie = fopen( 'php://output', 'w' );
	fwrite( $sortie, "\xEF\xBB\xBF" ); /* Excel lit alors les accents correctement. */
	$colonne = array( 'Numéro', 'Noms', 'Prénoms', 'Date de naissance', 'Lieu de naissance', 'Sexe', 'Nationalité', 'Téléphone', 'E-mail', 'CNI', 'Cycle', 'Diplôme', 'Filière', 'Option', 'Centre', 'Langue', 'Transaction', 'Paiement', 'Statut', 'Alerte âge', 'Envoyé le' );
	fputcsv( $sortie, $colonne, ';', '"', '' );
	foreach ( $lignes as $d ) {
		fputcsv( $sortie, array_map( 'ceb_cellule', array(
			$d->numero, $d->noms, $d->prenoms, ceb_pdf_date( $d->date_naissance ), $d->lieu_naissance,
			CEB_SEXES[ $d->sexe ] ?? '', $d->nationalite, $d->telephone, $d->email, $d->cni,
			CEB_CYCLES[ $d->cycle ]['court'] ?? '', CEB_DIPLOMES[ $d->diplome ] ?? '', CEB_FILIERES[ $d->filiere ] ?? '',
			CEB_OPTIONS[ $d->option_code ] ?? '', CEB_CENTRES[ $d->centre ] ?? '', CEB_LANGUES[ $d->langue ] ?? '',
			$d->transaction_num, CEB_PAIEMENTS[ $d->paiement ] ?? '', CEB_STATUTS[ $d->statut ] ?? '',
			$d->alerte_age ? 'Oui' : '', $d->soumis_le,
		) ), ';', '"', '' );
	}
	fclose( $sortie );
	exit;
}

function ceb_exporter_fiches() {
	ceb_exiger( 'ceb_exporter' );
	$f = ceb_filtres();
	$liste = array_values( array_filter( ceb_liste( $f, 300 ), static fn( $d ) => 'brouillon' !== $d->statut ) );
	if ( ! $liste ) {
		ceb_flash( 'erreur', 'Aucune fiche à imprimer avec ces filtres.' );
		ceb_rediriger( ceb_url( 'gestion' ) . '?vue=dossiers' );
	}
	set_time_limit( 180 );
	ceb_envoyer_fiches( $liste );
}

/** Candidatures envoyées par jour sur les $jours derniers jours, jours vides compris. */
function ceb_par_jour( $jours = 30 ) {
	global $wpdb;
	$lignes = $wpdb->get_results( $wpdb->prepare(
		'SELECT DATE(soumis_le) jour, COUNT(*) n FROM ' . ceb_table() . " WHERE annee = %s AND statut <> 'brouillon' AND soumis_le >= %s GROUP BY jour",
		ceb_reglage( 'annee' ), wp_date( 'Y-m-d', time() - ( $jours - 1 ) * DAY_IN_SECONDS ) . ' 00:00:00'
	), OBJECT_K );
	$serie = array();
	for ( $i = $jours - 1; $i >= 0; $i-- ) {
		$jour           = wp_date( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
		$serie[ $jour ] = (int) ( $lignes[ $jour ]->n ?? 0 );
	}
	return $serie;
}

/** Les dossiers qui attendent depuis le plus longtemps dans une file. */
function ceb_file( $file, $limite = 6 ) {
	return ceb_liste( array_merge( array_fill_keys( array( 'q', 'cycle', 'filiere', 'centre' ), '' ), array( 'file' => $file ) ), $limite );
}

/** Dossier suivant de la même file, pour enchaîner le traitement. */
function ceb_suivant( $id, $file = 'paiements' ) {
	global $wpdb;
	if ( ! isset( CEB_FILES[ $file ] ) ) {
		return 0;
	}
	return (int) $wpdb->get_var( $wpdb->prepare(
		'SELECT id FROM ' . ceb_table() . ' WHERE annee = %s AND ' . CEB_FILES[ $file ]['sql'] . ' AND id <> %d ORDER BY soumis_le ASC LIMIT 1',
		ceb_reglage( 'annee' ), $id
	) );
}

/** Nombre de dossiers de chaque file. */
function ceb_compteurs() {
	global $wpdb;
	$champs = 'COUNT(*) tous';
	foreach ( CEB_FILES as $cle => $f ) {
		$champs .= ', SUM(' . $f['sql'] . ') ' . $cle;
	}
	return $wpdb->get_row( $wpdb->prepare( "SELECT $champs FROM " . ceb_table() . ' WHERE annee = %s', ceb_reglage( 'annee' ) ) );
}

/** Initiales d'un candidat, quand il n'a pas encore de photo. */
function ceb_initiales( $d ) {
	$mots = preg_split( '/\s+/', trim( $d->noms . ' ' . $d->prenoms ), -1, PREG_SPLIT_NO_EMPTY );
	return mb_substr( $mots[0] ?? '?', 0, 1 ) . mb_substr( $mots[1] ?? '', 0, 1 );
}

function ceb_vignette( $d, $taille = 40 ) {
	if ( $d->photo ) {
		printf( '<img class="vignette" src="%s" alt="" width="%2$d" height="%2$d" loading="lazy">', esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/photo' ) ) ), (int) $taille );
	} else {
		printf( '<span class="vignette vignette--texte" style="width:%1$dpx;height:%1$dpx" aria-hidden="true">%2$s</span>', (int) $taille, esc_html( ceb_initiales( $d ) ) );
	}
}

/** Étiquette de statut : toujours un mot, jamais la couleur seule. */
function ceb_etiquette( $classe, $texte ) {
	$icone = array( 'verifie' => 'check', 'valide' => 'check', 'rejete' => 'alerte', 'attente' => 'horloge', 'soumis' => 'horloge' )[ $classe ] ?? 'crayon';
	printf( '<span class="etiquette etiquette--%s">%s%s</span>', esc_attr( $classe ), ceb_icone( $icone, 13 ), esc_html( $texte ) );
}
