<?php
/**
 * Fiche officielle de candidature : une page A4 dans le modèle de nos fiches
 * de préinscription (en-tête bilingue, rubriques à onglet, récépissé détachable),
 * aux couleurs de l'ENSET.
 *
 * Unités : millimètres. Polices Source (accents complets) incorporées entières.
 *
 * @package Concours_ENSET
 */

defined( 'ABSPATH' ) || exit;

const CEB_PDF_VERT   = array( 0, 107, 60 );
const CEB_PDF_FONCE  = array( 15, 44, 31 );
const CEB_PDF_ACCENT = array( 176, 72, 12 );
const CEB_PDF_GRIS   = array( 110, 120, 114 );
const CEB_PDF_FILET  = array( 196, 208, 200 );
const CEB_PDF_G      = 10.0;   /* marge gauche */
const CEB_PDF_L      = 190.0;  /* largeur utile */

function ceb_pdf_nouveau() {
	/* TCPDF appelle imagedestroy(), déprécié en PHP 8.5 : ces avis ne doivent pas se mêler au PDF. */
	error_reporting( error_reporting() & ~E_DEPRECATED );
	require_once CEB_DIR . '/lib/tcpdf/tcpdf.php';
	$pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );
	$pdf->setFontSubsetting( false ); /* Ces polices deviennent illisibles en sous-ensemble. */
	$pdf->SetCreator( 'Concours ENSET Ebolowa' );
	$pdf->SetAuthor( "ENSET d'Ebolowa" );
	$pdf->setPrintHeader( false );
	$pdf->setPrintFooter( false );
	$pdf->SetMargins( 0, 0, 0 );
	$pdf->SetAutoPageBreak( false );
	$pdf->setCellPaddings( 0, 0, 0, 0 );
	return $pdf;
}

function ceb_pdf_ecrire( $pdf, $x, $y, $l, $texte, $police, $taille, $couleur, $align = 'L', $h = 4 ) {
	$pdf->SetFont( $police, '', $taille );
	$pdf->SetTextColorArray( $couleur );
	$pdf->SetXY( $x, $y );
	/* Le dernier argument de Cell resserre une valeur trop longue au lieu de la couper. */
	$pdf->Cell( $l, $h, str_replace( "'", '’', (string) $texte ), 0, 0, $align, false, '', 1 );
}

/** En-tête : français à gauche, anglais à droite, logo de l'ENSET au centre. */
function ceb_pdf_entete( $pdf ) {
	$colonnes = array(
		array( 10, 'RÉPUBLIQUE DU CAMEROUN', 'Paix - Travail - Patrie', 'MINISTÈRE DE L\'ENSEIGNEMENT SUPÉRIEUR', 'UNIVERSITÉ D\'EBOLOWA', 'ÉCOLE NORMALE SUPÉRIEURE D\'ENSEIGNEMENT TECHNIQUE' ),
		array( 130, 'REPUBLIC OF CAMEROON', 'Peace - Work - Fatherland', 'MINISTRY OF HIGHER EDUCATION', 'THE UNIVERSITY OF EBOLOWA', 'HIGHER TECHNICAL TEACHER TRAINING COLLEGE' ),
	);
	foreach ( $colonnes as $c ) {
		ceb_pdf_ecrire( $pdf, $c[0], 8, 70, $c[1], 'uebserifb', 9, CEB_PDF_FONCE, 'C' );
		ceb_pdf_ecrire( $pdf, $c[0], 12, 70, $c[2], 'uebserifi', 8, CEB_PDF_FONCE, 'C' );
		ceb_pdf_ecrire( $pdf, $c[0], 16, 70, $c[3], 'uebsans', 7, CEB_PDF_FONCE, 'C' );
		ceb_pdf_ecrire( $pdf, $c[0], 20, 70, $c[4], 'uebserifb', 8.6, CEB_PDF_VERT, 'C' );
		ceb_pdf_ecrire( $pdf, $c[0], 24, 70, $c[5], 'uebsansb', 6.2, CEB_PDF_VERT, 'C' );
	}
	$pdf->Image( CEB_DIR . '/assets/images/enset.png', 90, 6, 30, 30, 'PNG' );
}

function ceb_pdf_photo( $pdf, $d, $x, $y, $l, $h ) {
	$pdf->SetDrawColorArray( CEB_PDF_FILET );
	$pdf->SetLineWidth( 0.25 );
	$chemin = ceb_chemin_fichier( $d->photo );
	if ( $chemin ) {
		/* Cadre 4 x 4 : la photo est centrée et recadrée, jamais déformée. */
		/* Passée en mémoire : donnée par chemin, TCPDF supprime le fichier en fin de génération. */
		$pdf->Image( '@' . file_get_contents( $chemin ), $x, $y, $l, $h, 'JPG', '', '', false, 200, '', false, false, 0, 'CM' );
		$pdf->Rect( $x, $y, $l, $h, 'D' );
	} else {
		$pdf->Rect( $x, $y, $l, $h, 'D', array( 'all' => array( 'dash' => '2,1.5', 'width' => 0.25, 'color' => CEB_PDF_GRIS ) ) );
		ceb_pdf_ecrire( $pdf, $x, $y + $h / 2 - 2, $l, 'PHOTO', 'uebsans', 7, CEB_PDF_GRIS, 'C' );
	}
}

function ceb_pdf_qr( $pdf, $d, $x, $y, $t ) {
	$pdf->write2DBarcode( ceb_url( 'verifier/' . $d->jeton ), 'QRCODE,M', $x, $y, $t, $t, array(
		'border' => 0, 'padding' => 0, 'fgcolor' => array( 15, 44, 31 ), 'bgcolor' => false,
	) );
}

/**
 * Une rubrique : onglet vert puis deux colonnes de lignes.
 * Chaque ligne : array( libellé, label anglais, valeur ).
 *
 * @return float position sous la rubrique.
 */
function ceb_pdf_rubrique( $pdf, $y, $titre, array $gauche, array $droite ) {
	$pas  = 8.1;
	$rangs = max( count( $gauche ), count( $droite ) );
	$h    = $rangs * $pas + 7.5;
	$pdf->SetDrawColorArray( CEB_PDF_FILET );
	$pdf->SetLineWidth( 0.25 );
	$pdf->RoundedRect( CEB_PDF_G, $y + 3, CEB_PDF_L, $h - 3, 2.6, '1111', 'D' );
	$pdf->SetFillColorArray( CEB_PDF_VERT );
	$pdf->SetFont( 'uebsansb', '', 8.4 );
	$lt = $pdf->GetStringWidth( $titre ) + 10;
	$pdf->RoundedRect( CEB_PDF_G + 3, $y, $lt, 6.4, 2, '1111', 'F' );
	ceb_pdf_ecrire( $pdf, CEB_PDF_G + 3, $y + 1.2, $lt, $titre, 'uebsansb', 8.4, array( 255, 255, 255 ), 'C' );

	foreach ( array( array( $gauche, CEB_PDF_G + 5, 34, 54 ), array( $droite, CEB_PDF_G + 99, 34, 53 ) ) as $col ) {
		list( $lignes, $x, $lab, $val ) = $col;
		$ly = $y + 8.2;
		foreach ( $lignes as $l ) {
			ceb_pdf_ecrire( $pdf, $x, $ly - 0.3, $lab, $l[0], 'uebsemi', 7.3, CEB_PDF_FONCE, 'L', 3.4 );
			ceb_pdf_ecrire( $pdf, $x, $ly + 2.8, $lab, $l[1], 'uebsans', 5.6, CEB_PDF_GRIS, 'L', 2.6 );
			ceb_pdf_ecrire( $pdf, $x + $lab, $ly + 0.6, $val, '' === (string) $l[2] ? '-' : $l[2], 'uebsans', 8, CEB_PDF_FONCE, 'L', 4 );
			$ly += $pas;
		}
	}
	return $y + $h + 2.8;
}

function ceb_pdf_date( $iso ) {
	$t = $iso ? strtotime( $iso ) : 0;
	return $t ? gmdate( 'd/m/Y', $t ) : '';
}

/** Trace la fiche d'un dossier sur une nouvelle page. */
function ceb_pdf_page( $pdf, $d ) {
	$pdf->AddPage();
	ceb_pdf_entete( $pdf );

	ceb_pdf_photo( $pdf, $d, CEB_PDF_G, 39, 30, 30 );
	ceb_pdf_qr( $pdf, $d, 172, 38, 28 );

	ceb_pdf_ecrire( $pdf, 42, 40, 128, 'FICHE DE CANDIDATURE AU CONCOURS', 'uebserifb', 15, CEB_PDF_VERT, 'C', 7 );
	ceb_pdf_ecrire( $pdf, 42, 48, 128, 'Entrée en première année du ' . mb_strtolower( CEB_CYCLES[ $d->cycle ]['nom'] ?? '' ), 'uebsans', 9.5, CEB_PDF_FONCE, 'C', 5 );
	ceb_pdf_ecrire( $pdf, 42, 54, 128, 'Année académique ' . str_replace( '/', ' / ', $d->annee ), 'uebsans', 9.5, CEB_PDF_FONCE, 'C', 5 );
	ceb_pdf_ecrire( $pdf, 42, 60.5, 128, 'N° candidat : ' . $d->numero, 'uebsansb', 11.5, CEB_PDF_ACCENT, 'C', 6 );

	$y = ceb_pdf_rubrique( $pdf, 72, 'CONCOURS', array(
		array( 'Cycle', 'Cycle', CEB_CYCLES[ $d->cycle ]['nom'] ?? '' ),
		array( 'Filière', 'Program', CEB_FILIERES[ $d->filiere ] ?? '' ),
		array( 'Option', 'Option', CEB_OPTIONS[ $d->option_code ] ?? '' ),
	), array(
		array( 'Centre d\'examen', 'Examination centre', CEB_CENTRES[ $d->centre ] ?? '' ),
		array( 'Langue de composition', 'Language of the exam', CEB_LANGUES[ $d->langue ] ?? '' ),
		array( 'Fonctionnaire', 'Civil servant', $d->fonctionnaire ? 'Oui' : 'Non' ),
	) );

	$y = ceb_pdf_rubrique( $pdf, $y, 'ÉTAT CIVIL', array(
		array( 'Nom(s)', 'Surname', $d->noms ),
		array( 'Prénom(s)', 'First name(s)', $d->prenoms ),
		array( 'Date et lieu de naissance', 'Date & place of birth', ceb_pdf_date( $d->date_naissance ) . ' à ' . $d->lieu_naissance ),
		array( 'Sexe', 'Gender', CEB_SEXES[ $d->sexe ] ?? '' ),
	), array(
		array( 'Nationalité', 'Nationality', $d->nationalite ),
		array( 'Situation familiale', 'Marital status', CEB_MARITAL[ $d->marital ] ?? '' ),
		array( 'N° CNI', 'ID card number', $d->cni ),
		array( 'Délivrée le / à', 'Issued on / at', ceb_pdf_date( $d->cni_date ) . ' à ' . $d->cni_lieu ),
	) );

	$y = ceb_pdf_rubrique( $pdf, $y, 'CONTACT ET ORIGINE', array(
		array( 'Téléphone', 'Phone', $d->telephone ),
		array( 'E-mail', 'E-mail', $d->email ),
		array( 'Adresse', 'Address', $d->adresse ),
	), array(
		array( 'Région d\'origine', 'Region of origin', CEB_REGIONS[ $d->region ]['nom'] ?? '' ),
		array( 'Département', 'Division', $d->departement ),
		array( 'Arrondissement', 'Subdivision', $d->arrondissement ),
	) );

	$y = ceb_pdf_rubrique( $pdf, $y, 'DIPLÔME ET PAIEMENT', array(
		array( 'Diplôme présenté', 'Diploma', CEB_DIPLOMES[ $d->diplome ] ?? '' ),
		array( 'Émetteur du diplôme', 'Issued by', $d->etablissement ),
		array( 'Année et mention', 'Year and grade', $d->annee_diplome . ', ' . ( CEB_MENTIONS[ $d->mention ] ?? '' ) ),
	), array(
		array( 'Frais de concours', 'Exam fee', ceb_fcfa( ceb_reglage( 'frais' ) ) ),
		array( 'N° de transaction', 'Transaction number', $d->transaction_num ),
		array( 'Banque', 'Bank', ceb_reglage( 'banque' ) ),
	) );

	ceb_pdf_ecrire( $pdf, CEB_PDF_G, $y - 1, CEB_PDF_L, 'Je déclare sur l\'honneur que les informations saisies sont exactes.', 'uebserifi', 8.4, CEB_PDF_FONCE );
	$y += 3;
	foreach ( array( array( CEB_PDF_G + 12, 'Signature de l\'Administration' ), array( 128, 'Signature du candidat' ) ) as $s ) {
		ceb_pdf_ecrire( $pdf, $s[0], $y, 60, $s[1], 'uebsansb', 7.8, CEB_PDF_FONCE, 'C' );
		$pdf->Rect( $s[0], $y + 4.4, 60, 11, 'D', array( 'all' => array( 'dash' => '1.5,1', 'width' => 0.2, 'color' => CEB_PDF_GRIS ) ) );
	}
	ceb_pdf_recepisse( $pdf, $d, $y + 20 );
	ceb_pdf_ecrire( $pdf, 0, 291.5, 210, CEB_CREDIT, 'uebserifi', 6.5, CEB_PDF_GRIS, 'C', 3 );
}

/** Coupon gardé par le candidat, cacheté au dépôt. */
function ceb_pdf_recepisse( $pdf, $d, $y ) {
	$pdf->SetLineStyle( array( 'width' => 0.25, 'dash' => '2,1.5', 'color' => CEB_PDF_GRIS ) );
	$pdf->Line( 0, $y, 210, $y );
	$pdf->SetLineStyle( array( 'width' => 0.35, 'dash' => 0, 'color' => CEB_PDF_VERT ) );
	$y += 3;
	$h = min( 44, 289 - $y );
	$pdf->RoundedRect( CEB_PDF_G, $y, CEB_PDF_L, $h, 2.6, '1111', 'D' );

	ceb_pdf_ecrire( $pdf, CEB_PDF_G + 30, $y + 2, 120, 'RÉCÉPISSÉ DE DÉPÔT', 'uebserifb', 11, CEB_PDF_VERT, 'C', 5 );
	ceb_pdf_photo( $pdf, $d, CEB_PDF_G + 3, $y + 3, 24, 24 );
	ceb_pdf_qr( $pdf, $d, 176, $y + 3, 21 );

	$lignes = array(
		array( 'N° candidat', $d->numero, 'Centre d\'examen', CEB_CENTRES[ $d->centre ] ?? '' ),
		array( 'Nom et prénom(s)', trim( $d->noms . ' ' . $d->prenoms ), 'Langue', CEB_LANGUES[ $d->langue ] ?? '' ),
		array( 'Filière', CEB_FILIERES[ $d->filiere ] ?? '', 'Cycle', CEB_CYCLES[ $d->cycle ]['court'] ?? '' ),
		array( 'Option', CEB_OPTIONS[ $d->option_code ] ?? '', 'Transaction', $d->transaction_num ),
	);
	$ly = $y + 9;
	foreach ( $lignes as $l ) {
		ceb_pdf_ecrire( $pdf, 40, $ly, 25, $l[0], 'uebsemi', 7.4, CEB_PDF_FONCE );
		ceb_pdf_ecrire( $pdf, 65, $ly, 49, $l[1], 'uebsans', 8, CEB_PDF_FONCE );
		ceb_pdf_ecrire( $pdf, 116, $ly, 21, $l[2], 'uebsemi', 7.4, CEB_PDF_FONCE );
		ceb_pdf_ecrire( $pdf, 137, $ly, 37, $l[3], 'uebsans', 8, CEB_PDF_FONCE );
		$ly += 4.7;
	}
	ceb_pdf_ecrire( $pdf, CEB_PDF_G + 3, $y + $h - 8, 74, 'Signature et cachet de l\'Administration', 'uebsemi', 7.4, CEB_PDF_FONCE );
	ceb_pdf_ecrire( $pdf, CEB_PDF_G + 3, $y + $h - 4.6, 150, 'Présente ce récépissé et ta CNI le jour des épreuves.', 'uebserifi', 7.6, CEB_PDF_GRIS );
}

/** Fiche d'un dossier, proposée au téléchargement. */
function ceb_envoyer_fiche( $d ) {
	$pdf = ceb_pdf_nouveau();
	$pdf->SetTitle( 'Fiche de candidature ' . $d->numero );
	ceb_pdf_page( $pdf, $d );
	nocache_headers();
	$pdf->Output( 'fiche-' . $d->numero . '.pdf', 'I' );
	exit;
}

/** Plusieurs fiches dans un seul PDF, pour l'impression à la scolarité. */
function ceb_envoyer_fiches( array $dossiers ) {
	$pdf = ceb_pdf_nouveau();
	$pdf->SetTitle( 'Fiches de candidature' );
	foreach ( $dossiers as $d ) {
		ceb_pdf_page( $pdf, $d );
	}
	nocache_headers();
	$pdf->Output( 'fiches-concours.pdf', 'D' );
	exit;
}
