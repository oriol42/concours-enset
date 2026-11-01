<?php
/**
 * Remplit une base de TEST avec des candidats fictifs, pour voir l'administration
 * chargée. À ne jamais lancer en production.
 * Usage : php tests/demo-donnees.php /chemin/wp-load.php [nombre]
 */
if ( PHP_SAPI !== 'cli' ) {
	exit;
}
$_SERVER['HTTP_HOST'] = '127.0.0.1:8099';
require $argv[1];
mt_srand( 2027 );
$noms    = array( 'ABENA', 'ATANGANA', 'BIYIK', 'EBOGO', 'ESSOMBA', 'FOUDA', 'KAMGA', 'MBARGA', 'MENDOUGA', 'NANA', 'NDONGO', 'NGUEMA', 'NKOULOU', 'OBAM', 'ONANA', 'TCHOUA', 'YOUMBI', 'ZE' );
$prenoms = array( 'ARMELLE', 'BLAISE', 'CHANTAL', 'DIDIER', 'ESTELLE', 'FABRICE', 'GISÈLE', 'HERVÉ', 'IRÈNE', 'JOËL', 'LANDRY', 'MIREILLE', 'PATRICE', 'ROSTAND', 'SANDRINE', 'VICTOIRE' );
$pick    = static fn( array $a ) => $a[ mt_rand( 0, count( $a ) - 1 ) ];
$couples = array();
foreach ( CEB_LIAISONS as $f => $options ) {
	foreach ( $options as $o => $diplomes ) {
		foreach ( $diplomes as $dip ) {
			$couples[] = array( $f, $o, $dip );
		}
	}
}
$n = (int) ( $argv[2] ?? 64 );
for ( $i = 0; $i < $n; $i++ ) {
	list( $f, $o, $dip ) = $pick( $couples );
	$age   = mt_rand( 17, 33 );
	$reg   = $pick( array( '18', '18', '11', '17', '12' ) );
	$dep   = $pick( CEB_REGIONS[ $reg ]['dep'] );
	$etat  = mt_rand( 1, 100 );
	$quand = gmdate( 'Y-m-d H:i:s', time() - mt_rand( 0, 26 ) * DAY_IN_SECONDS - mt_rand( 0, 40000 ) );
	$base  = array(
		'cycle' => ceb_cycle_du_diplome( $dip ), 'diplome' => $dip, 'filiere' => $f, 'option_code' => $o,
		'centre' => $pick( array( 'EBOL', 'EBOL', 'EBOL', 'YAO', 'YAO', 'DOUA', 'BAFO', 'GAR', 'BERT' ) ), 'langue' => mt_rand( 1, 12 ) > 11 ? '2' : '1',
		'noms' => $pick( $noms ), 'prenoms' => $pick( $prenoms ), 'date_naissance' => ( (int) gmdate( 'Y' ) - $age ) . '-0' . mt_rand( 1, 9 ) . '-1' . mt_rand( 1, 9 ),
		'lieu_naissance' => 'EBOLOWA', 'sexe' => (string) mt_rand( 1, 2 ), 'nationalite' => 'Camerounaise', 'marital' => '1',
	);
	$id = ceb_creer_dossier( $base );
	if ( $etat <= 14 ) {
		continue; /* Reste en brouillon. */
	}
	$statut   = $etat <= 56 ? 'soumis' : ( $etat <= 66 ? 'rejete' : 'valide' );
	$paiement = 'valide' === $statut || $etat <= 28 ? 'verifie' : ( 'rejete' === $statut ? 'rejete' : 'attente' );
	ceb_maj_dossier( $id, array(
		'telephone' => '6' . mt_rand( 70000000, 99999999 ), 'email' => 'candidat' . $id . '@example.invalid', 'adresse' => 'Ebolowa, Nkoétyé', 'region' => $reg,
		'departement' => $dep, 'arrondissement' => CEB_ARRONDISSEMENTS[ $dep ][0], 'cni' => 'DEMO' . str_pad( (string) $id, 6, '0', STR_PAD_LEFT ), 'cni_date' => '2024-03-12', 'cni_lieu' => 'EBOLOWA',
		'etablissement' => 'LYCÉE TECHNIQUE D\'EBOLOWA', 'annee_diplome' => 2024, 'mention' => $pick( array( 'P', 'AB', 'B' ) ), 'transaction_num' => 'SCB' . ( 240000 + $id ),
		'statut' => $statut, 'paiement' => $paiement, 'motif' => 'rejete' === $statut ? 'Reçu illisible' : '', 'soumis_le' => $quand, 'etape' => 7,
		'alerte_age' => ceb_depasse_age( $base['date_naissance'], $base['cycle'], 0 ) ? 1 : 0,
		'depot_le' => 'valide' === $statut || ( 'verifie' === $paiement && $etat % 2 ) ? $quand : null,
		'pieces_recues' => 'valide' === $statut || ( 'verifie' === $paiement && $etat % 2 ) ? '0,1,2,3,4,5,6,7,8' : '',
	) );
}
echo "$n dossiers fictifs créés\n";
