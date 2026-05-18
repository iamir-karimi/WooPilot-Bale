<?php

namespace WooPilot\Bale\Auth;

defined( 'ABSPATH' ) || exit;

final class BaleConnectPage {

	private BaleConnect $bale_connect;

	private BaleUserRepository $repository;

	public function __construct() {
		$this->bale_connect = new BaleConnect();
		$this->repository   = new BaleUserRepository();
	}

	public function add_endpoint(): void {
		add_rewrite_endpoint( 'bale-connect', EP_ROOT | EP_PAGES );
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'bale-connect';

		return $vars;
	}

	public function add_menu_item( array $items ): array {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			$new_items[ $key ] = $label;

			if ( 'dashboard' === $key ) {
				$new_items['bale-connect'] = __( 'اتصال حساب بله', 'woopilot-bale' );
			}
		}

		if ( ! isset( $new_items['bale-connect'] ) ) {
			$new_items['bale-connect'] = __( 'اتصال حساب بله', 'woopilot-bale' );
		}

		return $new_items;
	}

	public function render_endpoint(): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'برای اتصال حساب بله، ابتدا وارد حساب کاربری شوید.', 'woopilot-bale' ) . '</p>';
			return;
		}

		$user_id = get_current_user_id();

		$this->handle_form_submit( $user_id );

		$phone         = (string) get_user_meta( $user_id, 'billing_phone', true );
		$bale_username = (string) get_user_meta( $user_id, 'woopilot_bale_username', true );

		$connection = $this->get_user_connection( $user_id, $phone, $bale_username );

		echo '<div class="woopilot-bale-connect-page">';

		echo '<h3>' . esc_html__( 'اتصال حساب بله', 'woopilot-bale' ) . '</h3>';

		if ( $connection && 'active' === $connection->status && ! empty( $connection->bale_chat_id ) ) {
			$this->render_connected_state( $connection );
		} else {
			$this->render_pending_or_form_state( $user_id, $phone, $bale_username, $connection );
		}

		echo '</div>';
	}

	private function handle_form_submit( int $user_id ): void {
		if ( empty( $_POST['woopilot_bale_connect_action'] ) ) {
			return;
		}

		if (
			! isset( $_POST['woopilot_bale_connect_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['woopilot_bale_connect_nonce'] ) ),
				'woopilot_bale_connect'
			)
		) {
			wc_print_notice(
				__( 'درخواست معتبر نیست. لطفاً دوباره تلاش کنید.', 'woopilot-bale' ),
				'error'
			);
			return;
		}

		$phone = isset( $_POST['woopilot_bale_phone'] )
			? sanitize_text_field( wp_unslash( $_POST['woopilot_bale_phone'] ) )
			: '';

		$username = isset( $_POST['woopilot_bale_username'] )
			? sanitize_text_field( wp_unslash( $_POST['woopilot_bale_username'] ) )
			: '';

		$phone    = $this->bale_connect->normalize_phone( $phone );
		$username = $this->bale_connect->normalize_username( $username );

		if ( empty( $phone ) ) {
			wc_print_notice(
				__( 'شماره موبایل معتبر نیست.', 'woopilot-bale' ),
				'error'
			);
			return;
		}

		update_user_meta( $user_id, 'billing_phone', $phone );
		update_user_meta( $user_id, 'woopilot_bale_phone', $phone );
		update_user_meta( $user_id, 'woopilot_bale_username', $username );

		$token = $this->bale_connect->create_connect_token(
			$phone,
			$username,
			$user_id
		);

		update_user_meta( $user_id, 'woopilot_bale_connect_token', $token );

		wc_print_notice(
			__( 'کد اتصال ساخته شد. لطفاً دستور نمایش‌داده‌شده را در بله برای ربات ارسال کنید.', 'woopilot-bale' ),
			'success'
		);
	}

	private function render_connected_state( object $connection ): void {
		?>
		<div class="woopilot-bale-status woopilot-bale-status-success">
			<strong><?php echo esc_html__( 'حساب بله شما متصل است.', 'woopilot-bale' ); ?></strong>
		</div>

		<ul>
			<?php if ( ! empty( $connection->phone ) ) : ?>
				<li>
					<strong><?php echo esc_html__( 'شماره موبایل:', 'woopilot-bale' ); ?></strong>
					<?php echo esc_html( $connection->phone ); ?>
				</li>
			<?php endif; ?>

			<?php if ( ! empty( $connection->bale_username ) ) : ?>
				<li>
					<strong><?php echo esc_html__( 'آیدی بله:', 'woopilot-bale' ); ?></strong>
					@<?php echo esc_html( $connection->bale_username ); ?>
				</li>
			<?php endif; ?>

			<li>
				<strong><?php echo esc_html__( 'وضعیت:', 'woopilot-bale' ); ?></strong>
				<?php echo esc_html__( 'فعال', 'woopilot-bale' ); ?>
			</li>
		</ul>
		<?php
	}

	private function render_pending_or_form_state(
		int $user_id,
		string $phone,
		string $bale_username,
		?object $connection
	): void {
		$token = '';

		if ( $connection && 'pending' === $connection->status && ! empty( $connection->connect_token ) ) {
			$token = (string) $connection->connect_token;
		}

		if ( empty( $token ) ) {
			$token = (string) get_user_meta( $user_id, 'woopilot_bale_connect_token', true );
		}

		?>
		<p>
			<?php echo esc_html__( 'برای دریافت کد ورود و اعلان‌های سفارش در بله، حساب بله خود را به فروشگاه متصل کنید.', 'woopilot-bale' ); ?>
		</p>

		<form method="post" class="woopilot-bale-connect-form">
			<?php wp_nonce_field( 'woopilot_bale_connect', 'woopilot_bale_connect_nonce' ); ?>

			<input type="hidden" name="woopilot_bale_connect_action" value="create_token">

			<p>
				<label for="woopilot_bale_phone">
					<?php echo esc_html__( 'شماره موبایل', 'woopilot-bale' ); ?>
				</label>
				<input
					type="text"
					id="woopilot_bale_phone"
					name="woopilot_bale_phone"
					value="<?php echo esc_attr( $phone ); ?>"
					placeholder="09123456789"
					required
				>
			</p>

			<p>
				<label for="woopilot_bale_username">
					<?php echo esc_html__( 'آیدی بله', 'woopilot-bale' ); ?>
				</label>
				<input
					type="text"
					id="woopilot_bale_username"
					name="woopilot_bale_username"
					value="<?php echo esc_attr( $bale_username ); ?>"
					placeholder="مثلاً: myusername"
				>
			</p>

			<p>
				<button type="submit" class="button button-primary">
					<?php echo esc_html__( 'ساخت کد اتصال بله', 'woopilot-bale' ); ?>
				</button>
			</p>
		</form>

		<?php if ( ! empty( $token ) ) : ?>
			<div class="woopilot-bale-connect-instruction">
				<h4><?php echo esc_html__( 'مرحله بعد', 'woopilot-bale' ); ?></h4>

				<p>
					<?php echo esc_html__( 'در پیام‌رسان بله، وارد ربات فروشگاه شوید و دستور زیر را ارسال کنید:', 'woopilot-bale' ); ?>
				</p>

				<code dir="ltr" style="display:block;padding:12px;background:#f6f7f7;border:1px solid #ddd;margin:10px 0;">
					/start connect_<?php echo esc_html( $token ); ?>
				</code>

				<p>
					<?php echo esc_html__( 'بعد از ارسال این دستور، اتصال حساب شما فعال می‌شود.', 'woopilot-bale' ); ?>
				</p>
			</div>
		<?php endif; ?>
		<?php
	}

	private function get_user_connection( int $user_id, string $phone, string $username ): ?object {
		if ( ! empty( $phone ) ) {
			$connection = $this->repository->find_by_phone( $phone );

			if ( $connection ) {
				return $connection;
			}
		}

		if ( ! empty( $username ) ) {
			$connection = $this->repository->find_by_username( $username );

			if ( $connection ) {
				return $connection;
			}
		}

		return null;
	}
}