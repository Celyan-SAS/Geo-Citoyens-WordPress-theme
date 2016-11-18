<?php 

/** Détermination du niveau de profondeur géo (région / département / canton) **/
$ancestors = get_ancestors( get_queried_object()->term_id, 'subdivision' );
$niveau = 'région';
if( count( $ancestors ) > 0 )
	$niveau = 'département';
if( count( $ancestors ) > 1 )
	$niveau = 'canton';
if( count( $ancestors ) > 2 )
	$niveau = 'bureau';

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
		<?php if( 'bureau' == $niveau ) : ?>
			<h1>Bureau <?php echo get_queried_object()->name; ?></h1>
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

				<?php if( empty($dn) || $dn!=get_queried_object()->name ) : //Pas dans cas particuliers Paris/DOM-TOM ?>	
				<span class="region">	
					<a href="<?php echo get_term_link( get_queried_object()->term_id, 'subdivision' ); ?>"><?php echo get_queried_object()->name; ?><?php if('département'==$niveau) echo ' ('.get_queried_object()->description.')'; ?></a></span>
				</span>
				<?php endif; ?>
			</div>

			<?php if( get_term_children( get_queried_object()->term_id, 'subdivision' ) ) : $deps = array(); $empty_deps = array(); ?>

				<?php if ( $nivclass == 'departement') : ?>
					<h2>Liste des cantons</h2>
					<ul class="js-masonry liste_regions vertical clear " data-masonry-options='{ "columnWidth": 30, "itemSelector": "li.region" }'>
				<?php else : ?>
					<?php if ( $niveau == 'canton') : ?>
						<h2>Liste des bureaux de vote</h2>
					<?php else : ?>
						<h2>Liste des départements</h2>
					<?php endif; ?>
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
				if( $niveau != 'canton' ) {
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
				}
				?>
				
				<?php if ( $niveau != 'département' && $niveau != 'canton' && $niveau != 'bureau' ) : ?>
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
				<?php endif; //if ( $niveau != 'département' && $niveau != 'canton' ) ?>
				
				<?php if( 'département' == $niveau ) : ?>
				
					<div class="carte">
					<?php 
						echo do_shortcode( '[wpgeojson_map map_type="leaflet" post_type="city" selection="all"]' ); 
					?>
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
/**/
						foreach( $cantons as $canton ) {
							if( $geojson = get_field( 'geojson', 'subdivision_' . $canton->term_id ) ) {
								echo '<script>(function($){$(document).ready(function(){' .
									'additionalFeatures.push(' . html_entity_decode( $geojson ) . ');' .
									'});})(jQuery);</script>';
								//var_dump( $geojson );
							}
						}
/**/
					?>
					

					<h3>Cantons fichier json</h3>
					<?php 
						/**
						$file = ABSPATH;
						echo '<p>Fichier: ' . $file . '</p>';
						$file .= 'data/cantons_2015_simpl.json'; 
						echo '<p>Fichier: ' . $file . '</p>';
						/**/
					?>
					<ul>
					<?php 
					/** Importation des cantons **
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
							
							if( ! $res = term_exists( $feature->properties->nom, 'subdivision' ) ) {
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
							
							/** Stocker le GEOjson dans le champ ACF geojson de la subdivision **
							if( !empty( $res['term_id'] ) ) 
								update_field( 'geojson', $json_part, 'subdivision_' . $res['term_id'] );
							
							echo '</li>';	
						}
						fclose( $h );
					}
					/** **/
					?>
					</ul>
					
				<?php endif; ?>
				
				<?php if( 'canton' == $niveau ) : ?>
				
					<!--  p>Carte du canton <?php echo get_queried_object()->name; ?></p -->
					<div class="carte">
					<?php 
						/** Sélectionner uniquement les villes de ce canton **/
						$villes = get_posts( array( 
							'post_type'	=> 'city', 
							'tax_query'	=> array(
								array(
									'taxonomy'  => 'subdivision',
									'field'		=> 'name',
									'terms'		=> get_queried_object()->name
								)
							)
						) );
						//var_dump( $villes );
						
						/** Afficher la carte **/
						echo do_shortcode( '[wpgeojson_map map_type="leaflet" post_type="city" selection="' .$villes[0]->ID . '"]' );
						if( $geojson = get_field( 'geojson', 'subdivision_' . get_queried_object()->term_id ) )
							echo '<script>(function($){$(document).ready(function(){' .
									'additionalFeatures.push(' . html_entity_decode( $geojson ) . ');' .
									'});})(jQuery);</script>';
						
						/** Test troncons voies Nogent **/
						//ogr2ogr -s_srs EPSG:2154 -t_srs EPSG:4326 -f GeoJSON troncons-94052.json TRONCON_VOIE.shp -where "C_COINSEE=94052"
						if( 8606 == $villes[0]->ID ) :
							?>
							<script>(function($){$(document).ready(function(){
								var troncons = new L.geoJson();
								$.ajax({
									dataType: "json",
									url: "/data/cantons_2015_simpl.json",
									success: function(data) {
									    $(data.features).each(function(key, data) {
									    	troncons.addData(data);
									    });
									    additionalFeatures.push(data);
									}
								});
								//additionalFeatures.push(troncons);
							});})(jQuery);
							</script>
							<?php 
						endif;
					?>
					</div>
					
				<?php endif; ?>
				
				<?php if( 'bureau' == $niveau ) : ?>
				
				<h2>test bureau</h2>
				
				<div class="carte">
					<?php 
						/** Sélectionner uniquement les villes de ce canton **/
						$villes = get_posts( array( 
							'post_type'	=> 'city', 
							'tax_query'	=> array(
								array(
									'taxonomy'  => 'subdivision',
									'field'		=> 'name',
									'terms'		=> get_queried_object()->name
								)
							)
						) );
						//var_dump( $villes );
						
						/** Afficher la carte **/
						echo do_shortcode( '[wpgeojson_map map_type="leaflet" post_type="city" selection="' .$villes[0]->ID . '"]' );
						if( $geojson = get_field( 'geojson', 'subdivision_' . get_queried_object()->term_id ) )
							echo '<script>(function($){$(document).ready(function(){' .
									'additionalFeatures.push(' . html_entity_decode( $geojson ) . ');' .
									'});})(jQuery);</script>';
						
						/** Test troncons voies Nogent **/
						//ogr2ogr -s_srs EPSG:2154 -t_srs EPSG:4326 -f GeoJSON troncons-94052.json TRONCON_VOIE.shp -where "C_COINSEE=94052"
						if( 8606 == $villes[0]->ID ) :
							echo '<h3>Test bureau 1 Nogent</h3><table>';
							global $wpdb;
							$q = "
								SELECT * FROM geo_arretes_bureaux 
								WHERE code_insee='94052' AND
								Bureau = 1;
							";
							$rows = $wpdb->get_results( $q );	
							foreach( $rows as $row ) {
								echo '<tr>';
								echo '<td><a class="voiehover" href="#">' . $row->Voie . '</a></td>';
								echo '</tr>';
							}
							echo '</table>';
							?>
							<script>(function($){$(document).ready(function(){
								var troncons = new L.geoJson();
								$.ajax({
									dataType: "json",
									url: "/data/cantons_2015_simpl.json",
									success: function(data) {
									    $(data.features).each(function(key, data) {
									    	troncons.addData(data);
									    });
									    additionalFeatures.push(data);
									}
								});
								//additionalFeatures.push(troncons);
							});})(jQuery);
							</script>
							<script>
							(function($){$(document).ready(function(){
								console.log('setup hover voie');
								$('a.voiehover').on('hover',function(e){
									console.log( 'hovering on voie...' );
									$.each( allLayers, function( index, value ) {
										console.log( value );
									});
								});
							});})(jQuery);
							</script>
							<?php
						endif;
					?>
					</div>
				
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
