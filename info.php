<?php
/*

Copyright 2014 Patrick Smith

*/


function pvlpGetInPageURLToSectionWithID($sectionID)
{
	return '#section-' .$sectionID;
}


function pvlpGetAllUserOrderedPostsOfType($postType)
{
	return get_posts(array(
		'post_type' => $postType,
		'numberposts' => -1,
		'orderby' => 'menu_order',
		'order' => 'ASC'
	));
}


function pvlpGetAllPostsOfTypeInCustomCategoryWithSlug($postType, $categoryType, $categorySlug)
{
	return get_posts(array(
		'post_type' => $postType,
		'numberposts' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => $categoryType,
				'field' => 'slug',
				'terms' => $categorySlug
			)
		)
	));
}
