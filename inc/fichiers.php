<?php
/**
 * Photo d'identité et reçu de paiement.
 *
 * Le type se contrôle sur le contenu (finfo), les images sont réencodées en
 * JPEG (ce qui retire les métadonnées et tout contenu caché), les noms sont
 * aléatoires et le dossier refuse tout accès direct : la lecture passe
 * uniquement par ceb_servir_fichier().
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_FICHIER_MAX = 5 * MB_IN_BYTES;

function ceb_dossier_fichiers() {
	$base = wp_upload_dir( null, false )['basedir'] . '/ceb-concours';
	if ( ! is_dir( $base ) ) {
		wp_mkdir_p( $base );
	}
	if ( ! file_exists( $base . '/.htaccess' ) ) {
		file_put_contents( $base . '/.htaccess', "Options -Indexes\nRequire all denied\nDeny from all\n" );
		file_put_contents( $base . '/index.php', "<?php // Silence.\n" );
	}
	return $base;
}

/** Réencode une image, redressée selon l'EXIF, réduite à $cote pixels au plus. */
function ceb_reencoder_image( $source, $type, $cible, $cote ) {
	$image = 'image/png' === $type ? @imagecreatefrompng( $source ) : @imagecreatefromjpeg( $source );
	if ( ! $image ) {
		return false;
	}
	if ( 'image/jpeg' === $type && function_exists( 'exif_read_data' ) ) {
		$sens = (int) ( @exif_read_data( $source )['Orientation'] ?? 1 );
		$angle = array( 3 => 180, 6 => -90, 8 => 90 )[ $sens ] ?? 0;
		if ( $angle ) {
			$image = imagerotate( $image, $angle, 0 );
		}
	}
	$l = imagesx( $image );
	$h = imagesy( $image );
	$k = min( 1, $cote / max( $l, $h ) );
	$sortie = imagecreatetruecolor( (int) round( $l * $k ), (int) round( $h * $k ) );
	imagefill( $sortie, 0, 0, imagecolorallocate( $sortie, 255, 255, 255 ) );
	imagecopyresampled( $sortie, $image, 0, 0, 0, 0, imagesx( $sortie ), imagesy( $sortie ), $l, $h );
	return imagejpeg( $sortie, $cible, 84 );
}

/**
 * Enregistre un fichier envoyé.
 *
 * @return array{nom?: string, type?: string, erreur?: string}
 */
function ceb_recevoir_fichier( $champ, $image_seule, $cote ) {
	$f = $_FILES[ $champ ] ?? null;
	if ( ! $f || UPLOAD_ERR_NO_FILE === $f['error'] ) {
		return array();
	}
	if ( UPLOAD_ERR_OK !== $f['error'] || ! is_uploaded_file( $f['tmp_name'] ) ) {
		return array( 'erreur' => 'L\'envoi a échoué. Réessaie avec une connexion stable.' );
	}
	if ( $f['size'] > CEB_FICHIER_MAX ) {
		return array( 'erreur' => 'Ce fichier dépasse 5 Mo.' );
	}
	$type = ( new finfo( FILEINFO_MIME_TYPE ) )->file( $f['tmp_name'] );
	$nom  = bin2hex( random_bytes( 16 ) );
	$base = ceb_dossier_fichiers();
	if ( in_array( $type, array( 'image/jpeg', 'image/png' ), true ) ) {
		$nom .= '.jpg';
		if ( ! ceb_reencoder_image( $f['tmp_name'], $type, $base . '/' . $nom, $cote ) ) {
			return array( 'erreur' => 'Cette image est illisible. Choisis une autre photo.' );
		}
		return array( 'nom' => $nom, 'type' => 'image/jpeg' );
	}
	if ( ! $image_seule && 'application/pdf' === $type && '%PDF-' === file_get_contents( $f['tmp_name'], false, null, 0, 5 ) ) {
		$nom .= '.pdf';
		if ( move_uploaded_file( $f['tmp_name'], $base . '/' . $nom ) ) {
			return array( 'nom' => $nom, 'type' => 'application/pdf' );
		}
	}
	return array( 'erreur' => $image_seule ? 'Envoie une photo JPG ou PNG.' : 'Envoie une photo JPG ou PNG, ou un PDF.' );
}

function ceb_supprimer_fichier( $nom ) {
	if ( $nom && preg_match( '/^[a-f0-9]{32}\.(jpg|pdf)$/', $nom ) ) {
		wp_delete_file( ceb_dossier_fichiers() . '/' . $nom );
	}
}

function ceb_chemin_fichier( $nom ) {
	if ( ! preg_match( '/^[a-f0-9]{32}\.(jpg|pdf)$/', (string) $nom ) ) {
		return '';
	}
	$chemin = ceb_dossier_fichiers() . '/' . $nom;
	return is_file( $chemin ) ? $chemin : '';
}

/** Lecture réservée au candidat du dossier et à la scolarité. */
function ceb_servir_fichier( $quoi ) {
	$dossier = isset( $_GET['id'] ) && ceb_peut_gerer() ? ceb_dossier( (int) $_GET['id'] ) : ceb_dossier_courant();
	$nom     = $dossier ? ( 'photo' === $quoi ? $dossier->photo : $dossier->recu ) : '';
	$chemin  = ceb_chemin_fichier( $nom );
	if ( ! $chemin ) {
		status_header( 404 );
		exit;
	}
	nocache_headers();
	header( 'Content-Type: ' . ( str_ends_with( $chemin, '.pdf' ) ? 'application/pdf' : 'image/jpeg' ) );
	header( 'Content-Disposition: inline' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $chemin ) );
	readfile( $chemin );
	exit;
}
