<?php

return [
    'delete_otp_body' => 'Your account deletion code is :code (valid for 5 minutes). If you did not ask to delete your account, ignore this email and change your password.',
    'delete_otp_subject' => 'VaniShop account deletion code',
    'unauthenticated' => 'Please sign in.',
    'otp_invalid' => 'The verification code is incorrect or has expired.',
    'otp_rate_limited' => 'Too many code requests. Please try again later.',
    'otp_unavailable' => 'We cannot send a verification code to this phone number yet.',
    'otp_subject' => 'VaniShop verification code',
    'otp_body' => 'Your verification code is :code (valid for 5 minutes). Do not share it with anyone.',
    'invalid_credentials' => 'Incorrect email or password.',
    'email_taken' => 'This email is used by another account.',
    'phone_invalid' => 'Invalid phone number.',
    'address_invalid' => 'Invalid province or ward. Please choose again from the current administrative list.',
    'address_limit' => 'The address book holds at most :max addresses.',
    'not_found' => 'Customer not found.',
    'password_mismatch' => 'Current password is incorrect.',
    'merged' => 'Customers merged.',
    'anonymized' => 'Customer anonymized.',
    'social_unknown' => 'This sign-in method is not available.',
    'social_state' => 'The sign-in session has expired or is invalid. Please try again.',
    'social_redirect' => 'This return address is not allowed.',
    'social_failed' => 'Could not sign in with this account. Please try again or use your email.',
    'social_email_required' => 'This account has no verified email. Please sign up with your email.',
    'registration_link_invalid' => 'The verification link is invalid or has expired. Please sign up again.',
    'email_link_invalid' => 'The email verification link is invalid or has expired. Open your profile to send a new link.',
    'email_verification_sent' => 'We sent a verification link to :email. Please check your inbox.',
    'email_verified' => 'Your email has been verified.',
    'email_already_verified' => 'Your email is already verified, or your account has no email.',
    'password_reset_invalid' => 'The password reset link is invalid or has expired. Please request a new one.',
    'password_required' => 'Your account has no password yet. Please set a password first.',
    'too_many_attempts' => 'Too many attempts. Please try again later.',
    'verify_email_subject' => 'Verify your email to finish signing up',
    'verify_email_body' => 'Hi :name,

Open this link to finish creating your account (valid for :hours hours):
:url

If you did not sign up, please ignore this email.',
    'verify_email_change_subject' => 'Verify your account email',
    'verify_email_change_body' => 'Hi :name,

Open this link to verify this email for your account (valid for :hours hours):
:url

If you did not change your email, please ignore this email.',
    'reset_password_subject' => 'Reset your password',
    'reset_password_body' => 'Hi :name,

Open this link to set a new password (valid for :minutes minutes):
:url

If you did not ask for this, please ignore this email — your current password stays the same.',
    'deletion_blocked' => 'The account cannot be deleted yet because of pending items: :items. Please wait until they are completed or contact the store.',
    'note_added' => 'Note added.',
    'group_in_use' => 'This group still has customers — move them to another group before deleting it.',
    'group_not_found' => 'Customer group not found.',
    'too_many_tags' => 'A customer can have at most :max tags.',
    'segment_saved' => 'Customer group and tags saved.',
    'group_saved' => 'Customer group saved.',
    'group_deleted' => 'Customer group deleted.',
];
