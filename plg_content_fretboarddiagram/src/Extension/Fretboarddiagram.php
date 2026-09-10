<?php
/**
 * @package    Fretboard Diagram Content Plugin
 * @version    1.0
 * @license    GNU General Public License version 2
 */
namespace Naftee\Plugin\Content\Fretboarddiagram\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * Content plugin which replaces a Fretboard Scale Diagram shortcode
 * with an inline SVG guitar fretboard diagram.
 */
final class Fretboarddiagram extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        // Register the method to be triggered when the Joomla event occurs
        return [
            'onContentPrepare' => 'replaceFretboardDiagramTags',
        ];
    }

    public function replaceFretboardDiagramTags(ContentPrepareEvent $event): void
    {
        if (!$this->getApplication()->isClient('site')) {
            return; // Exit if this request is from the backend (administrator)
        }

        $context = $event->getContext();
        $item    = $event->getItem();
        $params  = $event->getParams();

        if ($context === 'com_finder.indexer') {
            return; // Exit if the content is being processed by Joomla's Smart Search (Finder) indexer
        }

        // Determine the content text based on context (module or article)
        if ($context === 'com_modules.module') {
            $text = $params->get('content', '');
        } elseif ($context === 'com_content.article' || $context === 'com_content.featured') {
            if (!isset($item->text)) {
                return;
            }

            $text = &$item->text;
        } else {
            return;
        }

        // Early exit if there are no {fretboarddiagram} tags to process
        if (strpos($text, '{fretboarddiagram}') === false) {
            return;
        }

        // Load the plugin stylesheet through Joomla's Web Asset Manager.
        $document = $this->getApplication()->getDocument();
        $wa       = $document->getWebAssetManager();

        $wa->getRegistry()->addExtensionRegistryFile('plg_content_fretboarddiagram');
        $wa->useStyle('plg_content_fretboarddiagram.fretboarddiagram');

        $text = preg_replace_callback(
            '~\{fretboarddiagram\}(.*?)\{/fretboarddiagram\}~is',
            fn(array $match): string => $this->renderDiagram($match[1]),
            $text
        );

        // Update the content based on context
        if ($context === 'com_modules.module') {
            $params->set('content', $text);
        } else {
            $item->text = $text;
        }
    }

    /**
     * Parse: C:x;3[3];2[2];o;1[1];o; or C:3(x;3[1];5[2];5[3];5[4];3[1])
     *
     * Format: Chord name:String[finger]; or Chord name:starting fret(x for block;o for open;fret[finger])
     *
     * @return array{name:string,fret:int,strings:array}|null
     */
    private function parseChord(string $content): ?array
    {
        $content = trim($content);
        $content = strip_tags(html_entity_decode($content));

        // Format 3: Name:StartFret(strings)
        if (preg_match('/^([^:]+):\s*(\d+)\(([^)]+)\)$/', $content, $matches)) {
            return [
                'name'    => trim($matches[1]),
                'fret'    => (int) $matches[2],
                'strings' => $this->parseChordStrings($matches[3])
            ];
        }

        // Format 2: Name:strings
        if (preg_match('/^([^:]+):\s*(.+)$/', $content, $matches)) {
            $stringsStr = $matches[2];

            // Format 1 (scales) uses commas/pipes, chords use semicolons
            if (strpos($stringsStr, ';') !== false) {
                return [
                    'name'    => trim($matches[1]),
                    'fret'    => 1,
                    'strings' => $this->parseChordStrings($stringsStr)
                ];
            }
        }

        return null;
    }

    private function parseChordStrings(string $stringsData): array
    {
        $strings = [];
        $parts = explode(';', trim($stringsData, '; '));

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (strcasecmp($part, 'x') === 0) {
                $strings[] = ['state' => 'muted'];
            } elseif (strcasecmp($part, 'o') === 0 || $part === '0') {
                $strings[] = ['state' => 'open'];
            } elseif (preg_match('/^(\d+)(?:\[(\d+)\])?$/', $part, $m)) {
                $fret   = (int) $m[1];
                $finger = isset($m[2]) ? (int) $m[2] : null;

                $strings[] = [
                    'state'  => 'fretted',
                    'fret'   => $fret,
                    'finger' => $finger
                ];
            } else {
                $strings[] = ['state' => 'unknown'];
            }
        }

        return $strings;
    }

    /**
     * Render the specific chord SVG format.
     */
    private function renderChordDiagram(array $chord, int $width): string
    {
        $name      = $chord['name'];
        $startFret = $chord['fret'];
        $strings   = $chord['strings'];

        // X coordinates from 6th string to 1st string (left to right)
        $strX = [
            '4.247862',
            '14.909792',
            '25.580084',
            '36.242012',
            '46.903942',
            '57.565873',
        ];

        // Y coordinates for fret lines (0 is the nut, 1-5 are frets)
        $fretYs = [
            -12.342537,
            -1.680607,
             8.989685,
            19.651613,
            30.313543,
            40.975473,
        ];

        $svg = [];
        $svg[] = '<?xml version="1.0" encoding="UTF-8"?>';

        $height = (int) round($width * (88 / 62));

        if ($startFret === 1) {
           $svg[] = '<svg class="fretboard-chord-svg" xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 62 88">';
        } else {
           $offsetWidth = $width + 39;
           $svg[] = '<svg class="fretboard-chord-svg fretboard-chord-svg--offset" xmlns="http://www.w3.org/2000/svg" width="' . $offsetWidth . '" height="' . $height . '" viewBox="-10 0 72 88">';
        }

        $svg[] = '  <text class="fretboard-chord-name" x="30.9" y="18" text-anchor="middle">' . $this->escape($name) . '</text>';
        $svg[] = '  <g class="fretboard-chord-group" transform="translate(0 44)">';

        // Base fretboard grid
        $svg[] = '    <path class="fretboard-chord-grid"';
        $svg[] = '      d="M57.565873 40.975473V-12.342537';
        $svg[] = '         M46.903942 40.975473V-12.342537';
        $svg[] = '         M36.242012 40.975473V-12.342537';
        $svg[] = '         M25.580084 40.975473V-12.342537';
        $svg[] = '         M14.909792 40.975473V-12.342537';
        $svg[] = '         M4.247862 40.975473V-12.342537';
        $svg[] = '         M4.247862 -12.342537H57.565873';
        $svg[] = '         M4.247862 -1.680607H57.565873';
        $svg[] = '         M4.247862 8.989685H57.565873';
        $svg[] = '         M4.247862 19.651613H57.565873';
        $svg[] = '         M4.247862 30.313543H57.565873';
        $svg[] = '         M4.247862 40.975473H57.565873"/>';

        // Draw thick nut OR draw starting fret number
        if ($startFret === 1) {
            $svg[] = '    <path class="fretboard-chord-nut"';
            $svg[] = '      d="M4.247862 -12.342537H57.565873V-13.404549H4.247862Z"/>';
        } else {
            $svg[] = '    <text class="fretboard-chord-start-fret" x="-2" y="-4.5" text-anchor="end">'
                . $startFret
                . '</text>';
        }

        $dots         = [];
        $texts        = [];
        $fingerGroups = [];

        foreach ($strings as $i => $stringData) {
            if ($i >= 6) {
                break;
            }

            $xStr   = $strX[$i];
            $xFloat = (float) $xStr;

            if ($stringData['state'] === 'muted') {
                $x1 = number_format($xFloat - 2.7585, 4, '.', '');
                $x2 = number_format($xFloat + 2.7585, 4, '.', '');

                $svg[] = '    <path class="fretboard-chord-muted"';
                $svg[] = '      d="M' . $x2 . ' -20.700444L' . $x1 . ' -15.18345';
                $svg[] = '         M' . $x1 . ' -20.700444L' . $x2 . ' -15.18345"/>';

            } elseif ($stringData['state'] === 'open') {
                $svg[] = '    <circle class="fretboard-chord-open" cx="' . $xStr . '" cy="-18.100866" r="2.9635687"/>';

            } elseif ($stringData['state'] === 'fretted') {
                $absFret     = $stringData['fret'];
                $diagramFret = $absFret - $startFret + 1;

                if ($diagramFret >= 1 && $diagramFret <= 5) {
                    $yCenter    = ($fretYs[$diagramFret - 1] + $fretYs[$diagramFret]) / 2;
                    $yCenterStr = number_format($yCenter, 6, '.', '');

                    $dots[] = '    <circle class="fretboard-chord-fretted" cx="' . $xStr
                        . '" cy="' . $yCenterStr
                        . '" r="3.509156"/>';

                    if (isset($stringData['finger'])) {
                        $finger = $stringData['finger'];
                        $textY  = number_format($yCenter + 2.3, 6, '.', '');

                        $texts[] = '    <text class="fretboard-chord-finger" x="' . $xStr
                            . '" y="' . $textY
                            . '" text-anchor="middle">'
                            . $finger
                            . '</text>';

                        // Group fretted notes to detect barres
                        $groupKey = $diagramFret . '_' . $finger;
                        $fingerGroups[$groupKey][] = [
                            'x'           => $xFloat,
                            'diagramFret' => $diagramFret,
                        ];
                    }
                }
            }
        }

        // Generate barre arcs for finger groups with 2 or more strings
        $barres = [];
        foreach ($fingerGroups as $group) {
            if (count($group) >= 2) {
                $xCoords     = array_column($group, 'x');
                $minX        = min($xCoords);
                $maxX        = max($xCoords);
                $diagramFret = $group[0]['diagramFret'];

                $yCenter   = ($fretYs[$diagramFret - 1] + $fretYs[$diagramFret]) / 2;
                $span      = $maxX - $minX;
                $arcHeight = min(4.5, max(2.5, $span * 0.10));
                $yControl  = $yCenter - $arcHeight;
                $xMid      = ($minX + $maxX) / 2;

                $barres[] = '    <path class="fretboard-chord-barre" d="M'
                    . number_format($minX, 4, '.', '') . ' ' . number_format($yCenter, 6, '.', '')
                    . ' Q' . number_format($xMid, 4, '.', '') . ' ' . number_format($yControl, 6, '.', '')
                    . ' ' . number_format($maxX, 4, '.', '') . ' ' . number_format($yCenter, 6, '.', '')
                    . '"/>';
            }
        }

        // Render barres, dots, and finger numbers in order
        foreach ($barres as $barre) {
            $svg[] = $barre;
        }

        foreach ($dots as $dot) {
            $svg[] = $dot;
        }

        foreach ($texts as $text) {
            $svg[] = $text;
        }

        $svg[] = '  </g>';
        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Parse: 6:5(1)[1],8(3)[4] | 5:5(4)[1],7(5)[3] | ...
     *
     * Format: String:Fret(finger)[scale degree]
     *
     * @return array<int, array<int, array{fret:int,degree:int,finger:int}>>
     */
    private function parseFretboard(string $content): array
    {
        $parsedFretboard = [];
        $content = strip_tags(html_entity_decode($content));

        foreach (
            preg_split('/\s*\|\s*/', trim($content), -1, PREG_SPLIT_NO_EMPTY)
            as $stringData
        ) {
            $parts = explode(':', trim($stringData), 2);

            if (count($parts) !== 2) {
                continue;
            }

            $stringNum = (int) trim($parts[0]);

            if ($stringNum < 1 || $stringNum > 6) {
                continue;
            }

            preg_match_all(
                '/(\d+)\((\d+)\)\[(\d+)\]/',
                $parts[1],
                $matches,
                PREG_SET_ORDER
            );

            $parsedFretboard[$stringNum] = [];

            foreach ($matches as $match) {
                $fret   = (int) $match[1];
                $degree = (int) $match[2];
                $finger = (int) $match[3];

                if (
                    $fret < 0 ||
                    $fret > 30 ||
                    $degree < 0 ||
                    $degree > 99 ||
                    $finger < 0 ||
                    $finger > 9
                ) {
                    continue;
                }

                $parsedFretboard[$stringNum][] = [
                    'fret'   => $fret,
                    'degree' => $degree,
                    'finger' => $finger,
                ];
            }
        }

        ksort($parsedFretboard); // Ensure strings are processed in ascending order (1 through 6).
        return $parsedFretboard;
        
    }

    private function renderDiagram(string $content): string
    {
        // Try to parse the new chord formats first.
        $chord = $this->parseChord($content);

        if ($chord !== null) {
            $width = (int) $this->params->get('chorddiagramwidth', 245);
            return $this->renderChordDiagram($chord, $width);
        }

        // Fallback to the existing scale generator logic.
        $fretboard = $this->parseFretboard($content);

        if ($fretboard === []) {
            return '<!-- Fretboard scale diagram: no valid notes found. -->';
        }

        $frets = [];

        foreach ($fretboard as $notes) {
            foreach ($notes as $note) {
                $frets[] = $note['fret'];
            }
        }

        if ($frets === []) {
            return '<!-- Fretboard scale diagram: no valid notes found. -->';
        }

        $minFret = min($frets);
        $maxFret = max($frets);

        // Normally show five fret positions, with one fret of context immediately after the highest plotted fret.
        $displayStart = $minFret;
        $displayEnd   = max($maxFret + 1, $displayStart + 4);

        // Prevent an accidentally huge diagram if a very wide range is supplied.
        if (($displayEnd - $displayStart) > 12) {
            $displayEnd = $maxFret + 1;
        }

        $fretCount = max(1, $displayEnd - $displayStart + 1);

        /*
         * Internal SVG coordinate system.
         *
         * All drawing coordinates are based on 760 × 330, but the SVG itself can be rendered at any configured width while retaining this aspect ratio.
         */
        $viewBoxWidth  = 760;
        $viewBoxHeight = 330;

        // Configured displayed width.
        $width = (int) $this->params->get('scalediagramwidth', $viewBoxWidth);

        if ($width < 1) {
            $width = $viewBoxWidth;
        }

        // Preserve the original 760 × 330 aspect ratio.
        $height = round(
            $width * $viewBoxHeight / $viewBoxWidth
        );

        // Fretboard layout within the internal 760 × 330 coordinate system.
        $left   = 92;
        $right  = 28;
        $top    = 42;
        $bottom = 55;

        $fretWidth = ($viewBoxWidth - $left - $right) / $fretCount;
        $stringGap = ($viewBoxHeight - $top - $bottom) / 5;

        $boardX = $left;
        $boardY = $top;
        $boardW = $viewBoxWidth - $left - $right;
        $boardH = $stringGap * 5;

        $svg = [];

        // Fretboard diagram container.
        $svg[] = '<div class="fretboard-scale-diagram">';

        $svg[] = '<svg class="fretboard-scale-svg" xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . $this->number($width) . '"'
            . ' height="' . $this->number($height) . '"'
            . ' viewBox="0 0 ' . $viewBoxWidth . ' ' . $viewBoxHeight . '"'
            . ' role="img" aria-label="Guitar fretboard scale diagram">';

        $svg[] = '<title>Guitar fretboard scale diagram</title>';

        // Background.
        $svg[] = '<rect class="fretboard-scale-background"'
            . ' x="0"'
            . ' y="0"'
            . ' width="' . $this->number($viewBoxWidth) . '"'
            . ' height="' . $this->number($viewBoxHeight) . '"/>';

        // Fretboard.
        $svg[] = '<rect class="fretboard-scale-board"'
            . ' x="' . $this->number($boardX) . '"'
            . ' y="' . $this->number($boardY) . '"'
            . ' width="' . $this->number($boardW) . '"'
            . ' height="' . $this->number($boardH) . '"/>';

        // Fret lines.
        for ($i = 0; $i <= $fretCount; $i++) {
            $x    = $left + ($i * $fretWidth);
            $edge = ($i === 0 || $i === $fretCount);

            $svg[] = '<line class="fretboard-scale-fret-line'
                . ($edge ? ' fretboard-scale-fret-line--edge' : '')
                . '"'
                . ' x1="' . $this->number($x) . '"'
                . ' y1="' . $this->number($boardY) . '"'
                . ' x2="' . $this->number($x) . '"'
                . ' y2="' . $this->number($boardY + $boardH) . '"/>';
        }

        // Strings: 1st at top, 6th at bottom.
        for ($string = 1; $string < 7; $string++) {
            $row = $string - 1;
            $y   = $top + ($row * $stringGap);

            $thickString = in_array($string, [6, 5], true);

            $svg[] = '<line class="fretboard-scale-string'
                . ($thickString ? ' fretboard-scale-string--thick' : '')
                . '"'
                . ' x1="' . $this->number($boardX) . '"'
                . ' y1="' . $this->number($y) . '"'
                . ' x2="' . $this->number($boardX + $boardW) . '"'
                . ' y2="' . $this->number($y) . '"/>';

            $svg[] = '<text class="fretboard-scale-string-label"'
                . ' x="' . $this->number($left - 24) . '"'
                . ' y="' . $this->number($y + 6) . '"'
                . ' text-anchor="middle">'
                . $string
                . '</text>';
        }

        // Fret numbers.
        // This uses the internal viewBox height, not the configured rendered height, so the label scales correctly with the SVG.
        for ($fret = $displayStart; $fret <= $displayEnd; $fret++) {
            $x = $left + (($fret - $displayStart + 0.5) * $fretWidth);

            $svg[] = '<text class="fretboard-scale-fret-label"'
                . ' x="' . $this->number($x) . '"'
                . ' y="' . $this->number($viewBoxHeight - 18) . '"'
                . ' text-anchor="middle">'
                . $fret
                . '</text>';
        }

        // Scale dots.
        foreach ($fretboard as $string => $notes) {
            $row = $string - 1;
            $y   = $top + ($row * $stringGap);

            foreach ($notes as $note) {
                $fret = $note['fret'];

                if ($fret < $displayStart || $fret > $displayEnd) {
                    continue;
                }

                $x = $left
                    + (($fret - $displayStart + 0.5) * $fretWidth);

                $radius = min(22, $stringGap * 0.34);

                // Degree 1 is the root.
                $isRoot = ($note['degree'] === 1);

                $svg[] = '<circle class="fretboard-scale-note'
                    . ($isRoot ? ' fretboard-scale-note--root' : '')
                    . '"'
                    . ' cx="' . $this->number($x) . '"'
                    . ' cy="' . $this->number($y) . '"'
                    . ' r="' . $this->number($radius) . '"/>';

                // Display fingering number.
                $svg[] = '<text class="fretboard-scale-finger"'
                    . ' x="' . $this->number($x) . '"'
                    . ' y="' . $this->number($y + 6) . '"'
                    . ' text-anchor="middle">'
                    . $this->escape((string) $note['finger'])
                    . '</text>';
            }
        }

        $svg[] = '</svg>';
        $svg[] = '</div>';

        return implode("\n", $svg);
    }

    private function number(float $value): string
    {
        return rtrim(
            rtrim(number_format($value, 2, '.', ''), '0'),
            '.'
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}