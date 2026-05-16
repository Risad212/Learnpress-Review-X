<?php


namespace LearnPress\CourseReview;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Helpers\Template;
use WP_Widget;
use Throwable;

defined('ABSPATH') || exit;


/**
 * Widget Class
 */

class CourseReviewWidget extends  WP_Widget
{
	use Singleton;

	public function __construct()
	{
		parent::__construct(
			'lpr_course_review',
			__('Learnpress - Course Review', 'course-review')
		);
	}

	/**
	 * abstract method defind in Singleton abstract class
	 */
	public function init() {}

	/**
	 * Front-end display
	 */

	public function widget($args, $instance)
	{

		try {
			$course_id = $instance['course_id'] ?? 0;
			if (empty($instance['course_id'])) {
				echo __('Please enter Course ID.', 'course-review');
				return;
			}

			echo $args['before_widget'];

			if (! empty($instance['title'])) {
				printf('<div>%s</div>', $instance['title']);
			}

			$courseModel = CourseModel::find($course_id, true);
			$userModel   = UserModel::find(get_current_user_id(), true);
			if (! $courseModel) {
				Template::print_message(
					__('Course is invalid', 'course-review'),
					'warning'
				);
			} else {
				do_action('learn-press/course-review/rating-reviews', $courseModel, $userModel);
			}

			echo $args['after_widget'];
		} catch (Throwable $e) {
			Template::print_message(
				$e->getMessage(),
				'error'
			);
		}
	}


	/**
	 * Widget Form
	 */
	public function form($instance)
	{
		$title     = $instance['title'] ?? __('Course Review', 'course-review');
		$course_id = $instance['course_id'] ?? '';
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">
				<?php _e('Title:', 'course-review'); ?>
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				name="<?php echo $this->get_field_name('title'); ?>" type="text"
				value="<?php echo esc_attr($title); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('course_id'); ?>">
				<?php _e('Course ID:', 'course-review'); ?>
			</label>
			<input style="width: 100%" type="number" value="<?php echo esc_attr($course_id); ?>"
				id="<?php echo $this->get_field_id('course_id'); ?>"
				name="<?php echo $this->get_field_name('course_id'); ?>">
		</p>
<?php
	}

	/**
	 * Sanitize widget form values as before save.
	 */
	public function update($new_instance, $old_instance)
	{
		$instance              = array();
		$instance['title']     = (! empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
		$instance['course_id'] = (! empty($new_instance['course_id'])) ? strip_tags($new_instance['course_id']) : '';

		return $instance;
	}
}
