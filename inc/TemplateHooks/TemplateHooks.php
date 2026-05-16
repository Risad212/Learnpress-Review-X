<?php

namespace LearnPress\CourseReview\TemplateHooks;

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserCourseModel;
use Course_Review_Preload;

class TemplateHooks
{
	use Singleton;
	public function init()
	{
		require_once COURSE_REVIEW_PATH . '/inc/plugin.php';

		add_filter(
			'learn-press/single-course/modern/section-instructor',
			[$this, 'add_review_section'],
			8,
			3
		);

		add_action(
			'learn-press/course-review/rating-reviews',
			[$this, 'render_review_section'],
			10,
			2
		);
	}

	/* ================= Inject Section ================= */
	public function add_review_section(array $section, CourseModel $courseModel, $userModel)
	{

	   if ( ! Course_Review_Preload::$addon->is_enable( $courseModel ) ) {
			return $section;
		}

		ob_start();
		  do_action('learn-press/course-review/rating-reviews', $courseModel, $userModel);
		$html = ob_get_clean();

		return apply_filters(
			'learn-press/course/rating-reviews/position',
			Template::insert_value_to_position_array(
				$section,
				'after',
				'wrapper_end',
				'review',
				$html
			),
			$html,
			$section,
			$courseModel,
			$userModel
		);
	}

	/* ================= Render Section ================= */
	public function render_review_section($courseModel, $userModel)
	{
		$reviews = learn_press_get_course_review($courseModel->get_id());

		echo '<h3 class="item-title">' . esc_html__('Reviews', 'course-review') . '</h3>';

		echo $this->course_rate($courseModel);
		echo $this->html_list_reviews($reviews);
		echo $this->html_btn_review($courseModel, $userModel);
	}

	/* ================= Review List ================= */
	public function html_list_reviews($reviews)
	{
		if (empty($reviews)) {
			return '';
		}

		$html = '<ul class="course-review-list">';

		foreach ($reviews as $review) {

			$rating = get_comment_meta($review->comment_ID, '_lpr_rating', true);

			$html .= '<li class="course-review-item">';

			$html .= '<div class="course-review-author">';
			$html .= get_avatar($review->user_id ?? $review->comment_author_email, 96);
			$html .= '</div>';

			$html .= '<div class="course-review-content">';

			$html .= '<div class="course-review-info">';
			$html .= '<div class="course-review-author-rated">';

			$stars = '<div class="course-review-stars" style="display:flex; gap:2px;">';

			for ($i = 1; $i <= 5; $i++) {
				$color = ($i <= $rating) ? '#f59e0b' : '#fff';

				$stars .= '
				<svg width="20" height="20" viewBox="0 0 24 24"
					fill="' . esc_attr($color) . '"
					stroke="#f59e0b"
					stroke-width="1.5"
					xmlns="http://www.w3.org/2000/svg">
					<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
				</svg>';
			}

			$stars .= '</div>';

			$html .= $stars;
			$html .= '</div>';

			$html .= '<div class="course-review-date">';
			$html .= esc_html(get_date_from_gmt($review->comment_date_gmt, 'F j, Y'));
			$html .= '</div>';

			$html .= '</div>';

			$html .= '<h4 class="course-review-user-name">';
			$html .= get_userdata($review->user_id)->display_name;
			$html .= '</h4>';

			$html .= '<h5 class="course-review-title">';
			$html .= esc_html(get_comment_meta($review->comment_ID, '_lpr_review_title', true));
			$html .= '</h5>';

			$html .= '<div class="course-review-text">';
			$html .= esc_html($review->comment_content);
			$html .= '</div>';

			$html .= '</div>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/* ================= Rating UI ================= */
	public function course_rate($courseModel): string
	{
		$course_id     = $courseModel->get_id();
		$courseRatings = count_rating_of_course($course_id);

		$averageStars  = round($this->average_calculation_rating($courseRatings), 1);
		$total_reviews = $courseRatings->total;

		$html = '<div class="course-rate">';

		/* ================= SUMMARY ================= */

		$html .= '<div class="course-rate__summary">';

		// Average rating number.
		$html .= '<div class="course-rate__summary-value">' . $averageStars . '</div>';

		// Stars.
		$html .= '<div class="course-rate__summary-stars">';
		$html .= '<div class="review-stars-rated">';

		for ($i = 1; $i <= 5; $i++) {

			$html .= ($i <= floor($averageStars))
				? '<div class="review-star">★</div>'
				: '<div class="review-star">☆</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		// Total reviews text.
		$html .= '<div class="course-rate__summary-text">';
		$html .= '<span>' . $total_reviews . '</span> ';
		$html .= esc_html__('rating', 'course-review');
		$html .= '</div>';

		$html .= '</div>';


		/* ================= DETAILS ================= */

		$html .= '<div class="course-rate__details">';

		$ratings = [
			5 => $courseRatings->five,
			4 => $courseRatings->four,
			3 => $courseRatings->three,
			2 => $courseRatings->two,
			1 => $courseRatings->one,
		];

		foreach ($ratings as $star => $count) {

			// Prevent divide by zero.
			$percent = $total_reviews
				? ($count / $total_reviews) * 100
				: 0;

			$html .= '<div class="course-rate__details-row">';

			// Star number.
			$html .= '<span class="course-rate__details-row-star">' . $star . '</span>';

			// Star icon.
			$html .= '<span class="course-rate__details-row-icon">★</span>';

			// Progress bar.
			$html .= '<div class="course-rate__details-row-value">';

			$html .= '<div class="rating-gray"></div>';

			$html .= '<div class="rating" style="width:' . $percent . '%;"></div>';

			$html .= '</div>';

			// Rating count.
            $html .= '<span class="rating-count">' . ($count ? $count : 0) . '</span>';

			$html .= '</div>';
		}

		$html .= '</div>';

		$html .= '</div>';

		return $html;
		}

	/* ================= Average Calculation ================= */
	public function average_calculation_rating($courseRatings)
	{
		if (empty($courseRatings) || empty($courseRatings->total)) {
			return 0;
		}

		$total = (int) $courseRatings->total;

		$sum =
			(5 * (int) $courseRatings->five) +
			(4 * (int) $courseRatings->four) +
			(3 * (int) $courseRatings->three) +
			(2 * (int) $courseRatings->two) +
			(1 * (int) $courseRatings->one);

		return round($sum / $total, 1);
	}

	/* ================= Review Button ================= */
	public function html_btn_review(CourseModel $courseModel, $userModel)
	{
		if (! $userModel) {
			return '';
		}

		$user_id   = $userModel->get_id();
		$course_id = $courseModel->get_id();

		$userCourse = UserCourseModel::find($user_id, $course_id, true);

		if (! $userCourse) {
			return '';
		}

		$can_review =
			($userCourse->has_enrolled_or_finished() ||
				($courseModel->is_offline() && $userCourse->has_purchased()))
			&& ! learn_press_get_user_rate($course_id, $user_id);

		if (! $can_review) {
			return '';
		}

		ob_start(); ?>

		<div class="write-review">
			<button type="button" class="review-button lp-button">
				<?php echo esc_html__('Write Review', 'course-review'); ?>
			</button>
		</div>

		<section class="course-review-wrapper">
			<div class="review-form">
				<form>

					<input type="hidden" name="rating" value="0">

					<h4>
						<?php echo esc_html__('Write a review', 'course-review'); ?>
						<a href="#" class="close" aria-label="<?php esc_attr_e('Close', 'course-review'); ?>">×</a>
					</h4>

					<ul class="review-fields">

						<li>
							<label><?php echo esc_html__('Title *', 'course-review'); ?></label>
							<input type="text" name="review_title" required />
						</li>

						<li>
							<label><?php echo esc_html__('Content *', 'course-review'); ?></label>
							<textarea name="review_content" required></textarea>
						</li>

						<li>
							<label><?php echo esc_html__('Rating *', 'course-review'); ?></label>
							<ul class="review-stars">
								<?php for ($i = 1; $i <= 5; $i++) { ?>
									<li data-star="<?php echo $i; ?>">★</li>
								<?php } ?>
							</ul>
						</li>

						<li class="review-actions">
							<button type="submit" class="lp-button submit-review"
								data-id="<?php echo esc_attr($course_id); ?>">
								<?php echo esc_html__('Submit Review', 'course-review'); ?>
							</button>
						</li>

					</ul>

					<?php wp_nonce_field('lp_review_nonce', 'lp_review_nonce_field'); ?>

				</form>
			</div>
		</section>

    <?php return ob_get_clean();
	}
}
