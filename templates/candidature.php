<?php
/**
 * Formulaire de candidature : un volet de progression et l'étape en cours.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$etape   = (int) $GLOBALS['ceb_etape'];
$dossier = ceb_dossier_courant();
list( $saisie, $erreurs ) = ceb_reprendre();

/* Valeur à afficher : saisie refusée, sinon dossier, sinon choix de l'étape 1 gardé en session. */
$base = $dossier ? get_object_vars( $dossier ) : ( $_SESSION['ceb_choix'] ?? array() );
$val  = static fn( $cle, $defaut = '' ) => $saisie[ $cle ] ?? ( $base[ $cle ] ?? $defaut );
$f    = static fn( array $c ) => ceb_champ( $c + array( 'valeur' => $val( $c['nom'] ), 'erreurs' => $erreurs ) );
$r    = ceb_reglages();

$titres = array(
	1 => array( 'Ton concours', 'Le choix du diplôme affiche les filières qui te sont ouvertes.' ),
	2 => array( 'Ton identité', 'Écris tout comme sur ton acte de naissance.' ),
	3 => array( 'Tes coordonnées', 'La scolarité s\'en sert pour te joindre.' ),
	4 => array( 'Ton diplôme et ta photo', 'Le diplôme qui te donne droit au concours.' ),
	5 => array( 'Ton paiement', 'Le numéro imprimé sur ton reçu de banque.' ),
	6 => array( 'Vérifie ton dossier', 'Relis tout avant l\'envoi. Tu pourras encore corriger tant qu\'il n\'est pas validé.' ),
);

ceb_debut( array( 'titre' => $titres[ $etape ][0], 'corps' => 'page-candidature', 'nu' => true ) );
?>
<main id="contenu" class="parcours">
	<header class="parcours__tete">
		<div class="conteneur">
			<div class="parcours__ligne">
				<a class="marque" href="<?php echo esc_url( ceb_url() ); ?>">
					<img src="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.png' ); ?>" alt="" width="50" height="50">
					<span class="marque__nom">Concours ENSET<small>Session <?php echo esc_html( $r['annee'] ); ?></small></span>
				</a>
				<span class="parcours__sauve"><?php echo ceb_icone( 'check', 15 ); ?>Enregistré à chaque étape</span>
			</div>
			<ol class="etapes">
				<?php foreach ( CEB_ETAPES as $n => $nom ) : ?>
					<?php
					$fait    = $n < $etape || ( $dossier && $n < (int) $dossier->etape );
					$ouverte = $dossier ? $n <= max( (int) $dossier->etape, 1 ) : $n <= $etape;
					$classe  = $n === $etape ? 'est-active' : ( $fait ? 'est-faite' : '' );
					?>
					<li class="<?php echo esc_attr( $classe ); ?>"<?php echo $n === $etape ? ' aria-current="step"' : ''; ?>>
						<?php if ( $ouverte && $n !== $etape ) : ?><a href="<?php echo esc_url( ceb_url( 'candidature/' . $n ) ); ?>"><?php else : ?><span class="etapes__item"><?php endif; ?>
							<i aria-hidden="true"><?php echo $fait && $n !== $etape ? ceb_icone( 'check', 14 ) : (int) $n; ?></i><?php echo esc_html( $nom ); ?>
						<?php echo $ouverte && $n !== $etape ? '</a>' : '</span>'; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<div class="progression" role="progressbar" aria-valuemin="1" aria-valuemax="6" aria-valuenow="<?php echo (int) $etape; ?>" aria-label="Étape <?php echo (int) $etape; ?> sur 6"><span style="width:<?php echo round( $etape / 6 * 100 ); ?>%"></span></div>
	</header>

	<div class="conteneur parcours__grille">
		<section class="feuille" aria-labelledby="titre-etape">
			<a class="feuille__retour" href="<?php echo esc_url( $etape > 1 ? ceb_url( 'candidature/' . ( $etape - 1 ) ) : ceb_url() ); ?>"><?php echo ceb_icone( 'fleche-g', 16 ); ?><?php echo $etape > 1 ? esc_html( CEB_ETAPES[ $etape - 1 ] ) : 'Accueil'; ?></a>
			<p class="feuille__compteur">Étape <?php echo (int) $etape; ?> sur 6</p>
			<h1 id="titre-etape" tabindex="-1"><?php echo esc_html( $titres[ $etape ][0] ); ?></h1>
			<p class="feuille__intro"><?php echo esc_html( $titres[ $etape ][1] ); ?></p>

			<?php ceb_afficher_flash(); ?>
			<?php if ( $dossier && 'rejete' === $dossier->statut && $dossier->motif ) : ?>
				<?php ceb_alerte( 'attention', 'À corriger : ' . $dossier->motif ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $erreurs['general'] ) ) : ?>
				<?php ceb_alerte( 'erreur', $erreurs['general'] ); ?>
			<?php endif; ?>
			<?php if ( $erreurs && empty( $erreurs['general'] ) ) : ?>
				<div class="resume-erreurs" role="alert" tabindex="-1" data-resume>
					<p><?php echo count( $erreurs ) > 1 ? count( $erreurs ) . ' points à corriger' : 'Un point à corriger'; ?></p>
					<ul>
						<?php foreach ( $erreurs as $cle => $message ) : ?>
							<li><a href="#c-<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $message ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( 6 === $etape ) : ?>
				<?php require CEB_DIR . '/templates/recapitulatif.php'; ?>
			<?php else : ?>
				<form class="formulaire" method="post" enctype="multipart/form-data" action="<?php echo esc_url( ceb_url( 'candidature/' . $etape ) ); ?>" data-etape-form novalidate>
					<?php ceb_champ_csrf(); ?>
					<input type="hidden" name="ceb_action" value="etape">
					<input type="hidden" name="etape" value="<?php echo (int) $etape; ?>">

					<?php if ( 1 === $etape ) : ?>
						<?php
						ceb_choix( array( 'nom' => 'cycle', 'libelle' => 'Tu vises', 'options' => array_map( static fn( $c ) => $c['nom'], CEB_CYCLES ), 'valeur' => $val( 'cycle' ), 'erreurs' => $erreurs ) );
						$cycle = $val( 'cycle' );
						$f( array( 'nom' => 'diplome', 'libelle' => 'Diplôme présenté', 'options' => $cycle ? ceb_diplomes_du_cycle( $cycle ) : CEB_DIPLOMES, 'vide' => 'Choisir un diplôme' ) );
						$parcours = $saisie['parcours'] ?? ( ! empty( $base['filiere'] ) ? $base['filiere'] . '|' . $base['option_code'] : '' );
						?>
						<fieldset class="parcours-choix<?php echo isset( $erreurs['parcours'] ) ? ' champ--invalide' : ''; ?>" data-champ="parcours" data-actuel="<?php echo esc_attr( $parcours ); ?>" hidden>
							<legend>Filière et option</legend>
							<div class="parcours-choix__liste" data-options></div>
							<p class="champ__erreur"<?php echo isset( $erreurs['parcours'] ) ? '' : ' hidden'; ?>><?php echo esc_html( $erreurs['parcours'] ?? '' ); ?></p>
						</fieldset>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'centre', 'libelle' => 'Centre d\'examen', 'options' => ceb_centres_ouverts() ) );
							ceb_choix( array( 'nom' => 'langue', 'libelle' => 'Langue de composition', 'options' => CEB_LANGUES, 'valeur' => $val( 'langue', '1' ), 'erreurs' => $erreurs ) );
							?>
						</div>
						<script type="application/json" id="offre"><?php echo wp_json_encode( ceb_offre_par_diplome(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
						<script type="application/json" id="diplomes"><?php echo wp_json_encode( array_map( static fn( $c ) => ceb_diplomes_du_cycle( $c ), array( 'CYC1' => 'CYC1', 'CYC2' => 'CYC2' ) ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>

					<?php elseif ( 2 === $etape ) : ?>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'noms', 'libelle' => 'Nom(s)', 'attrs' => array( 'autocomplete' => 'family-name', 'autocapitalize' => 'characters', 'maxlength' => 120 ) ) );
							$f( array( 'nom' => 'prenoms', 'libelle' => 'Prénom(s)', 'facultatif' => true, 'attrs' => array( 'autocomplete' => 'given-name', 'autocapitalize' => 'characters', 'maxlength' => 120 ) ) );
							$f( array( 'nom' => 'date_naissance', 'libelle' => 'Date de naissance', 'type' => 'date', 'aide' => 'Elle te servira à reprendre ton dossier.', 'attrs' => array( 'autocomplete' => 'bday', 'max' => ceb_aujourdhui() ) ) );
							$f( array( 'nom' => 'lieu_naissance', 'libelle' => 'Lieu de naissance', 'attrs' => array( 'autocapitalize' => 'characters', 'maxlength' => 120 ) ) );
							?>
						</div>
						<?php ceb_choix( array( 'nom' => 'sexe', 'libelle' => 'Sexe', 'options' => CEB_SEXES, 'valeur' => $val( 'sexe' ), 'erreurs' => $erreurs ) ); ?>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'nationalite', 'libelle' => 'Nationalité', 'valeur' => $val( 'nationalite' ) ?: 'Camerounaise', 'attrs' => array( 'maxlength' => 60 ) ) );
							$f( array( 'nom' => 'marital', 'libelle' => 'Situation familiale', 'options' => CEB_MARITAL, 'valeur' => $val( 'marital', '1' ) ) );
							?>
						</div>
						<label class="case"><input type="checkbox" name="fonctionnaire" value="1" <?php checked( (string) $val( 'fonctionnaire' ), '1' ); ?>><span>Je suis fonctionnaire <small>L'autorisation de ton ministère sera demandée et la limite d'âge ne s'applique pas.</small></span></label>
						<label class="case"><input type="checkbox" name="handicap" value="1" <?php checked( (string) $val( 'handicap' ), '1' ); ?>><span>Je suis en situation de handicap <small>La scolarité pourra adapter ton accueil en salle.</small></span></label>
						<div data-alerte-age hidden></div>

					<?php elseif ( 3 === $etape ) : ?>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'telephone', 'libelle' => 'Téléphone', 'type' => 'tel', 'aide' => 'Tu pourras retrouver ton dossier avec ce numéro.', 'attrs' => array( 'autocomplete' => 'tel-national', 'inputmode' => 'tel', 'placeholder' => '677 12 34 56' ) ) );
							$f( array( 'nom' => 'email', 'libelle' => 'E-mail', 'type' => 'email', 'aide' => 'Ton numéro candidat y sera envoyé.', 'attrs' => array( 'autocomplete' => 'email', 'autocapitalize' => 'none', 'spellcheck' => 'false' ) ) );
							?>
						</div>
						<?php $f( array( 'nom' => 'adresse', 'libelle' => 'Adresse actuelle', 'aide' => 'Ville et quartier.', 'attrs' => array( 'autocomplete' => 'street-address', 'maxlength' => 160 ) ) ); ?>
						<h2 class="sous-titre">Origine</h2>
						<div class="rangee rangee--trois">
							<?php
							$f( array( 'nom' => 'region', 'libelle' => 'Région', 'options' => array_map( static fn( $x ) => $x['nom'], CEB_REGIONS ) ) );
							$reg = $val( 'region' );
							$f( array( 'nom' => 'departement', 'libelle' => 'Département', 'options' => isset( CEB_REGIONS[ $reg ] ) ? array_combine( CEB_REGIONS[ $reg ]['dep'], CEB_REGIONS[ $reg ]['dep'] ) : array(), 'vide' => 'Choisis la région' ) );
							$dep = $val( 'departement' );
							$arr = CEB_ARRONDISSEMENTS[ $dep ] ?? array();
							$f( array( 'nom' => 'arrondissement', 'libelle' => 'Arrondissement', 'options' => $arr ? array_combine( $arr, $arr ) : array(), 'vide' => 'Choisis le département' ) );
							?>
						</div>
						<script type="application/json" id="regions"><?php echo wp_json_encode( array_map( static fn( $x ) => $x['dep'], CEB_REGIONS ), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
						<script type="application/json" id="arrondissements"><?php echo wp_json_encode( CEB_ARRONDISSEMENTS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
						<h2 class="sous-titre">Carte nationale d'identité</h2>
						<div class="rangee rangee--trois">
							<?php
							$f( array( 'nom' => 'cni', 'libelle' => 'Numéro', 'aide' => 'Ou celui de ton récépissé.', 'attrs' => array( 'autocapitalize' => 'characters', 'autocomplete' => 'off', 'spellcheck' => 'false', 'maxlength' => 24 ) ) );
							$f( array( 'nom' => 'cni_date', 'libelle' => 'Délivrée le', 'type' => 'date', 'attrs' => array( 'max' => ceb_aujourdhui() ) ) );
							$f( array( 'nom' => 'cni_lieu', 'libelle' => 'Délivrée à', 'attrs' => array( 'autocapitalize' => 'characters', 'maxlength' => 80 ) ) );
							?>
						</div>
						<h2 class="sous-titre">Personne à contacter</h2>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'urgence_nom', 'libelle' => 'Nom', 'facultatif' => true, 'attrs' => array( 'maxlength' => 120 ) ) );
							$f( array( 'nom' => 'urgence_tel', 'libelle' => 'Téléphone', 'type' => 'tel', 'facultatif' => true, 'attrs' => array( 'inputmode' => 'tel' ) ) );
							?>
						</div>

					<?php elseif ( 4 === $etape ) : ?>
						<div class="rappel"><?php echo ceb_icone( 'diplome', 18 ); ?><span>Diplôme présenté : <b><?php echo esc_html( CEB_DIPLOMES[ $dossier->diplome ] ?? '' ); ?></b></span></div>
						<?php $f( array( 'nom' => 'etablissement', 'libelle' => 'Émetteur du diplôme', 'aide' => 'Établissement, université ou office.', 'attrs' => array( 'maxlength' => 160, 'autocapitalize' => 'characters' ) ) ); ?>
						<div class="rangee">
							<?php
							$f( array( 'nom' => 'annee_diplome', 'libelle' => 'Année d\'obtention', 'attrs' => array( 'inputmode' => 'numeric', 'maxlength' => 4, 'pattern' => '[0-9]{4}', 'placeholder' => substr( ceb_aujourdhui(), 0, 4 ) ) ) );
							$f( array( 'nom' => 'mention', 'libelle' => 'Mention', 'options' => CEB_MENTIONS ) );
							?>
						</div>
						<?php
						ceb_fichier_champ( array(
							'nom' => 'photo', 'libelle' => 'Photo d\'identité', 'icone' => 'appareil', 'accept' => 'image/jpeg,image/png', 'present' => (bool) $dossier->photo,
							'apercu' => $dossier->photo ? ceb_url( 'fichier/photo' ) : '', 'aide' => 'De face, fond clair, visage bien visible. JPG ou PNG, 5 Mo au plus.', 'carree' => true, 'erreurs' => $erreurs,
						) );
						?>

					<?php elseif ( 5 === $etape ) : ?>
						<?php if ( 'verifie' === $dossier->paiement ) : ?>
							<?php ceb_alerte( 'succes', 'Ton paiement est vérifié par la scolarité.' ); ?>
						<?php else : ?>
							<div class="versement">
								<div><span>Montant</span><b><?php echo esc_html( ceb_fcfa( $r['frais'] ) ); ?></b></div>
								<div><span>Banque</span><b><?php echo esc_html( $r['banque'] ); ?></b></div>
								<button type="button" class="copie" data-copier="<?php echo esc_attr( $r['compte'] ); ?>" aria-label="Copier le numéro de compte"><span><?php echo esc_html( $r['compte'] ); ?></span><b data-copie-etat>Copier</b></button>
							</div>
							<?php
							$f( array( 'nom' => 'transaction_num', 'libelle' => 'Numéro de transaction', 'aide' => 'Un même reçu ne peut servir qu\'à un seul candidat.', 'attrs' => array( 'autocapitalize' => 'characters', 'autocomplete' => 'off', 'spellcheck' => 'false', 'maxlength' => 30 ) ) );
							ceb_fichier_champ( array(
								'nom' => 'recu', 'libelle' => 'Photo du reçu', 'icone' => 'recu', 'accept' => 'image/jpeg,image/png,application/pdf', 'present' => (bool) $dossier->recu,
								'apercu' => $dossier->recu && 'image/jpeg' === $dossier->recu_type ? ceb_url( 'fichier/recu' ) : '', 'aide' => 'Photo nette ou PDF, 5 Mo au plus.', 'carree' => false, 'erreurs' => $erreurs,
							) );
							?>
						<?php endif; ?>
					<?php endif; ?>

					<div class="actions-etape">
						<button class="btn btn--primaire btn--grand" type="submit" data-envoi><span>Continuer</span><?php echo ceb_icone( 'fleche', 18 ); ?></button>
					</div>
				</form>
				<script type="application/json" id="regles-age"><?php echo wp_json_encode( array( 'date' => $r['date_age'], 'CYC1' => (int) $r['age_cycle1'], 'CYC2' => (int) $r['age_cycle2'], 'cycle' => $val( 'cycle' ) ) ); ?></script>
			<?php endif; ?>
		</section>

		<aside class="lateral" aria-label="Ton dossier">
			<section>
				<h2>Ton dossier</h2>
				<?php if ( $dossier ) : ?>
					<p class="lateral__numero"><?php echo esc_html( $dossier->numero ); ?></p>
				<?php endif; ?>
				<?php $resume = array( 'Diplôme' => CEB_DIPLOMES[ $base['diplome'] ?? '' ] ?? '', 'Option' => CEB_OPTIONS[ $base['option_code'] ?? '' ] ?? '', 'Centre' => CEB_CENTRES[ $base['centre'] ?? '' ] ?? '' ); ?>
				<?php if ( ! array_filter( $resume ) ) : ?>
					<p>Tes choix s'afficheront ici.</p>
				<?php else : ?>
					<dl>
						<?php foreach ( array_filter( $resume ) as $k => $v ) : ?>
							<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</section>
			<section>
				<h2>Frais de concours</h2>
				<p><?php echo esc_html( ceb_fcfa( $r['frais'] ) ); ?> à <?php echo esc_html( $r['banque'] ); ?>. Garde ton reçu : il te sera demandé à l'étape 5.</p>
			</section>
			<?php if ( $r['telephone'] || $r['email'] ) : ?>
				<section>
					<h2>Besoin d'aide ?</h2>
					<p><?php echo esc_html( implode( ', ', array_filter( array( $r['telephone'], $r['email'] ) ) ) ); ?></p>
				</section>
			<?php endif; ?>
		</aside>
	</div>
	<?php ceb_credit(); ?>
</main>
<?php
ceb_fin( array( 'nu' => true ) );

/** Dépôt d'un fichier avec aperçu immédiat. */
function ceb_fichier_champ( array $c ) {
	$erreur = $c['erreurs'][ $c['nom'] ] ?? '';
	?>
	<div class="depot<?php echo $c['carree'] ? ' depot--photo' : ''; ?><?php echo $erreur ? ' champ--invalide' : ''; ?>" data-depot data-champ="<?php echo esc_attr( $c['nom'] ); ?>">
		<label for="c-<?php echo esc_attr( $c['nom'] ); ?>">
			<span class="depot__apercu" data-apercu>
				<?php if ( $c['apercu'] ) : ?>
					<img src="<?php echo esc_url( $c['apercu'] ); ?>" alt="">
				<?php else : ?>
					<?php echo ceb_icone( $c['icone'], 28 ); ?>
				<?php endif; ?>
			</span>
			<span class="depot__texte">
				<b><?php echo esc_html( $c['libelle'] ); ?></b>
				<span data-depot-etat><?php echo $c['present'] ? 'Fichier enregistré. Touche ici pour le remplacer.' : 'Touche ici pour prendre ou choisir une photo.'; ?></span>
				<small><?php echo esc_html( $c['aide'] ); ?></small>
			</span>
		</label>
		<input id="c-<?php echo esc_attr( $c['nom'] ); ?>" class="depot__fichier" type="file" name="<?php echo esc_attr( $c['nom'] ); ?>" accept="<?php echo esc_attr( $c['accept'] ); ?>">
		<p class="champ__erreur"<?php echo $erreur ? '' : ' hidden'; ?>><?php echo esc_html( $erreur ); ?></p>
	</div>
	<?php
}
