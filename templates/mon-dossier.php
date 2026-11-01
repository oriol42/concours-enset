<?php
/**
 * Suivi du dossier par le candidat : où il en est, sa fiche, ce qu'il reste à faire.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$d         = ceb_dossier_courant();
$brouillon = 'brouillon' === $d->statut;
$modifiable = ceb_modifiable( $d );
$telecharger = ! empty( $_SESSION['ceb_telecharger'] );
unset( $_SESSION['ceb_telecharger'] );

$suivi = array(
	array( 'Candidature envoyée', ! $brouillon, $d->soumis_le ? wp_date( 'd/m/Y', strtotime( $d->soumis_le ) ) : 'Pas encore envoyée' ),
	array( 'Paiement vérifié', 'verifie' === $d->paiement, CEB_PAIEMENTS[ $d->paiement ] ?? '' ),
	array( 'Pièces reçues à la scolarité', (bool) $d->depot_le, $d->depot_le ? wp_date( 'd/m/Y', strtotime( $d->depot_le ) ) : 'À déposer avant la date limite' ),
	array( 'Dossier validé', 'valide' === $d->statut, 'valide' === $d->statut ? 'Tu es admis à composer' : 'Dernière étape' ),
);

ceb_debut( array( 'titre' => 'Mon dossier', 'corps' => 'page-dossier' ) );
?>
<main id="contenu" class="conteneur espace">
	<?php ceb_afficher_flash(); ?>
	<header class="espace__tete">
		<div class="espace__photo">
			<?php if ( $d->photo ) : ?><img src="<?php echo esc_url( ceb_url( 'fichier/photo' ) ); ?>" alt="" width="84" height="84"><?php else : ?><?php echo ceb_icone( 'utilisateur', 32 ); ?><?php endif; ?>
		</div>
		<div>
			<h1><?php echo esc_html( trim( $d->prenoms . ' ' . $d->noms ) ); ?></h1>
			<p>N° candidat <b><?php echo esc_html( $d->numero ); ?></b></p>
		</div>
		<?php ceb_etiquette( $d->statut, CEB_STATUTS_CANDIDAT[ $d->statut ] ?? '' ); ?>
	</header>

	<?php if ( 'rejete' === $d->statut ) : ?>
		<?php ceb_alerte( 'erreur', 'À corriger : ' . $d->motif ); ?>
	<?php endif; ?>
	<?php if ( $d->alerte_age && ! $brouillon ) : ?>
		<?php ceb_alerte( 'attention', 'Ta date de naissance dépasse la limite d\'âge de l\'arrêté. La scolarité examinera ton dossier.' ); ?>
	<?php endif; ?>

	<div class="espace__grille">
		<section class="suivi" aria-labelledby="suivi-titre">
			<h2 id="suivi-titre">Où en est ton dossier</h2>
			<ol>
				<?php foreach ( $suivi as $s ) : ?>
					<li class="<?php echo $s[1] ? 'est-faite' : ''; ?>">
						<i aria-hidden="true"><?php echo $s[1] ? ceb_icone( 'check', 14 ) : ''; ?></i>
						<div><b><?php echo esc_html( $s[0] ); ?></b><span><?php echo esc_html( $s[2] ); ?></span></div>
					</li>
				<?php endforeach; ?>
			</ol>
			<div class="suivi__actions">
				<?php if ( $brouillon ) : ?>
					<a class="btn btn--primaire" href="<?php echo esc_url( ceb_url( 'candidature/' . min( 6, ceb_premiere_etape_manquante( $d ) ) ) ); ?>">Continuer ma candidature<?php echo ceb_icone( 'fleche', 18 ); ?></a>
				<?php else : ?>
					<a class="btn btn--primaire" href="<?php echo esc_url( ceb_url( 'mon-dossier/fiche' ) ); ?>" target="_blank" rel="noopener" <?php echo $telecharger ? 'data-ouvrir' : ''; ?>><?php echo ceb_icone( 'telecharger', 18 ); ?>Ma fiche PDF</a>
					<?php if ( $modifiable ) : ?>
						<a class="btn btn--fantome" href="<?php echo esc_url( ceb_url( 'candidature/6' ) ); ?>"><?php echo ceb_icone( 'crayon', 16 ); ?>Corriger</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</section>

		<section class="a-deposer" aria-labelledby="pieces-titre">
			<h2 id="pieces-titre">À déposer à la scolarité</h2>
			<ul class="pieces">
				<?php foreach ( ceb_pieces( $d->cycle ) as $piece ) : ?>
					<li><?php echo ceb_icone( 'check', 16 ); ?><?php echo esc_html( $piece ); ?></li>
				<?php endforeach; ?>
				<?php if ( $d->fonctionnaire ) : ?>
					<li><?php echo ceb_icone( 'check', 16 ); ?>L'autorisation de ton ministère de tutelle</li>
				<?php endif; ?>
			</ul>
			<?php if ( ceb_reglage( 'cloture' ) ) : ?>
				<p class="note">Date limite : <?php echo esc_html( ceb_date_fr( ceb_reglage( 'cloture' ) ) ); ?>.</p>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
ceb_fin();
