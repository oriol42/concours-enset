<?php
/**
 * Centre de notifications du candidat.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$d      = ceb_dossier_courant();
$liste  = ceb_notifs_candidat( $d->id );
ceb_debut( array( 'titre' => 'Notifications', 'corps' => 'page-dossier' ) );
ceb_marquer_candidat( $d->id );
?>
<main id="contenu" class="conteneur espace espace--etroit">
	<a class="feuille__retour" href="<?php echo esc_url( ceb_url( 'mon-dossier' ) ); ?>"><?php echo ceb_icone( 'fleche-g', 16 ); ?>Mon dossier</a>
	<h1 class="page-titre">Notifications</h1>
	<?php if ( ! $liste ) : ?>
		<p class="vide-carte">Les décisions de la scolarité sur ton dossier s'afficheront ici.</p>
	<?php else : ?>
		<ul class="notifs">
			<?php foreach ( $liste as $n ) : ?>
				<li class="<?php echo $n->lu ? '' : 'est-nouvelle'; ?>">
					<b><?php echo esc_html( $n->titre ); ?></b>
					<p><?php echo esc_html( $n->texte ); ?></p>
					<small><?php echo esc_html( wp_date( 'd/m/Y à H:i', strtotime( $n->cree_le ) ) ); ?></small>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</main>
<?php
ceb_fin();
