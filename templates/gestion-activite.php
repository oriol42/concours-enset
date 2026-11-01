<?php
/**
 * Journal d'activité : la trace de chaque décision.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$lignes = ceb_activite();
$tete( 'Journal', 'Les 120 dernières actions, de la plus récente à la plus ancienne.' );
?>
<?php if ( ! $lignes ) : ?>
	<div class="panneau vide-grand"><?php echo ceb_icone( 'horloge', 26 ); ?><p>Aucune action pour le moment.</p></div>
<?php else : ?>
	<div class="tableau">
		<table>
			<thead><tr><th>Date</th><th>Action</th><th>Dossier</th><th>Par</th></tr></thead>
			<tbody>
				<?php foreach ( $lignes as $j ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $j->cree_le ) ) ); ?></td>
						<td><?php echo esc_html( $j->action ); ?></td>
						<td><a href="<?php echo esc_url( add_query_arg( 'dossier', $j->dossier_id, $base ) ); ?>"><?php echo esc_html( trim( $j->noms . ' ' . $j->prenoms ) ?: (string) $j->numero ); ?></a><small><?php echo esc_html( (string) $j->numero ); ?></small></td>
						<td><?php echo esc_html( $j->auteur ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
