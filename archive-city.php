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

<?php get_sidebar(); ?>

<?php get_footer(); ?>
