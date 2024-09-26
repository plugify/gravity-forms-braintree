<?php

// Plugify_GForm_Braintree class

final class Plugify_GFAddOnFeedsTable extends GFAddOnFeedsTable {

	// The only thing we are overriding in this class is the "column_is_active" functionality
	function column_is_active ( $item ) {

		$is_active = intval(rgar($item, "is_active"));
    $src = GFCommon::get_base_url() . "/images/active{$is_active}.png";

    $title = $is_active ? __("Active", "gravityforms") : __("Inactive", "gravityforms");
    $img = sprintf( "<img src=\"{$src}\" class=\"toggle_active\" title=\"{$title}\" data-feed-id=\"%s\" style=\"cursor:pointer\";/>", $item['id'] );

    return $img;

  }

}

?>
