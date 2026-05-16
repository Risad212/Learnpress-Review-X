<?php

/**
 * Plugin Name: Course Review
 * Plugin URI:
 * Description: course review addon for learnpress.
 * Author: hmrisad
 * Version: 1.0.0
 * Author URI:
 * Tags: learnpress
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: course-review
 * Domain Path: /languages/
 * Require_LP_Version: 4.3.2.3
 * Requires Plugins: learnpress
 */

use LearnPress\CourseReview\TemplateHooks\TemplateHooks;
use LearnPress\CourseReview\CourseReviewShortCode;

if (!defined('ABSPATH')) exit;

final class Course_Review_Preload
{

    /**
	 * @var LP_Addon_Course_Review $addon
	 */
	public static $addon;

	public static $instance = null;

	/**
	 * Singleton instance
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('plugins_loaded', [$this, 'preload']);
	}

	/**
	 * Plugin preload check
	 */
	public function preload()
	{
		define('COURSE_REVIEW_FILE', __FILE__);
		define('COURSE_REVIEW_BASENAME', plugin_basename(__FILE__));

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$addon_info = get_file_data(
			__FILE__,
			[
				'Name'               => 'Plugin Name',
				'Require_LP_Version' => 'Require_LP_Version',
				'Version'            => 'Version',
			]
		);

		define('COURSE_REVIEW_VER', $addon_info['Version']);
		define('COURSE_REVIEW_REQUIRE_VER', $addon_info['Require_LP_Version']);

		// Check LearnPress active
		if (!is_plugin_active('learnpress/learnpress.php')) {

			add_action('admin_notices', function () use ($addon_info) {
				echo '<div class="notice notice-error"><p>';
				echo 'Please activate <strong>LearnPress version ' . COURSE_REVIEW_REQUIRE_VER . ' or later</strong> before activating <strong>' . $addon_info['Name'] . '</strong>';
				echo '</p></div>';
			});

			deactivate_plugins(COURSE_REVIEW_BASENAME);

			if (isset($_GET['activate'])) {
				unset($_GET['activate']);
			}

			return;
		}

		// Load plugin after LearnPress ready
		add_action('learn-press/ready', [$this, 'init_plugin']);
	}

	/**
	 * Initialize addon
	 */
	public function init_plugin()
	{ 
        require __DIR__ . '/vendor/autoload.php';
		include_once 'inc/plugin.php';

		self::$addon = Course_Review_Addon::instance();

		TemplateHooks::instance();
		CourseReviewShortCode::instance();
	}
}

/**
 * Start plugin
 */
Course_Review_Preload::instance();
