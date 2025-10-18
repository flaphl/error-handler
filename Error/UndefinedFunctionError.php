<?php

/**
 * This file is part of the Flaphl package.
 *
 * (c) Jade Phyressi <jade@flaphl.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flaphl\Element\ErrorHandler\Error;

/**
 * Represents an "Undefined function" error.
 * 
 * This error occurs when PHP tries to call a function that hasn't been defined,
 * typically due to missing extensions or typos in function names.
 */
class UndefinedFunctionError extends FatalError
{
    private string $functionName;

    public function __construct(
        string $functionName,
        string $message = '',
        string $filename = '',
        int $line = 0
    ) {
        $this->functionName = $functionName;
        
        if (empty($message)) {
            $message = sprintf('Call to undefined function %s()', $functionName);
        }

        parent::__construct($message, 0, E_ERROR, $filename, $line);
    }

    /**
     * Get the name of the undefined function.
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * Get suggestions for resolving this error.
     */
    public function getSuggestions(): array
    {
        $suggestions = [
            'Check if the function name is spelled correctly',
            'Verify the function exists in the current scope',
            'Ensure the file containing the function is included',
        ];

        // Add extension-specific suggestions
        $extensionSuggestions = $this->getExtensionSuggestions();
        if (!empty($extensionSuggestions)) {
            $suggestions = array_merge($suggestions, $extensionSuggestions);
        }

        return $suggestions;
    }

    /**
     * Get extension-specific suggestions based on function name.
     */
    private function getExtensionSuggestions(): array
    {
        $functionPrefixes = [
            'curl_' => 'Install or enable the cURL extension',
            'json_' => 'Install or enable the JSON extension',
            'mb_' => 'Install or enable the Multibyte String extension',
            'gd_' => 'Install or enable the GD extension',
            'mysqli_' => 'Install or enable the MySQLi extension',
            'pdo_' => 'Install or enable the PDO extension',
            'xml_' => 'Install or enable the XML extension',
            'openssl_' => 'Install or enable the OpenSSL extension',
            'zip_' => 'Install or enable the Zip extension',
            'hash_' => 'Install or enable the Hash extension',
            'filter_' => 'Install or enable the Filter extension',
        ];

        foreach ($functionPrefixes as $prefix => $suggestion) {
            if (str_starts_with($this->functionName, $prefix)) {
                return [$suggestion];
            }
        }

        return [];
    }

    /**
     * Get enhanced error information.
     */
    public function getErrorInfo(): array
    {
        return array_merge(parent::getErrorInfo(), [
            'function_name' => $this->getFunctionName(),
            'suggestions' => $this->getSuggestions(),
        ]);
    }
}
