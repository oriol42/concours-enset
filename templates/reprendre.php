<?php
/**
 * Reprise du dossier : numéro candidat ou téléphone, et date de naissance.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

list( $saisie, $erreurs ) = ceb_reprendre();
ceb_debut( array( 'titre' => 'Reprendre mon dossier', 'corps' => 'page-acces', 'nu' => true ) );
?>
<main id="contenu" class="acces">
	<div class="acces__image"><img src="<?php echo esc_url( CEB_URI . '/assets/images/campus-aerien.webp' ); ?>" alt="Vue aérienne du campus de l'Université d'Ebolowa" width="1400" height="700"></div>
	<div class="acces__carte">
		<section class="acces__volet">
			<a class="feuille__retour" href="<?php echo esc_url( ceb_url() ); ?>"><?php echo ceb_icone( 'fleche-g', 16 ); ?>Accueil</a>
			<img class="acces__logo" src="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.png' ); ?>" alt="ENSET d'Ebolowa" width="96" height="96">
			<h1>Reprendre mon dossier</h1>
			<p class="acces__intro">Pour le compléter, le corriger ou réimprimer ta fiche.</p>
			<?php ceb_afficher_flash(); ?>
			<?php if ( ! empty( $erreurs['general'] ) ) : ?>
				<?php ceb_alerte( 'erreur', $erreurs['general'] ); ?>
			<?php endif; ?>
			<form class="formulaire" method="post" action="<?php echo esc_url( ceb_url( 'reprendre' ) ); ?>" data-etape-form novalidate>
				<?php ceb_champ_csrf(); ?>
				<input type="hidden" name="ceb_action" value="reprendre">
				<?php
				ceb_champ( array( 'nom' => 'identifiant', 'libelle' => 'Numéro candidat ou téléphone', 'valeur' => $saisie['identifiant'] ?? '', 'attrs' => array( 'autocapitalize' => 'characters', 'autocomplete' => 'off', 'spellcheck' => 'false', 'placeholder' => 'UEB-2028-000123' ) ) );
				ceb_champ( array( 'nom' => 'date_naissance', 'libelle' => 'Date de naissance', 'type' => 'date' ) );
				?>
				<button class="btn btn--primaire btn--grand" type="submit" data-envoi><span>Ouvrir mon dossier</span></button>
			</form>
			<?php if ( 'ouvert' === ceb_etat_session() ) : ?>
				<p class="acces__bascule">Pas encore inscrit ? <a href="<?php echo esc_url( ceb_url( 'candidature/1' ) ); ?>">M'inscrire au concours</a></p>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
ceb_fin( array( 'nu' => true ) );
