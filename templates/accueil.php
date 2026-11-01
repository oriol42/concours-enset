<?php
/**
 * Page d'accueil : tout ce qu'un candidat doit savoir avant de s'inscrire,
 * dans l'ordre où il se pose les questions.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$etat    = ceb_etat_session();
$r       = ceb_reglages();
$offre   = ceb_offre_par_cycle();
$dossier = ceb_dossier_courant();
$centres = ceb_centres_ouverts();
$nb_opt  = array_sum( array_map( static fn( $c ) => array_sum( array_map( 'count', $c ) ), $offre ) );
$img     = static fn( $f ) => esc_url( CEB_URI . '/assets/images/' . $f );
$bouton  = static fn( $url, $texte, $classe = 'btn--primaire' ) => '<a class="btn ' . $classe . ' btn--grand btn--fleche" href="' . esc_url( $url ) . '">' . esc_html( $texte ) . '<span class="btn__pastille">' . ceb_icone( 'fleche', 18 ) . '</span></a>';
$etapes  = array(
	array( 'Paie les frais', ceb_fcfa( $r['frais'] ) . ' à ' . $r['banque'] . ', sur le compte de l\'ENSET. Garde ton reçu.' ),
	array( 'Remplis ta candidature', 'Six étapes depuis ton téléphone, enregistrées au fur et à mesure.' ),
	array( 'Imprime ta fiche', 'Ta fiche officielle sort en PDF avec ton numéro candidat et son récépissé.' ),
	array( 'Dépose ton dossier', 'À la scolarité de l\'ENSET ou à la délégation régionale du MINESEC de ton centre.' ),
);
$questions = array(
	array( 'Je n\'ai pas fini de remplir. Je perds tout ?', 'Non. Chaque étape est enregistrée. Reviens avec ton numéro candidat ou ton téléphone, et ta date de naissance.' ),
	array( 'J\'ai perdu mon numéro candidat.', 'Ouvre « Reprendre », saisis le téléphone donné à l\'inscription et ta date de naissance. Le numéro est aussi dans le courriel de confirmation.' ),
	array( 'Puis-je corriger mes informations ?', 'Oui, tant que la scolarité n\'a pas validé ton dossier et que les inscriptions sont ouvertes. Réimprime ensuite ta fiche.' ),
	array( 'Quelles photos puis-je envoyer ?', 'Une photo JPG ou PNG de 5 Mo au plus. Pour le reçu, un PDF est aussi accepté. Le numéro de transaction doit être lisible.' ),
	array( 'Mon diplôme n\'apparaît pas pour la filière voulue.', 'Le formulaire propose uniquement les filières ouvertes à chaque diplôme par l\'arrêté. Pour un diplôme étranger, joins l\'équivalence à ton dossier.' ),
	array( 'Que dois-je présenter le jour des épreuves ?', 'Ta carte nationale d\'identité et ton reçu de paiement.' ),
);

ceb_debut( array( 'corps' => 'page-accueil' ) );
?>
<main id="contenu">
	<section class="heros">
		<div class="conteneur heros__grille">
			<div class="heros__texte">
				<p class="heros__etat heros__etat--<?php echo esc_attr( $etat ); ?>" data-entree>
					<?php
					if ( 'clos' === $etat ) {
						echo 'Inscriptions closes, session ' . esc_html( $r['annee'] );
					} elseif ( 'a_venir' === $etat ) {
						echo 'Ouverture le ' . esc_html( ceb_date_fr( $r['ouverture'] ) );
					} else {
						echo 'Inscriptions ouvertes, session ' . esc_html( $r['annee'] );
					}
					?>
				</p>
				<h1 data-entree>Concours d'entrée à l'<em>ENSET d'Ebolowa</em></h1>
				<p class="heros__intro" data-entree>Choisis ta filière, remplis ta candidature en ligne et imprime ta fiche officielle.</p>
				<div class="heros__actions" data-entree>
					<?php if ( $dossier ) : ?>
						<?php echo $bouton( ceb_url( 'mon-dossier' ), 'Ouvrir mon dossier' ); // Déjà échappé. ?>
					<?php elseif ( 'ouvert' === $etat ) : ?>
						<?php echo $bouton( ceb_url( 'candidature/1' ), 'M\'inscrire au concours' ); // Déjà échappé. ?>
						<a class="heros__lien" href="<?php echo esc_url( ceb_url( 'reprendre' ) ); ?>">Reprendre mon dossier</a>
					<?php else : ?>
						<?php echo $bouton( ceb_url( 'reprendre' ), 'Reprendre mon dossier' ); // Déjà échappé. ?>
					<?php endif; ?>
				</div>
			</div>
			<div class="heros__scene" data-scene>
				<figure class="heros__arche">
					<img src="<?php echo $img( 'campus-aerien.webp' ); // Déjà échappé. ?>" alt="Vue aérienne du campus de l'Université d'Ebolowa, dans la forêt équatoriale" width="1400" height="700" fetchpriority="high">
				</figure>
			</div>
		</div>
	</section>

	<section class="reperes" aria-label="Le concours en chiffres">
		<div class="conteneur reperes__ligne">
			<p><b><?php echo esc_html( $r['cloture'] ? ceb_date_fr( $r['cloture'] ) : 'Selon l\'arrêté' ); ?></b><span>Date limite d'inscription</span></p>
			<p><b><?php echo esc_html( ceb_fcfa( $r['frais'] ) ); ?></b><span>Frais de concours</span></p>
			<p><b data-compte="<?php echo (int) $nb_opt; ?>"><?php echo (int) $nb_opt; ?></b><span>Options, en deux cycles</span></p>
			<p><b data-compte="<?php echo count( $centres ); ?>"><?php echo count( $centres ); ?></b><span>Centres d'examen</span></p>
		</div>
	</section>

	<section class="section">
		<div class="conteneur ecole">
			<div class="ecole__photos" data-apparition>
				<img src="<?php echo $img( 'sortie-technique.webp' ); // Déjà échappé. ?>" alt="Étudiants en sortie pédagogique sur un site industriel" width="1200" height="900" loading="lazy">
				<img src="<?php echo $img( 'en-cours.webp' ); // Déjà échappé. ?>" alt="Étudiants en salle de cours" width="1000" height="722" loading="lazy">
			</div>
			<div data-apparition>
				<h2>Former ceux qui transmettent la technique</h2>
				<p>L'ENSET d'Ebolowa prépare des enseignants de l'enseignement technique et des cadres pour l'industrie et le commerce.</p>
				<ul>
					<li><?php echo ceb_icone( 'diplome', 19 ); ?><span><b>Premier cycle</b> après un Baccalauréat, un GCE A/L ou un BT.</span></li>
					<li><?php echo ceb_icone( 'diplome', 19 ); ?><span><b>Second cycle</b> après une Licence ou un diplôme équivalent.</span></li>
					<li><?php echo ceb_icone( 'lieu', 19 ); ?><span><?php echo esc_html( implode( ', ', array_slice( $centres, 0, -1 ) ) . ' et ' . end( $centres ) ); ?> pour composer.</span></li>
				</ul>
			</div>
		</div>
	</section>

	<section class="section section--pale" id="filieres">
		<div class="conteneur">
			<header class="titre-section" data-apparition>
				<h2>Quelles filières pour ton diplôme ?</h2>
				<p>Choisis ton diplôme : seules les options que tu peux présenter restent affichées.</p>
			</header>
			<div data-trouveur>
				<div class="offre__barre">
					<div class="onglets" role="tablist" aria-label="Cycle">
						<?php foreach ( CEB_CYCLES as $code => $cycle ) : ?>
							<button type="button" role="tab" id="onglet-offre-<?php echo esc_attr( $code ); ?>" aria-controls="offre-<?php echo esc_attr( $code ); ?>" aria-selected="<?php echo 'CYC1' === $code ? 'true' : 'false'; ?>"><?php echo esc_html( $cycle['nom'] ); ?></button>
						<?php endforeach; ?>
					</div>
					<div class="champ">
						<label for="trouveur-diplome">Ton diplôme</label>
						<div class="champ__select">
							<select id="trouveur-diplome">
								<option value="">Tous les diplômes</option>
								<?php foreach ( CEB_CYCLES as $code => $cycle ) : ?>
									<optgroup label="<?php echo esc_attr( $cycle['nom'] ); ?>">
										<?php foreach ( ceb_diplomes_du_cycle( $code ) as $v => $l ) : ?>
											<option value="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $l ); ?></option>
										<?php endforeach; ?>
									</optgroup>
								<?php endforeach; ?>
							</select>
							<?php echo ceb_icone( 'chevron', 18 ); ?>
						</div>
					</div>
				</div>
				<div data-resultat aria-live="polite" hidden></div>
				<div data-listes>
					<?php foreach ( CEB_CYCLES as $code => $cycle ) : ?>
						<ul class="filieres" role="tabpanel" id="offre-<?php echo esc_attr( $code ); ?>" aria-labelledby="onglet-offre-<?php echo esc_attr( $code ); ?>"<?php echo 'CYC1' === $code ? '' : ' hidden'; ?>>
							<?php foreach ( $offre[ $code ] as $filiere => $options ) : ?>
								<li><b><?php echo esc_html( $filiere ); ?></b><span><?php echo esc_html( implode( ', ', $options ) ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</div>
			<script type="application/json" id="offre"><?php echo wp_json_encode( ceb_offre_par_diplome(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
		</div>
	</section>

	<section class="section" id="dossier">
		<div class="conteneur dossier-section dossier-section__grille">
			<div data-apparition>
				<h2>Prépare ton dossier</h2>
				<div class="onglets" role="tablist" aria-label="Pièces selon le cycle">
					<?php foreach ( CEB_CYCLES as $code => $cycle ) : ?>
						<button type="button" role="tab" id="onglet-<?php echo esc_attr( $code ); ?>" aria-controls="pieces-<?php echo esc_attr( $code ); ?>" aria-selected="<?php echo 'CYC1' === $code ? 'true' : 'false'; ?>"><?php echo esc_html( $cycle['nom'] ); ?></button>
					<?php endforeach; ?>
				</div>
				<?php foreach ( CEB_CYCLES as $code => $cycle ) : ?>
					<ul class="pieces" role="tabpanel" id="pieces-<?php echo esc_attr( $code ); ?>" aria-labelledby="onglet-<?php echo esc_attr( $code ); ?>"<?php echo 'CYC1' === $code ? '' : ' hidden'; ?>>
						<?php foreach ( ceb_pieces( $code ) as $piece ) : ?>
							<li><?php echo ceb_icone( 'check', 16 ); ?><?php echo esc_html( $piece ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endforeach; ?>
				<p class="note">Fonctionnaires : joignez l'autorisation de votre ministère. Diplôme étranger : joignez l'équivalence.</p>
			</div>
			<aside class="paiement-coque" data-apparition>
				<div class="paiement-carte">
					<h3>Frais de concours</h3>
					<p class="paiement-carte__montant"><?php echo esc_html( ceb_fcfa( $r['frais'] ) ); ?></p>
					<p>À verser dans toute agence <?php echo esc_html( $r['banque'] ); ?>, sur le compte de l'ENSET d'Ebolowa :</p>
					<button type="button" class="copie" data-copier="<?php echo esc_attr( $r['compte'] ); ?>" aria-label="Copier le numéro de compte">
						<span><?php echo esc_html( $r['compte'] ); ?></span><b data-copie-etat>Copier</b>
					</button>
					<p class="note">Aucun autre mode de paiement n'est accepté.</p>
					<?php if ( $r['date_cycle1'] || $r['date_cycle2'] ) : ?>
						<dl>
							<?php if ( $r['date_cycle1'] ) : ?><div><dt>Épreuves premier cycle</dt><dd><?php echo esc_html( ceb_date_fr( $r['date_cycle1'] ) ); ?></dd></div><?php endif; ?>
							<?php if ( $r['date_cycle2'] ) : ?><div><dt>Épreuves second cycle</dt><dd><?php echo esc_html( ceb_date_fr( $r['date_cycle2'] ) ); ?></dd></div><?php endif; ?>
						</dl>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>

	<section class="section section--pale" id="procedure">
		<div class="conteneur">
			<header class="titre-section" data-apparition>
				<h2>Quatre étapes jusqu'aux épreuves</h2>
			</header>
			<ol class="frise" data-frise>
				<?php foreach ( $etapes as $i => $e ) : ?>
					<li class="frise__etape">
						<span class="frise__num" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
						<h3><?php echo esc_html( $e[0] ); ?></h3>
						<p><?php echo esc_html( $e[1] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="section" id="questions">
		<div class="conteneur questions__grille">
			<header class="titre-section" data-apparition>
				<h2>Questions fréquentes</h2>
				<p>Âge limite : <?php echo (int) $r['age_cycle1']; ?> ans au premier cycle et <?php echo (int) $r['age_cycle2']; ?> ans au second, au <?php echo esc_html( ceb_date_fr( $r['date_age'] ) ); ?>. Les fonctionnaires ne sont pas concernés.</p>
			</header>
			<div class="accordeon" data-apparition>
				<?php foreach ( $questions as $q ) : ?>
					<details>
						<summary><?php echo esc_html( $q[0] ); ?><?php echo ceb_icone( 'chevron', 18 ); ?></summary>
						<p><?php echo esc_html( $q[1] ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if ( 'ouvert' === $etat && ! $dossier ) : ?>
		<section class="appel">
			<img src="<?php echo $img( 'campus-foret.webp' ); // Déjà échappé. ?>" alt="" width="1600" height="800" loading="lazy">
			<div class="conteneur appel__ligne" data-apparition>
				<div>
					<h2>Ton reçu est prêt ?</h2>
					<p>Garde ton diplôme et ta CNI à portée de main.</p>
				</div>
				<?php echo $bouton( ceb_url( 'candidature/1' ), 'M\'inscrire au concours', 'btn--clair' ); // Déjà échappé. ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
ceb_fin();
