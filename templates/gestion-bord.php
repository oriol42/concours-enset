<?php
/**
 * Tableau de bord : où en est la session, et ce qui attend la scolarité.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$s       = ceb_statistiques();
$t       = $s['totaux'];
$recus   = (int) $t->recus;
$verifie = (int) $t->verifies;
$valides = (int) $t->valides;
$deposes = (int) $t->deposes;
$frais   = (int) $r['frais'];
$part    = static fn( $n ) => $recus ? round( $n / $recus * 100 ) : 0;
$lien    = static fn( $args ) => add_query_arg( array_merge( array( 'vue' => 'dossiers' ), $args ), $base );
$jours   = ceb_par_jour( 30 );
$max     = max( 1, max( $jours ) );
$file    = ceb_file( 'paiements', 6 );

$barres = static function ( $titre, $lignes, $noms, $cle ) use ( $lien ) {
	$max = $lignes ? max( array_map( static fn( $l ) => (int) $l->n, $lignes ) ) : 1;
	?>
	<section class="panneau">
		<h2><?php echo esc_html( $titre ); ?></h2>
		<?php if ( ! $lignes ) : ?>
			<p class="vide">Les chiffres apparaîtront avec les premières candidatures.</p>
		<?php else : ?>
			<ul class="barres">
				<?php foreach ( $lignes as $l ) : ?>
					<li>
						<a href="<?php echo esc_url( $lien( array( $cle => $l->cle ) ) ); ?>">
							<span class="barres__nom"><?php echo esc_html( $noms[ $l->cle ] ?? $l->cle ); ?></span>
							<b><?php echo (int) $l->n; ?></b>
							<span class="barres__piste"><i style="width:<?php echo esc_attr( (string) max( 2, round( $l->n / $max * 100 ) ) ); ?>%"></i></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php
};

$action = ceb_peut( 'ceb_exporter' ) ? '<a class="btn btn--fantome" href="' . esc_url( add_query_arg( 'vue', 'export-csv', $base ) ) . '">' . ceb_icone( 'telecharger', 16 ) . 'Exporter</a>' : '';
$tete( 'Tableau de bord', $etat . ', session ' . $r['annee'] . ( $r['cloture'] ? ', clôture le ' . ceb_date_fr( $r['cloture'] ) : '' ), $action );
?>
<section class="heros-adm" aria-label="Avancement de la session">
	<div class="heros-adm__total">
		<p>Candidatures reçues</p>
		<b data-compter><?php echo (int) $recus; ?></b>
		<span><?php echo esc_html( ceb_fcfa( $verifie * $frais ) ); ?> vérifiés sur <?php echo esc_html( ceb_fcfa( $recus * $frais ) ); ?> attendus</span>
		<?php if ( (int) $t->paiements && ceb_peut( 'ceb_paiements' ) ) : ?>
			<a class="btn btn--clair" href="<?php echo esc_url( $lien( array( 'file' => 'paiements' ) ) ); ?>">Vérifier <?php echo (int) $t->paiements; ?> paiement<?php echo (int) $t->paiements > 1 ? 's' : ''; ?><?php echo ceb_icone( 'fleche', 16 ); ?></a>
		<?php endif; ?>
	</div>
	<ol class="heros-adm__etapes">
		<?php foreach ( array( array( 'Envoyées', $recus ), array( 'Paiement vérifié', $verifie ), array( 'Pièces reçues', $deposes ), array( 'Dossier validé', $valides ) ) as $e ) : ?>
			<li>
				<span><?php echo esc_html( $e[0] ); ?></span>
				<b><?php echo (int) $e[1]; ?></b>
				<small><?php echo (int) $part( $e[1] ); ?> %</small>
				<i style="--part:<?php echo esc_attr( (string) ( $part( $e[1] ) / 100 ) ); ?>"></i>
			</li>
		<?php endforeach; ?>
	</ol>
</section>

<div class="tuiles">
	<?php
	$tuiles = array(
		array( CEB_FILES['pieces']['nom'], (int) $compteurs->pieces, 'dossier', array( 'file' => 'pieces' ) ),
		array( CEB_FILES['valider']['nom'], (int) $compteurs->valider, 'check', array( 'file' => 'valider' ) ),
		array( 'À corriger par le candidat', (int) $compteurs->corriger, 'alerte', array( 'file' => 'corriger' ) ),
		array( 'Limite d\'âge dépassée', (int) $compteurs->age, 'horloge', array( 'file' => 'age' ) ),
	);
	foreach ( $tuiles as $u ) :
		?>
		<a href="<?php echo esc_url( $lien( $u[3] ) ); ?>"><span class="tuiles__icone"><?php echo ceb_icone( $u[2], 18 ); ?></span><b><?php echo (int) $u[1]; ?></b><span><?php echo esc_html( $u[0] ); ?></span></a>
	<?php endforeach; ?>
</div>

<div class="grille-bord">
	<section class="panneau panneau--jours">
		<h2>Candidatures par jour</h2>
		<p class="panneau__note"><?php echo (int) array_sum( $jours ); ?> sur les 30 derniers jours</p>
		<div class="jours" role="img" aria-label="<?php echo esc_attr( (int) array_sum( $jours ) . ' candidatures sur 30 jours, au plus ' . max( $jours ) . ' par jour' ); ?>">
			<?php foreach ( $jours as $jour => $n ) : ?>
				<span class="jours__jour" data-info="<?php echo esc_attr( wp_date( 'd/m', strtotime( $jour . ' 12:00' ) ) . ' : ' . $n ); ?>"><i style="height:<?php echo esc_attr( (string) ( $n ? max( 3, round( $n / $max * 100 ) ) : 0 ) ); ?>%"></i></span>
			<?php endforeach; ?>
		</div>
		<div class="jours__axe"><span><?php echo esc_html( wp_date( 'd/m', strtotime( array_key_first( $jours ) . ' 12:00' ) ) ); ?></span><span>Aujourd'hui</span></div>
	</section>

	<section class="panneau">
		<h2>À vérifier en premier</h2>
		<?php if ( ! $file ) : ?>
			<p class="vide">Aucun paiement en attente.</p>
		<?php else : ?>
			<ul class="file">
				<?php foreach ( $file as $d ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'dossier', $d->id, $base ) ); ?>">
						<?php ceb_vignette( $d, 38 ); ?>
						<span><b><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ); ?></b><small><?php echo esc_html( $d->transaction_num ); ?></small></span>
						<?php echo ceb_icone( 'fleche', 16 ); ?>
					</a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<?php
	$barres( 'Par filière', $s['filieres'], CEB_FILIERES, 'filiere' );
	$barres( 'Par centre d\'examen', $s['centres'], CEB_CENTRES, 'centre' );
	?>
</div>
