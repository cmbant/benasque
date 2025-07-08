<?php

/**
 * ArXiv API utility class for fetching paper metadata
 */
class ArxivAPI
{
    private const API_BASE_URL = 'http://export.arxiv.org/api/query';
    private const REQUEST_DELAY = 350000; // 350ms delay between requests (about 3 requests per second)

    /**
     * Extract arXiv ID from various URL formats
     *
     * @param string $url The arXiv URL
     * @return string|null The extracted arXiv ID or null if not found
     */
    public static function extractArxivId($url)
    {
        // Remove whitespace and normalize URL
        $url = trim($url);

        // Common arXiv URL patterns
        $patterns = [
            // https://arxiv.org/abs/2412.13894
            '/arxiv\.org\/abs\/([0-9]{4}\.[0-9]{4,5}(?:v[0-9]+)?)/i',
            // https://arxiv.org/pdf/2412.13894.pdf
            '/arxiv\.org\/pdf\/([0-9]{4}\.[0-9]{4,5}(?:v[0-9]+)?)(?:\.pdf)?/i',
            // Old format: https://arxiv.org/abs/hep-th/9901001
            '/arxiv\.org\/abs\/([a-z-]+\/[0-9]{7}(?:v[0-9]+)?)/i',
            // Old format PDF: https://arxiv.org/pdf/hep-th/9901001.pdf
            '/arxiv\.org\/pdf\/([a-z-]+\/[0-9]{7}(?:v[0-9]+)?)(?:\.pdf)?/i',
            // arXiv: prefix format: arXiv:2502.18098
            '/^arXiv:([0-9]{4}\.[0-9]{4,5}(?:v[0-9]+)?)$/i',
            // arXiv: prefix old format: arXiv:hep-th/9901001
            '/^arXiv:([a-z-]+\/[0-9]{7}(?:v[0-9]+)?)$/i',
            // Direct ID format: 2412.13894
            '/^([0-9]{4}\.[0-9]{4,5}(?:v[0-9]+)?)$/i',
            // Old direct format: hep-th/9901001
            '/^([a-z-]+\/[0-9]{7}(?:v[0-9]+)?)$/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Fetch paper metadata from arXiv API
     *
     * @param string $arxivId The arXiv ID
     * @return array|null Array with title, authors, etc. or null on failure
     */
    public static function fetchPaperMetadata($arxivId)
    {
        if (empty($arxivId)) {
            return null;
        }

        try {
            // Build API URL
            $apiUrl = self::API_BASE_URL . '?id_list=' . urlencode($arxivId);

            // Add delay to respect rate limiting
            usleep(self::REQUEST_DELAY);

            // Fetch data using cURL for better error handling
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Benasque Conference Website/1.0');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error || $httpCode !== 200 || !$response) {
                error_log("ArXiv API error for ID $arxivId: HTTP $httpCode, $error");
                return null;
            }

            // Parse XML response
            return self::parseArxivXML($response);
        } catch (Exception $e) {
            error_log("ArXiv API exception for ID $arxivId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse arXiv API XML response
     *
     * @param string $xmlContent The XML response from arXiv API
     * @return array|null Parsed metadata or null on failure
     */
    private static function parseArxivXML($xmlContent)
    {
        try {
            // Suppress XML parsing warnings
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlContent);
            if (!$xml) {
                error_log("Failed to parse arXiv XML response");
                return null;
            }

            // Check if we have entries
            if (!isset($xml->entry) || count($xml->entry) === 0) {
                error_log("No entries found in arXiv API response");
                return null;
            }

            $entry = $xml->entry[0]; // Get first entry

            // Extract title (remove extra whitespace and newlines)
            $title = isset($entry->title) ? trim(preg_replace('/\s+/', ' ', (string)$entry->title)) : null;

            // Extract authors
            $authors = [];
            if (isset($entry->author)) {
                foreach ($entry->author as $author) {
                    if (isset($author->name)) {
                        $authors[] = (string)$author->name;
                    }
                }
            }

            // Extract other metadata
            $published = isset($entry->published) ? (string)$entry->published : null;
            $summary = isset($entry->summary) ? trim(preg_replace('/\s+/', ' ', (string)$entry->summary)) : null;

            return [
                'title' => $title,
                'authors' => $authors,
                'published' => $published,
                'summary' => $summary
            ];
        } catch (Exception $e) {
            error_log("Error parsing arXiv XML: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process arXiv links and fetch titles
     *
     * @param array $urls Array of arXiv URLs
     * @return array Array of objects with 'url' and 'title' fields
     */
    public static function processArxivLinks($urls)
    {
        $result = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) {
                continue;
            }

            $linkData = [
                'url' => $url,
                'title' => null
            ];

            // Try to extract arXiv ID and fetch metadata
            $arxivId = self::extractArxivId($url);
            if ($arxivId) {
                $metadata = self::fetchPaperMetadata($arxivId);
                if ($metadata && !empty($metadata['title'])) {
                    $linkData['title'] = $metadata['title'];
                }
            }

            $result[] = $linkData;
        }

        return $result;
    }

    /**
     * Check if a URL appears to be an arXiv link
     *
     * @param string $url The URL to check
     * @return bool True if it looks like an arXiv URL
     */
    public static function isArxivUrl($url)
    {
        return self::extractArxivId($url) !== null;
    }
}
