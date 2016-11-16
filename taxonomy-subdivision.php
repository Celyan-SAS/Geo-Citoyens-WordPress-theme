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
			
		<?php if( 'région' == $niveau ) : ?>
			<h1>Région <?php echo get_queried_object()->name; ?></h1>
		<?php endif; ?>
		<?php if( 'département' == $niveau ) : ?>
			<h1>Département <?php echo get_queried_object()->name; ?></h1>
		<?php endif; ?>
		<?php if( 'canton' == $niveau ) : ?>
			<h1>Canton <?php echo get_queried_object()->name; ?></h1>
		<?php endif; ?>
		
		<?php if ( $niveau == 'région' ) : $nivclass = 'region'; elseif ( $niveau == 'département' ) : $nivclass = 'departement'; else  : $nivclass = 'ville'; endif; ?>

		<section class="clear top <?php echo $nivclass; ?>">

			<div class="breadcrumb">

				<span class="root"><a href="/city/">France</a></span>
				<span class="chev">&gt;</span>

				<?php if( count( $ancestors ) > 1 ) : ?>
				<span class="region"><a href="<?php echo get_term_link( $ancestors[1], 'subdivision' ); ?>"><?php echo get_term( $ancestors[1], 'subdivision' )->name; ?></a></span>
				<span class="chev">&gt;</span>
				<?php endif; ?>

				<?php if( count( $ancestors ) > 0 ) : ?>
					<span class="region"><a href="<?php echo get_term_link( $ancestors[0], 'subdivision' ); ?>"><?php echo $dn = get_term( $ancestors[0], 'subdivision' )->name; ?></a></span>
					<?php if( $dn==get_queried_object()->name ) : //cas particuliers Paris/DOM-TOM ?>
						<a href="<?php echo get_term_link( $ancestors[0], 'subdivision' ) ?>"><?php echo ' ('.get_queried_object()->description.')'; ?></a>
					<?php else : ?>
						<span class="chev">&gt;</span>
					<?php endif; ?>
				<?php endif; ?>

				<?php if( $dn!=get_queried_object()->name ) : //Pas dans cas particuliers Paris/DOM-TOM ?>	
				<span class="region">	
					<a href="<?php echo get_term_link( get_queried_object()->term_id, 'subdivision' ); ?>"><?php echo get_queried_object()->name; ?><?php if('département'==$niveau) echo ' ('.get_queried_object()->description.')'; ?></a></span>
				</span>
				<?php endif; ?>
			</div>

			<?php if( get_term_children( get_queried_object()->term_id, 'subdivision' ) ) : $deps = array(); $empty_deps = array(); ?>

				<?php if ( $nivclass == 'departement') : ?>
					<h2>Liste des cantons</h2>
					<ul class="js-masonry liste_regions <?php echo ('département'==$niveau?'horizontal':'vertical'); ?> clear " data-masonry-options='{ "columnWidth": 30, "itemSelector": "li.region" }'>
				<?php else : ?>
					<h2>Liste des départements</h2>
					<ul class="liste_regions <?php echo ('département'==$niveau?'horizontal':'vertical'); ?> clear">
				<?php endif; ?>
				
					<?php $regions = get_terms( 'subdivision', array( 'parent'=>get_queried_object()->term_id, 'hide_empty'=>($niveau=='ville'?true:false) ) ); ?>
					<?php foreach( $regions as $region ) : if( $region->parent!=get_queried_object()->term_id ) continue; $region_svg = ucfirst( sanitize_title( preg_replace( '/\'/', '_', $region->name ) ) ); $deps[] = $region; ?>
						<?php
						/** désactiver départements vides **/
						$empty = '';
						if( !get_term_children( $region->term_id, 'subdivision' ) && $niveau != 'département' && $region->name!='PARIS' ) {
							$empty = 'empty';
							$empty_deps[] = $region->term_id;
						}
						?>
						<li class="region <?php echo $empty; ?>">
							<?php if( !$empty ) : ?>
							<a class="region_link" href="<?php echo get_term_link( $region ); ?>" region="departement<?php echo $region->description; ?>">
							<?php endif; ?>
							<?php echo strtoupper( $region->name ); ?>
							<?php if( !$empty ) : ?>
							</a>
							<?php endif; ?>
						<?php if( 'département'==$niveau ) : ?>

						<?php endif; ?>							
						</li>
					<?php endforeach; ?>
				</ul>		
		
				<?php 
					if ( $nivclass == 'departement')
						$deps = array( get_queried_object() );
				?>	
		
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
				<?php endif; //if ( $niveau != 'département' ) ?>
				
				<?php if( 'département' == $niveau ) : ?>
				
					<div class="carte">
					<?php 
						echo do_shortcode( '[wpgeojson_map map_type="leaflet" post_type="city" selection="all"]' ); 
					?>
					</div>
					
					<div class="petite-carte">
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
					
					<h3>Cantons WP</h3>
					<?php 
						$cantons = get_terms( 
							'subdivision', 
							array( 
								'parent'=>get_queried_object()->term_id, 
								'hide_empty'=>($niveau=='ville'?true:false) 
							) 
						);
/*
						foreach( $cantons as $canton ) {
							if( $geojson = get_field( 'geojson', 'subdivision_' . $canton->term_id ) ) {
								echo '<script>(function($){$(document).ready(function(){var gf=' . html_entity_decode( $geojson ) . ';L.geoJSON(gf).addTo(map);});})(jQuery);</script>';
								var_dump( $geojson );
							}
						}
*/
					?>
					
					<script>
						(function($){
							$(document).ready(function(){
								var gf={
									"type":"Feature",
									"properties":{
										"ref":"094-05",
										"nom":"Charenton-le-Pont",
										"wikipedia":"http://fr.wikipedia.org/wiki/Canton de Charenton-le-Pont",
										"jorf":"JORFTEXT000028626311",
										"bureau":"Charenton-le-Pont",
										"dep":"94",
										"canton":"05",
										"Nom_1":null,
										"population":"66509"
									},
									"geometry":{
										"type":"Polygon",
										"coordinates":[[[2.390256,48.825726],[2.402488,48.829647],[2.409904,48.825307],[2.41987,48.824159],[2.427833,48.824011],[2.43735,48.818219],[2.449638,48.817962],[2.458633,48.817012],[2.462803,48.819028],[2.466178,48.827333],[2.465361,48.831405],[2.475671,48.830595],[2.47873,48.82351],[2.481791,48.819996],[2.481984,48.816059],[2.480104,48.813476],[2.473862,48.815793],[2.468952,48.811896],[2.464713,48.811745],[2.460597,48.809294],[2.45201,48.81531],[2.437913,48.816977],[2.415854,48.8163],[2.409361,48.816633],[2.396374,48.821549],[2.390256,48.825726]]]
									}
								};
								L.geoJSON(gf).addTo(map);
							});
						})(jQuery);
					</script>
					
					<h3>Cantons fichier json</h3>
					<?php 
						/**/
						$file = ABSPATH;
						echo '<p>Fichier: ' . $file . '</p>';
						$file .= 'data/cantons_2015_simpl.json'; 
						echo '<p>Fichier: ' . $file . '</p>';
						/**/
					?>
					<ul>
					<?php 
					/** Importation des cantons **/
					if( $h = fopen( $file, 'r' ) ) {
						while( $json_part = fgets( $h, 4096*2 ) ) {
						
							if( !preg_match( '/^\{\"type\"\:\"Feature\"/', $json_part ) )
								continue;
							
							$json_part = rtrim( $json_part );
							$json_part = rtrim( $json_part, ',' );
							
							$feature = json_decode( $json_part );
							
							//var_dump( $feature->properties->ref ); //exit;	//debug
							
							if( !isset( $feature->properties->dep ) )
								continue;
							
							if( $feature->properties->dep != get_queried_object()->description )
								continue;
							
							echo '<li>';
							echo 'nom: ' . $feature->properties->nom . '<br/>';
							echo 'bureau: ' . $feature->properties->bureau . '<br/>';
							echo 'Nom_1: ' . $feature->properties->Nom_1 . '<br/>';
							
							/**/
							if( !term_exists( $feature->properties->nom, 'subdivision' ) ) {
								$res = wp_insert_term(
									$feature->properties->nom,
									'subdivision',
									array(
										'description' => $feature->properties->ref,
										'parent' => get_queried_object()->term_id
									)
								);
								if( is_array( $res ) && !empty( $res['term_id'] ) )
									echo 'Créé terme: ' . $res['term_id'];
							}
							/**/
							
							echo '</li>';	
						}
						fclose( $h );
					}
					/** **/
					?>
					</ul>
					
				<?php endif; ?>
				
			<?php endif; ?>

			<h2>Villes</h2>
			
			<?php get_template_part('loop'); ?>

			<?php get_template_part('pagination'); ?>

		</section>
		<!-- /section -->
	</main>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
