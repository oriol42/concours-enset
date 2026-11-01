<?php
/**
 * Dernière étape : tout le dossier en lecture, chaque rubrique menant à sa correction.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$d = $dossier;
$rubriques = array(
	1 => array( 'Concours', array(
		'Cycle' => CEB_CYCLES[ $d->cycle ]['nom'] ?? '', 'Filière' => CEB_FILIERES[ $d->filiere ] ?? '', 'Option' => CEB_OPTIONS[ $d->option_code ] ?? '',
		'Centre d\'examen' => CEB_CENTRES[ $d->centre ] ?? '', 'Langue' => CEB_LANGUES[ $d->langue ] ?? '',
	) ),
	2 => array( 'Identité', array(
		'Nom complet' => trim( $d->noms . ' ' . $d->prenoms ), 'Naissance' => ceb_date_fr( $d->date_naissance ) . ' à ' . $d->lieu_naissance,
		'Sexe' => CEB_SEXES[ $d->sexe ] ?? '', 'Nationalité' => $d->nationalite, 'Situation familiale' => CEB_MARITAL[ $d->marital ] ?? '',
	) ),
	3 => array( 'Coordonnées', array(
		'Téléphone' => $d->telephone, 'E-mail' => $d->email, 'Adresse' => $d->adresse,
		'Origine' => implode( ', ', array_filter( array( CEB_REGIONS[ $d->region ]['nom'] ?? '', $d->departement, $d->arrondissement ) ) ),
		'CNI' => $d->cni . ', délivrée le ' . ceb_date_fr( $d->cni_date ) . ' à ' . $d->cni_lieu,
	) ),
	4 => array( 'Diplôme', array(
		'Diplôme' => CEB_DIPLOMES[ $d->diplome ] ?? '', 'Émetteur' => $d->etablissement, 'Année' => $d->annee_diplome, 'Mention' => CEB_MENTIONS[ $d->mention ] ?? '',
	) ),
	5 => array( 'Paiement', array(
		'Montant' => ceb_fcfa( ceb_reglage( 'frais' ) ), 'Transaction' => $d->transaction_num,
	) ),
);
?>
<?php if ( $d->alerte_age ) : ?>
	<?php ceb_alerte( 'attention', 'Selon ta date de naissance, tu dépasses la limite d\'âge de ' . ceb_limite_age( $d->cycle ) . ' ans au ' . ceb_date_fr( ceb_reglage( 'date_age' ) ) . '. Ton dossier sera signalé à la scolarité.' ); ?>
<?php endif; ?>

<div class="recap">
	<div class="recap__photo">
		<?php if ( $d->photo ) : ?>
			<img src="<?php echo esc_url( ceb_url( 'fichier/photo' ) ); ?>" alt="Ta photo d'identité" width="120" height="120">
		<?php endif; ?>
	</div>
	<?php foreach ( $rubriques as $n => $rubrique ) : ?>
		<section class="recap__bloc">
			<header>
				<h2><?php echo esc_html( $rubrique[0] ); ?></h2>
				<a href="<?php echo esc_url( ceb_url( 'candidature/' . $n ) ); ?>" aria-label="Modifier : <?php echo esc_attr( $rubrique[0] ); ?>"><?php echo ceb_icone( 'crayon', 15 ); ?>Modifier</a>
			</header>
			<dl>
				<?php foreach ( $rubrique[1] as $label => $valeur ) : ?>
					<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( (string) $valeur ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endforeach; ?>
</div>

<form class="formulaire" method="post" action="<?php echo esc_url( ceb_url( 'candidature/6' ) ); ?>" data-etape-form novalidate>
	<?php ceb_champ_csrf(); ?>
	<input type="hidden" name="ceb_action" value="soumettre">
	<div data-champ="engagement"<?php echo isset( $erreurs['engagement'] ) ? ' class="champ--invalide"' : ''; ?>>
		<label class="case case--engagement">
			<input id="c-engagement" type="checkbox" name="engagement" value="1" required>
			<span>Je déclare sur l'honneur que ces informations sont exactes.</span>
		</label>
		<p class="champ__erreur"<?php echo isset( $erreurs['engagement'] ) ? '' : ' hidden'; ?>><?php echo esc_html( $erreurs['engagement'] ?? '' ); ?></p>
	</div>
	<div class="actions-etape">
		<button class="btn btn--primaire btn--grand" type="submit" data-envoi><span><?php echo 'brouillon' === $d->statut ? 'Envoyer mon dossier' : 'Enregistrer les corrections'; ?></span><?php echo ceb_icone( 'check', 18 ); ?></button>
	</div>
</form>
