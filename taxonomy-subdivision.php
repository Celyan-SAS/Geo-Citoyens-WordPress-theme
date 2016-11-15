<?php 

/** Détermination du niveau de profondeur géo (région / département / canton) **/
$ancestors = get_ancestors( get_queried_object()->term_id, 'subdivision' );
$niveau = 'région';
if( count( $ancestors ) > 0 )
	$niveau = 'département';
if( count( $ancestors ) > 1 )
	$niveau = 'canton';

/** redirection canonique : cas particulier Paris et DOM-TOM **/
if( $niveau == 'département' || $niveau == 'région' ) {
	$my_uri = home_url( $_SERVER['REQUEST_URI'] );
	$canonical = get_term_link( get_queried_object(), 'subdivision' );
	if( $canonical != $my_uri )
		wp_redirect( $canonical );
}

/** bon article à afficher en titre de localité **/
$article_localite = 'en';
if( $niveau == 'département' && function_exists( 'get_field' ) ) {
	if( $tncc = $cached_coords =  get_field( 'tncc', 'subdivision_' . get_queried_object()->term_id ) ) {
		switch( $tncc ) {
			case(0):
				$article_localite = 'en';
				break;
			case(1):
				$article_localite = 'en';
				break;
			case(2):
				$article_localite = 'dans le';
				break;
			case(3):
				$article_localite = 'dans la';
				break;
			case(4):
				$article_localite = 'dans les';
				break;
			case(5):
				$article_localite = 'dans l&apos;';
				break;
			case(6):
				$article_localite = 'aux';
				break;
			case(7):
				$article_localite = 'à las';
				break;
			case(8):
				$article_localite = 'à los';
				break;
		}
	}
}

if(
		count( $ancestors ) > 1 ||
		get_queried_object()->name == 'Paris' ||
		get_queried_object()->name == 'PARIS' ||
		get_queried_object()->name == 'La Réunion' ||
		get_queried_object()->name == 'Mayotte'
)
	$article_localite = 'à';

if(
		get_queried_object()->name == 'Guadeloupe' ||
		get_queried_object()->name == 'Guyane' ||
		get_queried_object()->name == 'Martinique'
)
	$article_localite = 'en';
if(
		count( $ancestors ) == 0 &&
		get_queried_object()->name == 'Centre'
)
	$article_localite = 'en région';


$display_localite = strtoupper( get_queried_object()->name );
if(
		count( $ancestors ) > 1 &&
		preg_match( '/^le\s+/i', get_queried_object()->name )
) {
	$display_localite = preg_replace( '/^le\s+/i', '', get_queried_object()->name );
	$article_localite = 'au';
}
if(
		count( $ancestors ) > 1 &&
		preg_match( '/^les\s+/i', get_queried_object()->name )
) {
	$display_localite = preg_replace( '/^les\s+/i', '', get_queried_object()->name );
	$article_localite = 'aux';
}

get_header(); ?>

	<main role="main" class="cartes niveau <?php echo $niveau; ?>">
		<!-- section -->
		<section>

			
			<?php if( 'région' == $niveau ) : ?>
				<h1>Région <?php echo get_queried_object()->name; ?></h1>
			<?php endif; ?>
			<?php if( 'département' == $niveau ) : ?>
				<h1>Département <?php echo get_queried_object()->name; ?></h1>
			<?php endif; ?>
			<?php if( 'canton' == $niveau ) : ?>
				<h1>Canton <?php echo get_queried_object()->name; ?></h1>
			<?php endif; ?>
		
			<?php 
			/** 
			 * Extraction en base des données géographiques de contour 
			 * de tous les départements de la région, 
			 * et calcul des bords de la zone pour recadrage
			 *
			 **/
			global $wpdb;
			$minX = 1000;
			$minY = 1000;
			$maxX = 0;
			$maxY = 0;
			foreach( $deps as $dep ) {
				$query = "SELECT trace FROM cartes_departements WHERE dep LIKE '$dep->description';";
				$trace[$dep->description] = $wpdb->get_var( $query );
				$coords = preg_split( '/\s*L\s*/', preg_replace( '/^\s*M\s*/', '', $trace[$dep->description] ) );
				foreach( $coords as $coord ) {
					list( $x, $y ) = preg_split( '/,/', $coord );
					if( floatval($x) < floatval($minX) ) $minX = $x;
					if( floatval($y) < floatval($minY) ) $minY = $y;
					if( floatval($x) > floatval($maxX) ) $maxX = $x;
					if( floatval($y) > floatval($maxY) ) $maxY = $y;
				}
			}
			var_dump( $trace );
			?>
			
			<?php if ( $niveau != 'département' ) : ?>
				<div class="carte">
					<svg id="france-svg" class="svg france regions" viewBox="0 0 <?php echo $maxX - $minX; ?> <?php echo $maxY - $minY; ?>" style="width:100%;height:500px;max-height:500px;" width="100%"><g>
					<?php 
					// <svg id="france-svg" class="svg france regions" viewBox="<?php echo $minX; ? > <?php echo $minY; ? > <?php echo $maxX; ? > <?php echo $maxY; ? >" style="width:100%;height:auto;max-height:500px;"><g> <- cette façon plus simple de recadrer avec la viewbox ne permet pas de zoomer l'échelle

					/**
					 * Traçage du contour de chaque département
					 * en recadrant pour être à 0 0 en haut à gauche
					 *
					 */
					foreach( $deps as $dep ) {
						$coords = preg_split( '/\s*L\s*/', preg_replace( '/^\s*M\s*/', '', $trace[$dep->description] ) );
						/** Re-recadrage **/
						foreach( $coords as &$coord ) {
							list( $x, $y ) = preg_split( '/,/', $coord );
							$x = ( $x - $minX );
							$y = ( $y - $minY );
							$coord = join( ',', array( $x, $y ) );
						}
						/**/
						$totrace = 'M ' . join( ' L ', $coords );
						$empty = '';
						if( in_array( $dep->term_id, $empty_deps ) )
							$empty = 'empty';
						echo '<path d="' . $totrace . ' z " class="land departement' . $dep->description . ' ' . $empty . '" id="' . $dep->slug . '" />' . "\n";
					}
					?>
					</g></svg>
				</div>
			<?php endif; ?>
				

			<h2>Villes</h2>
			
			<?php get_template_part('loop'); ?>

			<?php get_template_part('pagination'); ?>

		</section>
		<!-- /section -->
	</main>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
