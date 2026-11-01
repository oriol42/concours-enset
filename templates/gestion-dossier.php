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
$suivant = $file ? ceb_suivant( $d->id, $file ) : 0;
$attente = 'rejete' === $d->statut;
$active  = $attente ? 2 : ( 'verifie' !== $d->paiement ? 0 : ( ! $d->depot_le ? 1 : 2 ) );
$cles    = array(
	'Option'     => CEB_OPTIONS[ $d->option_code ] ?? '',
	'Filière'    => CEB_FILIERES[ $d->filiere ] ?? '',
	'Cycle'      => CEB_CYCLES[ $d->cycle ]['nom'] ?? '',
	'Centre'     => CEB_CENTRES[ $d->centre ] ?? '',
	'Diplôme'    => CEB_DIPLOMES[ $d->diplome ] ?? '',
	'Naissance'  => ceb_pdf_date( $d->date_naissance ) . ' à ' . $d->lieu_naissance,
	'Téléphone'  => $d->telephone,
	'CNI'        => $d->cni,
);
$journal = ceb_journal( $d->id );
$etapes  = array(
	array( 'paiement', 'Paiement', 'verifie' === $d->paiement ),
	array( 'pieces', 'Pièces', (bool) $d->depot_le ),
	array( 'decision', 'Décision', 'valide' === $d->statut ),
);
?>
<datalist id="motifs"><?php foreach ( CEB_MOTIFS as $m ) : ?><option value="<?php echo esc_attr( $m ); ?>"></option><?php endforeach; ?></datalist>
<a class="feuille__retour" href="<?php echo esc_url( add_query_arg( array_filter( array( 'vue' => 'dossiers', 'file' => $file ) ), ceb_url( 'gestion' ) ) ); ?>"><?php echo ceb_icone( 'fleche-g', 16 ); ?><?php echo esc_html( $file ? CEB_FILES[ $file ]['nom'] : 'Tous les dossiers' ); ?></a>

<div class="dossier">
	<aside class="identite">
		<div class="identite__photo">
			<?php if ( $d->photo ) : ?>
				<img src="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/photo' ) ) ); ?>" alt="">
			<?php else : ?>
				<span><?php echo esc_html( ceb_initiales( $d ) ); ?></span>
			<?php endif; ?>
		</div>
		<h1><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ?: 'Sans nom' ); ?></h1>
		<p class="identite__numero"><?php echo esc_html( $d->numero ); ?></p>
		<p class="identite__statut"><?php ceb_etiquette( $d->statut, CEB_STATUTS[ $d->statut ] ?? '' ); ?></p>
		<?php if ( $d->alerte_age ) : ?>
			<p class="identite__age"><?php echo ceb_icone( 'alerte', 15 ); ?><?php echo (int) ceb_age_au( $d->date_naissance, ceb_reglage( 'date_age' ) ); ?> ans, limite <?php echo (int) ceb_limite_age( $d->cycle ); ?> ans</p>
		<?php endif; ?>
		<dl>
			<?php foreach ( $cles as $k => $v ) : ?>
				<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( (string) $v ) ?: '-'; ?></dd></div>
			<?php endforeach; ?>
		</dl>
		<?php if ( 'brouillon' !== $d->statut ) : ?>
			<a class="btn btn--fantome" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'fiche', $d->id, ceb_url( 'gestion' ) ) ); ?>"><?php echo ceb_icone( 'fichier', 16 ); ?>Fiche PDF</a>
		<?php endif; ?>
	</aside>

	<div class="atelier">
		<div class="atelier__onglets" role="tablist" aria-label="Traitement du dossier">
			<?php foreach ( $etapes as $i => $e ) : ?>
				<button type="button" role="tab" class="pas<?php echo $e[2] ? ' est-fait' : ''; ?>" id="o-<?php echo esc_attr( $e[0] ); ?>" aria-controls="t-<?php echo esc_attr( $e[0] ); ?>" aria-selected="<?php echo $i === $active ? 'true' : 'false'; ?>">
					<i aria-hidden="true"><?php echo $e[2] ? ceb_icone( 'check', 13 ) : (int) $i + 1; ?></i><?php echo esc_html( $e[1] ); ?>
				</button>
			<?php endforeach; ?>
			<span class="atelier__espace"></span>
			<button type="button" role="tab" id="o-infos" aria-controls="t-infos" aria-selected="false">Tout le dossier</button>
			<button type="button" role="tab" id="o-journal" aria-controls="t-journal" aria-selected="false">Journal</button>
		</div>

		<?php if ( $attente ) : ?>
			<div class="attente"><?php echo ceb_icone( 'horloge', 20 ); ?><div><b>En attente du candidat</b><p><?php echo esc_html( $d->motif ); ?>. Il a été prévenu et le dossier revient ici dès sa correction.</p></div></div>
		<?php endif; ?>

		<section id="t-paiement" role="tabpanel" aria-labelledby="o-paiement" class="volet volet--paiement"<?php echo 0 === $active ? '' : ' hidden'; ?>>
			<div class="volet__recu">
				<?php if ( ! $d->recu ) : ?>
					<p class="vide">Aucun reçu envoyé.</p>
				<?php elseif ( 'image/jpeg' === $d->recu_type ) : ?>
					<a target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/recu' ) ) ); ?>"><img src="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/recu' ) ) ); ?>" alt="Reçu de paiement du candidat"></a>
				<?php else : ?>
					<a class="btn btn--fantome" target="_blank" rel="noopener" href="<?php echo esc_url( add_query_arg( 'id', $d->id, ceb_url( 'fichier/recu' ) ) ); ?>"><?php echo ceb_icone( 'recu', 18 ); ?>Ouvrir le reçu PDF</a>
				<?php endif; ?>
			</div>
			<div class="volet__cote">
				<h2>Comparer avec le reçu</h2>
				<dl>
					<div><dt>Montant attendu</dt><dd><?php echo esc_html( ceb_fcfa( ceb_reglage( 'frais' ) ) ); ?></dd></div>
					<div><dt>Banque</dt><dd><?php echo esc_html( ceb_reglage( 'banque' ) ); ?></dd></div>
					<div><dt>N° de transaction</dt><dd class="grand"><?php echo esc_html( $d->transaction_num ?: 'Non renseigné' ); ?></dd></div>
				</dl>
				<?php if ( 'verifie' === $d->paiement ) : ?>
					<p class="volet__fait"><?php echo ceb_icone( 'check', 16 ); ?>Paiement vérifié</p>
				<?php endif; ?>
				<?php if ( $ouvert && ! $attente && ceb_peut( 'ceb_paiements' ) ) : ?>
					<?php if ( 'verifie' !== $d->paiement ) : ?>
						<?php $formulaire( 'verifier_paiement', 'paiements' === $file ? 'Paiement conforme, suivant' : 'Paiement conforme', 'btn--primaire', false, 'paiements' === $file ? 'paiements' : '' ); ?>
					<?php endif; ?>
					<?php $repli( 'Rejeter le paiement', 'rejeter_paiement', 'Rejeter le paiement' ); ?>
				<?php endif; ?>
			</div>
		</section>

		<section id="t-pieces" role="tabpanel" aria-labelledby="o-pieces" class="volet"<?php echo 1 === $active ? '' : ' hidden'; ?>>
			<header class="volet__tete">
				<div><h2>Pièces reçues à la scolarité</h2><p>Coche ce que le candidat a déposé en papier.</p></div>
				<b><?php echo count( $cochees ); ?> / <?php echo count( $pieces ); ?></b>
			</header>
			<form method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>" class="pointage">
				<?php ceb_champ_csrf(); ?>
				<input type="hidden" name="ceb_action" value="decision">
				<input type="hidden" name="dossier" value="<?php echo (int) $d->id; ?>">
				<input type="hidden" name="decision" value="pieces">
				<?php $peut = $ouvert && ! $attente && ceb_peut( 'ceb_pieces' ); ?>
				<div class="pointage__liste">
					<?php foreach ( $pieces as $i => $piece ) : ?>
						<label><input type="checkbox" name="pieces[]" value="<?php echo (int) $i; ?>" <?php checked( in_array( $i, $cochees, true ) ); ?> <?php disabled( ! $peut ); ?>><span><?php echo esc_html( $piece ); ?></span></label>
					<?php endforeach; ?>
				</div>
				<?php if ( $peut ) : ?><button class="btn btn--primaire" type="submit">Enregistrer les pièces</button><?php endif; ?>
			</form>
		</section>

		<section id="t-decision" role="tabpanel" aria-labelledby="o-decision" class="volet volet--decision"<?php echo 2 === $active ? '' : ' hidden'; ?>>
			<?php if ( 'brouillon' === $d->statut ) : ?>
				<h2>Candidature en cours de saisie</h2>
				<p>Le candidat n'a pas encore envoyé son dossier.</p>
			<?php elseif ( ! ceb_peut( 'ceb_valider' ) ) : ?>
				<h2><?php echo esc_html( CEB_STATUTS[ $d->statut ] ?? '' ); ?></h2>
				<p>Ton rôle permet de consulter ce dossier.</p>
			<?php elseif ( 'valide' === $d->statut ) : ?>
				<h2>Dossier validé</h2>
				<p>Le candidat est admis à composer au centre de <?php echo esc_html( CEB_CENTRES[ $d->centre ] ?? '' ); ?>.</p>
				<?php $formulaire( 'rouvrir', 'Rouvrir le dossier', 'btn--fantome' ); ?>
			<?php elseif ( $attente ) : ?>
				<h2>Rien à faire pour l'instant</h2>
				<p>Tu peux reprendre le dossier sans attendre si le candidat t'a apporté la correction.</p>
				<?php $formulaire( 'rouvrir', 'Reprendre le traitement', 'btn--fantome' ); ?>
			<?php else : ?>
				<?php $pret = 'verifie' === $d->paiement && $d->depot_le; ?>
				<h2><?php echo $pret ? 'Tout est en ordre' : 'Pas encore prêt'; ?></h2>
				<ul class="conditions">
					<li class="<?php echo 'verifie' === $d->paiement ? 'est-ok' : ''; ?>"><?php echo ceb_icone( 'verifie' === $d->paiement ? 'check' : 'horloge', 16 ); ?>Paiement vérifié</li>
					<li class="<?php echo $d->depot_le ? 'est-ok' : ''; ?>"><?php echo ceb_icone( $d->depot_le ? 'check' : 'horloge', 16 ); ?>Toutes les pièces reçues</li>
				</ul>
				<?php $formulaire( 'valider', 'valider' === $file ? 'Valider, suivant' : 'Valider le dossier', $pret ? 'btn--primaire' : 'btn--fantome', false, 'valider' === $file ? 'valider' : '' ); ?>
				<?php $repli( 'Renvoyer au candidat', 'rejeter', 'Renvoyer au candidat' ); ?>
			<?php endif; ?>
		</section>

		<section id="t-infos" role="tabpanel" aria-labelledby="o-infos" class="volet fiche-infos" hidden>
			<?php foreach ( $lignes as $titre => $champs ) : ?>
				<div>
					<h2><?php echo esc_html( $titre ); ?></h2>
					<dl>
						<?php foreach ( $champs as $k => $v ) : ?>
							<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( (string) $v ) ?: '-'; ?></dd></div>
						<?php endforeach; ?>
					</dl>
				</div>
			<?php endforeach; ?>
		</section>

		<section id="t-journal" role="tabpanel" aria-labelledby="o-journal" class="volet" hidden>
			<?php if ( ! $journal ) : ?><p class="vide">Aucune action pour le moment.</p><?php endif; ?>
			<ul class="journal">
				<?php foreach ( $journal as $j ) : ?>
					<li><?php echo esc_html( $j->action ); ?><small><?php echo esc_html( $j->auteur . ', ' . wp_date( 'd/m/Y H:i', strtotime( $j->cree_le ) ) ); ?></small></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<?php if ( $suivant ) : ?>
			<a class="atelier__suivant" href="<?php echo esc_url( add_query_arg( array( 'dossier' => $suivant, 'file' => $file ), ceb_url( 'gestion' ) ) ); ?>">Dossier suivant<?php echo ceb_icone( 'fleche', 16 ); ?></a>
		<?php endif; ?>
	</div>
</div>
