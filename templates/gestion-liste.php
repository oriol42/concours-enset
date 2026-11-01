<?php
/**
 * Registre des dossiers : onglets de travail, recherche, exports selon les filtres.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$f     = ceb_filtres();
$page  = max( 1, (int) ( $_GET['p'] ?? 1 ) );
$total = ceb_compter( $f );
$rangs = ceb_liste( $f, CEB_PAR_PAGE, ( $page - 1 ) * CEB_PAR_PAGE );
$args  = array_filter( $f, 'strlen' );
$pages = max( 1, (int) ceil( $total / CEB_PAR_PAGE ) );
$c     = $compteurs;
$onglets = array( '' => array( 'Tous', (int) $c->tous ) );
foreach ( array( 'paiements', 'pieces', 'valider', 'corriger', 'valides', 'saisie' ) as $cle ) {
	$onglets[ $cle ] = array( CEB_FILES[ $cle ]['nom'], (int) $c->$cle );
}
$select = static function ( $nom, $vide, $options, $valeur ) {
	echo '<label class="filtres__choix' . ( $valeur ? ' est-actif' : '' ) . '"><span class="evitement">' . esc_html( $vide ) . '</span><select name="' . esc_attr( $nom ) . '" data-auto><option value="">' . esc_html( $vide ) . '</option>';
	foreach ( $options as $k => $l ) {
		$l = is_array( $l ) ? $l['court'] : $l;
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $valeur, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select>' . ceb_icone( 'chevron', 15 ) . '</label>';
};
$actions = '';
if ( ceb_peut( 'ceb_exporter' ) ) {
	$actions = '<a class="btn btn--fantome" href="' . esc_url( add_query_arg( array_merge( $args, array( 'vue' => 'export-csv' ) ), $base ) ) . '">' . ceb_icone( 'telecharger', 16 ) . 'Excel (CSV)</a>'
		. '<a class="btn btn--fantome" href="' . esc_url( add_query_arg( array_merge( $args, array( 'vue' => 'export-fiches' ) ), $base ) ) . '">' . ceb_icone( 'fichier', 16 ) . 'Fiches PDF</a>';
}
$tete( 'Dossiers', $total . ' dossier' . ( $total > 1 ? 's' : '' ) . ' avec ces filtres', $actions );
?>
<nav class="onglets-adm" aria-label="Files de travail">
	<?php foreach ( $onglets as $cle => $o ) : ?>
		<a href="<?php echo esc_url( add_query_arg( array_filter( array( 'vue' => 'dossiers', 'file' => $cle ) ), $base ) ); ?>"<?php echo $f['file'] === $cle ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $o[0] ); ?><b><?php echo (int) $o[1]; ?></b></a>
	<?php endforeach; ?>
</nav>
<form class="filtres" method="get" action="<?php echo esc_url( $base ); ?>">
	<input type="hidden" name="vue" value="dossiers">
	<?php if ( $f['file'] ) : ?><input type="hidden" name="file" value="<?php echo esc_attr( $f['file'] ); ?>"><?php endif; ?>
	<label class="filtres__q"><span class="evitement">Rechercher</span><?php echo ceb_icone( 'loupe', 16 ); ?><input type="search" name="q" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="Nom, numéro, téléphone, CNI, transaction"></label>
	<?php
	$select( 'cycle', 'Tous les cycles', CEB_CYCLES, $f['cycle'] );
	$select( 'filiere', 'Toutes les filières', CEB_FILIERES, $f['filiere'] );
	$select( 'centre', 'Tous les centres', CEB_CENTRES, $f['centre'] );
	?>
	<?php if ( array_filter( array( $f['q'], $f['cycle'], $f['filiere'], $f['centre'] ) ) ) : ?>
		<a class="filtres__effacer" href="<?php echo esc_url( add_query_arg( array_filter( array( 'vue' => 'dossiers', 'file' => $f['file'] ) ), $base ) ); ?>"><?php echo ceb_icone( 'croix', 14 ); ?>Effacer</a>
	<?php endif; ?>
</form>
<?php if ( ! $rangs ) : ?>
	<div class="panneau vide-grand"><?php echo ceb_icone( 'loupe', 26 ); ?><p>Aucun dossier avec ces filtres.</p><a href="<?php echo esc_url( add_query_arg( 'vue', 'dossiers', $base ) ); ?>">Tout afficher</a></div>
<?php else : ?>
	<div class="tableau">
		<table>
			<thead><tr><th>Candidat</th><th>Concours</th><th>Centre</th><th>Paiement</th><th>Statut</th><th><span class="evitement">Ouvrir</span></th></tr></thead>
			<tbody>
				<?php foreach ( $rangs as $d ) : ?>
					<tr>
						<td class="tableau__candidat">
							<div class="tableau__id">
								<?php ceb_vignette( $d, 40 ); ?>
								<div><a href="<?php echo esc_url( add_query_arg( array_filter( array( 'dossier' => $d->id, 'file' => $f['file'] ) ), $base ) ); ?>"><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ); ?></a><small><?php echo esc_html( $d->numero ); ?></small></div>
							</div>
						</td>
						<td><?php echo esc_html( CEB_OPTIONS[ $d->option_code ] ?? '' ); ?><small><?php echo esc_html( CEB_CYCLES[ $d->cycle ]['court'] ?? '' ); ?></small></td>
						<td><?php echo esc_html( CEB_CENTRES[ $d->centre ] ?? '' ); ?></td>
						<td><?php ceb_etiquette( $d->paiement, CEB_PAIEMENTS[ $d->paiement ] ?? '' ); ?></td>
						<td><?php ceb_etiquette( $d->statut, CEB_STATUTS[ $d->statut ] ?? '' ); ?><?php if ( $d->alerte_age ) : ?><small class="age">Limite d'âge</small><?php endif; ?></td>
						<td class="tableau__fleche"><?php echo ceb_icone( 'fleche', 16 ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ( $pages > 1 ) : ?>
		<nav class="pagination" aria-label="Pages">
			<?php if ( $page > 1 ) : ?><a class="btn btn--fantome" href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'vue' => 'dossiers', 'p' => $page - 1 ) ), $base ) ); ?>">Précédent</a><?php endif; ?>
			<span>Page <?php echo (int) $page; ?> sur <?php echo (int) $pages; ?></span>
			<?php if ( $page < $pages ) : ?><a class="btn btn--fantome" href="<?php echo esc_url( add_query_arg( array_merge( $args, array( 'vue' => 'dossiers', 'p' => $page + 1 ) ), $base ) ); ?>">Suivant</a><?php endif; ?>
		</nav>
	<?php endif; ?>
<?php endif; ?>
<script>
document.querySelectorAll("[data-auto]").forEach((s) => s.addEventListener("change", () => s.form.submit()));
</script>
