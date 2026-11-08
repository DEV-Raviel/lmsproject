<?php

function student_profile_sections(): array
{
    return [
        'academic' => [
            'title' => 'Academic information',
            'fields' => [
                'classification' => ['label' => 'Classification', 'type' => 'text', 'max' => 100, 'aliases' => ['classification', 'student classification']],
                'program_course' => ['label' => 'Program / Course', 'type' => 'text', 'max' => 200, 'aliases' => ['program course', 'program / course', 'course']],
                'major' => ['label' => 'Major', 'type' => 'text', 'max' => 150, 'aliases' => ['major']],
                'year_level' => ['label' => 'Year level', 'type' => 'text', 'max' => 50, 'aliases' => ['year level', 'year']],
            ],
        ],
        'previous_school' => [
            'title' => 'Previous school attended',
            'fields' => [
                'previous_school_name' => ['label' => 'School name', 'type' => 'text', 'max' => 200, 'aliases' => ['previous school attended school name', 'school name', 'previous school']],
                'previous_school_address' => ['label' => 'School address', 'type' => 'textarea', 'max' => 2000, 'aliases' => ['school address', 'previous school address']],
                'previous_program_name' => ['label' => 'Program name / strand', 'type' => 'text', 'max' => 200, 'aliases' => ['program name strand', 'program name / strand', 'strand']],
                'gwa' => ['label' => 'General weighted average (GWA)', 'type' => 'number', 'max' => 5, 'aliases' => ['general weighted average gwa', 'general weighted average', 'gwa']],
            ],
        ],
        'personal' => [
            'title' => 'Personal details',
            'fields' => [
                'first_name' => ['label' => 'First name', 'type' => 'text', 'max' => 100, 'aliases' => ['first name']],
                'middle_name' => ['label' => 'Middle name', 'type' => 'text', 'max' => 100, 'aliases' => ['middle name']],
                'last_name' => ['label' => 'Last name', 'type' => 'text', 'max' => 100, 'aliases' => ['last name']],
                'suffix' => ['label' => 'Suffix', 'type' => 'text', 'max' => 30, 'aliases' => ['suffix']],
                'gender' => ['label' => 'Gender', 'type' => 'text', 'max' => 50, 'aliases' => ['gender']],
                'civil_status' => ['label' => 'Civil status', 'type' => 'text', 'max' => 50, 'aliases' => ['civil status']],
                'religion' => ['label' => 'Religion', 'type' => 'text', 'max' => 100, 'aliases' => ['religion']],
                'date_of_birth' => ['label' => 'Date of birth', 'type' => 'date', 'aliases' => ['date of birth', 'birth date']],
                'place_of_birth' => ['label' => 'Place of birth', 'type' => 'text', 'max' => 200, 'aliases' => ['place of birth', 'birthplace']],
                'barangay' => ['label' => 'Barangay', 'type' => 'text', 'max' => 150, 'aliases' => ['barangay']],
                'home_address' => ['label' => 'Home address', 'type' => 'textarea', 'max' => 2000, 'aliases' => ['home address', 'complete home address']],
            ],
        ],
        'contact' => [
            'title' => 'Contact details',
            'fields' => [
                'recovery_email' => ['label' => 'Recovery email', 'type' => 'email', 'max' => 254, 'aliases' => ['recovery email', 'personal email']],
                'ccc_email' => ['label' => 'CCC email', 'type' => 'email', 'max' => 254, 'aliases' => ['ccc email', 'school email', 'institutional email']],
                'contact_number' => ['label' => 'Contact number', 'type' => 'text', 'max' => 50, 'aliases' => ['contact number', 'student contact number', 'mobile number']],
            ],
        ],
        'socio_demographic' => [
            'title' => 'Student socio-demographic profile',
            'fields' => [
                'working_student' => ['label' => 'Working student', 'type' => 'choice', 'aliases' => ['are you a working student', 'working student']],
                'household_4ps' => ['label' => 'Household member of 4Ps', 'type' => 'choice', 'aliases' => ['is your household a member of 4ps', 'household member of 4ps', '4ps']],
                'is_pwd' => ['label' => 'Person with disability (PWD)', 'type' => 'choice', 'aliases' => ['are you a person with disability pwd', 'person with disability pwd', 'pwd']],
                'is_solo_parent' => ['label' => 'Solo parent', 'type' => 'choice', 'aliases' => ['are you a solo parent', 'solo parent']],
                'is_indigenous_people' => ['label' => 'Member of Indigenous Peoples (IP)', 'type' => 'choice', 'aliases' => ['member of indigenous peoples ip', 'indigenous peoples', 'indigenous people', 'ip member']],
            ],
        ],
        'family' => [
            'title' => 'Family background & emergency contact',
            'fields' => [
                'father_name' => ['label' => "Father's name", 'type' => 'text', 'max' => 200, 'aliases' => ["father's name", 'father name']],
                'father_contact_number' => ['label' => "Father's contact number", 'type' => 'text', 'max' => 50, 'aliases' => ["father's contact no", "father's contact number", 'father contact number']],
                'father_occupation' => ['label' => "Father's occupation", 'type' => 'text', 'max' => 150, 'aliases' => ["father's occupation", 'father occupation']],
                'mother_name' => ['label' => "Mother's name", 'type' => 'text', 'max' => 200, 'aliases' => ["mother's name", 'mother name']],
                'mother_contact_number' => ['label' => "Mother's contact number", 'type' => 'text', 'max' => 50, 'aliases' => ["mother's contact no", "mother's contact number", 'mother contact number']],
                'mother_occupation' => ['label' => "Mother's occupation", 'type' => 'text', 'max' => 150, 'aliases' => ["mother's occupation", 'mother occupation']],
                'guardian_name' => ['label' => 'Guardian / emergency contact name', 'type' => 'text', 'max' => 200, 'aliases' => ['guardian emergency contact name', 'guardian name', 'emergency contact name']],
                'guardian_contact_number' => ['label' => 'Guardian contact number', 'type' => 'text', 'max' => 50, 'aliases' => ['guardian contact number', 'emergency contact number']],
                'guardian_address' => ['label' => 'Guardian address', 'type' => 'textarea', 'max' => 2000, 'aliases' => ['guardian address', 'emergency contact address']],
            ],
        ],
    ];
}

function student_profile_fields(): array
{
    $fields = [];
    foreach (student_profile_sections() as $section) {
        $fields += $section['fields'];
    }
    return $fields;
}

function student_profile_csv_header_key(string $header): ?string
{
    $normalizedHeader = strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($header)) ?? '');
    if ($normalizedHeader === '') {
        return null;
    }

    $identifiers = [
        'student_id' => ['student id', 'student number', 'user code', 'student code'],
        'student_email' => ['student email', 'email address', 'email'],
    ];
    foreach ($identifiers as $key => $aliases) {
        foreach ($aliases as $alias) {
            $normalizedAlias = strtolower(preg_replace('/[^a-z0-9]+/i', '', $alias) ?? '');
            if ($normalizedHeader === $normalizedAlias) {
                return $key;
            }
        }
    }

    foreach (student_profile_fields() as $key => $field) {
        foreach ($field['aliases'] as $alias) {
            $normalizedAlias = strtolower(preg_replace('/[^a-z0-9]+/i', '', $alias) ?? '');
            if ($normalizedHeader === $normalizedAlias) {
                return $key;
            }
        }
    }

    foreach (student_profile_fields() as $key => $field) {
        foreach ($field['aliases'] as $alias) {
            $normalizedAlias = strtolower(preg_replace('/[^a-z0-9]+/i', '', $alias) ?? '');
            if (strlen($normalizedAlias) >= 8 && str_contains($normalizedHeader, $normalizedAlias)) {
                return $key;
            }
        }
    }

    return null;
}

function student_profile_normalize_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    foreach (['!Y-m-d', '!n/j/Y', '!m/d/Y', '!n/j/y', '!M j, Y'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }
    return null;
}

function student_profile_validate_values(array $input, array $keys): array
{
    $fields = student_profile_fields();
    $values = [];
    $errors = [];

    foreach ($keys as $key) {
        if (!isset($fields[$key])) {
            continue;
        }
        $field = $fields[$key];
        $rawValue = $input[$key] ?? '';
        if (!is_scalar($rawValue)) {
            $errors[] = $field['label'] . ' must be plain text.';
            continue;
        }
        $value = trim((string) $rawValue);
        if ($value === '') {
            $values[$key] = null;
            continue;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if (isset($field['max']) && $length > $field['max']) {
            $errors[] = $field['label'] . ' is too long.';
            continue;
        }
        if ($field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid ' . strtolower($field['label']) . '.';
            continue;
        }
        if ($field['type'] === 'date') {
            $normalizedDate = student_profile_normalize_date($value);
            if ($normalizedDate === null) {
                $errors[] = 'Enter a valid date for ' . strtolower($field['label']) . '.';
                continue;
            }
            if ($normalizedDate > date('Y-m-d')) {
                $errors[] = 'Date of birth cannot be in the future.';
                continue;
            }
            $value = $normalizedDate;
        }
        if ($field['type'] === 'number') {
            if (!preg_match('/\A[1-5](?:\.\d{1,2})?\z/', $value)
                || (float) $value < 1
                || (float) $value > 5) {
                $errors[] = 'GWA must be a number from 1.00 to 5.00.';
                continue;
            }
            $value = number_format((float) $value, 2, '.', '');
        }
        if ($field['type'] === 'choice') {
            $normalizedChoice = strtolower($value);
            if (in_array($normalizedChoice, ['yes', 'y', 'true', '1'], true)) {
                $value = 'Yes';
            } elseif (in_array($normalizedChoice, ['no', 'n', 'false', '0'], true)) {
                $value = 'No';
            } else {
                $errors[] = $field['label'] . ' must be Yes or No.';
                continue;
            }
        }

        $values[$key] = $value;
    }

    return [$values, $errors];
}
