<?php

class xrowMetaData extends ezcBaseStruct
{
    public $priority;
    public $change;
    public $title;
    public $keywords = array();
    public $description;
    public $sitemap_use;
    public $canonical_url;
    /**
     * The Open Graph image object id the page shows: the object's own image,
     * else the class default. Empty when there is none that can be shown.
     */
    public $og_image;
    /**
     * The Open Graph image object id stored in this attribute (the object's
     * own choice), whether or not that object can still be shown. This is
     * what is saved.
     */
    public $og_image_own;
    /**
     * Where og_image comes from: 'object', 'class' or ''.
     */
    public $og_image_source = '';
    /**
     * Whether the class lets an object choose its own Open Graph image
     * ("Allow a per-object Open Graph image" of the class attribute). When
     * false the class default is shown, and og_image_own is kept but unused.
     */
    public $og_image_allowed = true;
    /**
     * Kept for stored data only. The size printed for og:image is the size
     * of the image alias (og_image_info), not these values; the edit form no
     * longer offers them.
     */
    public $og_image_width;
    public $og_image_height;
    public $og_image_alt;
    public $og_image_type;

    /**
     * The license of the object: an id of the license catalogue
     * (xrowMetaDataLicense), '' to use the inherited one (class default,
     * else the installation default). Stored as <license>; data saved before
     * 1.4.9 has none and is ''.
     */
    public $license = '';
    /**
     * The copyright holder and the year (or range, "2019-2026") the license
     * notice names. '' when not set.
     */
    public $license_holder = '';
    public $license_year = '';
    /**
     * For a license whose terms are given per object (LicenseRef-Custom,
     * or a catalogue entry with "per_object"): where its terms are published and/or the terms.
     */
    public $license_custom_url = '';
    public $license_custom_terms = '';
    /**
     * Whether the class lets an object choose its own license ("Allow a
     * per-object license" of the class attribute). When false the class
     * default is used and $license is kept but unused.
     */
    public $license_allowed = true;
    /**
     * The default license id of the class, '' for none.
     */
    public $license_class = '';

    /**
     * The picker data and the inherited license, made when a template first
     * asks for them.
     */
    protected $licenseCache = array();

    /**
     * The picture of og_image (xrowMetaDataOpenGraphImage::info()), made when
     * a template first asks for it.
     */
    protected $ogImageInfo = null;

    public function __construct( $title = false, $keywords = array(), $description = false, $priority = false, $change = false, $sitemap_use = false, $canonical_url = false, $og_image = false, $og_image_width = false, $og_image_height = false, $og_image_alt = false, $og_image_type = false )
    {
        $this->title = $title;
        $this->keywords = $keywords;
        $this->description = $description;
        $this->canonical_url = $canonical_url;
        $this->sitemap_use = $sitemap_use;
        $this->og_image = $og_image;
        $this->og_image_own = $og_image;
        $this->og_image_source = ( $og_image !== false && $og_image !== '' && $og_image !== null ) ? 'object' : '';
        $this->og_image_width = $og_image_width;
        $this->og_image_height = $og_image_height;
        $this->og_image_alt = $og_image_alt;
        $this->og_image_type = $og_image_type;
        if ( empty( $priority ) )
        {
            $this->priority = null;
        }
        else
        {
            $this->priority = $priority;
        }
        if ( empty( $change ) )
        {
            $this->change = 'daily';
        }
        else
        {
            $this->change = $change;
        }
        if ( $sitemap_use === false )
        {
            $this->sitemap_use = '1';
        }
        elseif ( empty( $sitemap_use ) )
        {
            $this->sitemap_use = '0';
        }
        else
        {
            $this->sitemap_use = '1';
        }
    }

    function hasAttribute( $name )
    {
        return in_array( $name, $this->attributes(), true ) || in_array( $name, array( 'priority', 'change' ), true );
    }

    function attributes()
    {
        return array('title','description','keywords','sitemap_use','canonical_url','og_image','og_image_width','og_image_height','og_image_alt','og_image_type',
                     'og_image_own','og_image_source','og_image_allowed','og_image_info','og_image_object',
                     'license','license_holder','license_year','license_custom_url','license_custom_terms',
                     'license_allowed','license_class','license_info','license_inherited','license_effective','license_choices');
    }

    function attribute( $name )
    {
        switch ( $name )
        {
            case 'og_image_info':
                return $this->ogImageInfo();
            case 'og_image_object':
                return xrowMetaDataOpenGraphImage::usableObject( $this->og_image );
            case 'license_info':
                // The catalogue entry of the object's own license, false when
                // none is chosen or the id is not in the catalogue
                return $this->license !== '' ? xrowMetaDataLicensePicker::info( $this->license ) : false;
            case 'license_inherited':
                // What applies when the object chooses none: class, else installation
                if ( !array_key_exists( 'inherited', $this->licenseCache ) )
                {
                    $this->licenseCache['inherited'] = xrowMetaDataLicensePicker::inherited( $this->license_class );
                }
                return $this->licenseCache['inherited'];
            case 'license_effective':
                // What the page states: the own license (when allowed), else the inherited one
                if ( $this->license_allowed && $this->license !== '' )
                {
                    $info = xrowMetaDataLicensePicker::info( $this->license );
                    if ( $info )
                    {
                        $info['source'] = 'object';
                        return $info;
                    }
                }
                return $this->attribute( 'license_inherited' );
            case 'license_choices':
                if ( !array_key_exists( 'choices', $this->licenseCache ) )
                {
                    $this->licenseCache['choices'] = xrowMetaDataLicensePicker::choices( $this->license );
                }
                return $this->licenseCache['choices'];
        }
        return $this->$name;
    }

    /**
     * The picture of og_image for the page head, or false.
     *
     * @return array|false
     */
    function ogImageInfo()
    {
        if ( $this->ogImageInfo === null )
        {
            $this->ogImageInfo = xrowMetaDataOpenGraphImage::info( $this->og_image );
        }
        return $this->ogImageInfo;
    }

    /**
     * The object's own Open Graph image id as stored, '' for none.
     */
    function storedOgImage()
    {
        $id = xrowMetaDataOpenGraphImage::objectID( $this->og_image_own );
        return $id > 0 ? (string) $id : '';
    }

    /**
     * @return xrowMetaData
     */
    static public function __set_state( array $array )
    {
        $meta = new xrowMetaData( $array['title'], $array['keywords'], $array['description'], $array['priority'], $array['change'], $array['sitemap_use'], $array['canonical_url'], $array['og_image'], $array['og_image_width'], $array['og_image_height'], $array['og_image_alt'], $array['og_image_type'] );
        if ( array_key_exists( 'og_image_own', $array ) )
        {
            $meta->og_image_own = $array['og_image_own'];
        }
        if ( isset( $array['og_image_source'] ) )
        {
            $meta->og_image_source = $array['og_image_source'];
        }
        if ( array_key_exists( 'og_image_allowed', $array ) )
        {
            $meta->og_image_allowed = (bool) $array['og_image_allowed'];
        }
        $meta->setLicense( $array );
        if ( array_key_exists( 'license_allowed', $array ) )
        {
            $meta->license_allowed = (bool) $array['license_allowed'];
        }
        if ( isset( $array['license_class'] ) && is_scalar( $array['license_class'] ) )
        {
            $meta->license_class = (string) $array['license_class'];
        }
        return $meta;
    }

    /**
     * Sets the stored license fields from $array (missing keys keep their
     * value, non-text values are taken as '').
     */
    public function setLicense( array $array )
    {
        foreach ( array( 'license', 'license_holder', 'license_year', 'license_custom_url', 'license_custom_terms' ) as $key )
        {
            if ( array_key_exists( $key, $array ) )
            {
                $this->$key = is_scalar( $array[$key] ) ? trim( (string) $array[$key] ) : '';
            }
        }
        $this->licenseCache = array();
    }
}
