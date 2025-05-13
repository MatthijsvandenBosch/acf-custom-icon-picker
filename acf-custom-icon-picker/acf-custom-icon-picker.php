<?php
/*
Plugin Name: ACF Custom Icon Picker Field
Description: Voegt een ACF-veldtype toe waarmee je uit een dynamische set van iconen kiest, met upload-optie.
Version: 1.8
Author: Matthijs van den Bosch
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ACF_ICON_PICKER_URL', plugin_dir_url( __FILE__ ) );

// 0) Admin assets
add_action( 'admin_enqueue_scripts', 'acf_icon_picker_admin_assets' );
function acf_icon_picker_admin_assets( $hook_suffix ) {
	if ( strpos( $hook_suffix, 'acf-icon-picker-settings' ) !== false ) {
		wp_enqueue_media();
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script(
			'acf-icon-picker-admin',
			ACF_ICON_PICKER_URL . 'assets/js/admin-icon-picker.js',
			array(), // pure vanilla JS
			'1.0',
			true
		);
		wp_localize_script(
			'acf-icon-picker-admin',
			'ACFIconPicker',
			array(
				'prefix' => 'mb-icon-',
			)
		);
	}
}

// 1) Field assets (voor in de ACF-editor)
add_action( 'acf/input/admin_enqueue_scripts', 'acf_icon_picker_field_assets' );
function acf_icon_picker_field_assets() {
	wp_enqueue_style(
		'acf-icon-picker-field',
		ACF_ICON_PICKER_URL . 'assets/css/field-icon-picker.css',  // aangepast
		array(),
		'1.0'
	);
	wp_enqueue_script(
		'acf-icon-picker-field',
		ACF_ICON_PICKER_URL . 'assets/js/field-icon-picker.js',
		array(), // pure vanilla JS
		'1.0',
		true
	);
	wp_localize_script(
		'acf-icon-picker-field',
		'ACFIconPicker',
		array(
			'selector' => '.acf-custom-icon-picker .icon-option',
		)
	);
}

// 2) Register het ACF-veld
add_action( 'acf/include_field_types', 'register_acf_custom_icon_picker' );
function register_acf_custom_icon_picker( $version ) {
	new ACF_Field_Custom_Icon_Picker();
}

// 3) Admin submenu + Settings API
add_action( 'admin_menu', 'acf_icon_picker_admin_menu' );
function acf_icon_picker_admin_menu() {
	add_submenu_page(
		'themes.php',
		__( 'Icon Library', 'acf' ),
		__( 'Icon Library', 'acf' ),
		'manage_options',
		'acf-icon-picker-settings',
		'acf_icon_picker_settings_page'
	);
}
add_action( 'admin_init', 'acf_icon_picker_settings_init' );
function acf_icon_picker_settings_init() {
	register_setting( 'acf_icon_picker', 'acf_icon_picker_icons', 'acf_icon_picker_sanitize' );
}

function acf_icon_picker_sanitize( $input ) {
	$output = array();
	if ( ! empty( $input['label'] ) && is_array( $input['label'] ) ) {
		foreach ( $input['label'] as $i => $lbl_raw ) {
			$label = sanitize_text_field( $lbl_raw );
			$url   = esc_url_raw( $input['url'][ $i ] ?? '' );

			// Alleen opslaan als er wél een label én een URL is
			if ( empty( $label ) || empty( $url ) ) {
				add_settings_error(
					'acf_icon_picker',
					'missing_url_' . $i,
					__( 'Elke icon moet zowel een Label als een Afbeelding hebben.', 'acf' ),
					'error'
				);
				continue;
			}

			// Unieke slug
			static $used_slugs = array();
			$base              = sanitize_title( $label );
			$slug              = $base;
			$n                 = 2;
			while ( in_array( $slug, $used_slugs, true ) ) {
				$slug = "{$base}-" . $n++;
			}
			$used_slugs[] = $slug;

			$output[] = array(
				'class' => 'mb-icon-' . $slug,
				'label' => $label,
				'url'   => $url,
			);
		}
	}
	return $output;
}

function acf_icon_picker_settings_page() {
	$icons = get_option( 'acf_icon_picker_icons', array() );
	?>
<div class="wrap">
	<h1><?php _e( 'Icon Library', 'acf' ); ?></h1>
	<form method="post" action="options.php">
		<?php
			// nonce & optie‐groep aanmaken
			settings_fields( 'acf_icon_picker' );

			// toon WP melding (“Settings saved”) & eventuele fouten uit sanitize
			settings_errors( 'acf_icon_picker' );

			// haal evt. vers geüpdatete icons opnieuw op
			$icons = get_option( 'acf_icon_picker_icons', array() );
		?>
		<table id="icon-picker-table" class="widefat fixed">
			<thead>
				<tr>
					<th>Label</th>
					<th>CSS Class</th>
					<th>Icon Upload</th>
					<th>Acties</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $icons as $icon ) : ?>
				<tr>
					<td>
						<input type="text" class="icon-label" name="acf_icon_picker_icons[label][]" value="<?php echo esc_attr( $icon['label'] ); ?>" />
					</td>
					<td>
						<input type="text" class="icon-class" value="<?php echo esc_attr( $icon['class'] ); ?>" disabled />
					</td>
					<td>
						<input type="hidden" class="icon-url" name="acf_icon_picker_icons[url][]" value="<?php echo esc_url( $icon['url'] ); ?>" />
						<img src="<?php echo esc_url( $icon['url'] ); ?>" style="max-width:32px;" />
						<button class="button upload-icon">Upload/Select</button>
					</td>
					<td>
						<button class="button remove-row">Verwijder</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><button id="add-icon" class="button">+ Icon toevoegen</button></p>
		<?php submit_button(); ?>
	</form>
</div>
	<?php
}

// 4) De ACF Field Class
class ACF_Field_Custom_Icon_Picker extends acf_field {
	function __construct() {
		$this->name     = 'custom_icon_picker';
		$this->label    = __( 'Custom Icon Picker', 'acf' );
		$this->category = 'choice';
		parent::__construct();
	}

	function render_field( $field ) {
		static $items = null;
		if ( $items === null ) {
			$items = get_option( 'acf_icon_picker_icons', array() );
		}
		if (empty( $items )) return;

		// huidige waarde (altijd een slug)
		$current = $field['value'];

		echo '<div class="acf-custom-icon-picker">';
		foreach ( $items as $icon ) {
			$slug = $icon['class'];                 // altijd de unieke class/slug
			$sel  = ( $current === $slug ) ? ' selected' : '';
			echo '<div class="icon-option' . $sel . '" data-value="' . esc_attr( $slug ) . '" title="' . esc_attr( $icon['label'] ) . '">';
				// preview: óf plaatje óf font-icon
			if ( ! empty( $icon['url'] ) ) {
				echo '<img src="' . esc_url( $icon['url'] ) . '" style="width:24px;height:auto;"><br>';
			} else {
				echo '<i class="' . esc_attr( $slug ) . '"></i><br>';
			}
			echo '</div>';
		}
		// hidden: sla enkel de slug op
		echo '<input type="hidden" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $current ) . '" />';
		echo '</div>';
	}

	function format_value( $value, $post_id, $field ) {
		// Als we een array hebben met de volledige data, return die
		if (is_array($value) && isset($value['class'])) {
			return $value;
		}

		// Anders probeer de data op te halen
		if (!empty($value)) {
			$icons = get_option('acf_icon_picker_icons', array());
			foreach ($icons as $icon) {
				if ($icon['class'] === $value) {
					return array(
						'class' => $icon['class'],
						'label' => $icon['label'],
						'url'   => $icon['url']
					);
				}
			}
		}

		return $value;
	}
}
