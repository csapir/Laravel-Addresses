<?php

return [
	/*
	 * Addresses
	 */
	'addresses' => [
		/*
		 * Main table
		 */
		'table'   => 'addresses',

		/*
		 * Flag columns to be added to table
		 */
		'flags'   => ['billing', 'shipping'],

		/*
		 * Enable geocoding to add coordinates (lon/lat) to addresses
		 */
		'geocode' => false,
	]
];