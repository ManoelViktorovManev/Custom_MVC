<?php

namespace App\Core;

/**
 * Class YamlParser
 *
 * A simple YAML parser that converts YAML-formatted content into a PHP associative array.
 *
 * This class supports parsing YAML files with basic key-value pairs and nested structures.
 */
class YamlParser
{
    /**
     * Parses a YAML file and converts it into an associative array.
     *
     * @param string $file_path The path to the YAML file.
     * @return array The parsed YAML content as an associative array.
     * @throws \Exception If the file does not exist.
     */
    public static function parseFile($file_path)
    {
        if (!is_file($file_path)) {
            throw new \Exception(
                "Error 404: File config/routes.yaml does not exist"
            );
        }
        $content = file_get_contents($file_path);
        return self::parse($content);
    }

    /**
     * Parses YAML content from a string and converts it into an associative array.
     *
     * This function supports:
     * - Basic key-value pairs (`key: value`)
     * - Nested structures based on indentation
     *
     * @param string $content The YAML content as a string.
     * @return array The parsed data structure.
     */
    private static function parse($content)
    {
        $lines = explode("\n", $content);
        $data = [];
        $currentIndent = null;
        $stack = [];

        // Looping through every line
        foreach ($lines as $line) {

            $trimed = trim($line);
            // Skip empty lines and comments
            if ($trimed === '' || str_starts_with($trimed, '#')) {
                continue;
            }
            // count the indent 
            $indent = strlen($line) - strlen(ltrim($line));

            // regex that is looking for key:value
            if (preg_match('/^([\w-]+):\s*(.*)$/', $trimed, $matches)) {
                $key = $matches[1];
                $value =  $matches[2] !== '' ? $matches[2] : [];

                // if the indent is one tab to the right 
                /*
                 Example:

                 info:
                    path: /phpInfo
                */
                if ($indent > $currentIndent || !isset($currentIndent)) {
                    if (empty($stack)) {
                        $data[$key] = $value;
                        $stack[] = &$data[$key];
                    } else {
                        $last = &$stack[count($stack) - 1];
                        $last[$key] = $value;
                        $stack[] = &$last[$key];
                    }
                }
                // if the indent is one tab (or space) to the left 
                /*
                 Example:

                    info: "something"
                new_path: /phpInfo
                */ elseif ($indent < $currentIndent) {
                    // if we go to 0 level
                    if ($indent == 0) {
                        $data[$key] = $value;
                        $stack[] = &$data[$key];
                    } else {
                        $parent = &$stack[count($stack) - 1];
                        $parent[$key] = $value;
                        $stack[] = &$parent[$key];
                    }
                }
                // if the indent is not change
                /*
                 Example:
                 
                 info: "something"
                 path: /phpInfo
                */ else {
                    array_pop($stack);
                    if (!empty($stack)) {
                        $parent = &$stack[count($stack) - 1];
                        $parent[$key] = $value;
                        $stack[] = &$parent[$key];
                    } else {
                        $data[$key] = $value;
                        $stack[] = &$data[$key];
                    }
                }
                $currentIndent = $indent;
            }
        }
        return $data;
    }
};