<?php
/**
 * Tableau de bord : ce qui attend la scolarité, puis les chiffres de la session.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$s       = ceb_statistiques();
$t       = $s['totaux'];
$recus   = (int) $t->recus;
$frais   = (int) $r['frais'];
$filles  = 0;
$garcons = 0;
foreach ( $s['sexes'] as $x ) {
	if ( '2' === (string) $x->cle ) {
		$filles = (int) $x->n;
	} elseif ( '1' === (string) $x->cle ) {
		$garcons = (int) $x->n;
	}
}
$pct    = static fn( $n ) => $recus ? round( $n / $recus * 100 ) : 0;
$lien   = static fn( $args ) => add_query_arg( array_merge( array( 'vue' => 'dossiers' ), $args ), $base );
$jours  = ceb_par_jour( 30 );
$file   = ceb_file( 'paiements', 5 );
$action = ceb_peut( 'ceb_exporter' ) ? '<a class="btn btn--fantome" href="' . esc_url( add_query_arg( 'vue', 'export-csv', $base ) ) . '">' . ceb_icone( 'telecharger', 16 ) . 'Exporter</a>' : '';
$noms   = static function ( $lignes, $table ) {
	return array_map( static fn( $l ) => $table[ $l->cle ] ?? (string) $l->cle, $lignes );
};
$filieres = array();
foreach ( $s['filieres_sexe'] as $l ) {
	$filieres[ $l->filiere ]['nom']              = CEB_FILIERES[ $l->filiere ] ?? $l->filiere;
	$filieres[ $l->filiere ][ '2' === (string) $l->sexe ? 'f' : 'g' ] = (int) $l->n;
}
foreach ( $filieres as &$x ) {
	$x += array( 'f' => 0, 'g' => 0 );
}
unset( $x );
uasort( $filieres, static fn( $a, $b ) => ( $b['f'] + $b['g'] ) <=> ( $a['f'] + $a['g'] ) );
$donnees = array(
	'jours'    => array_map( static fn( $j, $n ) => array( 'd' => $j, 'n' => $n ), array_keys( $jours ), array_values( $jours ) ),
	'sexes'    => array( 'f' => $filles, 'g' => $garcons ),
	'cycles'   => array_map( static fn( $l ) => array( 'nom' => CEB_CYCLES[ $l->cle ]['nom'] ?? $l->cle, 'n' => (int) $l->n ), $s['cycles'] ),
	'centres'  => array( 'noms' => $noms( $s['centres'], CEB_CENTRES ), 'n' => array_map( static fn( $l ) => (int) $l->n, $s['centres'] ) ),
	'filieres' => array_values( $filieres ),
);
$tuiles = array(
	array( 'Candidatures reçues', $recus, '+' . (int) end( $jours ) . ' aujourd\'hui', array(), '' ),
	array( 'Paiements à vérifier', (int) $compteurs->paiements, ceb_fcfa( (int) $t->verifies * $frais ) . ' déjà vérifiés', array( 'file' => 'paiements' ), 'kpi--action' ),
	array( 'Dossiers validés', (int) $t->valides, $pct( (int) $t->valides ) . ' % des candidatures', array( 'file' => 'valides' ), '' ),
	array( 'Filles', $filles, $pct( $filles ) . ' % des candidatures', array(), '' ),
);
$tete( 'Tableau de bord', $etat . ', session ' . $r['annee'] . ( $r['cloture'] ? ', clôture le ' . ceb_date_fr( $r['cloture'] ) : '' ), $action );
?>
<div class="kpis" data-groupe>
	<?php foreach ( $tuiles as $u ) : ?>
		<a class="kpi <?php echo esc_attr( $u[4] ); ?>" href="<?php echo esc_url( $lien( $u[3] ) ); ?>">
			<span><?php echo esc_html( $u[0] ); ?></span>
			<b data-compte="<?php echo (int) $u[1]; ?>"><?php echo (int) $u[1]; ?></b>
			<small><?php echo esc_html( $u[2] ); ?></small>
		</a>
	<?php endforeach; ?>
</div>

<?php if ( ! $recus ) : ?>
	<div class="panneau vide-grand"><?php echo ceb_icone( 'tableau', 26 ); ?><p>Les graphiques apparaîtront avec les premières candidatures.</p></div>
<?php else : ?>
	<div class="grille-bord">
		<section class="panneau g-8">
			<header class="panneau__tete">
				<div><h2>Candidatures par jour</h2><p class="panneau__note" data-jours-total></p></div>
				<div class="segments" role="group" aria-label="Période">
					<button type="button" data-periode="7">7 j</button>
					<button type="button" data-periode="14">14 j</button>
					<button type="button" data-periode="30" aria-pressed="true">30 j</button>
				</div>
			</header>
			<div class="zone-graphe zone-graphe--haute"><canvas id="g-jours" role="img" aria-label="Nombre de candidatures envoyées chaque jour"></canvas></div>
		</section>

		<section class="panneau g-4">
			<h2>Parcours des dossiers</h2>
			<ol class="parcours-dossiers">
				<?php
				$etapes = array(
					array( 'Envoyés', $recus, '' ),
					array( 'Paiement vérifié', (int) $t->verifies, 'paiements' ),
					array( 'Pièces reçues', (int) $t->deposes, 'pieces' ),
					array( 'Dossier validé', (int) $t->valides, 'valider' ),
				);
				foreach ( $etapes as $e ) :
					?>
					<li style="--part:<?php echo esc_attr( (string) ( $pct( $e[1] ) / 100 ) ); ?>">
						<span><?php echo esc_html( $e[0] ); ?></span><b><?php echo (int) $e[1]; ?></b><small><?php echo (int) $pct( $e[1] ); ?> %</small>
					</li>
				<?php endforeach; ?>
			</ol>
			<p class="panneau__pied">
				<a href="<?php echo esc_url( $lien( array( 'file' => 'pieces' ) ) ); ?>"><?php echo (int) $compteurs->pieces; ?> à recevoir</a>
				<a href="<?php echo esc_url( $lien( array( 'file' => 'valider' ) ) ); ?>"><?php echo (int) $compteurs->valider; ?> à valider</a>
				<a href="<?php echo esc_url( $lien( array( 'file' => 'corriger' ) ) ); ?>"><?php echo (int) $compteurs->corriger; ?> à corriger</a>
				<a href="<?php echo esc_url( $lien( array( 'file' => 'age' ) ) ); ?>"><?php echo (int) $compteurs->age; ?> limite d'âge</a>
			</p>
		</section>

		<section class="panneau g-4">
			<h2>Filles et garçons</h2>
			<div class="anneau"><div class="zone-graphe"><canvas id="g-sexes" role="img" aria-label="<?php echo esc_attr( $filles . ' filles et ' . $garcons . ' garçons' ); ?>"></canvas></div></div>
			<ul class="legende">
				<li><i style="background:#8cc63f"></i>Filles<b><?php echo (int) $filles; ?></b><small><?php echo (int) $pct( $filles ); ?> %</small></li>
				<li><i style="background:#007a3d"></i>Garçons<b><?php echo (int) $garcons; ?></b><small><?php echo (int) $pct( $garcons ); ?> %</small></li>
			</ul>
		</section>

		<section class="panneau g-4">
			<h2>Par cycle</h2>
			<div class="anneau"><div class="zone-graphe"><canvas id="g-cycles" role="img" aria-label="Répartition par cycle"></canvas></div></div>
			<ul class="legende">
				<?php foreach ( $donnees['cycles'] as $i => $c ) : ?>
					<li><i style="background:<?php echo 0 === $i ? '#0c2a1b' : '#8cc63f'; ?>"></i><?php echo esc_html( $c['nom'] ); ?><b><?php echo (int) $c['n']; ?></b><small><?php echo (int) $pct( $c['n'] ); ?> %</small></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="panneau g-4">
			<h2>Par centre d'examen</h2>
			<div class="zone-graphe zone-graphe--centres"><canvas id="g-centres" role="img" aria-label="Candidatures par centre d'examen"></canvas></div>
		</section>

		<section class="panneau g-8">
			<header class="panneau__tete">
				<h2>Par filière, filles et garçons</h2>
				<ul class="legende legende--ligne"><li><i style="background:#8cc63f"></i>Filles</li><li><i style="background:#007a3d"></i>Garçons</li></ul>
			</header>
			<div class="zone-graphe" style="height:<?php echo (int) ( 34 * count( $filieres ) + 30 ); ?>px"><canvas id="g-filieres" role="img" aria-label="Candidatures par filière, réparties entre filles et garçons"></canvas></div>
		</section>

		<section class="panneau g-4">
			<h2>À vérifier en premier</h2>
			<?php if ( ! $file ) : ?>
				<p class="vide">Aucun paiement en attente.</p>
			<?php else : ?>
				<ul class="file">
					<?php foreach ( $file as $d ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( array( 'dossier' => $d->id, 'file' => 'paiements' ), $base ) ); ?>">
							<?php ceb_vignette( $d, 38 ); ?>
							<span><b><?php echo esc_html( trim( $d->noms . ' ' . $d->prenoms ) ); ?></b><small><?php echo esc_html( $d->transaction_num ); ?></small></span>
							<?php echo ceb_icone( 'fleche', 16 ); ?>
						</a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>
	<script type="application/json" id="bord-donnees"><?php echo wp_json_encode( $donnees, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?></script>
<?php endif; ?>
