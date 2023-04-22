<?php

declare( strict_types=1 );

namespace AgentFire\Plugin\Test;

use AgentFire\Plugin\Test\Traits\Singleton;


/**
 * Class Rest
 * @package AgentFire\Plugin\Test
 */
class Admin {
	use Singleton;

	public $slug = 'test-settings';
	public $key = 'test_settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'acf/init', [ $this, 'init' ] );
	}

	/**
	 * Add settings page
	 */
	public function menu() {
		if ( function_exists( 'acf_add_options_page' ) ) {
			// acf_add_options_page( [ ... ] );
		} else {
			$slug = add_menu_page( 'AgentFire Test', 'AgentFire Test', 'manage_options', 'test-settings', [ $this, 'renderPage' ] );
			add_action( "load-{$slug}", [ $this, 'adminLoad' ] );
		}
	}

	public function renderPage() {
		$fields = acf_get_fields( $this->key );

        $index = 0;
        foreach( $fields as $field ) {
            if( $field[ 'key' ] === 'test_mapbox_token' ) {
                $field[ 'value' ] = get_field( 'test_mapbox_token', 'acf-group_' .  $this->slug );
                $index = $field[ 'ID' ];
            }
        }

        $fields[ $index ][ 'value' ] = get_field("test_mapbox_token", "acf-group_" .  $this->slug);
		$field_group = acf_get_field_group( $this->key );

        $options = [
			'id'         => 'acf-group_' . $this->key,
			'key'        => $field_group['key'],
			'style'      => $field_group['style'],
			'label'      => $field_group['label_placement'],
			'visibility' => true,
		];
		?>
		<div class="wrap acf-settings-wrap">
			<h1>AgentFire Test</h1>
			<form id="post" method="post" name="post" >
				<?php
				acf_form_data( [
					'post_id' => 'acf-group_' . $this->key,
					'screen'   => 'options',
				] );
				?>  

				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables" class="meta-box-sortables ui-sortable">
								<div id="submitdiv" class="postbox ">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
									<h2 class="hndle ui-sortable-handle"><span>Save Settings</span></h2>
									<div class="inside">
										<div id="major-publishing-actions">
											<div id="publishing-action">
												<span class="spinner"></span>
												<input type="submit" accesskey="p" value="Save Settings" class="button button-primary button-large" id="publish" name="publish">
											</div>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div id="postbox-container-2" class="postbox-container">
							<div id="normal-sortables" class="meta-box-sortables ui-sortable">
								<div id="<?=$options['id']?>" class="postbox  acf-postbox">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button>
									<h2 class="hndle ui-sortable-handle"><span>Settings</span></h2>
									<div class="inside acf-fields -top">
										<?php
											acf_render_fields(  $fields );										
										?>
										
										<script type="text/javascript">
											if( typeof acf !== 'undefined' ) {
												var postboxOptions = <?php echo json_encode( $options ); ?>;
                                                console.log("postboxoptions are", postboxOptions);
												if ( typeof acf.newPostbox === 'function' ) {
                                                    console.log("new post box is", acf.newPostbox(postboxOptions));
												} else if ( typeof acf.postbox.render === 'function' ) {
													acf.postbox.render(postboxOptions);
												}
											}
										</script>
									</div>
								</div>
							</div>
						</div>
						<br class="clear">
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	public function adminLoad() {
        
		if ( acf_verify_nonce('options' ) ) {
			if ( acf_validate_save_post( true ) ) {
				acf_save_post( 'acf-group_' . $this->slug );
				wp_redirect( add_query_arg( [ 'message' => '1' ] ) );
				exit;
			}
		}
		acf_enqueue_scripts();
		wp_enqueue_script( 'post' );
	}

	/**
	 * Set settings page fields
	 */
	public function init() {
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group( [
				'key'                   => $this->key,
				'title'                 => 'Test Settings',
				'fields'                => [
                    [
                        'key'               => 'test_mapbox_token',
                        'label'             => 'MapBox Token',
                        'name'              => 'mapbox_token',
                        'type'              => 'text'
                    ],
				],
				'location'              => [
					[
						[
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => $this->slug,
						],
					],
				],
				'menu_order'            => 10,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => '',
				'active'                => 1,
				'description'           => '',
			] );
		}
	}

}