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
		


			<?php get_template_part('loop'); ?>

			<?php get_template_part('pagination'); ?>

		</section>
		<!-- /section -->
	</main>

<?php get_sidebar(); ?>

<?php get_footer(); ?>