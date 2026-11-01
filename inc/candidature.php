<?php
/**
 * Formulaire de candidature : six étapes enregistrées une à une, pour qu'une
 * coupure de réseau ne fasse jamais perdre plus d'une page de saisie.
 *
 * Chaque valeur est revérifiée ici : le navigateur n'aide qu'à aller plus vite.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_ETAPES = array(
	1 => 'Concours',
	2 => 'Identité',
	3 => 'Coordonnées',
	4 => 'Diplôme',
	5 => 'Paiement',
	6 => 'Vérification',
);

function ceb_post( $cle ) {
	return trim( wp_unslash( (string) ( $_POST[ $cle ] ?? '' ) ) );
}

/** Espaces réduits, balises retirées, longueur bornée. */
function ceb_texte( $cle, $max = 120 ) {
	return mb_substr( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( ceb_post( $cle ) ) ), 0, $max );
}

function ceb_date_valide( $valeur ) {
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $valeur );
	return $d && $d->format( 'Y-m-d' ) === $valeur ? $d : null;
}

/** Numéro normalisé, sans indicatif pour le Cameroun ; vide s'il est invalide. */
function ceb_normaliser_telephone( $valeur ) {
	$brut = preg_replace( '/[\s.\-()]/', '', (string) $valeur );
	$brut = preg_replace( '/^00/', '+', $brut );
	if ( preg_match( '/^(?:\+?237)?([26]\d{8})$/', $brut, $m ) ) {
		return $m[1];
	}
	return preg_match( '/^\+(?!237)\d{8,15}$/', $brut ) ? $brut : '';
}

/** Âge révolu à une date donnée. */
function ceb_age_au( $naissance, $date ) {
	$a = ceb_date_valide( $naissance );
	$b = ceb_date_valide( $date );
	return $a && $b ? (int) $a->diff( $b )->y : 0;
}

function ceb_limite_age( $cycle ) {
	return (int) ceb_reglage( 'CYC2' === $cycle ? 'age_cycle2' : 'age_cycle1' );
}

/** La limite d'âge ne vise pas les fonctionnaires (arrêté, article 2). */
function ceb_depasse_age( $naissance, $cycle, $fonctionnaire ) {
	return ! $fonctionnaire && ceb_age_au( $naissance, ceb_reglage( 'date_age' ) ) > ceb_limite_age( $cycle );
}

/* ---------- Validation par étape : renvoie array( données, erreurs ) ---------- */

function ceb_valider_etape1() {
	$d = array(
		'cycle'   => ceb_post( 'cycle' ),
		'diplome' => ceb_post( 'diplome' ),
		'centre'  => ceb_post( 'centre' ),
		'langue'  => ceb_post( 'langue' ),
	);
	list( $d['filiere'], $d['option_code'] ) = array_pad( explode( '|', ceb_post( 'parcours' ), 2 ), 2, '' );
	$e = array();
	if ( ! isset( CEB_CYCLES[ $d['cycle'] ] ) ) {
		$e['cycle'] = 'Choisis ton cycle.';
	}
	if ( ! isset( CEB_DIPLOMES[ $d['diplome'] ] ) || ceb_cycle_du_diplome( $d['diplome'] ) !== $d['cycle'] ) {
		$e['diplome'] = 'Choisis le diplôme que tu présentes.';
	} elseif ( ! ceb_liaison_valide( $d['diplome'], $d['filiere'], $d['option_code'] ) ) {
		$e['parcours'] = 'Choisis une filière ouverte à ton diplôme.';
	}
	if ( ! isset( ceb_centres_ouverts()[ $d['centre'] ] ) ) {
		$e['centre'] = 'Choisis ton centre d\'examen.';
	}
	if ( ! isset( CEB_LANGUES[ $d['langue'] ] ) ) {
		$e['langue'] = 'Choisis ta langue de composition.';
	}
	return array( $d, $e );
}

function ceb_valider_etape2() {
	$d = array(
		'noms'           => mb_strtoupper( ceb_texte( 'noms' ) ),
		'prenoms'        => mb_strtoupper( ceb_texte( 'prenoms' ) ),
		'date_naissance' => ceb_post( 'date_naissance' ),
		'lieu_naissance' => mb_strtoupper( ceb_texte( 'lieu_naissance' ) ),
		'sexe'           => ceb_post( 'sexe' ),
		'nationalite'    => ceb_texte( 'nationalite', 60 ),
		'marital'        => ceb_post( 'marital' ),
		'fonctionnaire'  => '1' === ceb_post( 'fonctionnaire' ) ? 1 : 0,
		'handicap'       => '1' === ceb_post( 'handicap' ) ? 1 : 0,
	);
	$e = array();
	foreach ( array( 'noms' => 'Indique ton nom.', 'lieu_naissance' => 'Indique ton lieu de naissance.', 'nationalite' => 'Indique ta nationalité.' ) as $cle => $message ) {
		if ( mb_strlen( $d[ $cle ] ) < 2 ) {
			$e[ $cle ] = $message;
		}
	}
	$age = ceb_date_valide( $d['date_naissance'] ) ? ceb_age_au( $d['date_naissance'], ceb_aujourdhui() ) : 0;
	if ( $age < 15 || $age > 70 ) {
		$e['date_naissance'] = 'Vérifie ta date de naissance.';
	}
	if ( ! isset( CEB_SEXES[ $d['sexe'] ] ) ) {
		$e['sexe'] = 'Indique ton sexe.';
	}
	if ( ! isset( CEB_MARITAL[ $d['marital'] ] ) ) {
		$e['marital'] = 'Indique ta situation familiale.';
	}
	return array( $d, $e );
}

function ceb_valider_etape3( $dossier ) {
	global $wpdb;
	$d = array(
		'telephone'      => ceb_normaliser_telephone( ceb_post( 'telephone' ) ),
		'email'          => strtolower( ceb_texte( 'email' ) ),
		'adresse'        => ceb_texte( 'adresse', 160 ),
		'region'         => ceb_post( 'region' ),
		'departement'    => ceb_post( 'departement' ),
		'arrondissement' => ceb_post( 'arrondissement' ),
		'cni'            => strtoupper( preg_replace( '/[\s.\-]/', '', ceb_post( 'cni' ) ) ),
		'cni_date'       => ceb_post( 'cni_date' ),
		'cni_lieu'       => mb_strtoupper( ceb_texte( 'cni_lieu', 80 ) ),
		'urgence_nom'    => ceb_texte( 'urgence_nom' ),
		'urgence_tel'    => ceb_normaliser_telephone( ceb_post( 'urgence_tel' ) ),
	);
	$e = array();
	if ( ! $d['telephone'] ) {
		$e['telephone'] = 'Saisis un numéro valide, par exemple 677 12 34 56.';
	}
	if ( ! is_email( $d['email'] ) ) {
		$e['email'] = 'Saisis une adresse e-mail valide.';
	}
	if ( mb_strlen( $d['adresse'] ) < 2 ) {
		$e['adresse'] = 'Indique ta ville ou ton quartier.';
	}
	if ( ! isset( CEB_REGIONS[ $d['region'] ] ) ) {
		$e['region'] = 'Choisis ta région d\'origine.';
	} elseif ( ! ceb_departement_valide( $d['region'], $d['departement'] ) ) {
		$e['departement'] = 'Choisis ton département d\'origine.';
	}
	if ( ! ceb_arrondissement_valide( $d['departement'], $d['arrondissement'] ) ) {
		$e['arrondissement'] = 'Choisis ton arrondissement d\'origine.';
	}
	if ( ! preg_match( '/^[A-Z0-9]{6,20}$/', $d['cni'] ) ) {
		$e['cni'] = 'Saisis le numéro de ta CNI ou de ton récépissé, sans espace.';
	} else {
		$existe = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . ceb_table() . ' WHERE annee = %s AND cni = %s AND id <> %d', $dossier->annee, $d['cni'], $dossier->id ) );
		if ( $existe ) {
			$e['cni'] = 'Un dossier existe déjà avec ce numéro. Utilise « Reprendre mon dossier ».';
		}
	}
	$date = ceb_date_valide( $d['cni_date'] );
	if ( ! $date || $d['cni_date'] > ceb_aujourdhui() || $d['cni_date'] <= $dossier->date_naissance ) {
		$e['cni_date'] = 'Vérifie la date de délivrance.';
	}
	if ( mb_strlen( $d['cni_lieu'] ) < 2 ) {
		$e['cni_lieu'] = 'Indique le lieu de délivrance.';
	}
	if ( '' !== ceb_post( 'urgence_tel' ) && ! $d['urgence_tel'] ) {
		$e['urgence_tel'] = 'Ce numéro n\'est pas valide.';
	}
	return array( $d, $e );
}

function ceb_valider_etape4( $dossier ) {
	$annee = (int) ceb_post( 'annee_diplome' );
	$d = array(
		'etablissement' => mb_strtoupper( ceb_texte( 'etablissement', 160 ) ),
		'annee_diplome' => $annee,
		'mention'       => ceb_post( 'mention' ),
	);
	$e = array();
	if ( mb_strlen( $d['etablissement'] ) < 3 ) {
		$e['etablissement'] = 'Indique l\'établissement ou l\'office qui a délivré le diplôme.';
	}
	$naissance = (int) substr( (string) $dossier->date_naissance, 0, 4 );
	if ( $annee < $naissance + 12 || $annee > (int) substr( ceb_aujourdhui(), 0, 4 ) ) {
		$e['annee_diplome'] = 'Saisis l\'année sur quatre chiffres, par exemple 2026.';
	}
	if ( ! isset( CEB_MENTIONS[ $d['mention'] ] ) ) {
		$e['mention'] = 'Choisis ta mention.';
	}
	$photo = ceb_recevoir_fichier( 'photo', true, 700 );
	if ( ! empty( $photo['erreur'] ) ) {
		$e['photo'] = $photo['erreur'];
	} elseif ( ! empty( $photo['nom'] ) ) {
		$d['photo'] = $photo['nom'];
	} elseif ( ! $dossier->photo ) {
		$e['photo'] = 'Ajoute ta photo d\'identité.';
	}
	return array( $d, $e );
}

function ceb_valider_etape5( $dossier ) {
	global $wpdb;
	$d = array( 'transaction_num' => strtoupper( preg_replace( '/\s+/', '', ceb_post( 'transaction_num' ) ) ) );
	$e = array();
	if ( 'verifie' === $dossier->paiement ) {
		return array( array(), array() ); /* Paiement déjà vérifié : plus rien à changer ici. */
	}
	if ( ! preg_match( '/^[A-Z0-9\/\-]{6,30}$/', $d['transaction_num'] ) || $d['transaction_num'] === $dossier->numero ) {
		$e['transaction_num'] = 'Saisis le numéro imprimé sur ton reçu de banque.';
	} else {
		$existe = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . ceb_table() . ' WHERE annee = %s AND transaction_num = %s AND id <> %d', $dossier->annee, $d['transaction_num'], $dossier->id ) );
		if ( $existe ) {
			$e['transaction_num'] = 'Ce numéro de transaction est déjà utilisé par un autre dossier.';
		}
	}
	$recu = ceb_recevoir_fichier( 'recu', false, 1600 );
	if ( ! empty( $recu['erreur'] ) ) {
		$e['recu'] = $recu['erreur'];
	} elseif ( ! empty( $recu['nom'] ) ) {
		$d['recu']      = $recu['nom'];
		$d['recu_type'] = $recu['type'];
	} elseif ( ! $dossier->recu ) {
		$e['recu'] = 'Ajoute la photo de ton reçu.';
	}
	return array( $d, $e );
}

/* ---------- Règles de parcours ---------- */

/** Le candidat peut-il encore changer ce dossier ? */
function ceb_modifiable( $dossier ) {
	return $dossier && 'valide' !== $dossier->statut && 'ouvert' === ceb_etat_session() && ceb_reglage( 'annee' ) === $dossier->annee;
}

/** Première étape incomplète : 7 quand tout est rempli. */
function ceb_premiere_etape_manquante( $d ) {
	if ( ! $d->centre || ! $d->option_code ) {
		return 1;
	}
	if ( ! $d->noms || ! $d->date_naissance ) {
		return 2;
	}
	if ( ! $d->telephone || ! $d->cni ) {
		return 3;
	}
	if ( ! $d->etablissement || ! $d->photo ) {
		return 4;
	}
	if ( ! $d->transaction_num || ! $d->recu ) {
		return 5;
	}
	return 7;
}

/** Enregistre une étape et renvoie l'adresse suivante. */
function ceb_action_etape() {
	$etape   = (int) ceb_post( 'etape' );
	$dossier = ceb_dossier_courant();
	$retour  = ceb_url( 'candidature/' . $etape );

	if ( 'ouvert' !== ceb_etat_session() ) {
		ceb_flash( 'erreur', 'Les inscriptions ne sont pas ouvertes.' );
		ceb_rediriger( ceb_url() );
	}
	if ( $dossier && ! ceb_modifiable( $dossier ) ) {
		ceb_flash( 'erreur', 'Ce dossier est validé : il ne se modifie plus. Contacte la scolarité en cas d\'erreur.' );
		ceb_rediriger( ceb_url( 'mon-dossier' ) );
	}
	if ( $etape > 2 && ! $dossier ) {
		ceb_rediriger( ceb_url( 'candidature/1' ) );
	}

	switch ( $etape ) {
		case 1:
			list( $d, $e ) = ceb_valider_etape1();
			break;
		case 2:
			list( $d, $e ) = ceb_valider_etape2();
			break;
		case 3:
			list( $d, $e ) = ceb_valider_etape3( $dossier );
			break;
		case 4:
			list( $d, $e ) = ceb_valider_etape4( $dossier );
			break;
		case 5:
			list( $d, $e ) = ceb_valider_etape5( $dossier );
			break;
		default:
			ceb_rediriger( ceb_url( 'candidature/1' ) );
	}

	if ( $e ) {
		/* Étape refusée : le fichier reçu n'est pas gardé, l'ancien reste en place. */
		foreach ( array( 'photo', 'recu' ) as $champ ) {
			if ( ! empty( $d[ $champ ] ) ) {
				ceb_supprimer_fichier( $d[ $champ ] );
				unset( $d[ $champ ] );
			}
		}
		ceb_memoriser( array_map( 'strval', array_merge( $d, array_intersect_key( wp_unslash( $_POST ), array( 'parcours' => 1 ) ) ) ), $e );
		ceb_rediriger( $retour );
	}

	if ( 1 === $etape ) {
		$d['cycle'] = ceb_cycle_du_diplome( $d['diplome'] );
	}
	if ( 1 === $etape && ! $dossier ) {
		/* Le choix attend en session : le dossier naît à l'étape identité. */
		$_SESSION['ceb_choix'] = $d;
		ceb_rediriger( ceb_url( 'candidature/2' ) );
	}
	if ( 2 === $etape && ! $dossier ) {
		$choix = $_SESSION['ceb_choix'] ?? null;
		if ( ! $choix ) {
			ceb_rediriger( ceb_url( 'candidature/1' ) );
		}
		$id = ceb_creer_dossier( array_merge( $choix, $d ) );
		if ( ! $id ) {
			ceb_flash( 'erreur', 'L\'enregistrement a échoué. Réessaie dans un instant.' );
			ceb_rediriger( $retour );
		}
		unset( $_SESSION['ceb_choix'] );
		ceb_ouvrir_dossier( $id );
		$dossier = ceb_dossier( $id );
		ceb_flash( 'numero', $dossier->numero );
	} else {
		if ( $d && ! ceb_maj_dossier( $dossier->id, $d ) ) {
			/* Deux envois simultanés : les clés uniques ont le dernier mot. */
			ceb_memoriser( array(), array( 'general' => 'Ce numéro vient d\'être utilisé sur un autre dossier.' ) );
			ceb_rediriger( $retour );
		}
		foreach ( array( 'photo', 'recu' ) as $champ ) {
			if ( ! empty( $d[ $champ ] ) ) {
				ceb_supprimer_fichier( $dossier->$champ ); /* Remplacé : l'ancien fichier part seulement maintenant. */
			}
		}
		$dossier = ceb_dossier( $dossier->id );
	}

	if ( 'brouillon' !== $dossier->statut ) {
		ceb_journaliser( $dossier->id, 'Candidat', 'Étape ' . CEB_ETAPES[ $etape ] . ' modifiée' );
	}
	$alerte = ceb_depasse_age( $dossier->date_naissance, $dossier->cycle, (int) $dossier->fonctionnaire ) ? 1 : 0;
	ceb_maj_dossier( $dossier->id, array( 'alerte_age' => $alerte, 'etape' => max( (int) $dossier->etape, $etape + 1 ) ) );
	ceb_rediriger( ceb_url( 'candidature/' . ( $etape + 1 ) ) );
}

/** Dernier bouton du parcours. */
function ceb_action_soumettre() {
	$dossier = ceb_dossier_courant();
	if ( ! ceb_modifiable( $dossier ) ) {
		ceb_rediriger( ceb_url( 'mon-dossier' ) );
	}
	$manque = ceb_premiere_etape_manquante( $dossier );
	if ( $manque < 7 ) {
		ceb_flash( 'erreur', 'Il manque des informations à cette étape.' );
		ceb_rediriger( ceb_url( 'candidature/' . $manque ) );
	}
	if ( '1' !== ceb_post( 'engagement' ) ) {
		ceb_memoriser( array(), array( 'engagement' => 'Coche la déclaration pour envoyer ton dossier.' ) );
		ceb_rediriger( ceb_url( 'candidature/6' ) );
	}
	$premier = 'soumis' !== $dossier->statut;
	$maj     = array( 'statut' => 'soumis', 'motif' => '' );
	if ( 'rejete' === $dossier->paiement ) {
		$maj['paiement'] = 'attente';
	}
	if ( ! $dossier->soumis_le ) {
		$maj['soumis_le'] = current_time( 'mysql' );
	}
	ceb_maj_dossier( $dossier->id, $maj );
	if ( $premier ) {
		ceb_journaliser( $dossier->id, 'Candidat', 'Dossier envoyé' );
		$nom = trim( $dossier->noms . ' ' . $dossier->prenoms );
		if ( 'rejete' === $dossier->statut ) {
			ceb_notifier_equipe( $dossier, 'Dossier corrigé', $nom . ' a corrigé son dossier ' . $dossier->numero . ' et l\'a renvoyé.' );
		} else {
			ceb_notifier_equipe( $dossier, 'Nouvelle candidature', $nom . ', ' . ( CEB_OPTIONS[ $dossier->option_code ] ?? '' ) . ', centre de ' . ( CEB_CENTRES[ $dossier->centre ] ?? '' ) . '.' );
			ceb_envoyer_confirmation( $dossier );
		}
	}
	$_SESSION['ceb_telecharger'] = true;
	ceb_flash( 'succes', 'Ton dossier est enregistré. Imprime ta fiche et dépose ton dossier complet.' );
	ceb_rediriger( ceb_url( 'mon-dossier' ) );
}

/** Courriel de confirmation : utile, jamais bloquant. */
function ceb_envoyer_confirmation( $d ) {
	$corps = sprintf(
		"Bonjour %s,\n\nTa candidature au concours de l'ENSET d'Ebolowa est enregistrée.\n\nNuméro candidat : %s\nDate de naissance à saisir pour reprendre ton dossier : %s\n\nTu peux revoir ta fiche et suivre ton dossier ici :\n%s\n\nGarde ce numéro : il te sera demandé pour toute démarche.\n",
		$d->prenoms ?: $d->noms,
		$d->numero,
		wp_date( 'd/m/Y', strtotime( $d->date_naissance ) ),
		ceb_url( 'reprendre' )
	);
	wp_mail( $d->email, 'Votre numéro candidat ' . $d->numero, $corps );
}

/** Reprise : numéro candidat ou téléphone, avec la date de naissance. */
function ceb_action_reprendre() {
	global $wpdb;
	$cle       = strtoupper( preg_replace( '/\s+/', '', ceb_post( 'identifiant' ) ) );
	$naissance = ceb_post( 'date_naissance' );
	$ip        = 'ip' . ceb_ip();
	if ( ceb_essais_bloques( $ip ) || ceb_essais_bloques( 'cle' . $cle ) ) {
		ceb_memoriser( array( 'identifiant' => $cle ), array( 'general' => 'Trop d\'essais. Réessaie dans 15 minutes.' ) );
		ceb_rediriger( ceb_url( 'reprendre' ) );
	}
	$telephone = ceb_normaliser_telephone( $cle );
	$id = $wpdb->get_var( $wpdb->prepare(
		'SELECT id FROM ' . ceb_table() . ' WHERE (numero = %s OR (telephone <> %s AND telephone = %s)) AND date_naissance = %s ORDER BY id DESC LIMIT 1',
		$cle, '', $telephone, $naissance
	) );
	if ( ! $id ) {
		ceb_essai_rate( $ip );
		ceb_essai_rate( 'cle' . $cle );
		ceb_memoriser( array( 'identifiant' => $cle ), array( 'general' => 'Aucun dossier ne correspond à ces informations.' ) );
		ceb_rediriger( ceb_url( 'reprendre' ) );
	}
	ceb_ouvrir_dossier( $id );
	ceb_rediriger( ceb_url( 'mon-dossier' ) );
}
