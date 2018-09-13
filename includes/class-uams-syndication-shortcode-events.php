<?php

class UAMS_Syndicate_Shortcode_Events extends UAMS_Syndicate_Shortcode_Base {

	/**
	 * @var array Overriding attributes applied to the base defaults.
	 */
	public $local_default_atts = array(
		'output'      => 'list',
		'host'        => 'news.uams.edu',
	);

	
	/**
	 * @var string Shortcode name.
	 */
	public $shortcode_name = 'uamswp_events';

	public function __construct() {
		parent::construct();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_syndication_events_stylesheet' ) );
		if ( class_exists('UAMS_Shortcakes') ) {
			add_action( 'admin_init', array( $this, 'build_shortcake' ) );
			// add_editor_style( plugins_url( '/css/uams-syndication-admin.css', __DIR__ ) );
			add_action( 'enqueue_shortcode_ui', function() {
				// wp_enqueue_script( 'uams_syndications_editor_js', plugins_url( '/js/uams-syndication-shortcake.js', __DIR__ ) );
			});
		}
		add_action( 'admin_init', array( $this, 'enqueue_syndication_stylesheet_admin' ) );
	}
	/**
	 * Add the shortcode provided.
	 */
	public function add_shortcode() {
		add_shortcode( 'uamswp_events', array( $this, 'display_shortcode' ) );
	}

	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	public function enqueue_syndication_events_stylesheet() {
		$post = get_post();
	 	if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'uamswp_events' ) ) {
			wp_enqueue_style( 'uamswp-syndication-events-style', plugins_url( '/css/uamswp-syndication-events.css', __DIR__ ), array(), '' );
		}
	}

	/**
	 * Enqueue styles specific to the network admin dashboard.
	 */
	public function enqueue_syndication_stylesheet_admin() {
		add_editor_style( 'uamswp-syndication-events-style-admin', plugins_url( '/css/uamswp-syndication-events.css', __DIR__ ), array(), '' );
	}
	public function build_shortcake() {
		shortcode_ui_register_for_shortcode(
	 
			/** Your shortcode handle */
			'uamswp_events',
			 
			/** Your Shortcode label and icon */
			array(
			 
			/** Label for your shortcode user interface. This part is required. */
			'label' => esc_html__('Events Syndication', 'uamswp_events'),
			 
			/** Icon or an image attachment for shortcode. Optional. src or dashicons-$icon.  */
			'listItemImage' => 'dashicons-calendar-alt',
			 
			/** Shortcode Attributes */
			'attrs'          => array(
			 
				/** Output format */
				array(
				'label'     => esc_html__('Format', 'uamswp_events'),
				'attr'      => 'output',
				'type'      => 'radio',
				    'options' => array(
						'headline'	=> 'Headlines Only',
				        'list'      => 'List',
				        'excerpts'    => 'Excerpt',
				        'cards'     => 'Card', // Maybe
				        //'full'     => 'Full', // Maybe
				    ),
				'description'  => 'Preferred output format',
				),

				array(
				 
				/** This label will appear in user interface */
				'label'        => esc_html__('Category', 'uamswp_events'),
				'attr'         => 'site_category_slug',
				'type'         => 'text',
				'description'  => 'Please enter the filter / category',
				),

				/** Count - Number of events */
				array(
				'label'        => esc_html__('Count', 'uamswp_events'),
				'attr'         => 'count',
				'type'         => 'number',
				'description'  => 'Number of events to display',
				'meta'   => array(
						'placeholder' 	=> esc_html__( '1' ),
						'min'			=> '1',
						'step'			=> '1',
					),
				),

				/** Offset - Number of articles to skip */
				array(
				'label'        => esc_html__('Offset', 'uamswp_events'),
				'attr'         => 'offset',
				'type'         => 'number',
				'description'  => 'Number of events to skip',
				'meta'   => array(
						'placeholder' 	=> esc_html__( '0' ),
						'min'			=> '0',
						'step'			=> '1',
					),
				),

			 
			),
			 
			/** You can select which post types will show shortcode UI */
			'post_type'     => array( 'post', 'page' ), 
			)
		);
	}

	/**
	 * Display events information for the [uamswp_events] shortcode.
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function display_shortcode( $atts ) {
		$atts = $this->process_attributes( $atts );

		$site_url = $this->get_request_url( $atts );
		if ( ! $site_url ) {
			return '<!-- uamswp_events ERROR - an empty host was supplied -->';
		}

		$request = $this->build_initial_request( $site_url, $atts );

		// Build taxonomies on the REST API request URL, except for `category`
		// as it's a different taxonomy in this case than the function expects.
		$taxonomy_filters_atts = $atts;

		unset( $taxonomy_filters_atts['category'] );

		// Handle the 'type' taxonomy separately, too.
		unset( $taxonomy_filters_atts['type'] );
		
		$request_url = $this->build_taxonomy_filters( $taxonomy_filters_atts, $request['url'] );

		//Add event post data args
		$request_url = add_query_arg( array(
			'filter[orderby]'=> 'meta_value',
			'filter[meta_key]' => 'event_begin',
			'filter[order]'=> 'ASC',
			'filter[meta_query][0][key]' => 'event_end',
			'filter[meta_query][1][key]' => 'event_end',
			'filter[meta_query][1][value]' => rawurlencode('0:0:00 0:'),
			'filter[meta_query][1][compare]' => rawurlencode('!='),
			'filter[meta_query][2][key]' => 'event_end',
			'filter[meta_query][2][value]' => ':00',
			'filter[meta_query][2][compare]' => rawurlencode('!='),
			'filter[meta_query][3][key]' => 'event_begin',
			'filter[meta_query][4][key]' => 'event_begin',
			'filter[meta_query][4][value]' => rawurlencode('0:0:00 0:'),
			'filter[meta_query][4][compare]' => rawurlencode('!='),
			'filter[meta_query][5][key]' => 'event_end',
			'filter[meta_query][5][value]' => rawurlencode((new \DateTime(null, new DateTimeZone('America/Chicago')))->format('Y-m-d H:i:s')),
			'filter[meta_query][5][compare]' => rawurlencode('>='),
		), $request_url );
		
		if ( 'past' === $atts['period'] ) {
			$request_url = add_query_arg( array(
				'tribe_event_display' => 'past',
			), $request_url );
		}
		// if ( '' !== $atts['category'] ) {
		// 	// $request_url = add_query_arg( array(
		// 	// 	'filter[taxonomy]' => 'tribe_events_cat',
		// 	// ), $request_url );

		// 	$terms = explode( ',', $atts['category'] );
		// 	foreach ( $terms as $term ) {
		// 		$term = trim( $term );
		// 		$request_url = add_query_arg( array(
		// 			'filter[term]' => sanitize_key( $term ),
		// 		), $request_url );
		// 	}
		// }

		if ( ! empty( $atts['offset'] ) ) {
			$atts['count'] = absint( $atts['count'] ) + absint( $atts['offset'] );
		}

		if ( $atts['count'] ) {
			$count = ( 100 < absint( $atts['count'] ) ) ? 100 : $atts['count'];
			$request_url = add_query_arg( array(
				'per_page' => absint( $count ),
			), $request_url );
		}

		$new_data = $this->get_content_cache( $atts, 'uamswp_events' );

		if ( ! is_array( $new_data ) ) {
			$response = wp_remote_get( $request_url );

			if ( ! is_wp_error( $response ) && 404 !== wp_remote_retrieve_response_code( $response ) ) {
				$data = wp_remote_retrieve_body( $response );

				$new_data = array();
				if ( ! empty( $data ) ) {
					$data = json_decode( $data );

					if ( null === $data ) {
						$data = array();
					}

					if ( isset( $data->code ) && 'rest_no_route' === $data->code ) {
						$data = array();
					}

					foreach ( $data as $post ) {
						$subset = new StdClass();
			
						// Only a subset of data is returned for a headlines request.
						// if ( 'headlines' === $atts['output'] ) {
						// 	$subset->link = $post->link;
						// 	$subset->date = $post->date;
						// 	$subset->title = $post->title->rendered;
						// } else {
							$subset->ID = $post->id;
							$subset->date = $post->date; // In time zone of requested site
							$subset->link = $post->link;
			
							// These fields all provide a rendered version when the response is generated.
							$subset->title   = $post->title->rendered;
							$subset->content = $post->content->rendered;
							$subset->excerpt = $post->excerpt->rendered;
			
							//Event Data
							$subset->event_begin = $post->{'post-meta-fields'}->event_begin[0];
							$subset->event_end = $post->{'post-meta-fields'}->event_end[0];
							$subset->event_address = $post->{'post-meta-fields'}->geo_address[0];
			
							// If a featured image is assigned (int), the full data will be in the `_embedded` property.
							if ( ! empty( $post->featured_media ) && isset( $post->_embedded->{'wp:featuredmedia'} ) && 0 < count( $post->_embedded->{'wp:featuredmedia'} ) ) {
								$subset_feature = $post->_embedded->{'wp:featuredmedia'}[0]->media_details;
			
								if ( isset( $subset_feature->sizes->{'post-thumbnail'} ) ) {
									$subset->thumbnail = $subset_feature->sizes->{'post-thumbnail'}->source_url;
									$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
									$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
								} elseif ( isset( $subset_feature->sizes->{'thumbnail'} ) ) {
									$subset->thumbnail = $subset_feature->sizes->{'thumbnail'}->source_url;
									$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
									$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
								} else {
									$subset->thumbnail = $post->_embedded->{'wp:featuredmedia'}[0]->source_url;
									$subset->thumbalt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
									$subset->thumbcaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
								}
			
								// Add Medium Image
								if ( isset( $subset_feature->sizes->{'uams_news'} ) ) {
									$subset->image = $subset_feature->sizes->{'uams_news'}->source_url;
									$subset->imagealt = $post->_embedded->{'wp:featuredmedia'}[0]->alt_text;
									$subset->imagecaption = $post->_embedded->{'wp:featuredmedia'}[0]->caption->rendered;
								} else {
									$subset->image = false;
								}
							} else {
								$subset->thumbnail = false;
							}
			
							// If an author is available, it will be in the `_embedded` property.
							if ( isset( $post->_embedded ) && isset( $post->_embedded->author ) && 0 < count( $post->_embedded->author ) ) {
								$subset->author_name = $post->_embedded->author[0]->name;
							} else {
								$subset->author_name = '';
							}
			
							// We've always provided an empty value for terms. @todo Implement terms. :)
							$subset->terms = array();
			
						// } // End if().
			
						/**
						 * Filter the data stored for an individual result after defaults have been built.
						 *
						 * @since 0.7.10
						 *
						 * @param object $subset Data attached to this result.
						 * @param object $post   Data for an individual post retrieved via `wp-json/posts` from a remote host.
						 * @param array  $atts   Attributes originally passed to the `uamswp_news` shortcode.
						 */
						$subset = apply_filters( 'uams_content_syndication_host_data', $subset, $post, $atts );
			
						if ( $post->date ) {
							$subset_key = strtotime( $post->date );
						} else {
							$subset_key = time();
						}
			
						while ( array_key_exists( $subset_key, $new_data ) ) {
							$subset_key++;
						}
						$new_data[ $subset_key ] = $subset;
					} // End foreach().
				}

				// Store the built content in cache for repeated use.
				$this->set_content_cache( $atts, 'uamswp_events', $new_data );
			}
		}
		if ( ! is_array( $new_data ) ) {
			$new_data = array();
		}

		// Only provide a count to match the total count, the array may be larger if local
		// items are also requested.
		if ( $atts['count'] ) {
			$new_data = array_slice( $new_data, 0, $atts['count'], false );
		}

		$content = apply_filters( 'uamswp_content_syndicate_news_output', false, $new_data, $atts );
		if ( false === $content ) {
			$content = $this->generate_shortcode_output( $new_data, $atts );
		}
		$content = apply_filters( 'uamswp_content_syndicate_news', $content, $atts );

		//$content .= '<br/><script>var request_url=["'. urldecode($request_url) .'"];</script>'; // Dev Testing
		return $content;
	}
	/**
	 * Generates the content to display for a shortcode.
	 *
	 * @since 1.2.0
	 *
	 * @param array $new_data Data containing the events to be displayed.
	 * @param array $atts     Array of options passed with the shortcode.
	 *
	 * @return string Content to display for the shortcode.
	 */
	public function generate_shortcode_output( $new_data, $atts ) {
		ob_start();
		// By default, we output a JSON object that can then be used by a script.
		if ( 'json' === $atts['output'] ) {
            echo '<!-- UAMSWP Output JSON -->';
            // print_r ($new_data);
            echo '<script>var ' . esc_js( $atts['object'] ) . ' = ' . wp_json_encode( $new_data ) .';</script>' . '<script>'. $url_var .';</script>';
		} elseif ( 'headlines' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Headlines -->
			<div class="uamswp-content-syndication-wrapper">
				<ul class="uamswp-content-syndication-event-headline">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?><li class="uamswp-content-syndication-item"><a href="<?php echo esc_url( $content->link ); ?>"><?php echo esc_html( $content->title ); ?></a></li><?php
					}
					?>
				</ul>
			</div>
			<?php
		} elseif ( 'list' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Headlines -->
			<div class="uamswp-content-syndication-wrapper">
				<ul class="uamswp-content-syndication-event-list">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
						<li class="uamswp-content-syndication-item"><a href="<?php echo esc_url( $content->link ); ?>"><?php echo esc_html( $content->title ); ?></a>
						<?php echo (!is_null($content->event_begin) ? '<small>'. $this->delta_date($this->parsedate($content->event_begin), $this->parsedate($content->event_end)) .'<span class="event_location">'. esc_html($content->event_address) .'</small></span>' : '' ) ?>
						</li><?php
					}
					?>
				</ul>
			</div>
			<?php
		} elseif ( 'excerpts' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Excerpts -->
			<div class="uamswp-content-syndication-wrapper">
				<ul class="uamswp-content-syndication-event-excerpts">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
						<li class="uamswp-content-syndication-item" itemscope itemtype="http://schema.org/NewsArticle">
							<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
							<a class="content-item-thumbnail" href="<?php echo esc_url( $content->link ); ?>" itemprop="image" itemscope itemtype="https://schema.org/ImageObject"><?php if ( $content->thumbnail ) : ?><img src="<?php echo esc_url( $content->thumbnail ); ?>" alt="<?php echo esc_html( $content->thumbalt ); ?>" itemprop="url"><?php endif; ?></a>
							<span class="content-item-title" itemprop="headline"><a href="<?php echo esc_url( $content->link ); ?>" class="news-link" itemprop="url"><?php echo esc_html( $content->title ); ?></a></span>
							<?php echo (!is_null($content->event_begin) ? '<small>'. $this->delta_date($this->parsedate($content->event_begin), $this->parsedate($content->event_end)) .'<span class="event_location">'. esc_html($content->event_address) .'</small></span>' : '' ) ?>
							<span class="content-item-excerpt" itemprop="articleBody"><?php echo wp_kses_post( $content->excerpt ); ?></span>
							<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
								<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
								<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
									<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
								    <meta itemprop="width" content="297"/>
								    <meta itemprop="height" content="40"/>
								</span>
							</span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php
		} elseif ( 'cards' === $atts['output'] ) {
			?>
            <!-- UAMSWP Output Cards -->
			<div class="uamswp-content-syndication-wrapper">
				<div class="uamswp-content-syndication-event-cards">
					<?php
					$offset_x = 0;
					foreach ( $new_data as $content ) {
						if ( $offset_x < absint( $atts['offset'] ) ) {
							$offset_x++;
							continue;
						}
						?>
					    <div class="default-card" itemscope itemtype="http://schema.org/NewsArticle">
					    	<meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?php echo esc_url( $content->link ); ?>"/>
					    	<?php if ( $content->image ) : ?><div class="card-image" itemprop="image" itemscope itemtype="https://schema.org/ImageObject"><img src="<?php echo esc_url( $content->image ); ?>" alt="<?php echo esc_html( $content->imagecaption ); ?>" itemprop="url"></div><?php endif; ?>
							<div class="card-body">
					      		<span>
					      			<h3 itemprop="headline">
					                	<a href="<?php echo esc_url( $content->link ); ?>" class="pic-title"><?php echo esc_html( $content->title ); ?></a>
					              	</h3>
					              	<span itemprop="articleBody">
									  <?php echo (!is_null($content->event_begin) ? '<small>'. $this->delta_date($this->parsedate($content->event_begin), $this->parsedate($content->event_end)) .'<span class="event_location">'. esc_html($content->event_address) .'</small></span>' : '' ) ?>
					      			<?php echo wp_kses_post( $content->excerpt ); ?>
					      			</span>
					              	<a href="<?php echo esc_url( $content->link ); ?>" class="pic-text-more uams-btn btn-sm btn-red" itemprop="url">Read more</a>
					              	<span class="content-item-byline-author" itemprop="author" itemscope itemtype="http://schema.org/Person"><meta itemprop="name" content="<?php echo esc_html( $content->author_name ); ?>"/></span>
					              	<meta itemprop="datePublished" content="<?php echo esc_html( date( 'c', strtotime( $content->date ) ) ); ?>"/>
					              	<meta itemprop="dateModified" content="<?php echo esc_html( date( 'c', strtotime( $content->modified ) ) ); ?>"/>
					            </span>

							</div>
							<span itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
								<meta itemprop="name" content="University of Arkansas for Medical Sciences"/>
								<span itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
									<meta itemprop="url" content="http://web.uams.edu/wp-content/uploads/sites/51/2017/09/UAMS_Academic_40-1.png"/>
								    <meta itemprop="width" content="297"/>
								    <meta itemprop="height" content="40"/>
								</span>
							</span>
					    </div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		} // End if().
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	/**
     *
     * @param string $date
     * @param string $sep
     * @return string
     */
    public function parsedate($date, $sep = '') {
        if (!empty($date)) {
            return substr($date, 0, 10) . $sep . substr($date, 11, 8);
        } else {
            return '';
        }
    }

	/**
     *
     * @param mixed $date
     * @param string $format
     * @return type
     */
    public function human_date($date, $format = 'l j F Y') {
        // if($this->settings['dateforhumans']){
            if (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y')) {
                return __('Today', 'event-post');
            } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('+1 day'))) {
                return __('Tomorrow', 'event-post');
            } elseif (is_numeric($date) && date('d/m/Y', $date) == date('d/m/Y', strtotime('-1 day'))) {
                return __('Yesterday', 'event-post');
            }
        // }
        return date_i18n($format, $date);
    }

    /**
     *
     * @param timestamp $time_start
     * @param timestamp $time_end
     * @return string
     */
    public function delta_date($time_start, $time_end){
        if(!$time_start || !$time_end){
            return;
		}
		
		$time_start = strtotime($time_start);
		$time_end = strtotime($time_end);

        //Display dates
        $dates="\t\t\t\t".'<div class="event_date" data-start="' . $this->human_date($time_start) . '" data-end="' . $this->human_date($time_end) . '">';
        if (date('d/m/Y', $time_start) == date('d/m/Y', $time_end)) { // same day
            $dates.= "\n\t\t\t\t\t\t\t".'<time itemprop="dtstart" datetime="' . date_i18n('c', $time_start) . '">'
                    . '<span class="date date-single">' . $this->human_date($time_end, get_option('date_format')) . "</span>";
            if (date('H:i', $time_start) != date('H:i', $time_end) && date('H:i', $time_start) != '00:00' && date('H:i', $time_end) != '00:00') {
                $dates.='   <span class="linking_word linking_word-from">' . _x('from', 'Time', 'event-post') . '</span>
                            <span class="time time-start">' . date_i18n(get_option('time_format'), $time_start) . '</span>
                            <span class="linking_word linking_word-to">' . _x('to', 'Time', 'event-post') . '</span>
                            <span class="time time-end">' . date_i18n(get_option('time_format'), $time_end) . '</span>';
            }
            elseif (date('H:i', $time_start) != '00:00') {
                $dates.='   <span class="linking_word">' . _x('at', 'Time', 'event-post') . '</span>
                            <time class="time time-single" itemprop="dtstart" datetime="' . date_i18n('c', $time_start) . '">' . date_i18n(get_option('time_format'), $time_start) . '</time>';
            }
            $dates.="\n\t\t\t\t\t\t\t".'</time>';
        } else { // not same day
            $dates.= '
                <span class="linking_word linking_word-from">' . _x('from', 'Date', 'event-post') . '</span>
                <time class="date date-start" itemprop="dtstart" datetime="' . date('c', $time_start) . '">' . $this->human_date($time_start, get_option('date_format'));
            if (date('H:i:s', $time_start) != '00:00:00' || date('H:i:s', $time_end) != '00:00:00'){
              $dates.= ', ' . date_i18n(get_option('time_format'), $time_start);
            }
            $dates.='</time>
                <span class="linking_word linking_word-to">' . _x('to', 'Date', 'event-post') . '</span>
                <time class="date date-end" itemprop="dtend" datetime="' . date('c', $time_end) . '">' . $this->human_date($time_end, get_option('date_format'));
            if (date('H:i:s', $time_start) != '00:00:00' || date('H:i:s', $time_end) != '00:00:00') {
              $dates.=  ', ' . date_i18n(get_option('time_format'), $time_end);
            }
            $dates.='</time>';
        }
        $dates.="\n\t\t\t\t\t\t".'</div><!-- .event_date -->';
        return $dates;
    }
	
}
