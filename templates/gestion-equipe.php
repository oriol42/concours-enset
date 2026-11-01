<?php
/**
 * Équipe : qui a accès à l'espace scolarité, et avec quels droits.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

$membres = ceb_equipe();
$roles   = array_map( static fn( $x ) => $x['nom'], CEB_ROLES );
$champs  = static function ( $operation ) {
	ceb_champ_csrf();
	echo '<input type="hidden" name="ceb_action" value="equipe"><input type="hidden" name="operation" value="' . esc_attr( $operation ) . '">';
};
$tete( 'Équipe', 'Chaque personne a son compte : le journal indique qui a fait quoi.' );
?>
<div class="equipe-grille">
	<section class="panneau">
		<h2>Membres</h2>
		<?php if ( ! $membres ) : ?>
			<p class="vide">Aucun membre pour le moment. Ajoute le premier agent ci-contre.</p>
		<?php else : ?>
			<ul class="membres">
				<?php foreach ( $membres as $m ) : ?>
					<li>
						<div><b><?php echo esc_html( $m->display_name ); ?></b><small><?php echo esc_html( $m->user_email ); ?></small></div>
						<form method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>">
							<?php $champs( 'role' ); ?>
							<input type="hidden" name="membre" value="<?php echo (int) $m->ID; ?>">
							<label><span class="evitement">Rôle de <?php echo esc_html( $m->display_name ); ?></span>
								<select name="role" onchange="this.form.submit()">
									<?php foreach ( $roles as $k => $l ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>" <?php selected( ceb_role_de( $m ), $k ); ?>><?php echo esc_html( $l ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</form>
						<form method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>">
							<?php $champs( 'retirer' ); ?>
							<input type="hidden" name="membre" value="<?php echo (int) $m->ID; ?>">
							<button class="btn btn--lien" type="submit" onclick="return confirm('Retirer l\'accès de ce membre ?')">Retirer</button>
						</form>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<div class="equipe-cote">
		<section class="panneau">
			<h2>Ajouter un membre</h2>
			<form class="formulaire" method="post" action="<?php echo esc_url( ceb_url( 'gestion' ) ); ?>">
				<?php $champs( 'ajouter' ); ?>
				<?php
				ceb_champ( array( 'nom' => 'nom', 'libelle' => 'Nom complet' ) );
				ceb_champ( array( 'nom' => 'email', 'libelle' => 'E-mail', 'type' => 'email' ) );
				ceb_champ( array( 'nom' => 'role', 'libelle' => 'Rôle', 'options' => $roles ) );
				?>
				<button class="btn btn--primaire" type="submit">Créer le compte</button>
			</form>
		</section>
		<section class="panneau">
			<h2>Les rôles</h2>
			<dl class="roles">
				<?php foreach ( CEB_ROLES as $x ) : ?>
					<div><dt><?php echo esc_html( $x['nom'] ); ?></dt><dd><?php echo esc_html( $x['aide'] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</section>
	</div>
</div>
