<?php get_header(); ?>

	<main role="main">
		<!-- section -->
		<section>

			<h1><?php _e( 'Cartes', 'html5blank' ); ?></h1>

			<ul class="liste_regions vertical">
				<?php $regions = get_terms( 'subdivision', array( 'parent'=>0 ) ); ?>
				<?php foreach( $regions as $region ) : $region_svg = ucfirst( sanitize_title( preg_replace( '/\'/', '-', $region->name ) ) ); ?>
					<li class="region"><a class="region_link" href="<?php echo get_term_link( $region ); ?>" region="<?php echo $region_svg; ?>"><?php echo strtoupper( $region->name ); ?></a></li>
				<?php endforeach; ?>
			</ul>
			
			<div class="carte">
				<img id="france-svg" class="svg france regions" src="<?php echo get_template_directory_uri(); ?>/img/france-regions.svg" alt="France - régions" />
			</div>
			
		</section>
		<!-- /section -->
	</main>
	
<script>
	/**
	 * Replace all SVG images with inline SVG
	 */
        jQuery('img.svg').each(function(){
            var $img = jQuery(this);
            var imgID = $img.attr('id');
            var imgClass = $img.attr('class');
            var imgURL = $img.attr('src');

            jQuery.get(imgURL, function(data) {
                // Get the SVG tag, ignore the rest
                var $svg = jQuery(data).find('svg');

                // Add replaced image's ID to the new SVG
                if(typeof imgID !== 'undefined') {
                    $svg = $svg.attr('id', imgID);
                }
                // Add replaced image's classes to the new SVG
                if(typeof imgClass !== 'undefined') {
                    $svg = $svg.attr('class', imgClass+' replaced-svg');
                }

                // Remove any invalid XML tags as per http://validator.w3.org
                $svg = $svg.removeAttr('xmlns:a');

		// Virer les parties inutiles du SVG
		$svg.find('#Cadre').remove();
		$svg.find('#dep').remove();

		// Gérer l'événement clic sur une région
		$svg.find('path').on('click', function(e){
			if( jQuery(this).attr('id') != 'dep' )
				path_click( jQuery(this).attr('id').toLowerCase() );
		});

		// Allumage des items du menu latéral au survol des régions
		$svg.find('path').on( 'hover', function( e ){
			var region_map = jQuery(this).attr('id');
			path_hover( region_map );
			//console.log( 'Hovering on 2 ' + region_map );
			//jQuery('ul.liste_regions.vertical a[region=' + region_map + ']').toggleClass('hovered');
		});

                // Replace image with new SVG
                $img.replaceWith($svg);

            }, 'xml');

        });

	/** Clic sur les régions de la carte **/
	function path_click( path_id ) {
		console.log( 'clicked path: ' + path_id );
		document.location='/subdivision/'+path_id+'/';
	}

	/** Survol des régions de la carte **/
	function path_hover( path_id ) {
		//console.log( 'hovering on path: ' + path_id );
		jQuery('ul.liste_regions.vertical a[region=' + path_id + ']').toggleClass('hovered');
	}
	
	(function($) {
		$(document).ready(function() {

			console.log( 'Document ready: initialisation survols...' );
			
			/** Allumage des bouts de carte au survol des liens **/
			$('ul.liste_regions.vertical a.region_link').hover(
				function( e ){
					var region_css = $(this).attr('region');
					$('#france-svg #' + region_css).css('fill','#008bcc');
				},
				function( e ) {
					var region_css = $(this).attr('region');
					$('#france-svg #' + region_css).css('fill','');
				}
			);

			/** Et l'inverse allumage des items du menu latéral au survol des régions **/
			/* NB: cette fonction n'est pas gérée ici mais ci-dessus dans le remplacement du SVG!
			$('#france-svg g path').on( 'hover', function( e ){
				var region_map = $(this).attr('id');
				console.log( 'Hovering on 1 ' + region_map );
				$('ul.liste_regions.vertical a[region=' + region_map + ']').toggleClass("hovered");
			});
			*/

			/** Localisez-moi **/
			$('a.geolocate').click( function(e) {
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(showPosition);
				} else {
					$('a.geolocate').innerHTML = "Votre navigateur ne permet pas de vous localiser.";
				}
			});
		});
	})( jQuery );
</script>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
