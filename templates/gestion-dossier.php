<?php
/**
 * Un dossier côté scolarité : informations, reçu, décisions et journal.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$d = $dossier;
$lignes = array(
	'Concours' => array( 'Cycle' => CEB_CYCLES[ $d->cycle ]['nom'] ?? '', 'Filière' => CEB_FILIERES[ $d->filiere ] ?? '', 'Option' => CEB_OPTIONS[ $d->option_code ] ?? '', 'Centre' => CEB_CENTRES[ $d->centre ] ?? '', 'Langue' => CEB_LANGUES[ $d->langue ] ?? '', 'Fonctionnaire' => $d->fonctionnaire ? 'Oui' : 'Non' ),
	'Identité' => array( 'Naissance' => ceb_pdf_date( $d->date_naissance ) . ' à ' . $d->lieu_naissance, 'Sexe' => CEB_SEXES[ $d->sexe ] ?? '', 'Nationalité' => $d->nationalite, 'Situation' => CEB_MARITAL[ $d->marital ] ?? '', 'Handicap' => $d->handicap ? 'Oui' : 'Non', 'CNI' => $d->cni . ', ' . ceb_pdf_date( $d->cni_date ) . ' à ' . $d->cni_lieu ),
	'Contact' => array( 'Téléphone' => $d->telephone, 'E-mail' => $d->email, 'Adresse' => $d->adresse, 'Origine' => implode( ', ', array_filter( array( CEB_REGIONS[ $d->region ]['nom'] ?? '', $d->departement, $d->arrondissement ) ) ), 'À contacter' => trim( $d->urgence_nom . ' ' . $d->urgence_tel ) ),
	'Diplôme' => array( 'Diplôme' => CEB_DIPLOMES[ $d->diplome ] ?? '', 'Émetteur' => $d->etablissement, 'Année' => $d->annee_diplome, 'Mention' => CEB_MENTIONS[ $d->mention ] ?? '' ),
);
$file    = isset( CEB_FILES[ $_GET['file'] ?? '' ] ) ? (string) $_GET['file'] : '';
$formulaire = static function ( $decision, $texte, $classe, $motif = false, $avec_suite = '' ) use ( $d ) {
	?>
	<form method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>" class="decision">
		<?php ceb_champ_csrf(); ?>
		<input type="hidden" name="ceb_action" value="decision">
		<input type="hidden" name="dossier" value="<?php echo (int) $d->id; ?>">
		<input type="hidden" name="decision" value="<?php echo esc_attr( $decision ); ?>">
		<?php if ( $avec_suite ) : ?><input type="hidden" name="suivant" value="<?php echo esc_attr( $avec_suite ); ?>"><?php endif; ?>
		<?php if ( $motif ) : ?>
			<label><span class="evitement">Motif</span><input name="motif" required maxlength="255" list="motifs" placeholder="Motif visible par le candidat"></label>
		<?php endif; ?>
		<button class="btn <?php echo esc_attr( $classe ); ?>" type="submit"><?php echo esc_html( $texte ); ?></button>
	</form>
	<?php
};
$repli = static function ( $resume, $decision, $texte ) use ( $formulaire ) {
	echo '<details class="repli"><summary>' . esc_html( $resume ) . ceb_icone( 'chevron', 15 ) . '</summary>';
	$formulaire( $decision, $texte, 'btn--danger', true );
	echo '</details>';
};
$ouvert  = ! in_array( $d->statut, array( 'brouillon', 'valide' ), true );
$pieces  = ceb_pieces_dossier( $d );
$cochees = ceb_pieces_cochees( $d );
$etapes  = array(
	array( 'Paiement', 'verifie' === $d->paiement ),
	array( 'Pièces', (bool) $d->depot_le ),
	array( 'Validation', 'valide' === $d->statut ),
);
$suivant = $file ? ceb_suivant( $d->id, $file ) : 0;
?>
<datalist id="motifs"><?php foreach ( CEB_MOTIFS as $m ) : ?><option value="<?php echo esc_attr( $m ); ?>"></option><?php endforeach; ?></datalist>
<a class="feuille__retour" href="<?php echo esc_url( add_query_arg( array_filter( array( 'vue' => 'dossiers', 'file' => $file ) ), ceb_url( 'gestion' ) ) ); ?>"><?php echo ceb_icone( 'fleche-g', 16 ); ?><?php echo esc_html( $file ? CEB_FILES[ $file ]['nom'] : 'Dossiers' ); ?></a>
<header class="fiche-tete">
	<?php ceb_vignette( $d, 76 ); ?>
	<div>
		<h1><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ?: 'Sans nom' ); ?></h1>
		<p><b><?php echo esc_html( $d->numero ); ?></b>
			<?php ceb_etiquette( $d->statut, CEB_STATUTS[ $d->statut ] ?? '' ); ?>
			<?php ceb_etiquette( $d->paiement, 'Paiement ' . mb_strtolower( CEB_PAIEMENTS[ $d->paiement ] ?? '' ) ); ?>
		</p>
	</div>
	<div class="fiche-tete__actions">
		<?php if ( 'brouillon' !== $d->statut ) : ?>
			<a class="btn btn--fantome" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'fiche', $d->id, ceb_url( 'gestion' ) ) ); ?>"><?php echo ceb_icone( 'fichier', 16 ); ?>Fiche PDF</a>
		<?php endif; ?>
		<?php if ( $suivant ) : ?>
			<a class="btn btn--fantome" href="<?php echo esc_url( add_query_arg( array( 'dossier' => $suivant, 'file' => $file ), ceb_url( 'gestion' ) ) ); ?>">Dossier suivant<?php echo ceb_icone( 'fleche', 16 ); ?></a>
		<?php endif; ?>
	</div>
</header>
<?php if ( $d->alerte_age ) : ?>
	<?php ceb_alerte( 'attention', 'Âge au ' . ceb_date_fr( ceb_reglage( 'date_age' ) ) . ' : ' . ceb_age_au( $d->date_naissance, ceb_reglage( 'date_age' ) ) . ' ans, pour une limite de ' . ceb_limite_age( $d->cycle ) . ' ans.' ); ?>
<?php endif; ?>
<?php if ( 'rejete' === $d->statut && $d->motif ) : ?>
	<?php ceb_alerte( 'erreur', 'Motif envoyé au candidat : ' . $d->motif ); ?>
<?php endif; ?>
<div class="fiche-grille">
	<div class="panneau fiche-infos">
		<?php foreach ( $lignes as $titre => $champs ) : ?>
			<section>
				<h2><?php echo esc_html( $titre ); ?></h2>
				<dl>
					<?php foreach ( $champs as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( (string) $v ) ?: '-'; ?></dd></div>
					<?php endforeach; ?>
				</dl>
			</section>
		<?php endforeach; ?>
	</div>
	<aside class="fiche-actions">
		<ol class="avancement" aria-label="Avancement du dossier">
			<?php foreach ( $etapes as $i => $e ) : ?>
				<li class="<?php echo $e[1] ? 'est-faite' : ''; ?>"><i aria-hidden="true"><?php echo $e[1] ? ceb_icone( 'check', 13 ) : (int) $i + 1; ?></i><?php echo esc_html( $e[0] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<section class="panneau">
			<h2>Paiement</h2>
			<p class="transaction"><small><?php echo esc_html( ceb_fcfa( ceb_reglage( 'frais' ) ) ); ?>, transaction</small><?php echo esc_html( $d->transaction_num ?: 'Non renseignée' ); ?></p>
			<?php if ( $d->recu ) : ?>
				<a class="recu-lien" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/recu' ) ) ); ?>">
					<?php if ( 'image/jpeg' === $d->recu_type ) : ?><img src="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/recu' ) ) ); ?>" alt="Reçu de paiement"><?php else : ?><?php echo ceb_icone( 'recu', 18 ); ?>Ouvrir le reçu PDF<?php endif; ?>
				</a>
			<?php endif; ?>
			<?php if ( $ouvert && ceb_peut( 'ceb_paiements' ) ) : ?>
				<?php if ( 'verifie' !== $d->paiement ) : ?>
					<?php $formulaire( 'verifier_paiement', 'paiements' === $file ? 'Paiement conforme, suivant' : 'Paiement conforme', 'btn--primaire', false, 'paiements' === $file ? 'paiements' : '' ); ?>
				<?php endif; ?>
				<?php $repli( 'Rejeter le paiement', 'rejeter_paiement', 'Rejeter le paiement' ); ?>
			<?php endif; ?>
		</section>
		<?php if ( 'brouillon' !== $d->statut ) : ?>
			<section class="panneau">
				<h2>Pièces déposées <small><?php echo count( $cochees ); ?> sur <?php echo count( $pieces ); ?></small></h2>
				<form method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>" class="pointage">
					<?php ceb_champ_csrf(); ?>
					<input type="hidden" name="ceb_action" value="decision">
					<input type="hidden" name="dossier" value="<?php echo (int) $d->id; ?>">
					<input type="hidden" name="decision" value="pieces">
					<?php $peut = $ouvert && ceb_peut( 'ceb_pieces' ); ?>
					<?php foreach ( $pieces as $i => $piece ) : ?>
						<label><input type="checkbox" name="pieces[]" value="<?php echo (int) $i; ?>" <?php checked( in_array( $i, $cochees, true ) ); ?> <?php disabled( ! $peut ); ?>><span><?php echo esc_html( $piece ); ?></span></label>
					<?php endforeach; ?>
					<?php if ( $peut ) : ?><button class="btn btn--fantome" type="submit">Enregistrer le pointage</button><?php endif; ?>
				</form>
			</section>
			<?php if ( ceb_peut( 'ceb_valider' ) ) : ?>
				<section class="panneau">
					<h2>Décision</h2>
					<?php if ( 'valide' === $d->statut ) : ?>
						<?php $formulaire( 'rouvrir', 'Rouvrir le dossier', 'btn--fantome' ); ?>
					<?php else : ?>
						<?php $pret = 'verifie' === $d->paiement && $d->depot_le; ?>
						<?php if ( ! $pret ) : ?><p class="decision__aide">La validation s'ouvre quand le paiement est vérifié et toutes les pièces pointées.</p><?php endif; ?>
						<?php $formulaire( 'valider', 'valider' === $file ? 'Valider, suivant' : 'Valider le dossier', $pret ? 'btn--primaire' : 'btn--fantome', false, 'valider' === $file ? 'valider' : '' ); ?>
						<?php $repli( 'Renvoyer au candidat', 'rejeter', 'Renvoyer au candidat' ); ?>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		<?php endif; ?>
		<section class="panneau">
			<h2>Journal</h2>
			<?php $journal = ceb_journal( $d->id ); ?>
			<?php if ( ! $journal ) : ?><p class="vide">Aucune action pour le moment.</p><?php endif; ?>
			<ul class="journal">
				<?php foreach ( $journal as $j ) : ?>
					<li><?php echo esc_html( $j->action ); ?><small><?php echo esc_html( $j->auteur . ', ' . wp_date( 'd/m/Y H:i', strtotime( $j->cree_le ) ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
		</section>
	</aside>
</div>
