<?php
/**
 * Réglages d'une session : à reprendre à chaque nouvel arrêté.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$c = static fn( $nom, $libelle, $type = 'text', $aide = '' ) => ceb_champ( array( 'nom' => $nom, 'libelle' => $libelle, 'type' => $type, 'valeur' => $r[ $nom ], 'aide' => $aide, 'facultatif' => true ) );
$bloc = static function ( $titre, $texte, callable $contenu ) {
	echo '<section class="reglage"><div><h2>' . esc_html( $titre ) . '</h2><p>' . esc_html( $texte ) . '</p></div><div class="reglage__champs">';
	$contenu();
	echo '</div></section>';
};
$tete( 'Réglages', 'À reprendre à chaque nouvel arrêté.' );
?>
<form class="reglages" method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>">
	<?php ceb_champ_csrf(); ?>
	<input type="hidden" name="ceb_action" value="reglages">
	<?php
	$bloc( 'Session', 'Le numéro candidat reprend la dernière année de la session.', static function () use ( $c ) {
		echo '<div class="rangee">';
		$c( 'annee', 'Année académique', 'text', 'Format 2027/2028.' );
		$c( 'ouverture', 'Ouverture', 'date', 'Vide : ouvert dès maintenant.' );
		$c( 'cloture', 'Clôture', 'date', 'Dernier jour inclus.' );
		echo '</div>';
	} );
	$bloc( 'Épreuves', 'Affichées sur l\'accueil dès qu\'elles sont renseignées.', static function () use ( $c ) {
		echo '<div class="rangee">';
		$c( 'date_cycle1', 'Premier cycle', 'date' );
		$c( 'date_cycle2', 'Second cycle', 'date' );
		echo '</div>';
	} );
	$bloc( 'Frais de concours', 'Imprimés sur la fiche et rappelés à l\'étape paiement.', static function () use ( $c ) {
		echo '<div class="rangee">';
		$c( 'frais', 'Montant (FCFA)', 'number' );
		$c( 'banque', 'Banque' );
		$c( 'compte', 'Numéro de compte' );
		echo '</div>';
	} );
	$bloc( 'Limite d\'âge', 'Un dépassement est signalé, sans bloquer. Les fonctionnaires sont exemptés.', static function () use ( $c ) {
		echo '<div class="rangee">';
		$c( 'age_cycle1', 'Premier cycle (ans)', 'number' );
		$c( 'age_cycle2', 'Second cycle (ans)', 'number' );
		$c( 'date_age', 'Âge calculé au', 'date' );
		echo '</div>';
	} );
	$bloc( 'Contact public', 'Affiché en pied de page.', static function () use ( $c ) {
		echo '<div class="rangee">';
		$c( 'telephone', 'Téléphone' );
		$c( 'email', 'E-mail', 'email' );
		echo '</div>';
	} );
	$bloc( 'Centres ouverts', 'Un centre fermé disparaît du formulaire.', static function () use ( $r ) {
		echo '<div class="bascules">';
		foreach ( CEB_CENTRES as $k => $l ) {
			printf( '<label><input type="checkbox" role="switch" name="centres[]" value="%s" %s><i></i>%s</label>', esc_attr( $k ), checked( ! in_array( $k, $r['centres_fermes'], true ), true, false ), esc_html( $l ) );
		}
		echo '</div>';
	} );
	$bloc( 'Options ouvertes', 'Une option fermée n\'est plus proposée aux candidats.', static function () use ( $r ) {
		echo '<div class="bascules">';
		foreach ( CEB_OPTIONS as $k => $l ) {
			printf( '<label><input type="checkbox" role="switch" name="options[]" value="%s" %s><i></i>%s</label>', esc_attr( $k ), checked( ! in_array( $k, $r['options_fermees'], true ), true, false ), esc_html( $l ) );
		}
		echo '</div>';
	} );
	?>
	<div class="reglages__barre"><button class="btn btn--primaire" type="submit">Enregistrer les réglages</button></div>
</form>
