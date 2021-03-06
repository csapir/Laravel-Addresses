<?php namespace Lecturize\Addresses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webpatser\Countries\Countries;

/**
 * Class Address
 * @package Lecturize\Addresses\Models
 */
class Address extends Model
{
	use SoftDeletes;

	/**
     * @todo make this editable via config file
     * @inheritdoc
	 */
	protected $table = 'addresses';

	/**
     * @inheritdoc
	 */
	protected $fillable = [
		'full_name',
		'company',
		'street_1',
		'street_2',
		'city',
		'state',
		'post_code',
		'country_id',
		'lat',
		'lng',
		'addressable_id',
		'addressable_type',
		'is_billing',
		'is_shipping',
		'phone'
	];

	/**
     * @inheritdoc
	 */
	protected $hidden = [];

	/**
     * @inheritdoc
	 */
	protected $dates = ['deleted_at'];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function country()
	{
		return $this->belongsTo(Countries::class, 'country_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo
	 */
	public function addressable()
	{
		return $this->morphTo();
	}

	/**
	 * {@inheritdoc}
	 */
	public static function boot() {
		parent::boot();

		// static::saving(function($address) {
		// 	if ( config('lecturize.addresses.geocode', false) ) {
		// 		$address->geocode();
		// 	}
		// });
	}

	/**
	 * Get validation rules
	 *
	 * @return array
	 */
	public static function getValidationRules() {
		$rules = [
			'full_name' => 'required|string|min:2|max:60',
			'company' =>	'string|min:2|max:60',
			'street_1'     => 'required|string|min:3|max:60',
			'street_2'     => 'string|min:3|max:60',
			'city'       => 'required|string|min:3|max:60',
			'state'      => 'string|min:3|max:60',
			'post_code'  => 'required|min:4|max:10|AlphaDash',
			'country_id' => 'required|integer',
			'phone'				=> 'string|min:2|max:30'
		];

		foreach( config('lecturize.addresses.flags', ['billing', 'shipping']) as $flag ) {
			$rules['is_'.$flag] = 'boolean';
		}

		return $rules;
	}

	/**
	 * Try to fetch the coordinates from Google
	 * and store it to database
	 *
	 * @return $this
	 */
	public function geocode() {
		// build query string
		$query = [];
		$query[] = $this->street_1     ?: '';
		$query[] = $this->city         ?: '';
		$query[] = $this->state        ?: '';
		$query[] = $this->post_code    ?: '';
		$query[] = $this->getCountry() ?: '';

		// build query string
		$query = trim( implode(',', array_filter($query)) );
		$query = str_replace(' ', '+', $query);

		// build url
		$url = 'https://maps.google.com/maps/api/geocode/json?address='.$query.'&sensor=false';

		// try to get geo codes
		if ( $geocode = file_get_contents($url) ) {
			$output = json_decode($geocode);

			if ( count($output->results) && isset($output->results[0]) ) {
				if ( $geo = $output->results[0]->geometry ) {
					$this->lat = $geo->location->lat;
					$this->lng = $geo->location->lng;
				}
			}
		}

		return $this;
	}

	/**
	 * Get address as array
	 *
	 * @return array|null
	 */
	public function getArray() {
		$address = [];

		$address[] = $this->full_name;
		$address[] = $this->company ?: '';
		$address[] = $this->street_1;
		$address[] = $this->street_2;
		$address[] = $this->city . ', ' . $this->state . ' ' . $this->post_code;
		$address[] = $this->phone;		


		return $address;
	}

	/**
	 * Get address as html block
	 *
	 * @return string|null
	 */
	public function getHtml() {
		if ( $address = $this->getArray() )
			return '<address>'. implode( '<br />', array_filter($address) ) .'</address>';

		return null;
	}

	/**
	 * Get address as a simple line
	 *
	 * @return string|null
	 */
	public function getLine() {
		if ( $address = $this->getArray() )
			return implode( ', ', array_filter($address) );

		return null;
	}

	/**
	 * Try to get country name
	 *
	 * @return string|null
	 */
	public function getCountry() {
		if ( $this->country && $country = $this->country->name )
			return $country;

		return null;
	}
}