<?php
/**
 * Deux candidats fictifs, à voir de leur côté : un dossier à corriger et un
 * dossier validé. À ne jamais lancer en production.
 * Usage : php tests/demo-candidats.php /chemin/wp-load.php /chemin/photo.jpg
 */
if ( PHP_SAPI !== 'cli' ) {
	exit;
}
$_SERVER['HTTP_HOST'] = '127.0.0.1:8099';
require $argv[1];

function demo_candidat( $noms, $prenoms, $naissance, $telephone, $cni, $transaction, $photo ) {
	$nom = bin2hex( random_bytes( 16 ) ) . '.jpg';
	copy( $photo, ceb_dossier_fichiers() . '/' . $nom );
	$id = ceb_creer_dossier( array(
		'cycle' => 'CYC1', 'diplome' => 'BAC_D', 'filiere' => 'GINFO', 'option_code' => 'TIC', 'centre' => 'EBOL', 'langue' => '1',
		'noms' => $noms, 'prenoms' => $prenoms, 'date_naissance' => $naissance, 'lieu_naissance' => 'EBOLOWA', 'sexe' => '2', 'nationalite' => 'Camerounaise', 'marital' => '1',
	) );
	ceb_maj_dossier( $id, array(
		'telephone' => $telephone, 'email' => 'candidat' . $id . '@example.invalid', 'adresse' => 'Ebolowa, Nkoétyé', 'region' => '18', 'departement' => 'Mvila', 'arrondissement' => 'EBOLOWA IER',
		'cni' => $cni, 'cni_date' => '2024-03-12', 'cni_lieu' => 'EBOLOWA', 'etablissement' => 'LYCÉE CLASSIQUE D\'EBOLOWA', 'annee_diplome' => 2025, 'mention' => 'AB',
		'photo' => $nom, 'recu' => $nom, 'recu_type' => 'image/jpeg', 'transaction_num' => $transaction, 'statut' => 'soumis', 'soumis_le' => current_time( 'mysql' ), 'etape' => 7,
	) );
	return ceb_dossier( $id );
}

$a = demo_candidat( 'MBALLA', 'GRÂCE', '2005-05-12', '677000111', 'TEST000111', 'SCB900111', $argv[2] );
ceb_maj_dossier( $a->id, array( 'paiement' => 'rejete', 'statut' => 'rejete', 'motif' => 'Reçu illisible' ) );
ceb_journaliser( $a->id, 'Scolarité', 'Paiement rejeté : Reçu illisible' );
ceb_notifier_candidat( $a, 'Paiement rejeté', 'Motif : Reçu illisible. Corrige ton dossier et renvoie-le.' );

$b = demo_candidat( 'ESSOMBA', 'PAUL', '2004-09-03', '699000222', 'TEST000222', 'SCB900222', $argv[2] );
ceb_maj_dossier( $b->id, array( 'paiement' => 'verifie', 'statut' => 'valide', 'pieces_recues' => '0,1,2,3,4,5,6,7,8', 'depot_le' => current_time( 'mysql' ) ) );
ceb_notifier_candidat( $b, 'Paiement vérifié', 'Ton paiement est vérifié. Dépose maintenant tes pièces à la scolarité.' );
ceb_notifier_candidat( $b, 'Pièces reçues', 'La scolarité a reçu toutes tes pièces.' );
ceb_notifier_candidat( $b, 'Dossier validé', 'Ton dossier est validé : tu es admis à composer au centre de Ebolowa.' );

echo $a->numero, "\n", $b->numero, "\n";
