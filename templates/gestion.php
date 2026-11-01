<?php
/**
 * Espace scolarité : coque (barre latérale verte) et aiguillage des vues.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$vue     = sanitize_key( $_GET['vue'] ?? 'bord' );
$dossier = isset( $_GET['dossier'] ) ? ceb_dossier( (int) $_GET['dossier'] ) : null;
if ( $dossier ) {
	$vue = 'dossiers';
}
$base      = ceb_url( 'gestion' );
$r         = ceb_reglages();
$compteurs = ceb_compteurs();
$etat      = array( 'ouvert' => 'Inscriptions ouvertes', 'a_venir' => 'Ouverture à venir', 'clos' => 'Inscriptions closes' )[ ceb_etat_session() ];
$tete      = static function ( $titre, $sous = '', $actions = '' ) {
	?>
	<header class="adm-tete">
		<div>
			<h1><?php echo esc_html( $titre ); ?></h1>
			<?php if ( $sous ) : ?><p><?php echo esc_html( $sous ); ?></p><?php endif; ?>
		</div>
		<?php if ( $actions ) : ?><div class="adm-tete__actions"><?php echo $actions; // Construit par les gabarits. ?></div><?php endif; ?>
	</header>
	<?php
};
ceb_debut( array( 'titre' => 'Scolarité', 'corps' => 'page-gestion', 'nu' => true ) );
?>
<div class="gestion" data-cloche="<?php echo esc_url( add_query_arg( 'vue', 'notifs-json', $base ) ); ?>">
	<aside class="barre">
		<a class="marque" href="<?php echo esc_url( $base ); ?>">
			<img src="<?php echo esc_url( CEB_URI . '/assets/images/enset-marque.webp' ); ?>" alt="" width="44" height="44">
			<span class="marque__nom">Scolarité<small>Concours ENSET</small></span>
		</a>
		<nav aria-label="Espace scolarité">
			<?php
			$liens = array(
				'bord'     => array( 'Tableau de bord', 'tableau', 0, 'ceb_voir' ),
				'dossiers' => array( 'Dossiers', 'dossier', (int) $compteurs->paiements, 'ceb_voir' ),
				'notifications' => array( 'Notifications', 'cloche', ceb_non_lues_equipe(), 'ceb_voir' ),
				'activite' => array( 'Journal', 'horloge', 0, 'ceb_exporter' ),
				'equipe'   => array( 'Équipe', 'dossiers', 0, 'ceb_equipe' ),
				'reglages' => array( 'Réglages', 'reglages', 0, 'ceb_regler' ),
			);
			foreach ( $liens as $k => $l ) :
				if ( ! ceb_peut( $l[3] ) ) {
					continue;
				}
				?>
				<a href="<?php echo esc_url( add_query_arg( 'vue', $k, $base ) ); ?>"<?php echo $vue === $k ? ' aria-current="page"' : ''; ?>>
					<?php echo ceb_icone( $l[1], 19 ); ?><span><?php echo esc_html( $l[0] ); ?></span>
					<?php if ( 'notifications' === $k ) : ?>
						<b data-cloche-nombre<?php echo $l[2] ? '' : ' hidden'; ?>><?php echo (int) $l[2]; ?></b>
					<?php elseif ( $l[2] ) : ?>
						<b title="Paiements à vérifier"><?php echo (int) $l[2]; ?></b>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<a class="barre__sortie" href="<?php echo esc_url( wp_logout_url( ceb_url() ) ); ?>" aria-label="Déconnexion"><?php echo ceb_icone( 'sortie', 20 ); ?></a>
		<div class="barre__pied">
			<p class="barre__session"><b>Session <?php echo esc_html( $r['annee'] ); ?></b><?php echo esc_html( $etat ); ?></p>
			<p class="barre__moi"><b><?php echo esc_html( wp_get_current_user()->display_name ); ?></b><?php
				$moi = ceb_role_de( wp_get_current_user() );
				echo esc_html( $moi ? CEB_ROLES[ $moi ]['nom'] : 'Administrateur' );
			?></p>
			<a href="<?php echo esc_url( ceb_url() ); ?>" target="_blank" rel="noopener"><?php echo ceb_icone( 'fleche', 16 ); ?>Voir le site</a>
			<a href="<?php echo esc_url( wp_logout_url( ceb_url() ) ); ?>"><?php echo ceb_icone( 'sortie', 16 ); ?>Déconnexion</a>
		</div>
	</aside>
	<main id="contenu" class="gestion__corps">
		<?php ceb_afficher_flash(); ?>
		<?php
		if ( $dossier ) {
			require CEB_DIR . '/templates/gestion-dossier.php';
		} elseif ( 'dossiers' === $vue ) {
			require CEB_DIR . '/templates/gestion-liste.php';
		} elseif ( 'reglages' === $vue ) {
			require CEB_DIR . '/templates/gestion-reglages.php';
		} elseif ( 'equipe' === $vue ) {
			require CEB_DIR . '/templates/gestion-equipe.php';
		} elseif ( 'notifications' === $vue ) {
			require CEB_DIR . '/templates/gestion-notifications.php';
		} elseif ( 'activite' === $vue ) {
			require CEB_DIR . '/templates/gestion-activite.php';
		} else {
			require CEB_DIR . '/templates/gestion-bord.php';
		}
		ceb_credit();
		?>
	</main>
</div>
<?php
ceb_fin( array( 'nu' => true ) );
