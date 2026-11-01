<?php
/**
 * Gabarits communs : en-tête, pied de page, champs de formulaire, icônes.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

function ceb_url( $chemin = '' ) {
	return home_url( '/' . ltrim( $chemin, '/' ) );
}

function ceb_icone( $nom, $taille = 20 ) {
	static $traces = array(
		'dossier'     => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
		'cloche'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
		'tableau'     => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
		'dossiers'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
		'reglages'    => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
		'fleche'      => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'fleche-g'    => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
		'check'       => '<path d="M20 6 9 17l-5-5"/>',
		'telecharger' => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
		'fichier'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
		'bouclier'    => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
		'utilisateur' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'telephone'   => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.18 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.1 9.9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'aide'        => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/>',
		'banque'      => '<path d="M3 21h18M5 21V10M19 21V10M9 21V10M15 21V10M12 3 2 8h20z"/>',
		'tampon'      => '<path d="M5 22h14M19.27 13.73A2.5 2.5 0 0 0 17.5 13h-11A2.5 2.5 0 0 0 4 15.5V17a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1.5a2.5 2.5 0 0 0-.73-1.77M14 13V8.5C14 7 15 7 15 5a3 3 0 0 0-6 0c0 2 1 2 1 3.5V13"/>',
		'recu'        => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8M12 17.5v-11"/>',
		'sortie'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
		'crayon'      => '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
		'alerte'      => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4M12 17h.01"/>',
		'info'        => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
		'croix'       => '<path d="M18 6 6 18M6 6l12 12"/>',
		'diplome'     => '<path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c3 2.5 9 2.5 12 0v-5M22 10v6"/>',
		'loupe'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'horloge'     => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
		'chevron'     => '<path d="m6 9 6 6 6-6"/>',
		'menu'        => '<path d="M4 6h16M4 12h16M4 18h16"/>',
		'courriel'    => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
		'lieu'        => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
		'qr'          => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>',
		'appareil'    => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/>',
		'image'       => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.09-3.09a2 2 0 0 0-2.82 0L6 21"/>',
		'cle'         => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3L22 7l-3-3"/>',
	);
	return sprintf(
		'<svg class="icone" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $taille,
		$traces[ $nom ] ?? ''
	);
}

/** @param array $o titre, corps (classe de body), nu (sans en-tête ni pied). */
function ceb_debut( array $o = array() ) {
	$o += array( 'titre' => '', 'corps' => '', 'nu' => false, 'description' => 'Inscription en ligne au concours d\'entrée de l\'ENSET d\'Ebolowa.' );
	add_filter( 'pre_get_document_title', static fn() => ( $o['titre'] ? $o['titre'] . ' | ' : '' ) . 'Concours ENSET Ebolowa' );
	?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
	<meta name="theme-color" content="#fefffd">
	<meta name="description" content="<?php echo esc_attr( $o['description'] ); ?>">
	<link rel="icon" href="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.png' ); ?>">
	<?php wp_head(); ?>
</head>
<body class="<?php echo esc_attr( $o['corps'] ); ?>">
<a class="evitement" href="#contenu">Aller au contenu</a>
	<?php
	if ( ! $o['nu'] ) {
		ceb_entete();
	}
}

function ceb_fin( array $o = array() ) {
	if ( empty( $o['nu'] ) ) {
		ceb_pied();
	}
	wp_footer();
	echo "</body>\n</html>\n";
}

/** En-tête : public sur les pages du concours, espace candidat dans son dossier. */
function ceb_entete() {
	$dossier = ceb_dossier_courant();
	$page    = get_query_var( 'ceb_page' );
	$logo    = '<img src="' . esc_url( CEB_URI . '/assets/images/enset-marque.png' ) . '" alt="" width="50" height="50">';
	if ( $dossier && in_array( $page, array( 'mon-dossier', 'notifications' ), true ) ) {
		$non_lues = ceb_non_lues_candidat( $dossier->id );
		?>
		<header class="bandeau">
			<div class="conteneur bandeau__ligne">
				<a class="marque" href="<?php echo esc_url( ceb_url() ); ?>"><?php echo $logo; // Déjà échappé. ?><span class="marque__nom">ENSET Ebolowa<small>Mon espace candidat</small></span></a>
				<nav class="bandeau__liens bandeau__liens--espace" aria-label="Mon espace" data-cloche="<?php echo esc_url( ceb_url( 'mon-dossier/notifications' ) . '?json=1' ); ?>">
					<a href="<?php echo esc_url( ceb_url( 'mon-dossier' ) ); ?>"<?php echo 'mon-dossier' === $page ? ' aria-current="page"' : ''; ?>>Mon dossier</a>
					<a href="<?php echo esc_url( ceb_url( 'mon-dossier/notifications' ) ); ?>"<?php echo 'notifications' === $page ? ' aria-current="page"' : ''; ?>>Notifications<b data-cloche-nombre<?php echo $non_lues ? '' : ' hidden'; ?>><?php echo (int) $non_lues; ?></b></a>
				</nav>
				<div class="bandeau__actions">
					<a class="btn btn--contour" href="<?php echo esc_url( ceb_url( 'quitter' ) ); ?>"><?php echo ceb_icone( 'sortie', 16 ); ?>Quitter</a>
				</div>
			</div>
		</header>
		<?php
		return;
	}
	?>
	<header class="bandeau">
		<div class="conteneur bandeau__ligne">
			<a class="marque" href="<?php echo esc_url( ceb_url() ); ?>"><?php echo $logo; // Déjà échappé. ?><span class="marque__nom">ENSET Ebolowa<small>Concours d'entrée</small></span></a>
			<nav class="bandeau__liens" aria-label="Navigation principale">
				<a href="<?php echo esc_url( ceb_url( '#filieres' ) ); ?>">Filières</a>
				<a href="<?php echo esc_url( ceb_url( '#dossier' ) ); ?>">Pièces et frais</a>
				<a href="<?php echo esc_url( ceb_url( '#procedure' ) ); ?>">Procédure</a>
				<a href="<?php echo esc_url( ceb_url( '#questions' ) ); ?>">Questions</a>
			</nav>
			<div class="bandeau__actions">
				<?php if ( $dossier ) : ?>
					<a class="btn btn--primaire" href="<?php echo esc_url( ceb_url( 'mon-dossier' ) ); ?>">Mon dossier</a>
				<?php else : ?>
					<a class="btn btn--contour bandeau__reprendre" href="<?php echo esc_url( ceb_url( 'reprendre' ) ); ?>">Reprendre</a>
					<?php if ( 'ouvert' === ceb_etat_session() ) : ?>
						<a class="btn btn--primaire" href="<?php echo esc_url( ceb_url( 'candidature/1' ) ); ?>">M'inscrire</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</header>
	<?php
}

/** Mention du prestataire, la même que sur nos autres plateformes et documents. */
const CEB_CREDIT = '@NexusCore : Nous développons vos solutions informatiques (676295488 / 659490221 / 693899150 / 673414381)';

function ceb_pied() {
	$tel   = ceb_reglage( 'telephone' );
	$email = ceb_reglage( 'email' );
	?>
	<footer class="pied">
		<div class="conteneur pied__grille">
			<div class="pied__marque">
				<img src="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.png' ); ?>" alt="" width="64" height="64" loading="lazy">
				<div>
					<p class="pied__nom">ENSET d'Ebolowa</p>
					<p>École Normale Supérieure d'Enseignement Technique, Université d'Ebolowa</p>
				</div>
			</div>
			<nav aria-label="Liens du pied de page">
				<h2>Le concours</h2>
				<a href="<?php echo esc_url( ceb_url( '#filieres' ) ); ?>">Filières</a>
				<a href="<?php echo esc_url( ceb_url( '#dossier' ) ); ?>">Pièces et frais</a>
				<a href="<?php echo esc_url( ceb_url( '#questions' ) ); ?>">Questions</a>
				<a href="<?php echo esc_url( ceb_url( 'reprendre' ) ); ?>">Reprendre mon dossier</a>
			</nav>
			<div>
				<h2>Contact</h2>
				<ul>
					<li><?php echo ceb_icone( 'lieu', 16 ); ?>Service de la scolarité, BP 118 Ebolowa</li>
					<?php if ( $tel ) : ?>
						<li><?php echo ceb_icone( 'telephone', 16 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $tel ) ); ?>"><?php echo esc_html( $tel ); ?></a></li>
					<?php endif; ?>
					<?php if ( $email ) : ?>
						<li><?php echo ceb_icone( 'courriel', 16 ); ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
		<div class="pied__bas">
			<div class="conteneur">
				<span>Session <?php echo esc_html( ceb_reglage( 'annee' ) ); ?></span>
				<span><?php echo esc_html( CEB_CREDIT ); ?></span>
			</div>
		</div>
	</footer>
	<?php
}

/** Mention discrète des pages sans pied complet. */
function ceb_credit() {
	echo '<p class="credit">' . esc_html( CEB_CREDIT ) . '</p>';
}

function ceb_alerte( $type, $message ) {
	$icone = array( 'succes' => 'check', 'erreur' => 'alerte', 'attention' => 'alerte' )[ $type ] ?? 'info';
	printf( '<div class="alerte alerte--%s" role="%s">%s<p>%s</p></div>', esc_attr( $type ), 'erreur' === $type ? 'alert' : 'status', ceb_icone( $icone, 20 ), esc_html( $message ) );
}

/** Messages flash ; « numero » a sa propre carte, plus visible. */
function ceb_afficher_flash() {
	foreach ( ceb_lire_flash() as $m ) {
		if ( 'numero' === $m[0] ) {
			printf(
				'<div class="numero-remis"><p>Ton numéro candidat</p><strong>%s</strong><span>Note-le maintenant. Avec ta date de naissance, il te permet de revenir sur ton dossier.</span></div>',
				esc_html( $m[1] )
			);
		} else {
			ceb_alerte( $m[0], $m[1] );
		}
	}
}

/**
 * Champ de formulaire.
 *
 * @param array $c nom, libelle, valeur, erreurs, type, aide, options (select), attrs, facultatif, classe.
 */
function ceb_champ( array $c ) {
	$c += array( 'type' => 'text', 'valeur' => '', 'erreurs' => array(), 'aide' => '', 'options' => null, 'attrs' => array(), 'facultatif' => false, 'classe' => '', 'vide' => 'Choisir', 'mention' => true );
	/* Date saisie au clavier, JJ/MM/AAAA : plus rapide qu'un calendrier pour une naissance. */
	if ( 'date' === $c['type'] ) {
		$c['type']  = 'text';
		$c['valeur'] = preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', (string) $c['valeur'], $m ) ? "$m[3]/$m[2]/$m[1]" : $c['valeur'];
		$c['attrs'] = array_merge( array_diff_key( $c['attrs'], array( 'max' => 1 ) ), array( 'inputmode' => 'numeric', 'maxlength' => 10, 'placeholder' => 'JJ/MM/AAAA', 'data-date' => '1', 'autocomplete' => $c['attrs']['autocomplete'] ?? 'off' ) );
	}
	$id     = 'c-' . $c['nom'];
	$erreur = $c['erreurs'][ $c['nom'] ] ?? '';
	$decrit = array_filter( array( $c['aide'] ? $id . '-aide' : '', $erreur ? $id . '-err' : '' ) );
	$attrs  = '';
	foreach ( $c['attrs'] as $k => $v ) {
		$attrs .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	if ( $decrit ) {
		$attrs .= ' aria-describedby="' . esc_attr( implode( ' ', $decrit ) ) . '"';
	}
	if ( $erreur ) {
		$attrs .= ' aria-invalid="true"';
	}
	if ( ! $c['facultatif'] ) {
		$attrs .= ' required';
	}
	?>
	<div class="champ <?php echo esc_attr( $c['classe'] . ( $erreur ? ' champ--invalide' : '' ) ); ?>" data-champ="<?php echo esc_attr( $c['nom'] ); ?>">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $c['libelle'] ); ?><?php if ( $c['facultatif'] && $c['mention'] ) : ?> <small>facultatif</small><?php endif; ?></label>
		<?php if ( is_array( $c['options'] ) ) : ?>
			<div class="champ__select">
				<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $c['nom'] ); ?>"<?php echo $attrs; // Déjà échappé. ?>>
					<option value=""><?php echo esc_html( $c['vide'] ); ?></option>
					<?php foreach ( $c['options'] as $v => $l ) : ?>
						<option value="<?php echo esc_attr( $v ); ?>"<?php selected( (string) $c['valeur'], (string) $v ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo ceb_icone( 'chevron', 18 ); ?>
			</div>
		<?php else : ?>
			<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $c['nom'] ); ?>" type="<?php echo esc_attr( $c['type'] ); ?>" value="<?php echo esc_attr( (string) $c['valeur'] ); ?>"<?php echo $attrs; // Déjà échappé. ?>>
		<?php endif; ?>
		<?php if ( $c['aide'] ) : ?>
			<p class="champ__aide" id="<?php echo esc_attr( $id ); ?>-aide"><?php echo esc_html( $c['aide'] ); ?></p>
		<?php endif; ?>
		<p class="champ__erreur" id="<?php echo esc_attr( $id ); ?>-err"<?php echo $erreur ? '' : ' hidden'; ?>><?php echo esc_html( $erreur ); ?></p>
	</div>
	<?php
}

/** Choix à boutons : plus rapide au doigt qu'une liste pour deux ou trois valeurs. */
function ceb_choix( array $c ) {
	$c += array( 'valeur' => '', 'erreurs' => array(), 'classe' => '' );
	$erreur = $c['erreurs'][ $c['nom'] ] ?? '';
	?>
	<fieldset class="choix <?php echo esc_attr( $c['classe'] . ( $erreur ? ' champ--invalide' : '' ) ); ?>" data-champ="<?php echo esc_attr( $c['nom'] ); ?>">
		<legend><?php echo esc_html( $c['libelle'] ); ?></legend>
		<div class="choix__liste">
			<?php foreach ( $c['options'] as $v => $l ) : ?>
				<label><input type="radio" name="<?php echo esc_attr( $c['nom'] ); ?>" value="<?php echo esc_attr( $v ); ?>" required <?php checked( (string) $c['valeur'], (string) $v ); ?>><span><?php echo esc_html( $l ); ?></span></label>
			<?php endforeach; ?>
		</div>
		<p class="champ__erreur"<?php echo $erreur ? '' : ' hidden'; ?>><?php echo esc_html( $erreur ); ?></p>
	</fieldset>
	<?php
}
