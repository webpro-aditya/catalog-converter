<?php

if (!function_exists('dd')) {
    /**
     * Dump and Die
     *
     * @param mixed $data
     * @return void
     */
    function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        exit;
    }
}


if (!function_exists('loadEnv')) {
    /**
     * Loads environment variables from a .env file into the system
     * * @param string $path Path to the .env file
     * @return void
     */
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: " . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments starting with #
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Split by the first '=' found
            list($name, $value) = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            // Remove optional quotes around values
            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    /**
     * Helper to retrieve environment variables
     * * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        // Handle special boolean strings
        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'empty':
                return '';
            case 'null':
                return null;
        }

        return $value;
    }
}

if (!function_exists('normalize')) {
    function normalize($str)
    {
        return strtolower(preg_replace('/\s+/', '.', trim($str)));
    }
}

if (!function_exists('safeWriteToFile')) {
    /**
     * Safely writes data to a file. 
     * Creates directories if missing and prevents data corruption via locking.
     */
    function safeWriteToFile($filePath, $data, $append = false)
    {
        // 1. Extract directory path and create it if it doesn't exist
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            // 0755 is standard permissions, 'true' allows nested folders
            if (!mkdir($directory, 0755, true)) {
                return "Error: Failed to create directories at $directory";
            }
        }

        // 2. Check if the directory is actually writable
        if (!is_writable($directory)) {
            return "Error: Directory is not writable. Check permissions.";
        }

        // 3. Set flags: LOCK_EX prevents multiple processes writing at once
        $flags = LOCK_EX;
        if ($append) {
            $flags |= FILE_APPEND;
        }

        // 4. Attempt the write
        $result = file_put_contents($filePath, $data, $flags);

        return ($result !== false) ? true : "Error: Unknown write failure.";
    }
}


if (!function_exists('countAttributes')) {
    /**
     * Count the number of attributes in Shopify CSV Sheet
     *
     * @param array $array
     * @return array
     */
    function countAttributes(array $data)
    {
        $optionCount = 0;

        foreach ($data as $key => $value) {
            // Matches "Option1 Name", "Option2 Name", etc.
            if (preg_match('/^Option\d+ Name$/', $key)) {
                // Only count it if the value is not empty
                if (!empty(trim($value))) {
                    $optionCount++;
                }
            }
        }
    }
}


if (!function_exists('countSessionKeysByPrefix')) {
    function countSessionKeysByPrefix(string $prefix): int
    {
        $count = 0;

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            return 0;
        }

        foreach ($_SESSION as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $count++;
            }
        }

        return $count;
    }
}


if (!function_exists('isGhostVariant')) {
    function isGhostVariant(array $data): bool
    {
        $hasOption = false;

        for ($i = 1; $i <= 10; $i++) {
            $val = trim($data["Option{$i} Value"] ?? '');
            if ($val !== '' && strtolower($val) !== 'default title') {
                $hasOption = true;
                break;
            }
        }

        return (
            !$hasOption &&
            empty(trim($data['Variant SKU'] ?? '')) &&
            empty(trim($data['Variant Price'] ?? ''))
        );
    }
}



if (!function_exists('renderTemplate')) {
    /**
     * Renders a PHP template with data and returns the output as a string.
     *
     * @param string $templatePath Path to the template file (relative to root)
     * @param array $data Associative array of data to extract into the template
     * @return string
     */
    function renderTemplate(string $templatePath, array $data = []): string
    {
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: {$templatePath}");
        }

        extract($data);

        ob_start();

        include $templatePath;

        return ob_get_clean();
    }
}