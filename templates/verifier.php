<?php
/**
 * Contrôle du QR code d'une fiche : le minimum pour authentifier un candidat
 * à l'entrée d'une salle, sans téléphone ni adresse.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$d = $GLOBALS['ceb_verifie'] ?? null;
if ( ! $d || 'brouillon' === $d->statut ) {
	status_header( 404 );
	$d = null;
}
ceb_debut( array( 'titre' => 'Vérification', 'corps' => 'page-acces', 'nu' => true ) );
?>
<main id="contenu" class="acces">
	<div class="acces__image"><img src="<?php echo esc_url( CEB_URI . '/assets/images/campus-aerien.webp' ); ?>" alt="Vue aérienne du campus de l'Université d'Ebolowa" width="1400" height="700"></div>
	<div class="acces__carte">
		<section class="acces__volet verification">
			<img class="acces__logo" src="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.png' ); ?>" alt="ENSET d'Ebolowa" width="96" height="96">
			<?php if ( ! $d ) : ?>
				<h1>Fiche introuvable</h1>
				<p class="acces__intro">Ce code ne correspond à aucune candidature enregistrée.</p>
			<?php else : ?>
				<h1><?php echo esc_html( $d->numero ); ?></h1>
				<?php ceb_alerte( 'valide' === $d->statut ? 'succes' : 'info', 'Candidature enregistrée. Statut : ' . ( CEB_STATUTS[ $d->statut ] ?? '' ) . '. Paiement : ' . mb_strtolower( CEB_PAIEMENTS[ $d->paiement ] ?? '' ) . '.' ); ?>
				<dl>
					<div><dt>Candidat</dt><dd><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ); ?></dd></div>
					<div><dt>Session</dt><dd><?php echo esc_html( $d->annee ); ?></dd></div>
					<div><dt>Option</dt><dd><?php echo esc_html( CEB_OPTIONS[ $d->option_code ] ?? '' ); ?></dd></div>
					<div><dt>Centre</dt><dd><?php echo esc_html( CEB_CENTRES[ $d->centre ] ?? '' ); ?></dd></div>
				</dl>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
ceb_fin( array( 'nu' => true ) );
