<?php
/**
 * Réglages du concours : à revoir à chaque nouvel arrêté. Chaque rubrique dit
 * en clair ce qu'elle change pour les candidats.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$c = static fn( $nom, $libelle, $type = 'text', $aide = '' ) => ceb_champ( array( 'nom' => $nom, 'libelle' => $libelle, 'type' => $type, 'valeur' => $r[ $nom ], 'aide' => $aide, 'facultatif' => true, 'mention' => false ) );
$rubriques = array(
	'calendrier' => array( 'Calendrier', 'horloge', 'Quand les candidats peuvent s\'inscrire et quand ils composent.' ),
	'frais'      => array( 'Frais de concours', 'banque', 'Le montant et le compte que les candidats voient avant de payer.' ),
	'age'        => array( 'Limite d\'âge', 'utilisateur', 'Le dossier d\'un candidat trop âgé reste accepté, mais il vous est signalé.' ),
	'offre'      => array( 'Centres et options', 'lieu', 'Ce que les candidats peuvent choisir dans le formulaire.' ),
	'contact'    => array( 'Contact', 'telephone', 'Les coordonnées affichées aux candidats qui ont besoin d\'aide.' ),
);
$etats = array(
	'ouvert'  => 'Les candidats peuvent s\'inscrire' . ( $r['cloture'] ? ' jusqu\'au ' . ceb_date_fr( $r['cloture'] ) : ', sans date limite pour le moment' ),
	'a_venir' => 'Les inscriptions ouvriront le ' . ceb_date_fr( $r['ouverture'] ),
	'clos'    => 'Les inscriptions sont fermées depuis le ' . ceb_date_fr( $r['cloture'] ),
);
$tete( 'Réglages du concours', 'Ce que voient les candidats et les règles de la session. À revoir à chaque nouvel arrêté.' );
?>
<p class="etat-session etat-session--<?php echo esc_attr( ceb_etat_session() ); ?>"><?php echo ceb_icone( 'info', 18 ); ?><span><b><?php echo esc_html( $etats[ ceb_etat_session() ] ); ?>.</b> Prochain numéro de candidat : <?php echo esc_html( ceb_prefixe_numero() ); ?>…</span></p>

<form class="reglages" method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>">
	<?php ceb_champ_csrf(); ?>
	<input type="hidden" name="ceb_action" value="reglages">
	<div class="reglages__onglets" role="tablist" aria-label="Rubriques des réglages" aria-orientation="vertical">
		<?php $premier = true; foreach ( $rubriques as $k => $x ) : ?>
			<button type="button" role="tab" id="o-<?php echo esc_attr( $k ); ?>" aria-controls="r-<?php echo esc_attr( $k ); ?>" aria-selected="<?php echo $premier ? 'true' : 'false'; ?>"><?php echo ceb_icone( $x[1], 18 ); ?><?php echo esc_html( $x[0] ); ?></button>
			<?php $premier = false; endforeach; ?>
	</div>
	<div class="reglages__corps">
		<?php
		$bloc = static function ( $k, callable $contenu ) use ( $rubriques ) {
			echo '<section id="r-' . esc_attr( $k ) . '" role="tabpanel" aria-labelledby="o-' . esc_attr( $k ) . '"' . ( 'calendrier' === $k ? '' : ' hidden' ) . '><h2>' . esc_html( $rubriques[ $k ][0] ) . '</h2><p class="reglages__but">' . esc_html( $rubriques[ $k ][2] ) . '</p>';
			$contenu();
			echo '</section>';
		};
		$bloc( 'calendrier', static function () use ( $c ) {
			echo '<div class="rangee">';
			$c( 'annee', 'Session', 'text', 'Année académique, par exemple 2027/2028.' );
			echo '<span></span>';
			$c( 'ouverture', 'Début des inscriptions', 'date', 'Laisse vide pour ouvrir tout de suite.' );
			$c( 'cloture', 'Date limite d\'inscription', 'date', 'Les inscriptions restent ouvertes tout ce jour.' );
			$c( 'date_cycle1', 'Épreuves du premier cycle', 'date', 'Affichée sur l\'accueil.' );
			$c( 'date_cycle2', 'Épreuves du second cycle', 'date', 'Affichée sur l\'accueil.' );
			echo '</div>';
		} );
		$bloc( 'frais', static function () use ( $c ) {
			echo '<div class="rangee">';
			$c( 'frais', 'Montant en FCFA', 'number' );
			$c( 'banque', 'Banque' );
			$c( 'compte', 'Numéro de compte', 'text', 'Imprimé sur l\'accueil et à l\'étape paiement.' );
			echo '</div>';
		} );
		$bloc( 'age', static function () use ( $c ) {
			echo '<div class="rangee">';
			$c( 'age_cycle1', 'Âge maximum, premier cycle', 'number' );
			$c( 'age_cycle2', 'Âge maximum, second cycle', 'number' );
			$c( 'date_age', 'Âge calculé à la date du', 'date', 'Fixée par l\'arrêté, en général le 1er janvier.' );
			echo '</div><p class="reglages__note">Les fonctionnaires ne sont pas concernés.</p>';
		} );
		$bloc( 'offre', static function () use ( $r ) {
			echo '<h3>Centres d\'examen ouverts</h3><div class="bascules">';
			foreach ( CEB_CENTRES as $k => $l ) {
				printf( '<label><input type="checkbox" role="switch" name="centres[]" value="%s" %s><i></i>%s</label>', esc_attr( $k ), checked( ! in_array( $k, $r['centres_fermes'], true ), true, false ), esc_html( $l ) );
			}
			echo '</div><h3>Options ouvertes</h3><div class="bascules">';
			foreach ( CEB_OPTIONS as $k => $l ) {
				printf( '<label><input type="checkbox" role="switch" name="options[]" value="%s" %s><i></i>%s</label>', esc_attr( $k ), checked( ! in_array( $k, $r['options_fermees'], true ), true, false ), esc_html( $l ) );
			}
			echo '</div>';
		} );
		$bloc( 'contact', static function () use ( $c ) {
			echo '<div class="rangee">';
			$c( 'telephone', 'Téléphone de la scolarité' );
			$c( 'email', 'E-mail de la scolarité', 'email' );
			echo '</div>';
		} );
		?>
		<div class="reglages__barre"><button class="btn btn--primaire" type="submit">Enregistrer</button></div>
	</div>
</form>
