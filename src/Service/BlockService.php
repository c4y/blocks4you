<?php

namespace C4Y\Blocks4you\Service;

use Doctrine\DBAL\Connection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
class BlockService
{
    private string $blocksPath;
    private Connection $connection;

    public function __construct(string $projectDir, string $blocksPath, Connection $connection)
    {
        $this->connection = $connection;

        // Construct absolute path
        $this->blocksPath = rtrim($projectDir, '/') . '/' . ltrim($blocksPath, '/');
    }

    /**
     * Get all available blocks
     */
    public function getAvailableBlocks(): array
    {
        $blocks = [];
        
        if (!is_dir($this->blocksPath)) {
            return $blocks;
        }

        $finder = new Finder();
        $finder->files()->in($this->blocksPath)->name('*.yaml')->name('*.yml');

        foreach ($finder as $file) {
            try {
                $config = Yaml::parseFile($file->getRealPath());
                $blocks[] = [
                    'id' => $file->getBasename('.' . $file->getExtension()),
                    'name' => $config['name'] ?? $file->getBasename('.' . $file->getExtension()),
                    'description' => $config['description'] ?? '',
                    'preview' => $config['preview'] ?? null,
                    'blocks' => $config['blocks'] ?? []
                ];
            } catch (\Exception $e) {
                // Skip invalid YAML files
                continue;
            }
        }

        // Sort alphabetically by name
        usort($blocks, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $blocks;
    }

    /**
     * Get preview for a specific block
     */
    public function getBlockPreview(string $blockId): array
    {
        $filePath = $this->blocksPath . '/' . $blockId . '.yaml';
        
        if (!file_exists($filePath)) {
            $filePath = $this->blocksPath . '/' . $blockId . '.yml';
        }

        if (!file_exists($filePath)) {
            throw new \Exception('Block nicht gefunden');
        }

        $config = Yaml::parseFile($filePath);
        
        return [
            'name' => $config['name'] ?? $blockId,
            'description' => $config['description'] ?? '',
            'preview' => $config['preview'] ?? null
        ];
    }

    /**
     * Get available insert positions for an article
     */
    public function getInsertPositions(int $articleId): array
    {
        $existingElements = $this->connection->fetchAllAssociative(
            "SELECT id, type, headline, text FROM tl_content WHERE pid = ? ORDER BY sorting",
            [$articleId]
        );

        $positions = [
            ['value' => 0, 'label' => 'Am Anfang einfügen', 'type' => 'start']
        ];

        foreach ($existingElements as $index => $element) {
            $label = $this->getElementLabel($element);
            $positions[] = [
                'value' => $index + 1,
                'label' => 'Nach: ' . $label,
                'type' => 'after',
                'elementId' => $element['id']
            ];
        }

        return $positions;
    }

    /**
     * Get a readable label for a content element
     */
    private function getElementLabel(array $element): string
    {
        $type = $element['type'] ?? 'unbekannt';
        
        if (!empty($element['headline'])) {
            return $element['headline'] . ' (' . $type . ')';
        }
        
        if (!empty($element['text'])) {
            $text = strip_tags($element['text']);
            $text = substr($text, 0, 50);
            if (strlen($element['text']) > 50) {
                $text .= '...';
            }
            return $text . ' (' . $type . ')';
        }
        
        return ucfirst($type) . '-Element';
    }

    /**
     * Insert block at a specific sorting position
     */
    public function insertBlockAtPosition(int $articleId, string $blockId, int $sorting): bool
    {
        $filePath = $this->blocksPath . '/' . $blockId . '.yaml';
        
        if (!file_exists($filePath)) {
            $filePath = $this->blocksPath . '/' . $blockId . '.yml';
        }

        if (!file_exists($filePath)) {
            throw new \Exception('Block nicht gefunden');
        }

        $config = Yaml::parseFile($filePath);
        $elements = $config['elements'] ?? [];

        if (empty($elements)) {
            throw new \Exception('Block enthält keine Elemente');
        }

        // Get current elements to calculate proper sorting
        $existingElements = $this->connection->fetchAllAssociative(
            "SELECT id, sorting FROM tl_content WHERE pid = ? ORDER BY sorting",
            [$articleId]
        );

        // Find position based on sorting value
        $position = 0;
        foreach ($existingElements as $index => $element) {
            if ($element['sorting'] >= $sorting) {
                $position = $index;
                break;
            }
            $position = $index + 1;
        }

        $sortingValues = $this->calculateSortingForBlock($existingElements, $position, count($elements));

        // Insert elements with calculated sorting values
        foreach ($elements as $index => $element) {
            $this->insertContentElement($articleId, $element, $sortingValues[$index]);
        }

        return true;
    }

    /**
     * Insert block into article
     */
    public function insertBlock(int $articleId, string $blockId, int $position = 0): bool
    {
        $filePath = $this->blocksPath . '/' . $blockId . '.yaml';
        
        if (!file_exists($filePath)) {
            $filePath = $this->blocksPath . '/' . $blockId . '.yml';
        }

        if (!file_exists($filePath)) {
            throw new \Exception('Block nicht gefunden');
        }

        $config = Yaml::parseFile($filePath);
        $elements = $config['elements'] ?? [];

        if (empty($elements)) {
            throw new \Exception('Block enthält keine Elemente');
        }

        // Get current elements to calculate sorting
        $existingElements = $this->connection->fetchAllAssociative(
            "SELECT id, sorting FROM tl_content WHERE pid = ? ORDER BY sorting",
            [$articleId]
        );

        $sortingValues = $this->calculateSortingForElementSet($existingElements, $position, count($elements));

        // Insert elements with calculated sorting values
        foreach ($elements as $index => $element) {
            $this->insertContentElement($articleId, $element, $sortingValues[$index]);
        }

        return true;
    }

    /**
     * Insert a single content element
     */
    private function insertContentElement(int $articleId, array $elementConfig, int $sorting): void
    {
        $data = [
            'pid' => $articleId,
            'ptable' => 'tl_article',
            'tstamp' => time(),
            'type' => $elementConfig['type'] ?? 'text',
            'sorting' => $sorting
        ];

        // Add fields from configuration
        if (isset($elementConfig['fields']) && is_array($elementConfig['fields'])) {
            foreach ($elementConfig['fields'] as $field => $value) {
                $data[$field] = $this->processFieldValue($field, $value);
            }
        }

        $this->connection->insert('tl_content', $data);
    }

    /**
     * Process field value - automatically detect and handle serialized fields based on YAML structure
     */
    private function processFieldValue(string $fieldName, $value)
    {
        // If the value is an array, it needs to be serialized for Contao database storage
        if (is_array($value)) {
            return serialize($value);
        }
        
        // Handle special string values that should remain empty
        if ($value === '' || $value === null) {
            return '';
        }
        
        // For simple string/numeric values, return as-is
        return $value;
    }

    /**
     * Calculate sorting values for element set insertion (Contao-style)
     */
    private function calculateSortingForBlock(array $existingElements, int $position, int $elementCount): array
    {
        $sortingValues = [];
        
        if (empty($existingElements)) {
            // No existing elements - start with 128 and increment by 1
            for ($i = 0; $i < $elementCount; $i++) {
                $sortingValues[] = 128 + $i;
            }
            return $sortingValues;
        }

        if ($position <= 0) {
            // Insert at beginning
            $firstSorting = $existingElements[0]['sorting'];
            $startSorting = max(1, $firstSorting - $elementCount);
            
            // Check if we have enough space
            if ($startSorting + $elementCount - 1 >= $firstSorting) {
                // Not enough space - resort all elements
                return $this->resortAllElements($existingElements, $position, $elementCount);
            }
            
            for ($i = 0; $i < $elementCount; $i++) {
                $sortingValues[] = $startSorting + $i;
            }
            return $sortingValues;
        }

        if ($position >= count($existingElements)) {
            // Insert at end
            $lastSorting = $existingElements[count($existingElements) - 1]['sorting'];
            for ($i = 0; $i < $elementCount; $i++) {
                $sortingValues[] = $lastSorting + 1 + $i;
            }
            return $sortingValues;
        }

        // Insert between elements
        $prevSorting = $existingElements[$position - 1]['sorting'];
        $nextSorting = $existingElements[$position]['sorting'];
        $availableSpace = $nextSorting - $prevSorting - 1;
        
        // Check if we have enough space between elements
        if ($availableSpace < $elementCount) {
            // Not enough space - resort all elements
            return $this->resortAllElements($existingElements, $position, $elementCount);
        }
        
        // We have enough space - insert with increment of 1
        for ($i = 0; $i < $elementCount; $i++) {
            $sortingValues[] = $prevSorting + 1 + $i;
        }
        
        return $sortingValues;
    }
    
    /**
     * Resort all elements when there's not enough space (Contao-style)
     */
    private function resortAllElements(array $existingElements, int $position, int $elementCount): array
    {
        $sortingValues = [];
        $counter = 1;
        
        // Resort existing elements and calculate new positions
        foreach ($existingElements as $index => $element) {
            if ($index == $position) {
                // Insert our new elements here
                for ($i = 0; $i < $elementCount; $i++) {
                    $sortingValues[] = $counter * 128;
                    $counter++;
                }
            }
            
            // Update existing element sorting
            $this->connection->update(
                'tl_content',
                ['sorting' => $counter * 128],
                ['id' => $element['id']]
            );
            $counter++;
        }
        
        // If position is at the end, add our elements
        if ($position >= count($existingElements)) {
            for ($i = 0; $i < $elementCount; $i++) {
                $sortingValues[] = $counter * 128;
                $counter++;
            }
        }
        
        return $sortingValues;
    }

}
