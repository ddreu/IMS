<?php

function generateSecurePassword($school_code, $role) {
    // Clean and lowercase the school code
    $school_code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $school_code));
    
    // Get role prefix
    $role_prefix = '';
    switch($role) {
        case 'School Admin':
            $role_prefix = 'sa';
            break;
        case 'Department Admin':
            $role_prefix = 'da';
            break;
        case 'Committee':
            $role_prefix = 'cm';
            break;
        default:
            $role_prefix = 'usr';
    }

    // Generate random components
    $numbers = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    $special_chars = ['@', '#', '$', '%', '&', '*'];
    $special_char = $special_chars[array_rand($special_chars)];
    
    // Get current year last two digits
    $year = date('y');
    
    // Combine components with consistent format:
    // [school_code][role_prefix][year][numbers][special_char]
    $password = $school_code . $role_prefix . $year . $numbers . $special_char;
    
    // Ensure at least one uppercase letter by capitalizing a random letter
    $random_position = random_int(0, strlen($password) - 2); // -2 to avoid special char
    $password = substr_replace(
        $password,
        strtoupper(substr($password, $random_position, 1)),
        $random_position,
        1
    );
    
    return $password;
}

?>
