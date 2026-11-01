<?php
/**
 * Référentiels du concours, repris des fichiers de l'ENSET (Cycles, Diplomes,
 * Diplome Cycles, Filieres, Liaisions, Lieu de Composition, mentions).
 *
 * La liaison diplôme → filière → option décide de ce qu'un candidat peut
 * présenter : le formulaire n'affiche que ces couples et le serveur les
 * revérifie. Un libellé marqué « à confirmer » dans SUIVI.md a été déduit
 * de son code, faute de table Options dans les fichiers reçus.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_CYCLES = array(
	'CYC1' => array( 'nom' => 'Premier cycle', 'court' => 'Cycle 1', 'titre' => 'PREMIER CYCLE' ),
	'CYC2' => array( 'nom' => 'Second cycle', 'court' => 'Cycle 2', 'titre' => 'SECOND CYCLE' ),
);

const CEB_DIPLOMES = array(
	'BAC_C'     => 'Baccalauréat C',
	'BAC_D'     => 'Baccalauréat D',
	'BAC_E'     => 'Baccalauréat E',
	'BAC_A4'    => 'Baccalauréat A4',
	'BAC_F2'    => 'Baccalauréat F2',
	'BAC_F3'    => 'Baccalauréat F3',
	'BAC_F4'    => 'Baccalauréat F4',
	'BAC_ITH'   => 'Baccalauréat ITH',
	'BAC_I'     => 'Baccalauréat I',
	'BAC_G1'    => 'Baccalauréat G1/AOC',
	'BAC_G2'    => 'Baccalauréat G2/CG',
	'BAC_G3'    => 'Baccalauréat G3/AOC',
	'GCE_AL'    => 'GCE Advanced Level',
	'BT_MA'     => 'BT Mécanique Automobile',
	'BT_MEB'    => 'BT Menuiserie Ébénisterie',
	'BT_EF'     => 'BT Exploitation Forestière',
	'BT_ESF'    => 'BT Économie Sociale et Familiale',
	'BT_MISE'   => 'BT MISE',
	'BT_FI'     => 'BT Fabrication Industrielle',
	'LIC_SOCIO' => 'Licence en Sociologie',
	'LIC_DROIT' => 'Licence en Droit',
	'LIC_INFO'  => 'Licence en Informatique',
	'LIC_PHYS'  => 'Licence en Physique',
	'LIC_TECH'  => 'Licence de Technologie',
	'LIC_SPEC'  => 'Licence dans la spécialité',
	'LIC_SHL'   => 'Licence en Sciences Humaines et Lettres',
	'LIC_ECO'   => 'Licence en Économie',
	'BACH_ECO'  => 'Bachelor en Économie',
);

const CEB_DIPLOMES_CYCLE2 = array( 'LIC_SOCIO', 'LIC_DROIT', 'LIC_INFO', 'LIC_PHYS', 'LIC_TECH', 'LIC_SPEC', 'LIC_SHL', 'LIC_ECO', 'BACH_ECO' );

const CEB_FILIERES = array(
	'BIOMED'  => 'Ingénierie Biomédicale',
	'DIDACT'  => 'Didactique des disciplines',
	'GCIV'    => 'Génie Civil',
	'GELEC'   => 'Génie Électrique',
	'GEOMAT'  => 'Géomatique',
	'GINFO'   => 'Génie Informatique',
	'GMECA'   => 'Génie Mécanique',
	'INGBOIS' => 'Ingénierie Bois',
	'INNOV'   => 'Innovation des techniques commerciales et de l\'industrialisation',
	'MAINT'   => 'Maintenance',
	'TOURHOT' => 'Tourisme et Hôtellerie',
);

const CEB_OPTIONS = array(
	'BTP' => 'Bâtiments et Travaux Publics',
	'ITH' => 'Industrie Textile et Habillement',
	'ET'  => 'Électrotechnique',
	'TOP' => 'Topographie',
	'II'  => 'Informatique Industrielle',
	'IF'  => 'Informatique Fondamentale',
	'TIC' => 'Technologies de l\'Information et de la Communication',
	'CM'  => 'Construction Mécanique',
	'FM'  => 'Fabrication Mécanique',
	'MA'  => 'Mécanique Automobile',
	'MB'  => 'Métiers du Bois',
	'EF'  => 'Exploitation Forestière',
	'GFC' => 'Gestion Financière et Comptable',
	'MK'  => 'Marketing',
	'MIO' => 'Management de l\'Information et des Organisations',
	'CAD' => 'Communication Administrative',
	'ECO' => 'Économie',
	'MIS' => 'Maintenance et Installation des Systèmes Électroniques',
	'MHB' => 'Maintenance Hospitalière et Biomédicale',
	'TO'  => 'Tourisme',
	'NUT' => 'Nutrition Humaine et Diététique',
	'CO'  => 'Conseillers d\'Orientation',
);

/* Liaisions.xlsx : filière => option => diplômes admis. */
const CEB_LIAISONS = array(
	'BIOMED'  => array( 'MHB' => array( 'LIC_TECH' ) ),
	'DIDACT'  => array( 'CO' => array( 'LIC_SOCIO', 'LIC_DROIT' ) ),
	'GCIV'    => array(
		'BTP' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL', 'BAC_F4' ),
		'ITH' => array( 'BAC_ITH' ),
	),
	'GELEC'   => array( 'ET' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL', 'BAC_F3' ) ),
	'GEOMAT'  => array( 'TOP' => array( 'LIC_TECH' ) ),
	'GINFO'   => array(
		'II'  => array( 'LIC_INFO', 'LIC_PHYS', 'LIC_TECH' ),
		'IF'  => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL' ),
		'TIC' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL', 'BAC_I' ),
	),
	'GMECA'   => array(
		'CM' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL' ),
		'FM' => array( 'BT_FI' ),
		'MA' => array( 'BT_MA' ),
	),
	'INGBOIS' => array(
		'MB' => array( 'BT_MEB' ),
		'EF' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'GCE_AL', 'BT_EF' ),
	),
	'INNOV'   => array(
		'GFC' => array( 'BAC_G2' ),
		'MK'  => array( 'BAC_G3' ),
		'MIO' => array( 'BAC_A4', 'BAC_G1', 'GCE_AL' ),
		'CAD' => array( 'LIC_SHL' ),
		'ECO' => array( 'LIC_ECO', 'BACH_ECO' ),
	),
	'MAINT'   => array( 'MIS' => array( 'BAC_C', 'BAC_D', 'BAC_E', 'BT_MISE', 'BAC_F3', 'BAC_F2', 'GCE_AL' ) ),
	'TOURHOT' => array(
		'TO'  => array( 'LIC_SPEC' ),
		'NUT' => array( 'BT_ESF' ),
	),
);

const CEB_CENTRES = array(
	'BAFO' => 'Bafoussam',
	'BERT' => 'Bertoua',
	'DOUA' => 'Douala',
	'EBOL' => 'Ebolowa',
	'GAR'  => 'Garoua',
	'YAO'  => 'Yaoundé',
);

const CEB_MENTIONS = array(
	'P'      => 'Passable',
	'AB'     => 'Assez bien',
	'B'      => 'Bien',
	'TB'     => 'Très bien',
	'E'      => 'Excellent',
	'PASSED' => 'Passed',
);

const CEB_SEXES    = array( '1' => 'Masculin', '2' => 'Féminin' );
const CEB_MARITAL  = array( '1' => 'Célibataire', '2' => 'Marié(e)', '3' => 'Divorcé(e)', '4' => 'Veuf(ve)' );
const CEB_LANGUES  = array( '1' => 'Français', '2' => 'Anglais' );

function ceb_cycle_du_diplome( $diplome ) {
	return in_array( $diplome, CEB_DIPLOMES_CYCLE2, true ) ? 'CYC2' : 'CYC1';
}

/** Vrai si le couple diplôme / filière / option figure dans les liaisons et reste ouvert. */
function ceb_liaison_valide( $diplome, $filiere, $option ) {
	return ! in_array( $option, ceb_reglage( 'options_fermees' ), true )
		&& in_array( $diplome, CEB_LIAISONS[ $filiere ][ $option ] ?? array(), true );
}

/** Diplômes présentables pour un cycle, dans l'ordre du référentiel. */
function ceb_diplomes_du_cycle( $cycle ) {
	return array_filter( CEB_DIPLOMES, static fn( $code ) => ceb_cycle_du_diplome( $code ) === $cycle, ARRAY_FILTER_USE_KEY );
}

/** Centres ouverts pour la session en cours. */
function ceb_centres_ouverts() {
	return array_diff_key( CEB_CENTRES, array_flip( ceb_reglage( 'centres_fermes' ) ) );
}

/**
 * Tout ce dont le choix du concours a besoin dans le navigateur :
 * diplôme => liste de { filiere, option, libellés }.
 */
function ceb_offre_par_diplome() {
	$offre = array();
	foreach ( CEB_LIAISONS as $filiere => $options ) {
		foreach ( $options as $option => $diplomes ) {
			if ( in_array( $option, ceb_reglage( 'options_fermees' ), true ) ) {
				continue;
			}
			foreach ( $diplomes as $diplome ) {
				$offre[ $diplome ][] = array(
					'f' => $filiere, 'o' => $option,
					'fl' => CEB_FILIERES[ $filiere ], 'ol' => CEB_OPTIONS[ $option ],
				);
			}
		}
	}
	return $offre;
}

/** Filières groupées par cycle pour la page d'accueil. */
function ceb_offre_par_cycle() {
	$offre = array( 'CYC1' => array(), 'CYC2' => array() );
	foreach ( CEB_LIAISONS as $filiere => $options ) {
		foreach ( $options as $option => $diplomes ) {
			if ( in_array( $option, ceb_reglage( 'options_fermees' ), true ) ) {
				continue;
			}
			$offre[ ceb_cycle_du_diplome( $diplomes[0] ) ][ CEB_FILIERES[ $filiere ] ][] = CEB_OPTIONS[ $option ];
		}
	}
	foreach ( $offre as &$cycle ) {
		ksort( $cycle );
	}
	return $offre;
}
