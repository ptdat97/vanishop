<?php

return [
    'cannot_change_self' => 'You cannot lock yourself or change your own roles.',
    'last_owner' => 'At least one active Owner must remain.',
    'exceeds_own_permissions' => 'You cannot grant permissions you do not have.',
    'role_in_use' => 'This role is assigned to staff and cannot be deleted.',
    'system_role' => 'System roles cannot be edited or deleted.',
    'invited' => 'Invitation sent to :email.',
    'invite_resent' => 'Invitation resent.',
    'saved' => 'Staff member saved.',
    'locked' => 'Staff member locked.',
    'unlocked' => 'Staff member unlocked.',
    'role_saved' => 'Role saved.',
    'role_deleted' => 'Role deleted.',
    'reset_link_sent' => 'If the email belongs to an active staff member, a password reset link has been sent.',
    'reset_throttled' => 'Too many requests. Please try again in :seconds seconds.',
    'link_invalid' => 'This link is invalid or has expired. Please request a new one.',
    'password_set' => 'Password set. Please sign in.',
    'reset_subject' => 'Reset your admin password',
    'reset_body' => "Hi :name,\n\nA password reset was requested for your admin account. Open this link within :minutes minutes:\n:url\n\nIf you did not request it, ignore this email — your password stays the same.",
    'invite_subject' => ':store admin invitation',
    'invite_body' => "Hi :name,\n\nYou have been invited to manage :store. Set your password to get started (link valid for :hours hours):\n:url",
];
