<?php

function getMainMenuKeyboard($userId) {
    global $allowedUsers;
    $lang = getLang($userId);

    if (in_array($userId, $allowedUsers)) {
        return [
            'inline_keyboard' => [
                [
                    ['text' => $lang['manage_admins'], 'callback_data' => 'manage_admins']
                ],
                [
                    ['text' => $lang['account_info'], 'callback_data' => 'account_info']
                ],
                [
                    ['text' => $lang['settings'], 'callback_data' => 'settings'],
                    ['text' => $lang['status'], 'callback_data' => 'show_status']
                ]
            ]
        ];
    } else {
        return [
            'inline_keyboard' => [
                [
                    ['text' => $lang['manage_admins'], 'callback_data' => 'manage_admins']
                ],
                [
                    ['text' => $lang['account_info'], 'callback_data' => 'account_info']
                ]
            ]
        ];
    }
}

function getbacktoadminselectbutton($userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [ 
            [
                ['text' => $lang['back'], 'callback_data' => 'manage_admins']
            ]
        ]
        ];
}

function getAdminKeyboard($userId, $adminId, $status) {
    global $allowedUsers; 
    
    if (in_array($userId, $allowedUsers)) {
        return getAdminManagementKeyboard($adminId, $status, $userId); 
    } else {
        return getLimitedAdminManagementKeyboard($adminId, $status, $userId); 
    }
}

function getAdminManagementKeyboard($adminId, $status, $userId) {

    $lang = getLang($userId);

    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['calculate_volume'], 'callback_data' => 'calculate_volume:' . $adminId]            
            ],
            [
                ['text' => $lang['admin_specifications_settings'], 'callback_data' => 'show_display_only_admin']
            ],
            [
                ['text' => $lang['set_traffic_button'], 'callback_data' => 'set_traffic:' . $adminId],
                ['text' => $lang['set_expiry_button'], 'callback_data' => 'set_expiry:' . $adminId]
            ],
            [
                ['text' => $lang['setuserlimitbutton'], 'callback_data' => 'set_user_limit:' . $adminId],
                ['text' => $lang['securityButton'], 'callback_data' => 'security:' . $adminId]
            ],
            [
                ['text' => $lang['admin_limitations_settings'], 'callback_data' => 'show_display_only_limit']
            ],
            [
                ['text' => $lang['limit_inbounds_button'], 'callback_data' => 'limit_inbounds:' . $adminId],
                [
                    'text' => ($status === 'active') ? $lang['disable_users_button'] : $lang['enable_users_button'],
                    'callback_data' => ($status === 'active') ? 'disable_users:' . $adminId : 'enable_users:' . $adminId
                ]
            ],
            [
                ['text' => $lang['GoToLimitsButton'], 'callback_data' => 'show_restrictions:' . $adminId],
                ['text' => $lang['protocolsettingsbutton'], 'callback_data' => 'protocol_settings:' . $adminId]
            ],
            [
                ['text' => $lang['admin_users_settings'], 'callback_data' => 'show_display_only_users']
            ],
            [
                ['text' => $lang['add_time_button'], 'callback_data' => 'add_time:' . $adminId],
                ['text' => $lang['subtract_time_button'], 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $lang['adddatalimitbutton'], 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $lang['subtractdata_button'], 'callback_data' => 'subtract_data_limit:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'manage_admins'],
                ['text' => $lang['refresh_button'], 'callback_data' => 'select_admin:' . $adminId]
            ]
        ]
    ];
}

function getLimitedAdminManagementKeyboard($adminId, $status, $userId) {
    $lang = getLang($userId);
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['add_time_button'], 'callback_data' => 'add_time:' . $adminId],
                ['text' => $lang['subtract_time_button'], 'callback_data' => 'reduce_time:' . $adminId]
            ],
            [
                ['text' => $lang['adddatalimitbutton'], 'callback_data' => 'add_data_limit:' . $adminId],
                ['text' => $lang['subtractdata_button'], 'callback_data' => 'subtract_data_limit:' . $adminId]
            ],
        [
            ['text' => $lang['back'], 'callback_data' => 'manage_admins'],
            ['text' => $lang['refresh_button'], 'callback_data' => 'select_admin:' . $adminId]
        ]
        ]
    ];
    
}

function getprotocolsttingskeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['add_protocol_button'], 'callback_data' => 'add_protocol:' . $adminId],
                ['text' => $lang['remove_protocol_button'], 'callback_data' => 'remove_protocol:' . $adminId]
            ],
            [
                ['text' => $lang['enable_inbounds_button'], 'callback_data' => 'enable_inbounds:' . $adminId],
                ['text' => $lang['disable_inbounds_button'], 'callback_data' => 'disable_inbounds:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getSettingsMenuKeyboard($userId) {
    $lang = getLang($userId);

    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['update_bot'], 'callback_data' => 'update_bot'],
                ['text' => $lang['save_admin_traffic'], 'callback_data' => 'save_admin_traffic']
            ],
            [
                ['text' => $lang['update_marzban'], 'callback_data' => 'update_marzban'],
                ['text' => $lang['restart_marzban'], 'callback_data' => 'restart_marzban']
            ],
            [
                ['text' => $lang['backup'], 'callback_data' => 'backup'],
                ['text' => $lang['change_template'], 'callback_data' => 'change_template']
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_main']
            ]
        ]
    ];
}

function getSecurityKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['changePasswordButton'], 'callback_data' => 'change_password:' . $adminId],
                ['text' => $lang['changeSudoButton'], 'callback_data' => 'change_sudo:' . $adminId]
            ],
            [
                ['text' => $lang['changeTelegramIdButton'], 'callback_data' => 'change_telegram_id:' . $adminId],
                ['text' => $lang['changeUsernameButton'], 'callback_data' => 'change_username:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
}

function getSudoConfirmationKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['confirm_yes_button'], 'callback_data' => 'confirm_sudo_yes:' . $adminId],
                ['text' => $lang['confirm_no_button'], 'callback_data' => 'confirm_sudo_no:' . $adminId]
            ]
        ]
    ];
    
}

function getConfirmationKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['confirm_yes_button'], 'callback_data' => 'confirm_disable_yes:' . $adminId],
                ['text' => $lang['confirm_no_button'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getBackToAdminManagementKeyboard($adminId, $userId) {
    $lang = getLang($userId);
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]
    ];
    
}

function getBackToMainKeyboard($userId) {
    $lang = getLang($userId);
    
    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_main']
            ]
        ]];
}

function getProtocolSelectionKeyboard($adminId, $action, $userId) {
    $lang = getLang($userId);

    return [
        'inline_keyboard' => [
            [
                ['text' => $lang['protocol_vmess'], 'callback_data' => $action . ':vmess:' . $adminId],
                ['text' => $lang['protocol_vless'], 'callback_data' => $action . ':vless:' . $adminId]
            ],
            [
                ['text' => $lang['protocol_trojan'], 'callback_data' => $action . ':trojan:' . $adminId],
                ['text' => $lang['protocol_shadowsocks'], 'callback_data' => $action . ':shadowsocks:' . $adminId]
            ],
            [
                ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
            ]
        ]];
    }

function getRestrictionsKeyboard($adminId, $preventUserDeletion, $preventUserCreation, $preventUserReset, $preventRevokeSubscription, $preventUnlimitedTraffic, $userId) {
    
        $lang = getLang($userId);
    
            $preventUserDeletionStatus = $preventUserDeletion ? $lang['active_status'] : $lang['inactive_status'];
            $preventUserCreationStatus = $preventUserCreation ? $lang['active_status'] : $lang['inactive_status'];
            $preventUserResetStatus = $preventUserReset ? $lang['active_status'] : $lang['inactive_status'];
            $preventRevokeSubscriptionStatus = $preventRevokeSubscription ? $lang['active_status'] : $lang['inactive_status'];
            $preventUnlimitedTrafficStatus = $preventUnlimitedTraffic ? $lang['active_status'] : $lang['inactive_status'];
        
            $preventUserDeletionButtonText = $lang['preventUserDeletionButton'] . ' ' . $preventUserDeletionStatus;
            $preventUserCreationButtonText = $lang['preventUserCreationButton'] . ' ' . $preventUserCreationStatus;
            $preventUserResetButtonText = $lang['preventUserResetButton'] . ' ' . $preventUserResetStatus;
            $preventRevokeSubscriptionButtonText = $lang['preventRevokeSubscriptionButton'] . ' ' . $preventRevokeSubscriptionStatus;
            $preventUnlimitedTrafficButtonText = $lang['preventUnlimitedTrafficButton'] . ' ' . $preventUnlimitedTrafficStatus;
        
            return [
                'inline_keyboard' => [
                    [
                        ['text' => $preventUserDeletionButtonText, 'callback_data' => 'toggle_prevent_user_deletion:' . $adminId],
                        ['text' => $preventUserCreationButtonText, 'callback_data' => 'toggle_prevent_user_creation:' . $adminId]
                    ],
                    [
                        ['text' => $preventUserResetButtonText, 'callback_data' => 'toggle_prevent_user_reset:' . $adminId],
                        ['text' => $preventRevokeSubscriptionButtonText, 'callback_data' => 'toggle_prevent_revoke_subscription:' . $adminId]
                    ],
                    [
                        ['text' => $preventUnlimitedTrafficButtonText, 'callback_data' => 'toggle_prevent_unlimited_traffic:' . $adminId]
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'back_to_admin_management:' . $adminId]
                    ]
                ]
            ];
        }

function getTemplateMenuKeyboard($currentIndex, $templateCount, $userId) {

            $lang = getLang($userId); 
        
            $buttons = [
                [
                    ['text' => $lang['prev'], 'callback_data' => 'template_prev'],
                    ['text' => $lang['next'], 'callback_data' => 'template_next']
                ],
                [
                    ['text' => $lang['apply_template'], 'callback_data' => 'apply_template']
                ],
                [
                    ['text' => $lang['back_to_settings'], 'callback_data' => 'back_to_settings']
                ]
            ];
        
            return [
                'inline_keyboard' => $buttons
            ];
        }
function getAdminExpireKeyboard($adminId, $userId) {
            global $botConn; 
        
            $lang = getLang($userId); 
        
            $stmt = $botConn->prepare("SELECT status, hashed_password_before FROM admin_settings WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
        
            $currentStatus = $row && $row['status'] ? json_decode($row['status'], true) : ['time' => 'active', 'data' => 'active', 'users' => 'active'];
            $usersButtonText = ($currentStatus['users'] === 'active') ? $lang['disable_users_button'] : $lang['enable_users_button'];
        
            $hashedPasswordBefore = $row['hashed_password_before'] ?? null;
            $passwordButtonText = ($hashedPasswordBefore) ? $lang['restore_password'] : $lang['change_password_temp'];
        
            return [
                'inline_keyboard' => [
                    [
                        ['text' => $usersButtonText, 'callback_data' => ($currentStatus['users'] === 'active') ? "disable_users_{$adminId}" : "enable_users_{$adminId}"],
                        ['text' => $passwordButtonText, 'callback_data' => ($hashedPasswordBefore) ? "restore_password_{$adminId}" : "change_password_{$adminId}"]
                    ]
                ]
            ];
        }

function getCalculateVolumeKeyboard($adminId, $userId) {
            global $botConn;
            $lang = getLang($userId);
        
            $stmt = $botConn->prepare("SELECT calculate_volume FROM admin_settings WHERE admin_id = ?");
            $stmt->bind_param("i", $adminId);
            $stmt->execute();
            $result = $stmt->get_result();
            $calculateVolume = $result->fetch_assoc()['calculate_volume'] ?? 'used_traffic';
            $stmt->close();
        
            $usedTrafficText = $lang['used_traffic_button'];
            $createdTrafficText = $lang['created_traffic_button'];
        
            if ($calculateVolume === 'used_traffic') {
                $usedTrafficText .= ' ✅';
            } else {
                $createdTrafficText .= ' ✅';
            }
        
            return [
                'inline_keyboard' => [
                    [
                        ['text' => $usedTrafficText, 'callback_data' => 'set_calculate_volume:used_traffic:' . $adminId]
                    ],
                    [
                        ['text' => $createdTrafficText, 'callback_data' => 'set_calculate_volume:created_traffic:' . $adminId]
                    ],
                    [
                        ['text' => $lang['back'], 'callback_data' => 'select_admin:' . $adminId]
                    ]
                ]
            ];
        }
function getstatuskeyboard($lang) {

    return [
        'inline_keyboard' => [
                [
                    ['text' => $lang['refresh_button'], 'callback_data' => 'show_status']
                ],
                [
                    ['text' => $lang['restart_xray'], 'callback_data' => 'restart_xray'],
                    ['text' => $lang['restart_marzban'], 'callback_data' => 'marzban_restart'],
                    ['text' => $lang['update_marzban'], 'callback_data' => 'marzban_update']
                ],
                [
                    ['text' => $lang['back'], 'callback_data' => 'back_to_main']
                ]
            ]
        ];
    
}