<?php
/**
 * Régions et départements du Cameroun. Les codes suivent ceux des fichiers de
 * l'ENSET (région 10 à 19 par ordre alphabétique, département = région + rang,
 * par exemple 1802 pour la Mvila). Les arrondissements sont dans arrondissements.php.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_REGIONS = array(
	'10' => array( 'nom' => 'Adamaoua', 'dep' => array( 'Djérem', 'Faro-et-Déo', 'Mayo-Banyo', 'Mbéré', 'Vina' ) ),
	'11' => array( 'nom' => 'Centre', 'dep' => array( 'Haute-Sanaga', 'Lekié', 'Mbam-et-Inoubou', 'Mbam-et-Kim', 'Méfou-et-Afamba', 'Méfou-et-Akono', 'Mfoundi', 'Nyong-et-Kéllé', 'Nyong-et-Mfoumou', 'Nyong-et-So\'o' ) ),
	'12' => array( 'nom' => 'Est', 'dep' => array( 'Boumba-et-Ngoko', 'Haut-Nyong', 'Kadey', 'Lom-et-Djérem' ) ),
	'13' => array( 'nom' => 'Extrême-Nord', 'dep' => array( 'Diamaré', 'Logone-et-Chari', 'Mayo-Danay', 'Mayo-Kani', 'Mayo-Sava', 'Mayo-Tsanaga' ) ),
	'14' => array( 'nom' => 'Littoral', 'dep' => array( 'Moungo', 'Nkam', 'Sanaga-Maritime', 'Wouri' ) ),
	'15' => array( 'nom' => 'Nord', 'dep' => array( 'Bénoué', 'Faro', 'Mayo-Louti', 'Mayo-Rey' ) ),
	'16' => array( 'nom' => 'Nord-Ouest', 'dep' => array( 'Boyo', 'Bui', 'Donga-Mantung', 'Menchum', 'Mezam', 'Momo', 'Ngo-Ketunjia' ) ),
	'17' => array( 'nom' => 'Ouest', 'dep' => array( 'Bamboutos', 'Haut-Nkam', 'Hauts-Plateaux', 'Koung-Khi', 'Ménoua', 'Mifi', 'Ndé', 'Noun' ) ),
	'18' => array( 'nom' => 'Sud', 'dep' => array( 'Dja-et-Lobo', 'Mvila', 'Océan', 'Vallée-du-Ntem' ) ),
	'19' => array( 'nom' => 'Sud-Ouest', 'dep' => array( 'Fako', 'Koupé-Manengouba', 'Lebialem', 'Manyu', 'Meme', 'Ndian' ) ),
);

require_once __DIR__ . '/arrondissements.php';

function ceb_arrondissement_valide( $departement, $arrondissement ) {
	return in_array( $arrondissement, CEB_ARRONDISSEMENTS[ $departement ] ?? array(), true );
}

function ceb_departement_valide( $region, $departement ) {
	return in_array( $departement, CEB_REGIONS[ $region ]['dep'] ?? array(), true );
}
