<?php
/**
 * Centre de notifications de l'équipe.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$repere = ceb_repere_equipe();
$liste  = ceb_notifs_equipe();
ceb_marquer_equipe();
$tete( 'Notifications', 'Nouvelles candidatures et corrections envoyées par les candidats.' );
?>
<?php if ( ! $liste ) : ?>
	<div class="panneau vide-grand"><?php echo ceb_icone( 'cloche', 26 ); ?><p>Rien de nouveau pour le moment.</p></div>
<?php else : ?>
	<ul class="notifs">
		<?php foreach ( $liste as $n ) : ?>
			<li class="<?php echo (int) $n->id > $repere ? 'est-nouvelle' : ''; ?>">
				<a href="<?php echo esc_url( add_query_arg( 'dossier', $n->dossier_id, $base ) ); ?>"><b><?php echo esc_html( $n->titre ); ?></b></a>
				<p><?php echo esc_html( $n->texte ); ?></p>
				<small><?php echo esc_html( wp_date( 'd/m/Y à H:i', strtotime( $n->cree_le ) ) ); ?></small>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
