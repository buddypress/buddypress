<?php

/**
 * Mock email delivery implementation.
 *
 * @since 2.5.0
 */
class BP_UnitTest_Mailer implements BP_Email_Delivery {

	/**
	 * Send email(s).
	 *
	 * @since 2.5.0
	 *
	 * @param BP_Email $email Email to send.
	 * @return bool
	 */
	public function bp_email( BP_Email $email ) {
		return true;
	}
}
